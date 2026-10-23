<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->unsignedSmallInteger('billable_providers_count')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->unsignedSmallInteger('billable_providers_count')->default(1)->change();
        });
    }
};