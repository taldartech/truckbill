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
            Schema::table('cities', function (Blueprint $table) {
                $table->unique(['name', 'state_id'], 'cities_name_state_unique');
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
            Schema::table('cities', function (Blueprint $table) {
                $table->dropUnique('cities_name_state_unique');
            });
        } catch (\Exception $e) {
            // Unique constraint doesn't exist
        }
    }
};
