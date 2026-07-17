<?php

namespace Tests\Feature;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\Organization;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketNotification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SupportTicketFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_support_ticket_in_current_organization(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $this->grantSupportRequesterPermissions($user);

        $response = $this->actingAs($user)
            ->from(route('support.index'))
            ->post(route('support.store'), [
                'title' => 'Unable to submit exam',
                'category' => 'bug',
                'priority' => 'high',
                'module_name' => 'Resident Exams',
                'page_url' => '/resident-exams',
                'details' => 'Submission button stays disabled after all questions are answered.',
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect('/support');

        $ticket = SupportTicket::query()->where('title', 'Unable to submit exam')->first();

        $this->assertNotNull($ticket);
        $this->assertSame($organization->id, $ticket->organization_id);
        $this->assertSame($user->id, $ticket->user_id);
        $this->assertSame('SUP-'.str_pad((string) $ticket->id, 5, '0', STR_PAD_LEFT), $ticket->ticket_number);
        $this->assertSame('open', $ticket->status);
    }

    public function test_support_ticket_detail_includes_activity_logs(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $this->grantSupportRequesterPermissions($user);

        $this->actingAs($user)->post(route('support.store'), [
            'title' => 'Activity log check',
            'category' => 'bug',
            'priority' => 'medium',
            'module_name' => 'Support',
            'page_url' => '/support',
            'details' => 'Create a ticket and verify the timeline is present.',
        ])->assertRedirect('/support');

        $ticket = SupportTicket::query()->where('title', 'Activity log check')->firstOrFail();

        $response = $this->actingAs($user)->get(route('support.show', $ticket));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('support/show')
            ->where('ticket.id', $ticket->id)
            ->where('activityLogs.0.description', 'Support ticket created')
        );
    }

    public function test_support_index_only_shows_requester_tickets_for_current_organization(): void
    {
        $this->withoutMiddleware([SetOrganizationFromUrl::class]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $otherOrganization = $this->createOrganization('beta-chapter', 'Beta Chapter');
        $user = $this->createUserForOrganization($organization);
        $this->grantSupportRequesterPermissions($user);

        SupportTicket::create([
            'ticket_number' => 'SUP-00001',
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'title' => 'Current org ticket',
            'category' => 'bug',
            'priority' => 'medium',
            'status' => 'open',
            'details' => 'Visible in current org.',
        ]);

        SupportTicket::create([
            'ticket_number' => 'SUP-00002',
            'organization_id' => $otherOrganization->id,
            'user_id' => $user->id,
            'title' => 'Other org ticket',
            'category' => 'feature',
            'priority' => 'low',
            'status' => 'open',
            'details' => 'Should not appear when switched away.',
        ]);

        $response = $this->actingAs($user)->get(route('support.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('support/index')
            ->where('tickets.total', 1)
            ->where('tickets.data.0.title', 'Current org ticket')
        );
    }

    public function test_staff_with_permission_can_access_support_queue(): void
    {
        $this->withoutMiddleware([SetOrganizationFromUrl::class]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $staff = $this->createUserForOrganization($organization);
        $this->grantSupportManagerPermissions($staff);

        $response = $this->actingAs($staff)->get(route('support.manage'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('support/manage'));
    }

    public function test_support_queue_can_filter_by_assignee(): void
    {
        $this->withoutMiddleware([SetOrganizationFromUrl::class]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $staff = $this->createUserForOrganization($organization);
        $assignee = $this->createUserForOrganization($organization);
        $otherAssignee = $this->createUserForOrganization($organization);
        $requester = $this->createUserForOrganization($organization);
        $this->grantSupportManagerPermissions($staff);
        $this->grantSupportRequesterPermissions($requester);

        SupportTicket::create([
            'ticket_number' => 'SUP-00021',
            'organization_id' => $organization->id,
            'user_id' => $requester->id,
            'assigned_to_user_id' => $assignee->id,
            'title' => 'Assigned ticket',
            'category' => 'bug',
            'priority' => 'high',
            'status' => 'open',
            'details' => 'Should appear for selected assignee.',
        ]);

        SupportTicket::create([
            'ticket_number' => 'SUP-00022',
            'organization_id' => $organization->id,
            'user_id' => $requester->id,
            'assigned_to_user_id' => $otherAssignee->id,
            'title' => 'Different assignee ticket',
            'category' => 'feature',
            'priority' => 'medium',
            'status' => 'open',
            'details' => 'Should not appear for selected assignee.',
        ]);

        $response = $this->actingAs($staff)->get('/support/manage?assignee_user_id='.$assignee->id);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('support/manage')
            ->where('tickets.total', 1)
            ->where('tickets.data.0.title', 'Assigned ticket')
            ->where('filters.assignee_user_id', (string) $assignee->id)
        );
    }

    public function test_user_without_manage_permission_cannot_access_support_queue(): void
    {
        $this->withoutMiddleware([SetOrganizationFromUrl::class]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $this->grantSupportRequesterPermissions($user);

        $response = $this->actingAs($user)->get(route('support.manage'));

        $response->assertForbidden();
    }

    public function test_cross_organization_ticket_access_is_blocked(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $otherOrganization = $this->createOrganization('beta-chapter', 'Beta Chapter');
        $user = $this->createUserForOrganization($organization);
        $this->grantSupportRequesterPermissions($user);

        $ticket = SupportTicket::create([
            'ticket_number' => 'SUP-00077',
            'organization_id' => $otherOrganization->id,
            'user_id' => $user->id,
            'title' => 'Other org ticket',
            'category' => 'bug',
            'priority' => 'medium',
            'status' => 'open',
            'details' => 'Should be blocked in current org context.',
        ]);

        $showResponse = $this->actingAs($user)->get(route('support.show', $ticket));
        $showResponse->assertForbidden();

        $replyResponse = $this->actingAs($user)->post(route('support.messages.store', $ticket), [
            'message' => 'Trying to reply across orgs.',
        ]);
        $replyResponse->assertForbidden();
    }

    public function test_requester_reply_reopens_resolved_ticket(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $this->grantSupportRequesterPermissions($user);

        $ticket = SupportTicket::create([
            'ticket_number' => 'SUP-00101',
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'title' => 'Need more help',
            'category' => 'account',
            'priority' => 'medium',
            'status' => 'resolved',
            'details' => 'This was marked resolved too early.',
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('support.messages.store', $ticket), [
            'message' => 'The issue still happens after the fix.',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect();

        $ticket->refresh();

        $this->assertSame('open', $ticket->status);
        $this->assertNull($ticket->resolved_at);
        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'The issue still happens after the fix.',
        ]);
    }

    public function test_ticket_creation_notifies_support_managers_in_same_organization(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);
        Notification::fake();

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $otherOrganization = $this->createOrganization('beta-chapter', 'Beta Chapter');
        $user = $this->createUserForOrganization($organization);
        $manager = $this->createUserForOrganization($organization);
        $otherManager = $this->createUserForOrganization($otherOrganization);

        $this->grantSupportRequesterPermissions($user);
        $this->grantSupportManagerPermissions($manager);
        $this->grantSupportManagerPermissions($otherManager);

        $this->actingAs($user)->post(route('support.store'), [
            'title' => 'Unable to open analytics',
            'category' => 'bug',
            'priority' => 'high',
            'details' => 'Analytics page keeps failing to load.',
        ])->assertRedirect('/support');

        Notification::assertSentTo(
            $manager,
            SupportTicketNotification::class,
            function (SupportTicketNotification $notification, array $channels) {
                return in_array('database', $channels, true)
                    && in_array('mail', $channels, true);
            }
        );
        Notification::assertNotSentTo($otherManager, SupportTicketNotification::class);
    }

    public function test_system_admin_receives_ticket_notification_even_if_current_org_differs(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);
        Notification::fake();

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $differentOrganization = $this->createOrganization('beta-chapter', 'Beta Chapter');
        $resident = $this->createUserForOrganization($organization);
        $this->grantSupportRequesterPermissions($resident);

        $systemAdmin = User::factory()->create([
            'current_organization_id' => $differentOrganization->id,
        ]);
        $systemAdmin->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);
        $systemAdmin->organizations()->attach($differentOrganization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        $systemAdminRole = \Spatie\Permission\Models\Role::findOrCreate('System Admin', 'web');
        $systemAdmin->assignRole($systemAdminRole);
        $this->grantSupportManagerPermissions($systemAdmin);

        $this->actingAs($resident)->post(route('support.store'), [
            'title' => 'Resident support request',
            'category' => 'bug',
            'priority' => 'medium',
            'details' => 'This should still notify the system admin.',
        ])->assertRedirect('/support');

        Notification::assertSentTo(
            $systemAdmin,
            SupportTicketNotification::class,
            function (SupportTicketNotification $notification, array $channels) {
                return in_array('database', $channels, true)
                    && in_array('mail', $channels, true)
                    && $notification->toArray(new class {
                        public string $name = 'System Admin';
                        public string $email = 'admin@example.com';
                    })['event'] === 'support.ticket.created';
            }
        );
    }

    public function test_manager_reply_notifies_ticket_creator(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);
        Notification::fake();

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $manager = $this->createUserForOrganization($organization);
        $this->grantSupportRequesterPermissions($user);
        $this->grantSupportManagerPermissions($manager);

        $ticket = SupportTicket::create([
            'ticket_number' => 'SUP-00111',
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'title' => 'Exam freeze',
            'category' => 'bug',
            'priority' => 'high',
            'status' => 'open',
            'details' => 'The timer freezes after reconnect.',
        ]);

        $this->actingAs($manager)->post(route('support.messages.store', $ticket), [
            'message' => 'We are checking the exam session logs now.',
        ])->assertRedirect();

        Notification::assertSentTo(
            $user,
            SupportTicketNotification::class,
            function (SupportTicketNotification $notification, array $channels) {
                return in_array('database', $channels, true)
                    && in_array('mail', $channels, true)
                    && $notification->toArray(new class {
                        public string $name = 'User';
                        public string $email = 'user@example.com';
                    })['event'] === 'support.ticket.replied';
            }
        );
    }

    public function test_resolving_ticket_notifies_creator(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);
        Notification::fake();

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $manager = $this->createUserForOrganization($organization);
        $this->grantSupportRequesterPermissions($user);
        $this->grantSupportManagerPermissions($manager);

        $ticket = SupportTicket::create([
            'ticket_number' => 'SUP-00112',
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'title' => 'Login issue',
            'category' => 'account',
            'priority' => 'medium',
            'status' => 'in_review',
            'details' => 'Still cannot login from Safari.',
        ]);

        $this->actingAs($manager)->patch(route('support.update', $ticket), [
            'status' => 'resolved',
            'priority' => 'medium',
            'assigned_to_user_id' => null,
        ])->assertRedirect();

        Notification::assertSentTo(
            $user,
            SupportTicketNotification::class,
            function (SupportTicketNotification $notification, array $channels) {
                return in_array('database', $channels, true)
                    && in_array('mail', $channels, true)
                    && $notification->toArray(new class {
                        public string $name = 'User';
                        public string $email = 'user@example.com';
                    })['event'] === 'support.ticket.resolved';
            }
        );
    }

    public function test_assigning_ticket_notifies_assignee(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);
        Notification::fake();

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $manager = $this->createUserForOrganization($organization);
        $assignee = $this->createUserForOrganization($organization);
        $this->grantSupportRequesterPermissions($user);
        $this->grantSupportManagerPermissions($manager);

        $ticket = SupportTicket::create([
            'ticket_number' => 'SUP-00113',
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'title' => 'Question bank import',
            'category' => 'content',
            'priority' => 'medium',
            'status' => 'open',
            'details' => 'Import preview and final import disagree.',
        ]);

        $this->actingAs($manager)->patch(route('support.update', $ticket), [
            'status' => 'in_review',
            'priority' => 'medium',
            'assigned_to_user_id' => $assignee->id,
        ])->assertRedirect();

        Notification::assertSentTo(
            $assignee,
            SupportTicketNotification::class,
            function (SupportTicketNotification $notification, array $channels) {
                return in_array('database', $channels, true)
                    && in_array('mail', $channels, true)
                    && $notification->toArray(new class {
                        public string $name = 'Assignee';
                        public string $email = 'assignee@example.com';
                    })['event'] === 'support.ticket.assigned';
            }
        );
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $this->withoutMiddleware([SetOrganizationFromUrl::class]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $this->grantNotificationPermission($user);
        $ticket = SupportTicket::create([
            'ticket_number' => 'SUP-00114',
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'title' => 'Need help',
            'category' => 'bug',
            'priority' => 'low',
            'status' => 'open',
            'details' => 'Example ticket for notification read test.',
        ]);
        $user->notify(SupportTicketNotification::ticketCreated($ticket));

        $notificationId = $user->fresh()->notifications()->first()->id;

        $this->actingAs($user)->post(route('notifications.read', $notificationId))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->notifications()->first()->read_at);
    }

    public function test_opening_notification_marks_it_as_read_and_redirects(): void
    {
        $this->withoutMiddleware([SetOrganizationFromUrl::class]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $this->grantNotificationPermission($user);
        $ticket = SupportTicket::create([
            'ticket_number' => 'SUP-00115',
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'title' => 'Redirect notification',
            'category' => 'bug',
            'priority' => 'low',
            'status' => 'open',
            'details' => 'Example ticket for notification redirect test.',
        ]);
        $user->notify(SupportTicketNotification::ticketCreated($ticket));

        $notificationId = $user->fresh()->notifications()->first()->id;

        $this->actingAs($user)->post(route('notifications.read', $notificationId), [
            'redirect_to' => '/support/'.$ticket->id,
        ])->assertRedirect('/support/'.$ticket->id);

        $this->assertNotNull($user->fresh()->notifications()->first()->read_at);
    }

    public function test_system_admin_can_view_support_queue_across_all_organizations(): void
    {
        $this->withoutMiddleware([SetOrganizationFromUrl::class]);

        $alpha = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $beta = $this->createOrganization('beta-chapter', 'Beta Chapter');

        $systemAdmin = User::factory()->create([
            'current_organization_id' => $alpha->id,
        ]);
        $systemAdmin->organizations()->attach($alpha->id, ['joined_at' => now(), 'is_active' => true]);
        $systemAdmin->organizations()->attach($beta->id, ['joined_at' => now(), 'is_active' => true]);
        $systemAdmin->assignRole(\Spatie\Permission\Models\Role::findOrCreate('System Admin', 'web'));
        $this->grantSupportManagerPermissions($systemAdmin);

        $resident = $this->createUserForOrganization($alpha);
        $this->grantSupportRequesterPermissions($resident);

        SupportTicket::create([
            'ticket_number' => 'SUP-20101',
            'organization_id' => $alpha->id,
            'user_id' => $resident->id,
            'title' => 'Alpha ticket',
            'category' => 'bug',
            'priority' => 'medium',
            'status' => 'open',
            'details' => 'Alpha details.',
        ]);

        SupportTicket::create([
            'ticket_number' => 'SUP-20102',
            'organization_id' => $beta->id,
            'user_id' => $resident->id,
            'title' => 'Beta ticket',
            'category' => 'feature',
            'priority' => 'high',
            'status' => 'open',
            'details' => 'Beta details.',
        ]);

        $response = $this->actingAs($systemAdmin)->get('/support/manage?org=all-organizations');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('support/manage')
            ->where('tickets.total', 2)
            ->where('isAllOrganizationsContext', true)
        );
    }

    public function test_all_organizations_context_blocks_support_ticket_creation(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $systemAdmin = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $systemAdmin->organizations()->attach($organization->id, ['joined_at' => now(), 'is_active' => true]);
        $systemAdmin->assignRole(\Spatie\Permission\Models\Role::findOrCreate('System Admin', 'web'));
        $this->grantSupportManagerPermissions($systemAdmin);

        $response = $this->actingAs($systemAdmin)->post('/support?org=all-organizations', [
            'title' => 'Blocked ticket',
            'category' => 'bug',
            'priority' => 'medium',
            'details' => 'This should be blocked.',
        ]);

        $response->assertStatus(422);
    }

    public function test_support_routes_require_permissions(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        $organization = $this->createOrganization('gamma-chapter', 'Gamma Chapter');
        $user = $this->createUserForOrganization($organization);

        $this->actingAs($user)->get(route('support.index'))->assertForbidden();

        Permission::findOrCreate('view-support-tickets', 'web');
        $user->givePermissionTo('view-support-tickets');

        $this->actingAs($user)->post(route('support.store'), [
            'title' => 'Blocked create',
            'category' => 'bug',
            'priority' => 'low',
            'details' => 'Should require create-support-tickets.',
        ])->assertForbidden();
    }

    private function createOrganization(string $slug, string $name): Organization
    {
        return Organization::create([
            'name' => $name,
            'slug' => $slug,
            'type' => 'chapter',
            'is_active' => true,
        ]);
    }

    private function createUserForOrganization(Organization $organization): User
    {
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $user->organizations()->attach($organization->id, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        return $user;
    }

    private function grantSupportRequesterPermissions(User $user): void
    {
        Permission::findOrCreate('view-support-tickets', 'web');
        Permission::findOrCreate('create-support-tickets', 'web');

        $user->givePermissionTo([
            'view-support-tickets',
            'create-support-tickets',
        ]);
    }

    private function grantSupportManagerPermissions(User $user): void
    {
        Permission::findOrCreate('manage-support-tickets', 'web');
        Permission::findOrCreate('edit-support-tickets', 'web');

        $this->grantSupportRequesterPermissions($user);

        $user->givePermissionTo([
            'manage-support-tickets',
            'edit-support-tickets',
        ]);
    }

    private function grantNotificationPermission(User $user): void
    {
        Permission::findOrCreate('view-notifications', 'web');
        $user->givePermissionTo('view-notifications');
    }
}
