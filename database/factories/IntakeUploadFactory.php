<?php

namespace Database\Factories;

use App\Enums\AiExtractionStatus;
use App\Enums\IntakeUploadType;
use App\Models\IntakeSubmission;
use App\Models\IntakeUpload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntakeUpload>
 */
class IntakeUploadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intake_submission_id' => IntakeSubmission::factory(),
            'upload_type' => fake()->randomElement(IntakeUploadType::cases()),
            'original_filename' => fake()->word().'.pdf',
            'storage_path' => 'intake/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(50000, 5000000),
            'ai_extraction_status' => AiExtractionStatus::Pending,
            'ai_extracted_data' => null,
            'ai_error_message' => null,
            'processed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_extraction_status' => AiExtractionStatus::Completed,
            'ai_extracted_data' => ['extracted' => true],
            'processed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_extraction_status' => AiExtractionStatus::Failed,
            'ai_error_message' => 'Claude API timeout.',
            'processed_at' => now(),
        ]);
    }
}
