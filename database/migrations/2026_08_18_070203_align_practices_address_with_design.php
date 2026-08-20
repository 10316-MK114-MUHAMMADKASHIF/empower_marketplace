<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('practices', 'city')) {
            DB::statement("UPDATE practices SET address = NULLIF(TRIM(BOTH ', ' FROM CONCAT_WS(', ', address, city, state, zip_code)), '')");
        }

        $columnsToDrop = array_filter(
            ['city', 'state', 'zip_code'],
            fn (string $column) => Schema::hasColumn('practices', $column)
        );

        if ($columnsToDrop !== []) {
            Schema::table('practices', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('practices', function (Blueprint $table) {
            if (! Schema::hasColumn('practices', 'city')) {
                $table->string('city')->nullable();
            }
            if (! Schema::hasColumn('practices', 'state')) {
                $table->string('state', 100)->nullable();
            }
            if (! Schema::hasColumn('practices', 'zip_code')) {
                $table->string('zip_code', 20)->nullable();
            }
        });
    }
};
