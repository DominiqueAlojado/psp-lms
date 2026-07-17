<?php

namespace Tests\Feature\Settings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SyncPermissionModulesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_syncs_permission_modules_from_existing_permissions(): void
    {
        Permission::create([
            'name' => 'view-feedback',
            'guard_name' => 'web',
            'module' => 'Feedback',
            'display_order' => 12,
        ]);

        Permission::create([
            'name' => 'view-support',
            'guard_name' => 'web',
            'module' => 'Support',
            'display_order' => 18,
        ]);

        $this->artisan('permissions:sync-modules')
            ->expectsOutput('Synced 2 permission module entries from permissions.')
            ->assertSuccessful();

        $this->assertDatabaseHas('permission_module_options', [
            'name' => 'Feedback',
            'display_order' => 12,
        ]);

        $this->assertDatabaseHas('permission_module_options', [
            'name' => 'Support',
            'display_order' => 18,
        ]);

        $this->assertDatabaseHas('permission_module_options', [
            'name' => 'Other',
        ]);
    }
}
