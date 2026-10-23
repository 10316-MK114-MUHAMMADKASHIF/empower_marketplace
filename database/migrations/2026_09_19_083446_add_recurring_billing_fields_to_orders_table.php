<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // tokenize/detokenize never return card expiry — captured and stored by us at signup,
            // re-checked before every charge attempt.
            $table->unsignedTinyInteger('card_expiry_month')->nullable()->after('clover_card_token');
            $table->unsignedSmallInteger('card_expiry_year')->nullable()->after('card_expiry_month');
            $table->string('card_last_four', 4)->nullable()->after('card_expiry_year');
            $table->string('mtbc_reference_number')->nullable()->after('card_last_four');

            $table->date('next_bill_date')->nullable()->after('mtbc_reference_number');
            $table->unsignedTinyInteger('renewal_attempts')->default(0)->after('next_bill_date');
            $table->timestamp('last_renewal_attempt_at')->nullable()->after('renewal_attempts');
            $table->string('last_renewal_error')->nullable()->after('last_renewal_attempt_at');

            $table->index(['payment_status', 'next_bill_date']);
            $table->index(['payment_status', 'trial_ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status', 'next_bill_date']);
            $table->dropIndex(['payment_status', 'trial_ends_at']);

            $table->dropColumn([
                'card_expiry_month', 'card_expiry_year', 'card_last_four', 'mtbc_reference_number',
                'next_bill_date', 'renewal_attempts', 'last_renewal_attempt_at', 'last_renewal_error',
            ]);
        });
    }
};
