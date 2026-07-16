<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Services\EventReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_organizations_context_includes_other_organization_events_for_system_admin(): void
    {
        $service = app(EventReadService::class);

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

        \Spatie\Permission\Models\Role::create([
            'name' => 'System Admin',
            'guard_name' => 'web',
        ]);
        $user->assignRole('System Admin');

        Event::create([
            'organization_id' => $otherOrganization->id,
            'scope' => 'organization',
            'title' => 'Other org event',
            'description' => 'Visible in aggregate mode',
            'event_type' => 'virtual',
            'event_category' => 'seminar',
            'location' => 'Auditorium',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'registration_deadline' => now()->addHours(12),
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        request()->query->set('org', 'all-organizations');

        $payload = $service->indexPayload($user, []);
        $titles = collect($payload['events']->items())->pluck('title');

        $this->assertTrue($titles->contains('Other org event'));
    }
}
