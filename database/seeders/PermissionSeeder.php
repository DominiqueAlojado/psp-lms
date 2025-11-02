<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Course Management
            'view-courses',
            'create-courses',
            'edit-courses',
            'delete-courses',
            'publish-courses',
            'enroll-residents',
            'manage-course-content',
            'assign-instructors',

            // Resident/Student Management
            'view-residents',
            'create-residents',
            'edit-residents',
            'delete-residents',
            'view-resident-progress',
            'approve-residents',
            'deactivate-residents',

            // Learning Content/Materials
            'view-materials',
            'upload-materials',
            'edit-materials',
            'delete-materials',
            'download-materials',
            'approve-materials',

            // Assessments/Examinations
            'view-assessments',
            'create-assessments',
            'edit-assessments',
            'delete-assessments',
            'take-assessments',
            'grade-assessments',
            'view-assessment-results',
            'export-assessment-results',

            // Case Studies/Pathology Cases
            'view-cases',
            'submit-cases',
            'review-cases',
            'approve-cases',
            'edit-cases',
            'delete-cases',

            // Certifications/Credentials
            'view-certificates',
            'issue-certificates',
            'revoke-certificates',
            'verify-certificates',

            // Reports & Analytics
            'view-reports',
            'generate-reports',
            'export-reports',
            'view-analytics',
            'view-organization-analytics',

            // Organization Management
            'manage-organization',
            'manage-organization-settings',
            'manage-training-officers',
            'view-organization-members',

            // User Management
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'assign-roles',
            'manage-permissions',

            // Announcements/Communications
            'view-announcements',
            'create-announcements',
            'edit-announcements',
            'delete-announcements',
            'send-notifications',

            // Logbook/Clinical Experience
            'view-logbook',
            'create-logbook-entries',
            'edit-logbook-entries',
            'delete-logbook-entries',
            'approve-logbook-entries',
            'export-logbook',

            // Rotation/Schedule Management
            'view-rotations',
            'create-rotations',
            'edit-rotations',
            'assign-rotations',
            'view-schedules',
            'manage-schedules',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $this->command->info('Created '.count($permissions).' permissions');
    }
}
