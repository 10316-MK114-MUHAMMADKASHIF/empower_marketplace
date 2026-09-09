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
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('upload_type')->unique(); // IntakeUploadType enum value
            $table->string('title');
            $table->text('description');
            $table->json('tiers')->nullable(); // null = all tiers; else array of PackageTier values
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->string('questionnaire_file_path'); // public disk — the client-downloadable .docx
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionnaires');
    }
};
