<?php

namespace Tests\Unit;

use App\Models\SystemConfig;
use App\Services\SystemConfigReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemConfigReadServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_payload_is_nested_and_casts_boolean_values(): void
    {
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

        $payload = app(SystemConfigReadService::class)->publicPayload();

        $this->assertTrue($payload['ui']['residentDemoNoticeEnabled']);
    }
}
