<?php

namespace App\Models;

use App\Enums\IntakeSubmissionStatus;
use Database\Factories\IntakeSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id', 'status', 'handbook_answers', 'admin_documents',
    'reviewer_notes', 'reviewed_by', 'reviewed_at', 'submitted_at',
])]
class IntakeSubmission extends Model
{
    /** @use HasFactory<IntakeSubmissionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IntakeSubmissionStatus::class,
            'handbook_answers' => 'array',
            'admin_documents' => 'array',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function intakeUploads(): HasMany
    {
        return $this->hasMany(IntakeUpload::class);
    }

    public function allUploadsProcessed(): bool
    {
        return $this->intakeUploads()
            ->whereNotIn('ai_extraction_status', ['completed', 'failed'])
            ->doesntExist();
    }
}
