<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed in correct order
        $this->call([
            OrganizationSeeder::class,  // Create organizations
            RoleSeeder::class,           // Create roles
            PermissionSeeder::class,     // Create permissions
            AssignmentPermissionSeeder::class, // Create assignment-specific permissions
            EventPermissionSeeder::class, // Create event-specific permissions
            AnnouncementPermissionSeeder::class, // Create announcement-specific permissions
            RolePermissionSeeder::class, // Assign permissions to roles
            SystemAdminSeeder::class,    // Create system admin user
            StaffSeeder::class,          // Create staff members
            ResidentSeeder::class,       // Seed residents with user accounts
            TopicSeeder::class,          // Create topics for questions
            QuestionBankSeeder::class,   // Populate the question bank (institution questions)
            InServiceQuestionBankSeeder::class, // Populate national in-service exam question bank
            InServiceExamSeeder::class,  // Seed in-service (national) exams
            InServiceExamPart2Seeder::class, // Seed additional in-service exams (Part 2) with questions from bank
            InServiceExamResidentSeeder::class, // Enroll all residents in in-service exams
            InServiceExamCompletedAttemptsSeeder::class, // Create completed exam attempts with answers for statistics
            BataanGeneralHospitalExamsSeeder::class, // Create dummy exams
            LongFormQuestionsExamSeeder::class,      // Create exam with long questions
            AnnouncementSeeder::class,   // Create sample announcements
            LearningResourceSeeder::class, // Create sample learning resources
        ]);

        // User::factory(10)->create();

        // Create test user if not exists
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
    }
}
