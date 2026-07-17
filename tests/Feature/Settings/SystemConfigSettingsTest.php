<?php

namespace Tests\Feature\Settings;

use App\Http\Middleware\SetOrganizationFromUrl;
use App\Models\SystemConfig;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SystemConfigSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_system_configuration_settings(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::firstOrCreate([
            'name' => 'manage-system-configurations',
            'guard_name' => 'web',
        ]);

        SystemConfig::query()->updateOrCreate([
            'key' => 'ui.resident_demo_notice_enabled',
        ], [
            'module' => 'User Experience',
            'label' => 'Resident Demo Environment Notice',
            'description' => 'Show the resident portal demo-data notice modal after sign in.',
            'type' => 'boolean',
            'value' => '1',
            'is_public' => true,
            'is_editable' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('manage-system-configurations');

        $this->actingAs($user)
            ->get(route('configurations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/configurations')
                ->where('summary.total', 1)
                ->where('configurations.0.key', 'ui.resident_demo_notice_enabled')
                ->where('configurations.0.value', true)
            );
    }

    public function test_authorized_user_can_update_boolean_system_configuration(): void
    {
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            SetOrganizationFromUrl::class,
        ]);

        Permission::firstOrCreate([
            'name' => 'manage-system-configurations',
            'guard_name' => 'web',
        ]);

        $config = SystemConfig::query()->updateOrCreate([
            'key' => 'ui.resident_demo_notice_enabled',
        ], [
            'module' => 'User Experience',
            'label' => 'Resident Demo Environment Notice',
            'description' => 'Show the resident portal demo-data notice modal after sign in.',
            'type' => 'boolean',
            'value' => '1',
            'is_public' => true,
            'is_editable' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('manage-system-configurations');

        $this->actingAs($user)
            ->from(route('configurations.index'))
            ->patch(route('configurations.update'), [
                'configs' => [
                    'ui.resident_demo_notice_enabled' => false,
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('configurations.index'));

        $this->assertSame('0', $config->fresh()->value);
    }
}
