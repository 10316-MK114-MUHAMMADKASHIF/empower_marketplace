<?php

namespace App\Models;

use Database\Factories\OshaLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'practice_id', 'name', 'address',
    'osha_officer', 'safety_coordinator',
    'uses_hazardous_drugs', 'has_operating_rooms',
    'cleaning_provider', 'cleaning_frequency',
    'offers_hep_b_vaccination', 'offers_tb_screening',
    'employees_per_year', 'waste_hauler', 'sort_order',
])]
class OshaLocation extends Model
{
    /** @use HasFactory<OshaLocationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uses_hazardous_drugs' => 'boolean',
            'has_operating_rooms' => 'boolean',
            'offers_hep_b_vaccination' => 'boolean',
            'offers_tb_screening' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    protected static function booted(): void
    {
        static::updated(function (OshaLocation $location) {
            if (! $location->wasChanged([
                'name', 'address', 'osha_officer', 'safety_coordinator',
                'uses_hazardous_drugs', 'has_operating_rooms',
                'cleaning_provider', 'cleaning_frequency',
                'offers_hep_b_vaccination', 'offers_tb_screening',
                'employees_per_year', 'waste_hauler',
            ])) {
                return;
            }

            $location->generatedDocuments()
                ->where('is_stale', false)
                ->update(['is_stale' => true, 'stale_reason' => 'osha_location_updated']);
        });
    }
}
