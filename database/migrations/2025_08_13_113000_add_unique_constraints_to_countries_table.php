<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('countries', function (Blueprint $table) {
                $table->unique('name', 'countries_name_unique');
            });
        } catch (\Exception $e) {
            // Unique constraint on name already exists
        }

        try {
            Schema::table('countries', function (Blueprint $table) {
                $table->unique('code', 'countries_code_unique');
            });
        } catch (\Exception $e) {
            // Unique constraint on code already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropUnique('countries_name_unique');
            });
        } catch (\Exception $e) {
            // Unique constraint doesn't exist
        }

        try {
            Schema::table('countries', function (Blueprint $table) {
                $table->dropUnique('countries_code_unique');
            });
        } catch (\Exception $e) {
            // Unique constraint doesn't exist
        }
    }
};
