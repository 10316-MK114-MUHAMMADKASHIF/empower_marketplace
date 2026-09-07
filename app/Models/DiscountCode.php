<?php

namespace App\Models;

use App\Enums\DiscountType;
use Database\Factories\DiscountCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code', 'type', 'percentage', 'trial_days', 'starts_at', 'expires_at',
    'max_uses', 'used_count', 'is_active',
])]
class DiscountCode extends Model
{
    /** @use HasFactory<DiscountCodeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'percentage' => 'integer',
            'trial_days' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasReachedUsageLimit(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    public function isCurrentlyValid(): bool
    {
        return $this->is_active
            && ! $this->isExpired()
            && ! $this->hasReachedUsageLimit()
            && ($this->starts_at === null || ! $this->starts_at->isFuture());
    }
}
