<?php

namespace Tests\Feature;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\Organization;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketNotification;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_support_index_only_shows_requester_tickets_for_current_organization(): void
    {
        $this->withoutMiddleware([SetOrganizationFromUrl::class]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $otherOrganization = $this->createOrganization('beta-chapter', 'Beta Chapter');
        $user = $this->createUserForOrganization($organization);

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

        Permission::findOrCreate('manage-support-tickets', 'web');
        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $staff = $this->createUserForOrganization($organization);
        $staff->givePermissionTo('manage-support-tickets');

        $response = $this->actingAs($staff)->get(route('support.manage'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('support/manage'));
    }

    public function test_user_without_manage_permission_cannot_access_support_queue(): void
    {
        $this->withoutMiddleware([SetOrganizationFromUrl::class]);

        Permission::findOrCreate('manage-support-tickets', 'web');
        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);

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

        Permission::findOrCreate('manage-support-tickets', 'web');

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $otherOrganization = $this->createOrganization('beta-chapter', 'Beta Chapter');
        $user = $this->createUserForOrganization($organization);
        $manager = $this->createUserForOrganization($organization);
        $otherManager = $this->createUserForOrganization($otherOrganization);

        $manager->givePermissionTo('manage-support-tickets');
        $otherManager->givePermissionTo('manage-support-tickets');

        $this->actingAs($user)->post(route('support.store'), [
            'title' => 'Unable to open analytics',
            'category' => 'bug',
            'priority' => 'high',
            'details' => 'Analytics page keeps failing to load.',
        ])->assertRedirect('/support');

        $manager->refresh();
        $otherManager->refresh();

        $this->assertSame(1, $manager->notifications()->count());
        $this->assertSame(0, $otherManager->notifications()->count());
    }

    public function test_system_admin_receives_ticket_notification_even_if_current_org_differs(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $differentOrganization = $this->createOrganization('beta-chapter', 'Beta Chapter');
        $resident = $this->createUserForOrganization($organization);

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

        $this->actingAs($resident)->post(route('support.store'), [
            'title' => 'Resident support request',
            'category' => 'bug',
            'priority' => 'medium',
            'details' => 'This should still notify the system admin.',
        ])->assertRedirect('/support');

        $systemAdmin->refresh();

        $this->assertSame(1, $systemAdmin->notifications()->count());
        $this->assertSame('support.ticket.created', $systemAdmin->notifications()->latest()->first()->data['event']);
    }

    public function test_manager_reply_notifies_ticket_creator(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::findOrCreate('manage-support-tickets', 'web');

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $manager = $this->createUserForOrganization($organization);
        $manager->givePermissionTo('manage-support-tickets');

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

        $user->refresh();

        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame('support.ticket.replied', $user->notifications()->latest()->first()->data['event']);
    }

    public function test_resolving_ticket_notifies_creator(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::findOrCreate('manage-support-tickets', 'web');

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $manager = $this->createUserForOrganization($organization);
        $manager->givePermissionTo('manage-support-tickets');

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

        $user->refresh();

        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame('support.ticket.resolved', $user->notifications()->latest()->first()->data['event']);
    }

    public function test_assigning_ticket_notifies_assignee(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::findOrCreate('manage-support-tickets', 'web');

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
        $manager = $this->createUserForOrganization($organization);
        $assignee = $this->createUserForOrganization($organization);
        $manager->givePermissionTo('manage-support-tickets');

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

        $assignee->refresh();

        $this->assertSame(1, $assignee->notifications()->count());
        $this->assertSame('support.ticket.assigned', $assignee->notifications()->latest()->first()->data['event']);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $this->withoutMiddleware([SetOrganizationFromUrl::class]);

        $organization = $this->createOrganization('alpha-chapter', 'Alpha Chapter');
        $user = $this->createUserForOrganization($organization);
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

        $resident = $this->createUserForOrganization($alpha);

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

        $response = $this->actingAs($systemAdmin)->post('/support?org=all-organizations', [
            'title' => 'Blocked ticket',
            'category' => 'bug',
            'priority' => 'medium',
            'details' => 'This should be blocked.',
        ]);

        $response->assertStatus(422);
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
}
