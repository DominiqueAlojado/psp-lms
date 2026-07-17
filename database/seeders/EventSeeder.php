<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Organization;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class EventSeeder extends Seeder
{
    private const YEAR_LEVELS = [
        'First Year',
        'Second Year',
        'Third Year',
        'Fourth Year',
        'Graduate',
    ];

    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', 'bataan-general-hospital')
            ->first() ?? Organization::query()->first();

        if (! $organization) {
            $this->command->warn('No organization found. Skipping event seeding.');

            return;
        }

        $creator = $organization->users()
            ->whereDoesntHave('roles', fn($query) => $query->where('name', 'Resident'))
            ->first() ?? User::query()->first();

        if (! $creator) {
            $this->command->warn('No event creator available. Skipping event seeding.');

            return;
        }

        $residents = Resident::query()
            ->with('user')
            ->where('organization_id', $organization->id)
            ->whereNotNull('user_id')
            ->take(6)
            ->get();

        $events = [
            [
                'match' => ['organization_id' => $organization->id, 'title' => 'Clinical Pathology Case Conference'],
                'attributes' => [
                    'scope' => 'organization',
                    'description' => 'Weekly case conference focused on diagnostic reasoning, specimen correlation, and interdisciplinary discussion.',
                    'event_category' => 'conference',
                    'event_type' => 'hybrid',
                    'start_date' => now()->addDays(10)->setTime(8, 30),
                    'end_date' => now()->addDays(10)->setTime(12, 0),
                    'registration_deadline' => now()->addDays(8)->setTime(17, 0),
                    'location' => 'Bataan General Hospital Auditorium',
                    'virtual_link' => 'https://example.com/events/clinical-pathology-case-conference',
                    'capacity' => 40,
                    'price' => 0,
                    'is_free' => true,
                    'cme_credits' => 2,
                    'target_year_levels' => ['Second Year', 'Third Year', 'Fourth Year'],
                    'requirements' => 'Bring one anonymized case summary from your current rotation.',
                    'requires_approval' => true,
                    'is_published' => true,
                    'speakers' => [
                        ['name' => 'Dr. Elena Ramos', 'title' => 'Consultant Pathologist', 'organization' => $organization->name],
                        ['name' => 'Dr. Miguel Torres', 'title' => 'Training Officer', 'organization' => $organization->name],
                    ],
                    'agenda_items' => [
                        ['day' => 'Day 1', 'start_time' => '08:30', 'end_time' => '09:00', 'session_title' => 'Opening Review', 'speaker' => 'Dr. Elena Ramos'],
                        ['day' => 'Day 1', 'start_time' => '09:00', 'end_time' => '11:30', 'session_title' => 'Case Presentations', 'speaker' => 'Dr. Miguel Torres'],
                    ],
                    'created_by' => $creator->id,
                ],
            ],
            [
                'match' => ['organization_id' => null, 'title' => 'PSP National Quality Improvement Workshop'],
                'attributes' => [
                    'scope' => 'system',
                    'description' => 'National workshop on quality metrics, audit readiness, and documenting improvement projects across training institutions.',
                    'event_category' => 'workshop',
                    'event_type' => 'virtual',
                    'start_date' => now()->addDays(20)->setTime(13, 0),
                    'end_date' => now()->addDays(20)->setTime(17, 0),
                    'registration_deadline' => now()->addDays(18)->setTime(18, 0),
                    'location' => null,
                    'virtual_link' => 'https://example.com/events/psp-national-quality-improvement-workshop',
                    'capacity' => 200,
                    'price' => 0,
                    'is_free' => true,
                    'cme_credits' => 3,
                    'target_year_levels' => self::YEAR_LEVELS,
                    'requirements' => 'Stable internet connection and active UNIFIED LMS account.',
                    'requires_approval' => false,
                    'is_published' => true,
                    'speakers' => [
                        ['name' => 'Dr. Paula Reyes', 'title' => 'PSP Board Representative', 'organization' => 'PSP'],
                    ],
                    'agenda_items' => [
                        ['day' => 'Day 1', 'start_time' => '13:00', 'end_time' => '14:30', 'session_title' => 'Quality Dashboard Review', 'speaker' => 'Dr. Paula Reyes'],
                    ],
                    'created_by' => $creator->id,
                ],
            ],
        ];

        foreach ($events as $payload) {
            $event = Event::query()->updateOrCreate($payload['match'], $payload['attributes']);

            $this->seedRegistrations($event, $organization, $residents);
        }

        $this->command->info('Seeded sample events and registrations.');
    }

    private function seedRegistrations(Event $event, Organization $organization, Collection $residents): void
    {
        foreach ($residents->values() as $index => $resident) {
            if (! $resident->user) {
                continue;
            }

            $registrationStatus = match (true) {
                $event->requires_approval && $index === 0 => 'pending',
                $index === 1 => 'approved',
                default => 'confirmed',
            };

            $paymentStatus = $event->is_free ? 'not_required' : 'pending';

            EventRegistration::query()->updateOrCreate(
                [
                    'event_id' => $event->id,
                    'user_id' => $resident->user_id,
                ],
                [
                    'organization_id' => $organization->id,
                    'registration_status' => $registrationStatus,
                    'payment_status' => $paymentStatus,
                    'payment_amount' => $event->is_free ? 0 : ($event->price ?? 0),
                    'payment_date' => $paymentStatus === 'paid' ? now()->subDays(2) : null,
                    'checked_in_at' => null,
                    'custom_fields' => [
                        'department' => 'Pathology',
                        'year_level' => $resident->year_level,
                    ],
                    'cancellation_reason' => null,
                    'cancelled_at' => null,
                ]
            );
        }
    }
}
