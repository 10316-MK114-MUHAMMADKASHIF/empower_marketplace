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
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('osha_location_id')->nullable()->constrained()->nullOnDelete();
            // value from DocumentType enum
            $table->string('document_type');
            $table->string('status')->default('pending');
            $table->string('pdf_storage_path')->nullable();
            $table->string('docx_storage_path')->nullable();
            // AES-256 owner password stored encrypted via Laravel's encrypt()
            $table->text('pdf_owner_password')->nullable();
            $table->boolean('is_stale')->default(false);
            $table->string('stale_reason')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_documents');
    }
};
