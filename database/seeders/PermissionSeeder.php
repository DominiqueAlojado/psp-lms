<?php

namespace Database\Seeders;

use App\Models\PermissionModuleOption;
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
            ['name' => 'view-courses', 'module' => 'Course Management', 'display_order' => 1],
            ['name' => 'create-courses', 'module' => 'Course Management', 'display_order' => 2],
            ['name' => 'edit-courses', 'module' => 'Course Management', 'display_order' => 3],
            ['name' => 'delete-courses', 'module' => 'Course Management', 'display_order' => 4],
            ['name' => 'publish-courses', 'module' => 'Course Management', 'display_order' => 5],
            ['name' => 'enroll-residents', 'module' => 'Course Management', 'display_order' => 6],
            ['name' => 'manage-course-content', 'module' => 'Course Management', 'display_order' => 7],
            ['name' => 'assign-instructors', 'module' => 'Course Management', 'display_order' => 8],

            // Resident Management
            ['name' => 'view-residents', 'module' => 'Resident Management', 'display_order' => 1],
            ['name' => 'create-residents', 'module' => 'Resident Management', 'display_order' => 2],
            ['name' => 'edit-residents', 'module' => 'Resident Management', 'display_order' => 3],
            ['name' => 'delete-residents', 'module' => 'Resident Management', 'display_order' => 4],
            ['name' => 'export-residents', 'module' => 'Resident Management', 'display_order' => 5],
            ['name' => 'view-resident-progress', 'module' => 'Resident Management', 'display_order' => 6],
            ['name' => 'approve-residents', 'module' => 'Resident Management', 'display_order' => 7],
            ['name' => 'deactivate-residents', 'module' => 'Resident Management', 'display_order' => 8],

            // Resident Experience
            ['name' => 'view-resident-grades', 'module' => 'Resident Experience', 'display_order' => 1],
            ['name' => 'view-resident-assignments', 'module' => 'Resident Experience', 'display_order' => 2],

            // Staff Management
            ['name' => 'view-staff', 'module' => 'Staff Management', 'display_order' => 1],
            ['name' => 'create-staff', 'module' => 'Staff Management', 'display_order' => 2],
            ['name' => 'edit-staff', 'module' => 'Staff Management', 'display_order' => 3],
            ['name' => 'delete-staff', 'module' => 'Staff Management', 'display_order' => 4],
            ['name' => 'export-staff', 'module' => 'Staff Management', 'display_order' => 5],

            // Institution Management
            ['name' => 'view-institutions', 'module' => 'Institution Management', 'display_order' => 1],
            ['name' => 'create-institutions', 'module' => 'Institution Management', 'display_order' => 2],
            ['name' => 'edit-institutions', 'module' => 'Institution Management', 'display_order' => 3],
            ['name' => 'delete-institutions', 'module' => 'Institution Management', 'display_order' => 4],
            ['name' => 'export-institutions', 'module' => 'Institution Management', 'display_order' => 5],

            // Learning Materials
            ['name' => 'view-materials', 'module' => 'Learning Materials', 'display_order' => 1],
            ['name' => 'upload-materials', 'module' => 'Learning Materials', 'display_order' => 2],
            ['name' => 'edit-materials', 'module' => 'Learning Materials', 'display_order' => 3],
            ['name' => 'delete-materials', 'module' => 'Learning Materials', 'display_order' => 4],
            ['name' => 'download-materials', 'module' => 'Learning Materials', 'display_order' => 5],
            ['name' => 'approve-materials', 'module' => 'Learning Materials', 'display_order' => 6],

            // Assessments
            ['name' => 'view-assessments', 'module' => 'Assessments', 'display_order' => 1],
            ['name' => 'create-assessments', 'module' => 'Assessments', 'display_order' => 2],
            ['name' => 'edit-assessments', 'module' => 'Assessments', 'display_order' => 3],
            ['name' => 'delete-assessments', 'module' => 'Assessments', 'display_order' => 4],
            ['name' => 'take-assessments', 'module' => 'Assessments', 'display_order' => 5],
            ['name' => 'grade-assessments', 'module' => 'Assessments', 'display_order' => 6],
            ['name' => 'view-assessment-results', 'module' => 'Assessments', 'display_order' => 7],
            ['name' => 'export-assessment-results', 'module' => 'Assessments', 'display_order' => 8],
            ['name' => 'view-inservice-exams', 'module' => 'Assessments', 'display_order' => 9],
            ['name' => 'view-institution-exams', 'module' => 'Assessments', 'display_order' => 10],

            // Case Studies
            ['name' => 'view-cases', 'module' => 'Case Studies', 'display_order' => 1],
            ['name' => 'submit-cases', 'module' => 'Case Studies', 'display_order' => 2],
            ['name' => 'review-cases', 'module' => 'Case Studies', 'display_order' => 3],
            ['name' => 'approve-cases', 'module' => 'Case Studies', 'display_order' => 4],
            ['name' => 'edit-cases', 'module' => 'Case Studies', 'display_order' => 5],
            ['name' => 'delete-cases', 'module' => 'Case Studies', 'display_order' => 6],

            // Certificates
            ['name' => 'view-certificates', 'module' => 'Certificates', 'display_order' => 1],
            ['name' => 'issue-certificates', 'module' => 'Certificates', 'display_order' => 2],
            ['name' => 'revoke-certificates', 'module' => 'Certificates', 'display_order' => 3],
            ['name' => 'verify-certificates', 'module' => 'Certificates', 'display_order' => 4],

            // Reports & Analytics
            ['name' => 'view-reports', 'module' => 'Reports & Analytics', 'display_order' => 1],
            ['name' => 'generate-reports', 'module' => 'Reports & Analytics', 'display_order' => 2],
            ['name' => 'export-reports', 'module' => 'Reports & Analytics', 'display_order' => 3],
            ['name' => 'view-analytics', 'module' => 'Reports & Analytics', 'display_order' => 4],
            ['name' => 'view-organization-analytics', 'module' => 'Reports & Analytics', 'display_order' => 5],
            ['name' => 'view-all-assessment-reports', 'module' => 'Assessment Reports', 'display_order' => 6],

            // Assessment Reports
            ['name' => 'view-assessment-reports', 'module' => 'Assessment Reports', 'display_order' => 1],

            // Assignments
            ['name' => 'view-assignments', 'module' => 'Assignments', 'display_order' => 1],
            ['name' => 'create-assignments', 'module' => 'Assignments', 'display_order' => 2],
            ['name' => 'edit-assignments', 'module' => 'Assignments', 'display_order' => 3],
            ['name' => 'delete-assignments', 'module' => 'Assignments', 'display_order' => 4],
            ['name' => 'grade-assignments', 'module' => 'Assignments', 'display_order' => 5],
            ['name' => 'view-all-submissions', 'module' => 'Assignments', 'display_order' => 6],

            // Events
            ['name' => 'view-events', 'module' => 'Events', 'display_order' => 1],
            ['name' => 'create-events', 'module' => 'Events', 'display_order' => 2],
            ['name' => 'edit-events', 'module' => 'Events', 'display_order' => 3],
            ['name' => 'delete-events', 'module' => 'Events', 'display_order' => 4],

            // Support Tickets
            ['name' => 'view-support-tickets', 'module' => 'Support Tickets', 'display_order' => 1],
            ['name' => 'create-support-tickets', 'module' => 'Support Tickets', 'display_order' => 2],
            ['name' => 'edit-support-tickets', 'module' => 'Support Tickets', 'display_order' => 3],
            ['name' => 'delete-support-tickets', 'module' => 'Support Tickets', 'display_order' => 4],
            ['name' => 'manage-support-tickets', 'module' => 'Support Tickets', 'display_order' => 5],

            // Feedback
            ['name' => 'view-feedback', 'module' => 'Feedback', 'display_order' => 1],
            ['name' => 'create-feedback', 'module' => 'Feedback', 'display_order' => 2],
            ['name' => 'view-all-feedback', 'module' => 'Feedback', 'display_order' => 3],
            ['name' => 'delete-feedback', 'module' => 'Feedback', 'display_order' => 4],

            // Notifications
            ['name' => 'view-notifications', 'module' => 'Notifications', 'display_order' => 1],

            // Activity Logs
            ['name' => 'view-activity-logs', 'module' => 'Activity Logs', 'display_order' => 1],

            // Organization
            ['name' => 'manage-organization', 'module' => 'Organization', 'display_order' => 1],
            ['name' => 'manage-organization-settings', 'module' => 'Organization', 'display_order' => 2],
            ['name' => 'manage-training-officers', 'module' => 'Organization', 'display_order' => 3],
            ['name' => 'view-organization-members', 'module' => 'Organization', 'display_order' => 4],

            // User Management
            ['name' => 'view-users', 'module' => 'User Management', 'display_order' => 1],
            ['name' => 'create-users', 'module' => 'User Management', 'display_order' => 2],
            ['name' => 'edit-users', 'module' => 'User Management', 'display_order' => 3],
            ['name' => 'delete-users', 'module' => 'User Management', 'display_order' => 4],
            ['name' => 'assign-roles', 'module' => 'User Management', 'display_order' => 5],
            ['name' => 'manage-permissions', 'module' => 'User Management', 'display_order' => 6],

            // Announcements
            ['name' => 'view-announcements', 'module' => 'Announcements', 'display_order' => 1],
            ['name' => 'create-announcements', 'module' => 'Announcements', 'display_order' => 2],
            ['name' => 'create-system-announcements', 'module' => 'Announcements', 'display_order' => 3],
            ['name' => 'edit-announcements', 'module' => 'Announcements', 'display_order' => 4],
            ['name' => 'delete-announcements', 'module' => 'Announcements', 'display_order' => 5],
            ['name' => 'send-notifications', 'module' => 'Announcements', 'display_order' => 6],

            // Logbook
            ['name' => 'view-logbook', 'module' => 'Logbook', 'display_order' => 1],
            ['name' => 'create-logbook-entries', 'module' => 'Logbook', 'display_order' => 2],
            ['name' => 'edit-logbook-entries', 'module' => 'Logbook', 'display_order' => 3],
            ['name' => 'delete-logbook-entries', 'module' => 'Logbook', 'display_order' => 4],
            ['name' => 'approve-logbook-entries', 'module' => 'Logbook', 'display_order' => 5],
            ['name' => 'export-logbook', 'module' => 'Logbook', 'display_order' => 6],

            // Rotations & Schedules
            ['name' => 'view-rotations', 'module' => 'Rotations & Schedules', 'display_order' => 1],
            ['name' => 'create-rotations', 'module' => 'Rotations & Schedules', 'display_order' => 2],
            ['name' => 'edit-rotations', 'module' => 'Rotations & Schedules', 'display_order' => 3],
            ['name' => 'assign-rotations', 'module' => 'Rotations & Schedules', 'display_order' => 4],
            ['name' => 'view-schedules', 'module' => 'Rotations & Schedules', 'display_order' => 5],
            ['name' => 'manage-schedules', 'module' => 'Rotations & Schedules', 'display_order' => 6],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                $permission
            );
        }

        collect($permissions)
            ->groupBy('module')
            ->map(fn ($group) => [
                'name' => $group->first()['module'],
                'display_order' => min(array_column($group->all(), 'display_order')),
            ])
            ->each(function (array $module): void {
                PermissionModuleOption::query()->updateOrCreate(
                    ['name' => $module['name']],
                    ['display_order' => $module['display_order']],
                );
            });

        PermissionModuleOption::query()->firstOrCreate(
            ['name' => 'Other'],
            ['display_order' => 999],
        );

        $this->command->info('Created/Updated '.count($permissions).' permissions with modules');
    }
}
