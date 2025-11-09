<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create new permission for assessment reports (both by-resident and live monitor)
        $permission = Permission::create([
            'name' => 'view-assessment-reports',
            'guard_name' => 'web',
        ]);

        // Assign to staff roles only (System Admin, BOP, Training Officer)
        // Residents will NOT have access to assessment reports
        // Residents use "My Exams" to see their own results
        $staffRoles = [
            'System Admin',
            'BOP',
            'Training Officer',
        ];

        foreach ($staffRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('name', 'view-assessment-reports')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
