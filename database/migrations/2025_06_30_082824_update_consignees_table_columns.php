<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            // Check and drop old name columns
            if (Schema::hasColumn('consignees', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('consignees', 'state')) {
                $table->dropColumn('state');
            }
            if (Schema::hasColumn('consignees', 'country')) {
                $table->dropColumn('country');
            }
            
            // Add location ID columns if they don't exist
            if (!Schema::hasColumn('consignees', 'country_id')) {
                $table->foreignId('country_id')->nullable()->constrained('countries');
            }
            if (!Schema::hasColumn('consignees', 'state_id')) {
                $table->foreignId('state_id')->nullable()->constrained('states');
            }
            if (!Schema::hasColumn('consignees', 'city_id')) {
                $table->foreignId('city_id')->nullable()->constrained('cities');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            // Drop foreign key constraints and columns if they exist
            if (Schema::hasColumn('consignees', 'country_id')) {
                $table->dropForeign(['country_id']);
                $table->dropColumn('country_id');
            }
            if (Schema::hasColumn('consignees', 'state_id')) {
                $table->dropForeign(['state_id']);
                $table->dropColumn('state_id');
            }
            if (Schema::hasColumn('consignees', 'city_id')) {
                $table->dropForeign(['city_id']);
                $table->dropColumn('city_id');
            }
            
            // Add back old name columns if they don't exist
            if (!Schema::hasColumn('consignees', 'city')) {
                $table->string('city')->nullable();
            }
            if (!Schema::hasColumn('consignees', 'state')) {
                $table->string('state')->nullable();
            }
            if (!Schema::hasColumn('consignees', 'country')) {
                $table->string('country')->nullable();
            }
        });
    }
};
