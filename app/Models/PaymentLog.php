<?php

namespace App\Models;

use Database\Factories\PaymentLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'guest_email', 'package_id', 'order_id',
    'amount', 'success', 'transaction_id', 'message', 'billing_address',
])]
class PaymentLog extends Model
{
    /** @use HasFactory<PaymentLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'billing_address' => 'array',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @param  array<string, mixed>  $billingAddress
     */
    public static function record(
        bool $success,
        float $amount,
        ?User $user = null,
        ?string $guestEmail = null,
        ?Package $package = null,
        ?Order $order = null,
        ?string $transactionId = null,
        ?string $message = null,
        array $billingAddress = [],
    ): self {
        return self::create([
            'user_id' => $user?->id,
            'guest_email' => $guestEmail,
            'package_id' => $package?->id,
            'order_id' => $order?->id,
            'amount' => $amount,
            'success' => $success,
            'transaction_id' => $transactionId,
            'message' => $message,
            'billing_address' => $billingAddress ?: null,
        ]);
    }
}
