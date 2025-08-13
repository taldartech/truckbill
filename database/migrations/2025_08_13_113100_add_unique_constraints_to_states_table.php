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
        try {
            Schema::table('states', function (Blueprint $table) {
                $table->unique(['name', 'country_id'], 'states_name_country_unique');
            });
        } catch (\Exception $e) {
            // Unique constraint already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('states', function (Blueprint $table) {
                $table->dropUnique('states_name_country_unique');
            });
        } catch (\Exception $e) {
            // Unique constraint doesn't exist
        }
    }
};
