<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id', 'package_id', 'checkout_batch_id', 'status', 'payment_status', 'billing_cycle',
    'payment_reference', 'billing_address', 'amount_paid', 'paid_at', 'completed_at',
    'cancelled_at', 'notes', 'terms_accepted_at', 'terms_accepted_ip',
    'discount_code_id', 'discount_code', 'discount_percentage', 'original_price', 'discount_amount',
    'trial_ends_at', 'trial_confirmed_at', 'trial_reminder_sent_at', 'clover_card_token',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Every payment_status value that means "checkout is complete, proceed to onboarding" — not
     * strictly "money was collected" (Trialing hasn't been charged yet, SimulatedPaid never
     * charges a real card either; both still grant full portal access like a real payment does).
     */
    public const PAID_STATUSES = [PaymentStatus::SimulatedPaid, PaymentStatus::Paid, PaymentStatus::Trialing];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'billing_address' => 'array',
            'amount_paid' => 'decimal:2',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'original_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'discount_percentage' => 'integer',
            'trial_ends_at' => 'datetime',
            'trial_confirmed_at' => 'datetime',
            'trial_reminder_sent_at' => 'datetime',
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

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    public function intakeSubmission(): HasOne
    {
        return $this->hasOne(IntakeSubmission::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->payment_status, self::PAID_STATUSES, true);
    }

    /**
     * A free trial that ended without converting to a paid subscription — either the client
     * explicitly cancelled or never responded before trial_ends_at. AI document generation
     * (new intake submissions, regenerating a document) is blocked for these; documents already
     * generated/approved during the trial remain downloadable.
     */
    public function blockedFromAiGeneration(): bool
    {
        return $this->status === OrderStatus::Cancelled && $this->payment_status === PaymentStatus::Trialing;
    }

    /**
     * Delete every file this order owns (generated documents, intake uploads) ahead of a
     * DB-level cascading delete, which removes the rows but never touches storage.
     */
    public function deleteCascadingFiles(): void
    {
        foreach ($this->generatedDocuments as $document) {
            foreach ([$document->pdf_storage_path, $document->docx_storage_path, $document->custom_storage_path] as $path) {
                if ($path) {
                    Storage::disk('local')->delete($path);
                }
            }
        }

        foreach ($this->intakeSubmission?->intakeUploads ?? [] as $upload) {
            if ($upload->storage_path) {
                Storage::disk('local')->delete($upload->storage_path);
            }
        }
    }
}
