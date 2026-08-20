<?php

namespace Database\Factories;

use App\Enums\PackageTier;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'message' => fake()->paragraph(),
            'package_interest' => fake()->randomElement(array_column(PackageTier::cases(), 'value')),
            'is_contacted' => false,
            'contacted_at' => null,
            'contacted_by' => null,
            'admin_notes' => null,
        ];
    }

    public function contacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_contacted' => true,
            'contacted_at' => now(),
        ]);
    }
}
