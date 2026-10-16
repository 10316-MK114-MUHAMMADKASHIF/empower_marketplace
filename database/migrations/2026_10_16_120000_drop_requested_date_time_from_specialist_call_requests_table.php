<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialist_call_requests', function (Blueprint $table) {
            $table->dropColumn(['requested_date', 'requested_time']);
        });
    }

    public function down(): void
    {
        Schema::table('specialist_call_requests', function (Blueprint $table) {
            $table->date('requested_date')->nullable()->after('order_id');
            $table->string('requested_time')->nullable()->after('requested_date');
        });
    }
};
