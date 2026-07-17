<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // National org and chapters
        $organizations = [
            [
                'name' => 'In-Service Exams',
                'slug' => 'in-service-exams',
                'description' => 'National organization dedicated to managing in-service exams',
                'type' => 'national',
                'is_active' => true,
                'training_officers' => [],
            ],
            [
                'name' => 'Manila Chapter',
                'slug' => 'manila-chapter',
                'description' => 'PSP Manila Chapter',
                'type' => 'chapter',
                'is_active' => true,
            ],
            [
                'name' => 'Cebu Chapter',
                'slug' => 'cebu-chapter',
                'description' => 'PSP Cebu Chapter',
                'type' => 'chapter',
                'is_active' => true,
            ],
            [
                'name' => 'Davao Chapter',
                'slug' => 'davao-chapter',
                'description' => 'PSP Davao Chapter',
                'type' => 'chapter',
                'is_active' => true,
            ],
        ];

        foreach ($organizations as $org) {
            Organization::firstOrCreate(
                ['slug' => $org['slug']],
                $org
            );
        }

        // 50 Hospitals/Training Institutions
        $hospitals = [
            // Metro Manila Hospitals
            'Philippine General Hospital',
            'St. Luke\'s Medical Center - Quezon City',
            'St. Luke\'s Medical Center - Global City',
            'Makati Medical Center',
            'The Medical City',
            'Cardinal Santos Medical Center',
            'Veterans Memorial Medical Center',
            'National Kidney and Transplant Institute',
            'Lung Center of the Philippines',
            'Philippine Heart Center',
            'East Avenue Medical Center',
            'Jose R. Reyes Memorial Medical Center',
            'Manila Doctors Hospital',
            'Chinese General Hospital',
            'University of Santo Tomas Hospital',
            'De La Salle Medical and Health Sciences Institute',
            'Manila Central University Hospital',
            'Mary Chiles General Hospital',
            'Quirino Memorial Medical Center',
            'AFP Medical Center',
            'Ospital ng Maynila Medical Center',
            'San Juan de Dios Hospital',
            'Medical Center Manila',
            'Asian Hospital and Medical Center',
            'Perpetual Succour Hospital',

            // Regional Hospitals - Luzon
            'Baguio General Hospital and Medical Center',
            'Notre Dame de Chartres Hospital - Baguio',
            'Pines City Doctors Hospital',
            'Saint Louis University Hospital of the Sacred Heart',
            'Benguet General Hospital',
            'Bataan General Hospital',
            'Dr. Paulino J. Garcia Memorial Research and Medical Center',
            'Batangas Medical Center',
            'Vicente Gustilo Memorial District Hospital',
            'Bicol Regional Training and Teaching Hospital',
            'Bicol Medical Center',

            // Regional Hospitals - Visayas
            'Vicente Sotto Memorial Medical Center',
            'Cebu Doctors\' University Hospital',
            'Chong Hua Hospital',
            'Perpetual Succour Hospital - Cebu',
            'Cebu Velez General Hospital',
            'Western Visayas Medical Center',
            'Iloilo Mission Hospital',
            'West Visayas State University Medical Center',
            'Corazon Locsin Montelibano Memorial Regional Hospital',

            // Regional Hospitals - Mindanao
            'Southern Philippines Medical Center',
            'Davao Doctors Hospital',
            'Davao Regional Medical Center',
            'Brokenshire Memorial Hospital',
            'San Pedro Hospital',
            'Northern Mindanao Medical Center',
            'Mindanao Sanitarium and Hospital',
            'Zamboanga City Medical Center',
            'J.R. Borja General Hospital',
            'Cagayan de Oro Medical Center',
        ];

        foreach ($hospitals as $index => $hospitalName) {
            $slug = \Illuminate\Support\Str::slug($hospitalName);

            Organization::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $hospitalName,
                    'slug' => $slug,
                    'description' => 'Training institution and hospital partner',
                    'type' => 'institution',
                    'is_active' => true,
                ]
            );
        }
    }
}
