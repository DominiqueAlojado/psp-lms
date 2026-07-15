<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Services\EventManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_updates_and_deletes_event(): void
    {
        Storage::fake('public');

        $service = app(EventManagementService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);

        $event = $service->create($user, [
            'title' => 'Workshop',
            'scope' => 'organization',
            'description' => 'Desc',
            'event_category' => 'workshop',
            'event_type' => 'virtual',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'registration_deadline' => now()->addHours(12),
            'virtual_link' => 'https://example.com/meet',
            'is_free' => true,
            'requires_approval' => false,
            'is_published' => true,
        ], UploadedFile::fake()->image('poster.png'));

        $this->assertSame($organization->id, $event->organization_id);
        Storage::disk('public')->assertExists($event->image_path);

        $service->update($user, $event, [
            'title' => 'Updated Workshop',
            'scope' => 'organization',
            'description' => 'Updated',
            'event_category' => 'seminar',
            'event_type' => 'hybrid',
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(4),
            'is_free' => false,
            'price' => 100,
            'requires_approval' => true,
            'is_published' => false,
        ]);

        $this->assertSame('Updated Workshop', $event->fresh()->title);
        $this->assertTrue($service->delete($event));
        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }
}
