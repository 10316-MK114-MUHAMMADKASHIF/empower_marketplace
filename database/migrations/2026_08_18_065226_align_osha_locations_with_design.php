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

        $columnsToDrop = array_filter(
            ['city', 'state', 'zip_code'],
            fn (string $column) => Schema::hasColumn('osha_locations', $column)
        );

        if ($columnsToDrop !== []) {
            Schema::table('osha_locations', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        DB::statement('ALTER TABLE osha_locations MODIFY employees_per_year VARCHAR(255) NULL');
        DB::statement('ALTER TABLE osha_locations ALTER COLUMN offers_hep_b_vaccination SET DEFAULT 1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('osha_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('osha_locations', 'city')) {
                $table->string('city')->nullable();
            }
            if (! Schema::hasColumn('osha_locations', 'state')) {
                $table->string('state', 100)->nullable();
            }
            if (! Schema::hasColumn('osha_locations', 'zip_code')) {
                $table->string('zip_code', 20)->nullable();
            }
        });

        DB::statement('ALTER TABLE osha_locations MODIFY employees_per_year SMALLINT UNSIGNED NULL');
        DB::statement('ALTER TABLE osha_locations ALTER COLUMN offers_hep_b_vaccination SET DEFAULT 0');
    }
};
