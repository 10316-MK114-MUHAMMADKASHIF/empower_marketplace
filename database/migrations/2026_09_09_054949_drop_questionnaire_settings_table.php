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
        // Superseded by the questionnaires table — is_visible/is_required are now first-class
        // columns there instead of a separate override table.
        Schema::dropIfExists('questionnaire_settings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('questionnaire_settings', function (Blueprint $table) {
            $table->id();
            $table->string('upload_type')->unique();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_required')->nullable();
            $table->timestamps();
        });
    }
};
