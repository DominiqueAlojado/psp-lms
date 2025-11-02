<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resident>
 */
class ResidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $middleName = $this->faker->lastName(); // Using lastName as middle name is common in PH
        $lastName = $this->faker->lastName();

        return [
            'organization_id' => \App\Models\Organization::inRandomOrder()->first()?->id ?? 1,
            'user_id' => null, // Can be linked to a user later
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'email' => strtolower($firstName.'.'.$lastName.'@'.fake()->randomElement(['gmail.com', 'yahoo.com', 'outlook.com'])),
            'contact_number' => '+639'.$this->faker->numerify('#########'), // Philippine mobile format
            'course' => $this->faker->randomElement([
                'Anatomic and Clinical Pathology',
                'Anatomic Pathology',
                'Clinical Pathology',
            ]),
            'year_level' => $this->faker->numberBetween(1, 4),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive', 'graduated']), // More active residents
            'other_info' => [
                'medical_school' => $this->faker->randomElement([
                    'University of the Philippines College of Medicine',
                    'University of Santo Tomas Faculty of Medicine and Surgery',
                    'Ateneo de Manila School of Medicine and Public Health',
                    'De La Salle Medical and Health Sciences Institute',
                    'Cebu Institute of Medicine',
                    'University of Perpetual Help System DALTA',
                    'Far Eastern University - NRMF',
                ]),
                'graduation_year' => $this->faker->year('-5 years'),
                'license_number' => 'PRC-'.$this->faker->numerify('########'),
                'date_started' => $this->faker->date(),
                'expected_completion' => $this->faker->date('+3 years'),
                'subspecialty_interest' => $this->faker->randomElement([
                    'Surgical Pathology',
                    'Cytopathology',
                    'Hematopathology',
                    'Dermatopathology',
                    'Molecular Pathology',
                    'Forensic Pathology',
                ]),
            ],
        ];
    }
}
