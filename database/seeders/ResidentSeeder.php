<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResidentSeeder extends Seeder
{
    private const ADDITIONAL_INSTITUTION_SLUGS = [
        'st-lukes-medical-center-global-city',
        'makati-medical-center',
        'asian-hospital-and-medical-center',
        'cebu-doctors-university-hospital',
        'western-visayas-medical-center',
        'vicente-sotto-memorial-medical-center',
        'southern-philippines-medical-center',
        'davao-doctors-hospital',
        'bicol-medical-center',
        'batangas-medical-center',
        'philippine-general-hospital',
        'university-of-santo-tomas-hospital',
    ];

    private const FIRST_NAMES = [
        'Adrian', 'Bea', 'Carlo', 'Denise', 'Ethan', 'Frances', 'Gabriel', 'Hannah',
        'Isaac', 'Jillian', 'Kurt', 'Lara', 'Marco', 'Nina', 'Owen', 'Paula',
        'Quinn', 'Rafael', 'Sofia', 'Theo', 'Una', 'Vince', 'Wendy', 'Xander',
        'Yasmin', 'Zion',
    ];

    private const MIDDLE_NAMES = [
        'Santos', 'Reyes', 'Cruz', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Navarro',
        'Lim', 'Tan', 'Co', 'Uy', 'Mercado', 'Domingo', 'Lopez', 'Aquino',
        'Castro', 'Villanueva', 'Pascual', 'Ramos', 'Bautista', 'Sy', 'Agustin', 'Yu',
    ];

    private const LAST_NAMES = [
        'Alvarez', 'Bautista', 'Castillo', 'DelosReyes', 'Escobar', 'Fernandez', 'Gutierrez',
        'Hernandez', 'Ignacio', 'Jimenez', 'Lorenzo', 'Moreno', 'Natividad', 'Ocampo',
        'Pineda', 'Quinto', 'Rosales', 'Samonte', 'Tolentino', 'Valdez', 'Zamora',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cohorts = [
            'bataan-general-hospital' => [
                ['first_name' => 'John', 'middle_name' => 'Mendoza', 'last_name' => 'Doe', 'email' => 'john.doe@demo.psplms.test', 'year_level' => 'Second Year', 'status' => 'active'],
                ['first_name' => 'Jane', 'middle_name' => 'Garcia', 'last_name' => 'Cruz', 'email' => 'jane.cruz@demo.psplms.test', 'year_level' => 'Second Year', 'status' => 'active'],
                ['first_name' => 'Mark', 'middle_name' => 'Santos', 'last_name' => 'Reyes', 'email' => 'mark.reyes@demo.psplms.test', 'year_level' => 'First Year', 'status' => 'active'],
                ['first_name' => 'Alyssa', 'middle_name' => 'Torres', 'last_name' => 'Lim', 'email' => 'alyssa.lim@demo.psplms.test', 'year_level' => 'First Year', 'status' => 'active'],
                ['first_name' => 'Paolo', 'middle_name' => 'Navarro', 'last_name' => 'Gomez', 'email' => 'paolo.gomez@demo.psplms.test', 'year_level' => 'Third Year', 'status' => 'active'],
                ['first_name' => 'Rica', 'middle_name' => 'Villanueva', 'last_name' => 'Alonzo', 'email' => 'rica.alonzo@demo.psplms.test', 'year_level' => 'Third Year', 'status' => 'active'],
                ['first_name' => 'Miguel', 'middle_name' => 'Aquino', 'last_name' => 'Bautista', 'email' => 'miguel.bautista@demo.psplms.test', 'year_level' => 'Fourth Year', 'status' => 'active'],
                ['first_name' => 'Celine', 'middle_name' => 'Mercado', 'last_name' => 'Ramos', 'email' => 'celine.ramos@demo.psplms.test', 'year_level' => 'Fourth Year', 'status' => 'active'],
                ['first_name' => 'Noel', 'middle_name' => 'Castro', 'last_name' => 'Herrera', 'email' => 'noel.herrera@demo.psplms.test', 'year_level' => 'Graduate', 'status' => 'active'],
                ['first_name' => 'Lea', 'middle_name' => 'Domingo', 'last_name' => 'Pascual', 'email' => 'lea.pascual@demo.psplms.test', 'year_level' => 'Graduate', 'status' => 'active'],
            ],
            'west-visayas-state-university-medical-center' => [
                ['first_name' => 'Kevin', 'middle_name' => 'Flores', 'last_name' => 'Manalo', 'email' => 'kevin.manalo@demo.psplms.test', 'year_level' => 'Second Year', 'status' => 'active'],
                ['first_name' => 'Trisha', 'middle_name' => 'Sy', 'last_name' => 'Morales', 'email' => 'trisha.morales@demo.psplms.test', 'year_level' => 'Third Year', 'status' => 'active'],
                ['first_name' => 'Bryan', 'middle_name' => 'Co', 'last_name' => 'Velasco', 'email' => 'bryan.velasco@demo.psplms.test', 'year_level' => 'First Year', 'status' => 'active'],
                ['first_name' => 'Diane', 'middle_name' => 'Lopez', 'last_name' => 'Agustin', 'email' => 'diane.agustin@demo.psplms.test', 'year_level' => 'Fourth Year', 'status' => 'active'],
                ['first_name' => 'Harold', 'middle_name' => 'Misa', 'last_name' => 'Natividad', 'email' => 'harold.natividad@demo.psplms.test', 'year_level' => 'Graduate', 'status' => 'active'],
                ['first_name' => 'Sheena', 'middle_name' => 'Uy', 'last_name' => 'Santiago', 'email' => 'sheena.santiago@demo.psplms.test', 'year_level' => 'Second Year', 'status' => 'inactive'],
            ],
            'baguio-general-hospital-and-medical-center' => [
                ['first_name' => 'Anton', 'middle_name' => 'Diaz', 'last_name' => 'Roxas', 'email' => 'anton.roxas@demo.psplms.test', 'year_level' => 'First Year', 'status' => 'active'],
                ['first_name' => 'Monique', 'middle_name' => 'Tan', 'last_name' => 'Lozada', 'email' => 'monique.lozada@demo.psplms.test', 'year_level' => 'Second Year', 'status' => 'active'],
                ['first_name' => 'Jared', 'middle_name' => 'Ocampo', 'last_name' => 'Salvador', 'email' => 'jared.salvador@demo.psplms.test', 'year_level' => 'Third Year', 'status' => 'active'],
                ['first_name' => 'Patricia', 'middle_name' => 'Go', 'last_name' => 'Marquez', 'email' => 'patricia.marquez@demo.psplms.test', 'year_level' => 'Fourth Year', 'status' => 'active'],
                ['first_name' => 'Lorenzo', 'middle_name' => 'Yu', 'last_name' => 'Cabrera', 'email' => 'lorenzo.cabrera@demo.psplms.test', 'year_level' => 'Graduate', 'status' => 'active'],
                ['first_name' => 'Faith', 'middle_name' => 'Reyes', 'last_name' => 'Navarro', 'email' => 'faith.navarro@demo.psplms.test', 'year_level' => 'Second Year', 'status' => 'active'],
            ],
        ];

        $createdCount = 0;

        foreach ($cohorts as $organizationSlug => $residents) {
            $institution = Organization::where('slug', $organizationSlug)->first();

            if (! $institution) {
                $this->command->warn("Organization {$organizationSlug} not found. Skipping seeded residents.");

                continue;
            }

            foreach ($residents as $residentData) {
                $createdCount += $this->seedResident($institution, $residentData) ? 1 : 0;
            }

            $this->command->info("Ensured seeded resident cohort for {$institution->name}");
        }

        foreach (self::ADDITIONAL_INSTITUTION_SLUGS as $organizationSlug) {
            $institution = Organization::where('slug', $organizationSlug)->first();

            if (! $institution) {
                $this->command->warn("Organization {$organizationSlug} not found. Skipping generated cohort.");

                continue;
            }

            foreach ($this->buildGeneratedCohort($institution) as $residentData) {
                $createdCount += $this->seedResident($institution, $residentData) ? 1 : 0;
            }

            $this->command->info("Ensured generated resident cohort for {$institution->name}");
        }

        // Reset permission context
        setPermissionsTeamId(null);

        $totalResidents = Resident::count();
        $this->command->info("Total residents created: {$totalResidents}");
        $this->command->info("Residents created or updated in this run: {$createdCount}");

        // Display some statistics
        $this->command->newLine();
        $this->command->info('Resident Statistics:');
        $this->command->table(
            ['Year Level', 'Count'],
            [
                ['Pre-Resident', Resident::where('year_level', 'Pre-Resident')->count()],
                ['First Year', Resident::where('year_level', 'First Year')->count()],
                ['Second Year', Resident::where('year_level', 'Second Year')->count()],
                ['Third Year', Resident::where('year_level', 'Third Year')->count()],
                ['Fourth Year', Resident::where('year_level', 'Fourth Year')->count()],
                ['Graduate', Resident::where('year_level', 'Graduate')->count()],
            ]
        );

        $this->command->table(
            ['Status', 'Count'],
            [
                ['Active', Resident::where('status', 'active')->count()],
                ['Inactive', Resident::where('status', 'inactive')->count()],
            ]
        );
    }

    private function seedResident(Organization $institution, array $residentData): bool
    {
        $user = User::updateOrCreate(
            ['email' => $residentData['email']],
            [
                'name' => trim($residentData['first_name'].' '.$residentData['last_name']),
                'password' => 'password',
                'email_verified_at' => now(),
                'current_organization_id' => $institution->id,
            ]
        );

        $resident = Resident::updateOrCreate(
            ['email' => $residentData['email']],
            [
                'organization_id' => $institution->id,
                'user_id' => $user->id,
                'first_name' => $residentData['first_name'],
                'middle_name' => $residentData['middle_name'],
                'last_name' => $residentData['last_name'],
                'contact_number' => $residentData['contact_number'] ?? '+639'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
                'course' => 'Anatomic and Clinical Pathology',
                'year_level' => $residentData['year_level'],
                'status' => $residentData['status'],
                'other_info' => [
                    'medical_school' => $residentData['medical_school'] ?? 'University of the Philippines College of Medicine',
                    'graduation_year' => $residentData['graduation_year'] ?? 2022,
                    'license_number' => $residentData['license_number'] ?? 'PRC-'.random_int(10000000, 99999999),
                    'date_started' => $residentData['date_started'] ?? now()->subYears(2)->toDateString(),
                    'expected_completion' => $residentData['expected_completion'] ?? now()->addYear()->toDateString(),
                    'subspecialty_interest' => $residentData['subspecialty_interest'] ?? 'Surgical Pathology',
                ],
            ]
        );

        if ($user->organizations()->where('organizations.id', $institution->id)->exists()) {
            $user->organizations()->updateExistingPivot($institution->id, [
                'joined_at' => $user->organizations()->firstWhere('id', $institution->id)?->pivot?->joined_at ?? now(),
                'is_active' => $resident->status === 'active',
            ]);
        } else {
            $user->organizations()->attach($institution->id, [
                'joined_at' => now(),
                'is_active' => $resident->status === 'active',
            ]);
        }

        setPermissionsTeamId($institution->id);

        if (! $user->hasRole('Resident')) {
            $user->assignRole('Resident');
        }

        return true;
    }

    private function buildGeneratedCohort(Organization $institution): array
    {
        $seed = abs(crc32($institution->slug));
        $targetCount = 8 + ($seed % 5);
        $yearLevels = [
            'First Year',
            'Second Year',
            'Third Year',
            'Fourth Year',
            'Graduate',
        ];

        $cohort = [];

        for ($i = 0; $i < $targetCount; $i++) {
            $firstName = self::FIRST_NAMES[($seed + ($i * 3)) % count(self::FIRST_NAMES)];
            $middleName = self::MIDDLE_NAMES[($seed + ($i * 5)) % count(self::MIDDLE_NAMES)];
            $lastName = self::LAST_NAMES[($seed + ($i * 7)) % count(self::LAST_NAMES)];
            $yearLevel = $yearLevels[$i % count($yearLevels)];
            $status = (($seed + $i) % 9 === 0) ? 'inactive' : 'active';
            $emailSlug = strtolower($firstName.'.'.$lastName.'.'.$institution->slug.'.'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT));

            $cohort[] = [
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'email' => $emailSlug.'@demo.psplms.test',
                'year_level' => $yearLevel,
                'status' => $status,
                'medical_school' => $this->medicalSchoolForSeed($seed + $i),
                'graduation_year' => 2020 + (($seed + $i) % 5),
                'subspecialty_interest' => $this->subspecialtyForSeed($seed + $i),
                'date_started' => now()->subYears(max(1, 5 - ($i % 5)))->toDateString(),
                'expected_completion' => now()->addMonths(6 + (($i % 5) * 6))->toDateString(),
            ];
        }

        return $cohort;
    }

    private function medicalSchoolForSeed(int $seed): string
    {
        $schools = [
            'University of the Philippines College of Medicine',
            'University of Santo Tomas Faculty of Medicine and Surgery',
            'Ateneo School of Medicine and Public Health',
            'De La Salle Medical and Health Sciences Institute',
            'Cebu Institute of Medicine',
            'West Visayas State University College of Medicine',
            'Far Eastern University - NRMF',
            'University of the East Ramon Magsaysay Memorial Medical Center',
        ];

        return $schools[$seed % count($schools)];
    }

    private function subspecialtyForSeed(int $seed): string
    {
        $specialties = [
            'Surgical Pathology',
            'Cytopathology',
            'Hematopathology',
            'Dermatopathology',
            'Molecular Pathology',
            'Clinical Chemistry',
            'Microbiology',
            'Transfusion Medicine',
        ];

        return $specialties[$seed % count($specialties)];
    }
}
