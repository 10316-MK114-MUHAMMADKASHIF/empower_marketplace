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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            // Identifies which of the OpenAI call sites this hit came from (e.g.
            // "intake_extraction_vision", "intake_verification", "schema_field_description").
            $table->string('purpose');
            $table->boolean('success');
            $table->string('model')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            // Nullable — not every call (e.g. schema generation during a manual template upload)
            // is tied to an IntakeUpload.
            $table->foreignId('intake_upload_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
