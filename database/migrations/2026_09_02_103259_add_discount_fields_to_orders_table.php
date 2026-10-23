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
            $table->foreignId('discount_code_id')->nullable()->after('package_id')->constrained()->nullOnDelete();
            $table->string('discount_code')->nullable()->after('discount_code_id');
            $table->unsignedTinyInteger('discount_percentage')->nullable()->after('discount_code');
            $table->decimal('original_price', 10, 2)->nullable()->after('amount_paid');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('original_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_code_id');
            $table->dropColumn(['discount_code', 'discount_percentage', 'original_price', 'discount_amount']);
        });
    }
};
