<?php

namespace Database\Factories;

use App\Enums\IntakeUploadType;
use App\Models\Questionnaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Questionnaire>
 */
class QuestionnaireFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'upload_type' => IntakeUploadType::ComplianceEthicsQuestionnaire,
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'tiers' => null,
            'is_required' => false,
            'is_visible' => true,
            'questionnaire_file_path' => 'Manuals/Questionnaires/'.fake()->lexify('????????').'.docx',
        ];
    }
}
