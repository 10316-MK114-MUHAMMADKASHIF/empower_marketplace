<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => null,
            'subject_type' => null,
            'subject_id' => null,
            'event_type' => fake()->randomElement([
                'order.created', 'order.paid', 'intake.submitted',
                'intake.approved', 'document.generated', 'document.downloaded',
            ]),
            'description' => fake()->sentence(),
            'metadata' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
