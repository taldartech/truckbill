<?php

namespace App\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('consignees', function (Blueprint $table) {
            // Drop unique constraint safely
            try {
                $table->dropUnique('consignees_company_pan_unique');
            } catch (\Exception $e) {
                // Ignore if already dropped or doesn't exist
            }

            // Ensure pan_number is a normal column
            $table->string('pan_number')->nullable()->change();
        });

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            // Recreate the unique constraint if rolled back
            $table->unique(['company_id', 'pan_number'], 'consignees_company_pan_unique');
        });
    }
};
