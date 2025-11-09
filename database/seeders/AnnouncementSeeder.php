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

        // Get a sample organization and user (prefer PSP main, or any organization)
        $organization = Organization::where('slug', 'psp-main')
            ->orWhereNotNull('id')
            ->first();

        if (! $organization) {
            $this->command->warn('⚠️  No organizations found. Skipping announcement seeding.');

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
                'title' => '🎭 DEMO SITE NOTICE - All Data is Sample/Test Data',
                'content' => '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 10px; color: white; margin-bottom: 15px;"><h2 style="color: white; margin-top: 0;">⚠️ Welcome to the Demo Environment</h2><p style="font-size: 16px;"><strong>Please Note:</strong> This is a demonstration site for testing and evaluation purposes.</p></div><div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0;"><h3 style="color: #856404; margin-top: 0;">📋 Important Information:</h3><ul style="color: #856404;"><li><strong>All data displayed is fictitious</strong> - Names, organizations, exam results, and announcements are randomly generated for demo purposes</li><li><strong>Data resets regularly</strong> - The database may be refreshed periodically to showcase the system\'s features</li><li><strong>Test freely</strong> - Feel free to explore all features, create test entries, and experiment with the system</li><li><strong>No real data</strong> - Do not enter any real personal information, medical records, or confidential data</li></ul></div><div style="background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 15px 0;"><h3 style="color: #0c5460; margin-top: 0;">✨ System Features:</h3><p style="color: #0c5460;">This demonstration includes sample residents, institutions, exams, assessments, and learning materials to help you evaluate the complete Learning Management System experience.</p></div><p style="text-align: center; color: #6c757d; font-style: italic; margin-top: 20px;">Thank you for testing our platform! For inquiries, please contact the system administrator.</p>',
                'scope' => 'system',
                'priority' => 'urgent',
                'is_pinned' => true,
            ],
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
