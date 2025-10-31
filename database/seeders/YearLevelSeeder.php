<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class YearLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $yearLevels = [
            [
                'name' => 'Pre-Resident',
                'slug' => 'pre-resident',
                'order' => 1,
                'description' => 'Pre-residency training level',
            ],
            [
                'name' => '1st Year',
                'slug' => '1st-year',
                'order' => 2,
                'description' => 'First year resident',
            ],
            [
                'name' => '2nd Year',
                'slug' => '2nd-year',
                'order' => 3,
                'description' => 'Second year resident',
            ],
            [
                'name' => '3rd Year',
                'slug' => '3rd-year',
                'order' => 4,
                'description' => 'Third year resident',
            ],
            [
                'name' => '4th Year',
                'slug' => '4th-year',
                'order' => 5,
                'description' => 'Fourth year resident',
            ],
            [
                'name' => 'Graduate',
                'slug' => 'graduate',
                'order' => 6,
                'description' => 'Graduated from residency program',
            ],
        ];

        foreach ($yearLevels as $level) {
            DB::connection('landlord')->table('year_levels')->updateOrInsert(
                ['slug' => $level['slug']],
                [
                    ...$level,
                    'uuid' => Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('Successfully seeded year levels!');
    }
}
