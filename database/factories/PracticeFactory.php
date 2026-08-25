<?php

namespace Database\Factories;

use App\Models\Practice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Practice>
 */
class PracticeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company().' Medical Practice',
            'logo_path' => null,
            'address' => fake()->streetAddress().', '.fake()->city().', '.fake()->stateAbbr(),
            'npi_number' => fake()->numerify('##########'),
            'specialty' => fake()->randomElement(Practice::SPECIALTIES),
            'billable_providers_count' => fake()->numberBetween(1, 20),
            'is_profile_locked' => false,
            'locked_at' => null,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_profile_locked' => true,
            'locked_at' => now(),
        ]);
    }
}
