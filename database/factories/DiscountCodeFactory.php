<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\DiscountCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountCode>
 */
class DiscountCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'type' => DiscountType::Percentage,
            'percentage' => 20,
            'trial_days' => null,
            'starts_at' => null,
            'expires_at' => null,
            'max_uses' => null,
            'used_count' => 0,
            'is_active' => true,
        ];
    }

    public function freeTrial(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DiscountType::FreeTrial,
            'percentage' => null,
            'trial_days' => $days,
        ]);
    }
}
