<?php

namespace App\Models;

use Database\Factories\AiUsageLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'purpose', 'success', 'model', 'prompt_tokens', 'completion_tokens', 'total_tokens',
    'intake_upload_id', 'message',
])]
class AiUsageLog extends Model
{
    /** @use HasFactory<AiUsageLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    public function intakeUpload(): BelongsTo
    {
        return $this->belongsTo(IntakeUpload::class);
    }

    public static function record(
        string $purpose,
        bool $success,
        ?string $model = null,
        ?int $promptTokens = null,
        ?int $completionTokens = null,
        ?int $totalTokens = null,
        ?IntakeUpload $intakeUpload = null,
        ?string $message = null,
    ): self {
        return self::create([
            'purpose' => $purpose,
            'success' => $success,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'intake_upload_id' => $intakeUpload?->id,
            'message' => $message,
        ]);
    }
}
