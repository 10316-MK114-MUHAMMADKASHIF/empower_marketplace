<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'package_id' => Package::factory(),
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::SimulatedPaid,
            'billing_cycle' => 'annual',
            'payment_reference' => 'SIM-'.strtoupper(fake()->lexify('????????')),
            'amount_paid' => fake()->randomElement([1490.00, 2490.00, 3990.00]),
            'paid_at' => now(),
            'completed_at' => null,
            'cancelled_at' => null,
            'notes' => null,
        ];
    }

    public function pendingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PendingPayment,
            'payment_status' => PaymentStatus::Pending,
            'paid_at' => null,
            'amount_paid' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Approved,
        ]);
    }

    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'payment_status' => PaymentStatus::Trialing,
            'payment_reference' => null,
            'amount_paid' => 0,
            'paid_at' => now(),
            'trial_ends_at' => now()->addDays(30),
            'clover_card_token' => 'tok_'.fake()->lexify('????????????'),
            'card_expiry_month' => 12,
            'card_expiry_year' => (int) now()->addYears(3)->format('Y'),
            'card_last_four' => fake()->numerify('####'),
            'mtbc_reference_number' => fake()->numerify('####################'),
        ]);
    }

    /** A trial that ended (or was rejected) without converting to paid. */
    public function trialCancelled(): static
    {
        return $this->trialing()->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'trial_ends_at' => now()->subDay(),
        ]);
    }

    /** A trial that converted to a real paying subscription, now waiting on its next annual charge. */
    public function convertedFromTrial(): static
    {
        return $this->trialing()->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Paid,
            'payment_reference' => 'SIM-'.strtoupper(fake()->lexify('????????')),
            'amount_paid' => fake()->randomElement([1490.00, 2490.00, 3990.00]),
            'trial_confirmed_at' => now(),
            'next_bill_date' => now()->addYear(),
        ]);
    }

    /** A converted subscription whose most recent renewal attempt(s) failed. */
    public function pastDue(int $attempts = 1): static
    {
        return $this->convertedFromTrial()->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::PastDue,
            'next_bill_date' => now()->subDays(1),
            'renewal_attempts' => $attempts,
            'last_renewal_attempt_at' => now(),
            'last_renewal_error' => 'Your card was declined. Please check your details and try again.',
        ]);
    }
}
