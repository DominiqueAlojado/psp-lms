<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'manage-system-configurations',
            'guard_name' => 'web',
        ], [
            'module' => 'System Configuration',
            'display_order' => 1,
        ]);

        $role = Role::query()->where('name', 'System Admin')->first();

        if ($role && ! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        Permission::query()->where('name', 'manage-system-configurations')->delete();
    }
};
