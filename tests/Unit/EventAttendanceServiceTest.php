<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\MeetingAttendance;
use App\Models\Organization;
use App\Models\User;
use App\Services\EventAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventAttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_user_and_handles_meeting_lifecycle(): void
    {
        $service = app(EventAttendanceService::class);

        $organization = Organization::create([
            'name' => 'Alpha Chapter',
            'slug' => 'alpha-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $event = Event::create([
            'organization_id' => $organization->id,
            'title' => 'Live Event',
            'event_category' => 'workshop',
            'event_type' => 'virtual',
            'start_date' => now()->subMinutes(10),
            'end_date' => now()->addMinutes(50),
            'registration_deadline' => now()->subHour(),
            'virtual_link' => 'https://example.com/meet',
            'is_free' => true,
            'requires_approval' => false,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        $registerEvent = Event::create([
            'organization_id' => $organization->id,
            'title' => 'Upcoming Event',
            'event_category' => 'workshop',
            'event_type' => 'virtual',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'registration_deadline' => now()->addHours(12),
            'virtual_link' => 'https://example.com/upcoming',
            'is_free' => true,
            'requires_approval' => false,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        $registerResult = $service->register($user, $registerEvent);
        $this->assertSame('success', $registerResult['type']);

        $request = Request::create('/events/' . $event->id . '/meeting/join', 'POST', [
            'metadata' => ['attendance_type' => 'virtual'],
        ]);
        $request->setUserResolver(fn () => $user);
        $request->server->set('HTTP_USER_AGENT', 'Mozilla/5.0 Chrome');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $joinResult = $service->joinMeeting($request, $event);
        $this->assertSame(200, $joinResult['status']);

        $heartbeat = $service->meetingHeartbeat($user, $event);
        $this->assertTrue($heartbeat['payload']['success']);

        $leaveResult = $service->leaveMeeting($user, $event);
        $this->assertSame(200, $leaveResult['status']);
        $this->assertGreaterThanOrEqual(0, $leaveResult['payload']['duration_seconds']);
    }

    public function test_it_returns_not_found_for_missing_active_attendance(): void
    {
        $service = app(EventAttendanceService::class);

        $organization = Organization::create([
            'name' => 'Beta Chapter',
            'slug' => 'beta-chapter',
            'type' => 'chapter',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'current_organization_id' => $organization->id,
        ]);
        $event = Event::create([
            'organization_id' => $organization->id,
            'title' => 'No Attendance Event',
            'event_category' => 'seminar',
            'event_type' => 'virtual',
            'start_date' => now()->subMinutes(10),
            'end_date' => now()->addMinutes(50),
            'is_free' => true,
            'requires_approval' => false,
            'is_published' => true,
            'created_by' => $user->id,
        ]);

        $result = $service->leaveMeeting($user, $event);
        $this->assertSame(404, $result['status']);
    }
}
