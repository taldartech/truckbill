<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign keys with raw SQL to avoid errors if they do not exist
        try { \DB::statement('ALTER TABLE customers DROP FOREIGN KEY customers_country_id_foreign'); } catch (\Exception $e) {}
        try { \DB::statement('ALTER TABLE customers DROP FOREIGN KEY customers_state_id_foreign'); } catch (\Exception $e) {}
        try { \DB::statement('ALTER TABLE customers DROP FOREIGN KEY customers_city_id_foreign'); } catch (\Exception $e) {}
        // Drop columns if they exist
        if (Schema::hasColumn('customers', 'country_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('country_id');
            });
        }
        if (Schema::hasColumn('customers', 'state_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('state_id');
            });
        }
        if (Schema::hasColumn('customers', 'city_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('city_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // No down migration, as columns will be re-created by the other migration
        });
    }
};
