<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'api_key_hash', 'is_active'])]
class SsoPartner extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function loginTokens(): HasMany
    {
        return $this->hasMany(SsoLoginToken::class);
    }
}
