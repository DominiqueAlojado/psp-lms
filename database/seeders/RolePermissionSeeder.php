<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // System Admin - All permissions except sensitive resident-only actions
        $systemAdmin = Role::findByName('System Admin');
        $excludedPermissions = [
            'take-assessments',
            'view-resident-grades',
        ];
        $systemAdminPermissions = Permission::whereNotIn('name', $excludedPermissions)->get();
        // Use sync to ensure excluded permissions are removed if previously assigned
        $systemAdmin->syncPermissions($systemAdminPermissions);

        // Admin - All permissions except system-wide user/permission management
        $admin = Role::findByName('Admin');
        $admin->givePermissionTo([
            // Course Management
            'view-courses',
            'create-courses',
            'edit-courses',
            'delete-courses',
            'publish-courses',
            'enroll-residents',
            'manage-course-content',
            'assign-instructors',

            // Resident Management
            'view-residents',
            'create-residents',
            'edit-residents',
            'delete-residents',
            'view-resident-progress',
            'approve-residents',
            'deactivate-residents',

            // Materials
            'view-materials',
            'upload-materials',
            'edit-materials',
            'delete-materials',
            'download-materials',
            'approve-materials',

            // Assessments
            'view-assessments',
            'create-assessments',
            'edit-assessments',
            'delete-assessments',
            'view-inservice-exams',
            'view-institution-exams',
            'grade-assessments',
            'view-assessment-results',
            'export-assessment-results',
            'view-assessment-reports',
            'view-all-assessment-reports',

            // Assignments
            'view-assignments',
            'create-assignments',
            'edit-assignments',
            'delete-assignments',
            'grade-assignments',
            'view-all-submissions',

            // Events
            'view-events',
            'create-events',
            'edit-events',
            'delete-events',

            // Support / notifications / activity
            'manage-support-tickets',
            'view-support-tickets',
            'create-support-tickets',
            'edit-support-tickets',
            'delete-support-tickets',
            'view-feedback',
            'create-feedback',
            'view-all-feedback',
            'delete-feedback',
            'view-notifications',
            'view-activity-logs',

            // Cases
            'view-cases',
            'submit-cases',
            'review-cases',
            'approve-cases',
            'edit-cases',
            'delete-cases',

            // Certificates
            'view-certificates',
            'issue-certificates',
            'revoke-certificates',
            'verify-certificates',

            // Reports
            'view-reports',
            'generate-reports',
            'export-reports',
            'view-analytics',
            'view-organization-analytics',

            // Organization
            'manage-organization',
            'manage-organization-settings',
            'manage-training-officers',
            'view-organization-members',

            // Users (limited)
            'view-users',
            'create-users',
            'edit-users',

            // Announcements
            'view-announcements',
            'create-announcements',
            'create-system-announcements',
            'edit-announcements',
            'delete-announcements',
            'send-notifications',

            // Logbook
            'view-logbook',
            'approve-logbook-entries',
            'export-logbook',

            // Rotations
            'view-rotations',
            'create-rotations',
            'edit-rotations',
            'assign-rotations',
            'view-schedules',
            'manage-schedules',
        ]);

        // Training Officer
        $trainingOfficer = Role::findByName('Training Officer');
        $trainingOfficer->givePermissionTo([
            // Courses
            'view-courses',
            'create-courses',
            'edit-courses',
            'publish-courses',
            'enroll-residents',
            'manage-course-content',
            'assign-instructors',

            // Materials
            'view-materials',
            'upload-materials',
            'edit-materials',
            'delete-materials',
            'download-materials',

            // Assessments
            'view-assessments',
            'create-assessments',
            'edit-assessments',
            'grade-assessments',
            'view-inservice-exams',
            'view-institution-exams',
            'view-assessment-results',
            'export-assessment-results',
            'view-assessment-reports',
            'view-all-assessment-reports',

            // Assignments
            'view-assignments',
            'create-assignments',
            'edit-assignments',
            'delete-assignments',
            'grade-assignments',
            'view-all-submissions',

            // Events
            'view-events',
            'create-events',
            'edit-events',
            'delete-events',

            // Support / notifications / activity
            'manage-support-tickets',
            'view-support-tickets',
            'edit-support-tickets',
            'view-feedback',
            'create-feedback',
            'view-all-feedback',
            'view-notifications',
            'view-activity-logs',

            // Cases
            'view-cases',
            'review-cases',
            'approve-cases',

            // Certificates
            'view-certificates',
            'issue-certificates',

            // Reports
            'view-reports',
            'generate-reports',
            'export-reports',
            'view-analytics',

            // Announcements
            'view-announcements',
            'create-announcements',
            'edit-announcements',

            // Logbook
            'view-logbook',
            'approve-logbook-entries',
            'export-logbook',

            // Rotations
            'view-rotations',
            'create-rotations',
            'edit-rotations',
            'assign-rotations',
            'view-schedules',
            'manage-schedules',
        ]);

        // BOP (Board of Pathology)
        $bop = Role::findByName('BOP');
        $bop->givePermissionTo([
            // View/Review focus
            'view-courses',

            'view-materials',
            'download-materials',
            'approve-materials',

            'view-assessments',
            'grade-assessments',
            'view-inservice-exams',
            'view-institution-exams',
            'view-assessment-results',
            'view-assessment-reports',

            // Case review is important for BOP
            'view-cases',
            'review-cases',
            'approve-cases',

            // Certificates
            'view-certificates',
            'issue-certificates',
            'verify-certificates',

            // Reports/Analytics
            'view-reports',
            'view-analytics',
            'view-organization-analytics',
            'view-all-assessment-reports',

            // Assignments / events / support visibility
            'view-assignments',
            'grade-assignments',
            'view-all-submissions',
            'view-events',
            'manage-support-tickets',
            'view-support-tickets',
            'edit-support-tickets',
            'view-feedback',
            'create-feedback',
            'view-all-feedback',
            'view-notifications',
            'view-activity-logs',

            'view-announcements',
            'create-system-announcements',

            // Logbook review
            'view-logbook',
            'approve-logbook-entries',

            'view-rotations',
            'view-schedules',
        ]);

        // Resident
        $resident = Role::findByName('Resident');
        $resident->givePermissionTo([
            // View enrolled courses
            'view-courses',

            // Resident experience
            'view-resident-grades',
            'view-resident-assignments',
            'view-feedback',
            'create-feedback',
            'view-notifications',

            // Materials
            'view-materials',
            'download-materials',

            // Assessments
            'view-assessments',
            'take-assessments',
            'view-assessment-results', // Own results

            // Cases
            'view-cases', // Own cases
            'submit-cases',

            // Certificates
            'view-certificates', // Own certificates

            // Announcements
            'view-announcements',

            // Support
            'view-support-tickets',
            'create-support-tickets',

            // Logbook
            'view-logbook', // Own logbook
            'create-logbook-entries',
            'edit-logbook-entries',
            'delete-logbook-entries',

            // Rotations/Schedules
            'view-rotations', // Own rotations
            'view-schedules', // Own schedules
        ]);

        $this->command->info('Assigned permissions to all roles');
    }
}
