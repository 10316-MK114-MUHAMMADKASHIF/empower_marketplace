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
        Schema::create('intake_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intake_submission_id')->constrained()->cascadeOnDelete();
            // practice_intake | osha_questionnaire
            $table->string('upload_type');
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('ai_extraction_status')->default('pending');
            $table->json('ai_extracted_data')->nullable();
            $table->text('ai_error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intake_uploads');
    }
};
