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
            $table->timestamp('trial_ends_at')->nullable()->after('paid_at');
            $table->timestamp('trial_confirmed_at')->nullable()->after('trial_ends_at');
            $table->timestamp('trial_reminder_sent_at')->nullable()->after('trial_confirmed_at');
            $table->string('clover_card_token')->nullable()->after('trial_reminder_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'trial_ends_at', 'trial_confirmed_at', 'trial_reminder_sent_at',
                'clover_card_token',
            ]);
        });
    }
};
