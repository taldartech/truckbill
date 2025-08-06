<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            // Add country_id as nullable first
            $table->unsignedBigInteger('country_id')->nullable()->after('name');
        });

        // Update existing cities with country_id based on their state's country
        DB::statement('
            UPDATE cities 
            SET country_id = (
                SELECT states.country_id 
                FROM states 
                WHERE states.id = cities.state_id
            )
            WHERE cities.state_id IS NOT NULL
        ');

        // Set a default country for cities without state (if any)
        $defaultCountry = DB::table('countries')->first();
        if ($defaultCountry) {
            DB::table('cities')
                ->whereNull('country_id')
                ->update(['country_id' => $defaultCountry->id]);
        }

        Schema::table('cities', function (Blueprint $table) {
            // Make country_id non-nullable and add foreign key constraint
            $table->unsignedBigInteger('country_id')->nullable(false)->change();
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropColumn('country_id');
        });
    }
};

