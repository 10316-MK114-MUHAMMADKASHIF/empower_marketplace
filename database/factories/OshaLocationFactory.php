<?php

namespace Database\Factories;

use App\Models\OshaLocation;
use App\Models\Practice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OshaLocation>
 */
class OshaLocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'practice_id' => Practice::factory(),
            'name' => fake()->randomElement(['Main Office', 'Branch Clinic', 'Satellite Location']).' '.fake()->city(),
            'address' => fake()->streetAddress().', '.fake()->city().', '.fake()->stateAbbr(),
            'osha_officer' => fake()->name(),
            'safety_coordinator' => fake()->name(),
            'uses_hazardous_drugs' => fake()->boolean(30),
            'has_operating_rooms' => fake()->boolean(20),
            'cleaning_provider' => fake()->company(),
            'cleaning_frequency' => fake()->randomElement(['Daily', 'Weekly', 'Bi-weekly']),
            'offers_hep_b_vaccination' => fake()->boolean(70),
            'offers_tb_screening' => fake()->boolean(70),
            'employees_per_year' => (string) fake()->numberBetween(5, 200),
            'waste_hauler' => fake()->company().' Waste Services',
            'sort_order' => 0,
        ];
    }
}
