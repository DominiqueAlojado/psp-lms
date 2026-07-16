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
            'name' => 'manage-support-tickets',
            'guard_name' => 'web',
        ]);

        foreach (['System Admin', 'BOP', 'Admin', 'Training Officer'] as $roleName) {
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
        Permission::where('name', 'manage-support-tickets')->delete();
    }
};
