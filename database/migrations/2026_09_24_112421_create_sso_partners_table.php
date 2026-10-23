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
        Schema::create('sso_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            // sha256 of the raw API key — the raw value is shown once at creation and never stored.
            $table->string('api_key_hash', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sso_partners');
    }
};
