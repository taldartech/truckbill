<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasCountry = Schema::hasColumn('customers', 'country_id');
        $hasState = Schema::hasColumn('customers', 'state_id');
        $hasCity = Schema::hasColumn('customers', 'city_id');

        if (! $hasCountry && ! $hasState && ! $hasCity) {
            return;
        }

        // Drop the foreign keys and columns in a driver-safe way (works on SQLite)
        Schema::disableForeignKeyConstraints();

        Schema::table('customers', function (Blueprint $table) use ($hasCountry, $hasState, $hasCity) {
            if ($hasCountry) {
                $table->dropConstrainedForeignId('country_id');
            }

            if ($hasState) {
                $table->dropConstrainedForeignId('state_id');
            }

            if ($hasCity) {
                $table->dropConstrainedForeignId('city_id');
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Columns intentionally left removed; the prior migration can recreate them if needed.
    }
};
