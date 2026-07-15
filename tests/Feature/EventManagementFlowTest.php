<?php

namespace Tests\Feature;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EventManagementFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_and_update_event(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'create-events', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit-events', 'guard_name' => 'web']);

        Storage::fake('public');

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, ['joined_at' => now(), 'is_active' => true]);
        $user->givePermissionTo('create-events');
        $user->givePermissionTo('edit-events');

        $create = $this->actingAs($user)
            ->from(route('events.manage'))
            ->post(route('events.store'), [
                'title' => 'Workshop',
                'scope' => 'organization',
                'description' => 'Desc',
                'event_category' => 'workshop',
                'event_type' => 'virtual',
                'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'end_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'registration_deadline' => now()->addHours(12)->format('Y-m-d H:i:s'),
                'virtual_link' => 'https://example.com/meet',
                'is_free' => true,
                'requires_approval' => false,
                'is_published' => true,
                'image' => UploadedFile::fake()->image('poster.png'),
            ]);

        $create->assertSessionHasNoErrors()->assertRedirect(route('events.manage'));

        $event = Event::where('title', 'Workshop')->first();

        $update = $this->actingAs($user)
            ->from(route('events.manage'))
            ->patch(route('events.update', $event), [
                'title' => 'Updated Workshop',
                'scope' => 'organization',
                'description' => 'Updated',
                'event_category' => 'seminar',
                'event_type' => 'hybrid',
                'start_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
                'end_date' => now()->addDays(4)->format('Y-m-d H:i:s'),
                'is_free' => false,
                'price' => 50,
                'requires_approval' => true,
                'is_published' => false,
            ]);

        $update->assertSessionHasNoErrors();
        $this->assertSame('Updated Workshop', $event->fresh()->title);
    }

    public function test_user_can_register_and_cancel_registration(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, ['joined_at' => now(), 'is_active' => true]);

        $event = Event::create([
            'organization_id' => $organization->id,
            'title' => 'Register Event',
            'event_category' => 'workshop',
            'event_type' => 'virtual',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'registration_deadline' => now()->addHours(12),
            'virtual_link' => 'https://example.com/meet',
            'is_free' => true,
            'requires_approval' => false,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        $register = $this->actingAs($user)
            ->from(route('events.show', $event))
            ->post(route('events.register', $event));

        $register->assertSessionHasNoErrors();
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);

        $cancel = $this->actingAs($user)
            ->from(route('events.show', $event))
            ->post(route('events.cancel-registration', $event), [
                'reason' => 'Unavailable',
            ]);

        $cancel->assertSessionHasNoErrors();
        $this->assertSame('cancelled', EventRegistration::where('event_id', $event->id)->where('user_id', $user->id)->first()->registration_status);
    }

    public function test_non_system_user_cannot_create_system_event(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::create(['name' => 'create-events', 'guard_name' => 'web']);
        Permission::create(['name' => 'create-system-announcements', 'guard_name' => 'web']);

        Storage::fake('public');

        $organization = Organization::create([
            'name' => 'Gamma Chapter',
            'slug' => 'gamma-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, ['joined_at' => now(), 'is_active' => true]);
        $user->givePermissionTo('create-events');

        $response = $this->actingAs($user)
            ->from(route('events.manage'))
            ->post(route('events.store'), [
                'title' => 'System Workshop',
                'scope' => 'system',
                'description' => 'Desc',
                'event_category' => 'workshop',
                'event_type' => 'virtual',
                'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
                'end_date' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'registration_deadline' => now()->addHours(12)->format('Y-m-d H:i:s'),
                'virtual_link' => 'https://example.com/meet',
                'is_free' => true,
                'requires_approval' => false,
                'is_published' => true,
            ]);

        $response->assertSessionHasErrors('scope')
            ->assertRedirect(route('events.manage'));

        $this->assertDatabaseMissing('events', [
            'title' => 'System Workshop',
        ]);
    }

    public function test_user_cannot_view_other_organization_scoped_event(): void
    {
        $this->withoutMiddleware([
            SetOrganizationFromUrl::class,
        ]);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $otherOrganization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $user->organizations()->attach($organization->id, ['joined_at' => now(), 'is_active' => true]);

        $event = Event::create([
            'organization_id' => $otherOrganization->id,
            'scope' => 'organization',
            'title' => 'Private Workshop',
            'event_category' => 'workshop',
            'event_type' => 'virtual',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'registration_deadline' => now()->addHours(12),
            'virtual_link' => 'https://example.com/meet',
            'is_free' => true,
            'requires_approval' => false,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertForbidden();
    }
}
