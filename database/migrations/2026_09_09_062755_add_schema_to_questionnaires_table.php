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
        Schema::table('questionnaires', function (Blueprint $table) {
            // {prefix, count, extra_fields: {key: description}} — the AI-extraction schema,
            // replacing ManualQuestionSets' hardcoded per-type match arm.
            $table->json('schema')->nullable()->after('questionnaire_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropColumn('schema');
        });
    }
};
