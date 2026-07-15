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
        $permission = Permission::firstOrCreate([
            'name' => 'view-activity-logs',
            'guard_name' => 'web',
        ]);

        $roles = [
            'System Admin',
            'BOP',
            'Admin',
            'Training Officer',
        ];

        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'view-activity-logs')->delete();
    }
};
