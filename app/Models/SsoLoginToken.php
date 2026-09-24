<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'sso_partner_id', 'token_hash', 'expires_at', 'requested_ip'])]
class SsoLoginToken extends Model
{
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ssoPartner(): BelongsTo
    {
        return $this->belongsTo(SsoPartner::class);
    }

    #[Scope]
    protected function valid(Builder $query): void
    {
        $query->whereNull('consumed_at')->where('expires_at', '>', now());
    }
}
