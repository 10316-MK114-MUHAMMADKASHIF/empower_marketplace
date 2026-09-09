<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\IntakeUploadType;
use Database\Factories\QuestionnaireFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'upload_type', 'title', 'description', 'tiers', 'is_required', 'is_visible', 'questionnaire_file_path', 'schema',
])]
class Questionnaire extends Model
{
    /** @use HasFactory<QuestionnaireFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'upload_type' => IntakeUploadType::class,
            'tiers' => 'array',
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'schema' => 'array',
        ];
    }

    /** The compliance manual this questionnaire's answers feed, if the code-level link exists. */
    public function documentType(): ?DocumentType
    {
        return DocumentType::forQuestionnaireType($this->upload_type);
    }
}
