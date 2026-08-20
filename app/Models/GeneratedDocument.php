<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Database\Factories\GeneratedDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id', 'osha_location_id', 'document_type', 'status',
    'pdf_storage_path', 'docx_storage_path', 'pdf_owner_password',
    'is_stale', 'stale_reason', 'failure_reason', 'generated_at',
])]
class GeneratedDocument extends Model
{
    /** @use HasFactory<GeneratedDocumentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'is_stale' => 'boolean',
            'generated_at' => 'datetime',
            // owner password is encrypted at rest
            'pdf_owner_password' => 'encrypted',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function oshaLocation(): BelongsTo
    {
        return $this->belongsTo(OshaLocation::class);
    }

    public function isReady(): bool
    {
        return $this->status === DocumentStatus::Completed && ! $this->is_stale;
    }

    public function markStale(string $reason): void
    {
        $this->update(['is_stale' => true, 'stale_reason' => $reason]);
    }
}
