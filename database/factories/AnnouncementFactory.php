<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'scope' => 'organization',
            'priority' => fake()->randomElement(['normal', 'important', 'urgent']),
            'is_published' => true,
            'is_pinned' => false,
            'target_year_levels' => null,
            'expires_at' => null,
        ];
    }

    /**
     * Indicate that the announcement is system-wide.
     */
    public function systemWide(): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => null,
            'scope' => 'system',
        ]);
    }

    /**
     * Indicate that the announcement is pinned.
     */
    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    /**
     * Indicate that the announcement is urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }

    /**
     * Indicate that the announcement has an expiration date.
     */
    public function expires(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('+1 week', '+3 months'),
        ]);
    }
}
