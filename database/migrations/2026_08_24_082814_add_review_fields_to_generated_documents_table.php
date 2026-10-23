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
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('generated_at');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            // 'ai_generated' | 'custom' — which file gets delivered to the client
            $table->string('delivery_source')->default('ai_generated')->after('reviewed_by');
            $table->string('custom_storage_path')->nullable()->after('delivery_source');
            $table->string('custom_original_filename')->nullable()->after('custom_storage_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'delivery_source', 'custom_storage_path', 'custom_original_filename']);
        });
    }
};
