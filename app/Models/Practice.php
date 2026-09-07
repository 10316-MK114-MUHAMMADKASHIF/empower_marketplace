<?php

namespace App\Models;

use Database\Factories\PracticeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'name', 'logo_path', 'address',
    'npi_number', 'specialty', 'billable_providers_count',
    'is_profile_locked', 'locked_at',
])]
class Practice extends Model
{
    /** @use HasFactory<PracticeFactory> */
    use HasFactory;

    public const SPECIALTIES = [
        'General Practice', 'Dermatology', 'Cardiology', 'Behavioral Health',
        'Pediatrics', 'Orthopedics', 'Dental', 'Other',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_profile_locked' => 'boolean',
            'locked_at' => 'datetime',
            'billable_providers_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function oshaLocations(): HasMany
    {
        return $this->hasMany(OshaLocation::class)->orderBy('sort_order');
    }

    protected static function booted(): void
    {
        static::updated(function (Practice $practice) {
            if (! $practice->getOriginal('is_profile_locked')) {
                return;
            }

            if (! $practice->wasChanged(['name', 'logo_path', 'address', 'npi_number', 'specialty', 'billable_providers_count'])) {
                return;
            }

            $orderIds = $practice->user->orders()->pluck('id');

            GeneratedDocument::whereIn('order_id', $orderIds)
                ->where('is_stale', false)
                ->update(['is_stale' => true, 'stale_reason' => 'practice_profile_updated']);
        });
    }
}
