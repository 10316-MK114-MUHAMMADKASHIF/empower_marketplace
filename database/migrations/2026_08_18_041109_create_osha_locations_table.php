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
        Schema::create('osha_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('osha_officer')->nullable();
            $table->string('safety_coordinator')->nullable();
            $table->boolean('uses_hazardous_drugs')->default(false);
            $table->boolean('has_operating_rooms')->default(false);
            $table->string('cleaning_provider')->nullable();
            $table->string('cleaning_frequency')->nullable();
            $table->boolean('offers_hep_b_vaccination')->default(true);
            $table->boolean('offers_tb_screening')->default(false);
            $table->string('employees_per_year')->nullable();
            $table->string('waste_hauler')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('osha_locations');
    }
};
