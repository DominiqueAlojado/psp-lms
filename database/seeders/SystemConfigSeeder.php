<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

class SystemConfigSeeder extends Seeder
{
    public function run(): void
    {
        SystemConfig::query()->updateOrCreate(
            ['key' => 'ui.resident_demo_notice_enabled'],
            [
                'module' => 'User Experience',
                'label' => 'Resident Demo Environment Notice',
                'description' => 'Show the resident portal demo-data notice modal after sign in.',
                'type' => 'boolean',
                'value' => '1',
                'is_public' => true,
                'is_editable' => true,
                'sort_order' => 1,
            ],
        );
    }
}
