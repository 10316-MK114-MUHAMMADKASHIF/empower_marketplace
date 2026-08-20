<?php

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'user_id', 'order_id', 'subject_type', 'subject_id',
    'event_type', 'description', 'metadata', 'ip_address', 'user_agent',
])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        string $eventType,
        string $description,
        ?User $user = null,
        ?Order $order = null,
        ?Model $subject = null,
        array $metadata = [],
    ): self {
        return self::create([
            'user_id' => $user?->id,
            'order_id' => $order?->id,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
