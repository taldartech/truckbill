<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // COUNTRY_ID
            if (!Schema::hasColumn('customers', 'country_id')) {
                $table->foreignId('country_id')->nullable()->constrained('countries');
            }
            // STATE_ID
            if (!Schema::hasColumn('customers', 'state_id')) {
                $table->foreignId('state_id')->nullable()->constrained('states');
            }
            // CITY_ID
            if (!Schema::hasColumn('customers', 'city_id')) {
                $table->foreignId('city_id')->nullable()->constrained('cities');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn(['country_id', 'state_id', 'city_id']);
        });
    }
};
