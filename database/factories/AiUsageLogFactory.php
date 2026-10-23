<?php

namespace Database\Factories;

use App\Models\AiUsageLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiUsageLog>
 */
class AiUsageLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purpose' => fake()->randomElement([
                'intake_extraction_vision', 'intake_extraction_docx', 'intake_verification',
                'document_polish_vision', 'document_polish_docx', 'schema_field_description',
            ]),
            'success' => true,
            'model' => 'gpt-4o',
            'prompt_tokens' => fake()->numberBetween(500, 4000),
            'completion_tokens' => fake()->numberBetween(100, 1500),
            'total_tokens' => fake()->numberBetween(600, 5500),
            'intake_upload_id' => null,
            'message' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => false,
            'prompt_tokens' => null,
            'completion_tokens' => null,
            'total_tokens' => null,
            'message' => 'OpenAI API error: 500',
        ]);
    }
}
