<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\PaymentLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentLog>
 */
class PaymentLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'guest_email' => null,
            'package_id' => Package::factory(),
            'order_id' => null,
            'amount' => fake()->randomElement([1490.00, 2490.00, 3990.00]),
            'success' => true,
            'transaction_id' => strtoupper(fake()->lexify('TXN????????')),
            'message' => null,
            'billing_address' => [
                'name' => fake()->name(),
                'address1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'zip' => fake()->postcode(),
            ],
        ];
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => false,
            'transaction_id' => null,
            'message' => 'Your card was declined. Please check your details and try again.',
            'order_id' => null,
        ]);
    }
}
