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
            ['name' => 'view-courses', 'category' => 'Course Management', 'display_order' => 1],
            ['name' => 'create-courses', 'category' => 'Course Management', 'display_order' => 2],
            ['name' => 'edit-courses', 'category' => 'Course Management', 'display_order' => 3],
            ['name' => 'delete-courses', 'category' => 'Course Management', 'display_order' => 4],
            ['name' => 'publish-courses', 'category' => 'Course Management', 'display_order' => 5],
            ['name' => 'enroll-residents', 'category' => 'Course Management', 'display_order' => 6],
            ['name' => 'manage-course-content', 'category' => 'Course Management', 'display_order' => 7],
            ['name' => 'assign-instructors', 'category' => 'Course Management', 'display_order' => 8],

            // Resident Management
            ['name' => 'view-residents', 'category' => 'Resident Management', 'display_order' => 1],
            ['name' => 'create-residents', 'category' => 'Resident Management', 'display_order' => 2],
            ['name' => 'edit-residents', 'category' => 'Resident Management', 'display_order' => 3],
            ['name' => 'delete-residents', 'category' => 'Resident Management', 'display_order' => 4],
            ['name' => 'export-residents', 'category' => 'Resident Management', 'display_order' => 5],
            ['name' => 'view-resident-progress', 'category' => 'Resident Management', 'display_order' => 6],
            ['name' => 'approve-residents', 'category' => 'Resident Management', 'display_order' => 7],
            ['name' => 'deactivate-residents', 'category' => 'Resident Management', 'display_order' => 8],

            // Resident Experience
            ['name' => 'view-resident-grades', 'category' => 'Resident Experience', 'display_order' => 1],
            ['name' => 'view-resident-assignments', 'category' => 'Resident Experience', 'display_order' => 2],

            // Staff Management
            ['name' => 'view-staff', 'category' => 'Staff Management', 'display_order' => 1],
            ['name' => 'create-staff', 'category' => 'Staff Management', 'display_order' => 2],
            ['name' => 'edit-staff', 'category' => 'Staff Management', 'display_order' => 3],
            ['name' => 'delete-staff', 'category' => 'Staff Management', 'display_order' => 4],
            ['name' => 'export-staff', 'category' => 'Staff Management', 'display_order' => 5],

            // Institution Management
            ['name' => 'view-institutions', 'category' => 'Institution Management', 'display_order' => 1],
            ['name' => 'create-institutions', 'category' => 'Institution Management', 'display_order' => 2],
            ['name' => 'edit-institutions', 'category' => 'Institution Management', 'display_order' => 3],
            ['name' => 'delete-institutions', 'category' => 'Institution Management', 'display_order' => 4],
            ['name' => 'export-institutions', 'category' => 'Institution Management', 'display_order' => 5],

            // Learning Materials
            ['name' => 'view-materials', 'category' => 'Learning Materials', 'display_order' => 1],
            ['name' => 'upload-materials', 'category' => 'Learning Materials', 'display_order' => 2],
            ['name' => 'edit-materials', 'category' => 'Learning Materials', 'display_order' => 3],
            ['name' => 'delete-materials', 'category' => 'Learning Materials', 'display_order' => 4],
            ['name' => 'download-materials', 'category' => 'Learning Materials', 'display_order' => 5],
            ['name' => 'approve-materials', 'category' => 'Learning Materials', 'display_order' => 6],

            // Assessments
            ['name' => 'view-assessments', 'category' => 'Assessments', 'display_order' => 1],
            ['name' => 'create-assessments', 'category' => 'Assessments', 'display_order' => 2],
            ['name' => 'edit-assessments', 'category' => 'Assessments', 'display_order' => 3],
            ['name' => 'delete-assessments', 'category' => 'Assessments', 'display_order' => 4],
            ['name' => 'take-assessments', 'category' => 'Assessments', 'display_order' => 5],
            ['name' => 'grade-assessments', 'category' => 'Assessments', 'display_order' => 6],
            ['name' => 'view-assessment-results', 'category' => 'Assessments', 'display_order' => 7],
            ['name' => 'export-assessment-results', 'category' => 'Assessments', 'display_order' => 8],

            // Case Studies
            ['name' => 'view-cases', 'category' => 'Case Studies', 'display_order' => 1],
            ['name' => 'submit-cases', 'category' => 'Case Studies', 'display_order' => 2],
            ['name' => 'review-cases', 'category' => 'Case Studies', 'display_order' => 3],
            ['name' => 'approve-cases', 'category' => 'Case Studies', 'display_order' => 4],
            ['name' => 'edit-cases', 'category' => 'Case Studies', 'display_order' => 5],
            ['name' => 'delete-cases', 'category' => 'Case Studies', 'display_order' => 6],

            // Certificates
            ['name' => 'view-certificates', 'category' => 'Certificates', 'display_order' => 1],
            ['name' => 'issue-certificates', 'category' => 'Certificates', 'display_order' => 2],
            ['name' => 'revoke-certificates', 'category' => 'Certificates', 'display_order' => 3],
            ['name' => 'verify-certificates', 'category' => 'Certificates', 'display_order' => 4],

            // Reports & Analytics
            ['name' => 'view-reports', 'category' => 'Reports & Analytics', 'display_order' => 1],
            ['name' => 'generate-reports', 'category' => 'Reports & Analytics', 'display_order' => 2],
            ['name' => 'export-reports', 'category' => 'Reports & Analytics', 'display_order' => 3],
            ['name' => 'view-analytics', 'category' => 'Reports & Analytics', 'display_order' => 4],
            ['name' => 'view-organization-analytics', 'category' => 'Reports & Analytics', 'display_order' => 5],

            // Organization
            ['name' => 'manage-organization', 'category' => 'Organization', 'display_order' => 1],
            ['name' => 'manage-organization-settings', 'category' => 'Organization', 'display_order' => 2],
            ['name' => 'manage-training-officers', 'category' => 'Organization', 'display_order' => 3],
            ['name' => 'view-organization-members', 'category' => 'Organization', 'display_order' => 4],

            // User Management
            ['name' => 'view-users', 'category' => 'User Management', 'display_order' => 1],
            ['name' => 'create-users', 'category' => 'User Management', 'display_order' => 2],
            ['name' => 'edit-users', 'category' => 'User Management', 'display_order' => 3],
            ['name' => 'delete-users', 'category' => 'User Management', 'display_order' => 4],
            ['name' => 'assign-roles', 'category' => 'User Management', 'display_order' => 5],
            ['name' => 'manage-permissions', 'category' => 'User Management', 'display_order' => 6],

            // Announcements
            ['name' => 'view-announcements', 'category' => 'Announcements', 'display_order' => 1],
            ['name' => 'create-announcements', 'category' => 'Announcements', 'display_order' => 2],
            ['name' => 'edit-announcements', 'category' => 'Announcements', 'display_order' => 3],
            ['name' => 'delete-announcements', 'category' => 'Announcements', 'display_order' => 4],
            ['name' => 'send-notifications', 'category' => 'Announcements', 'display_order' => 5],

            // Logbook
            ['name' => 'view-logbook', 'category' => 'Logbook', 'display_order' => 1],
            ['name' => 'create-logbook-entries', 'category' => 'Logbook', 'display_order' => 2],
            ['name' => 'edit-logbook-entries', 'category' => 'Logbook', 'display_order' => 3],
            ['name' => 'delete-logbook-entries', 'category' => 'Logbook', 'display_order' => 4],
            ['name' => 'approve-logbook-entries', 'category' => 'Logbook', 'display_order' => 5],
            ['name' => 'export-logbook', 'category' => 'Logbook', 'display_order' => 6],

            // Rotations & Schedules
            ['name' => 'view-rotations', 'category' => 'Rotations & Schedules', 'display_order' => 1],
            ['name' => 'create-rotations', 'category' => 'Rotations & Schedules', 'display_order' => 2],
            ['name' => 'edit-rotations', 'category' => 'Rotations & Schedules', 'display_order' => 3],
            ['name' => 'assign-rotations', 'category' => 'Rotations & Schedules', 'display_order' => 4],
            ['name' => 'view-schedules', 'category' => 'Rotations & Schedules', 'display_order' => 5],
            ['name' => 'manage-schedules', 'category' => 'Rotations & Schedules', 'display_order' => 6],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                $permission
            );
        }

        $this->command->info('Created/Updated '.count($permissions).' permissions with categories');
    }
}
