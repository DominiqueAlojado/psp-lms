<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔔 Seeding announcements...');

        // Get a sample organization and user
        $organization = Organization::where('type', 'training_institution')->first();

        if (! $organization) {
            $this->command->warn('⚠️  No training institution found. Skipping announcement seeding.');

            return;
        }

        $creator = User::whereHas('organizations', function ($query) use ($organization) {
            $query->where('organizations.id', $organization->id);
        })->first();

        if (! $creator) {
            $this->command->warn('⚠️  No user found in organization. Skipping announcement seeding.');

            return;
        }

        // System-wide announcements (for admins/BOP)
        $systemAnnouncements = [
            [
                'title' => 'National In-Service Examination Schedule',
                'content' => '<p>The National In-Service Examination for all residency programs will be held on <strong>December 15, 2024</strong>.</p><p>All residents are required to attend. Please coordinate with your respective training officers for the examination venue and schedule.</p><p>Topics covered:</p><ul><li>Basic Sciences</li><li>Clinical Medicine</li><li>Specialty-specific modules</li></ul>',
                'scope' => 'system',
                'priority' => 'important',
                'is_pinned' => true,
            ],
            [
                'title' => 'System Maintenance Notice',
                'content' => '<p>The PSP Learning Management System will undergo scheduled maintenance on <strong>November 12, 2024, from 2:00 AM to 6:00 AM</strong>.</p><p>During this period, the system will be temporarily unavailable. We apologize for any inconvenience.</p>',
                'scope' => 'system',
                'priority' => 'normal',
                'expires_at' => now()->addDays(7),
            ],
        ];

        foreach ($systemAnnouncements as $data) {
            Announcement::create([
                'organization_id' => null,
                'created_by' => $creator->id,
                'title' => $data['title'],
                'content' => $data['content'],
                'scope' => $data['scope'],
                'priority' => $data['priority'],
                'is_published' => true,
                'is_pinned' => $data['is_pinned'] ?? false,
                'expires_at' => $data['expires_at'] ?? null,
            ]);
        }

        // Organization-specific announcements
        $organizationAnnouncements = [
            [
                'title' => 'New Rotation Schedule for PGY-2 Residents',
                'content' => '<p>The rotation schedule for PGY-2 residents has been updated for the upcoming quarter.</p><p>Please check your individual schedules in the system and coordinate with your respective departments.</p><p><strong>Key Changes:</strong></p><ul><li>Surgery rotation extended by 2 weeks</li><li>Pediatrics rotation moved to January</li><li>Elective periods adjusted</li></ul>',
                'priority' => 'urgent',
                'is_pinned' => true,
                'target_year_levels' => ['PGY-2'],
            ],
            [
                'title' => 'Grand Rounds: COVID-19 Management Update',
                'content' => '<p>Join us for our monthly Grand Rounds session featuring Dr. Maria Santos discussing the latest updates in COVID-19 management protocols.</p><p><strong>Date:</strong> November 20, 2024<br><strong>Time:</strong> 2:00 PM - 4:00 PM<br><strong>Venue:</strong> Main Conference Hall</p><p>Attendance is mandatory for all residents. CME credits will be provided.</p>',
                'priority' => 'important',
            ],
            [
                'title' => 'Library Resources Now Available Online',
                'content' => '<p>We are pleased to announce that all medical library resources are now accessible online through our institution portal.</p><p>Access codes have been sent to your institutional email addresses. Please check your spam folder if you haven\'t received it.</p>',
                'priority' => 'normal',
            ],
            [
                'title' => 'Reminder: Submit Monthly Case Reports',
                'content' => '<p>This is a friendly reminder that monthly case reports are due by the end of this week.</p><p>Please submit your reports through the designated portal. Late submissions may affect your evaluation scores.</p>',
                'priority' => 'normal',
                'expires_at' => now()->addDays(5),
            ],
        ];

        foreach ($organizationAnnouncements as $data) {
            Announcement::create([
                'organization_id' => $organization->id,
                'created_by' => $creator->id,
                'title' => $data['title'],
                'content' => $data['content'],
                'scope' => 'organization',
                'priority' => $data['priority'],
                'is_published' => true,
                'is_pinned' => $data['is_pinned'] ?? false,
                'target_year_levels' => $data['target_year_levels'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
            ]);
        }

        $totalCount = count($systemAnnouncements) + count($organizationAnnouncements);
        $this->command->info("✅ Successfully created {$totalCount} announcements!");
    }
}
