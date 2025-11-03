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
        // --- Disable FK checks globally for this session ---
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            DB::statement('ALTER TABLE consignees DROP INDEX consignees_company_pan_unique');
        } catch (\Throwable $e) {
            // Ignore if the index doesn't exist or was already removed
        }

        // --- Modify the column ---
        Schema::table('consignees', function (Blueprint $table) {
            $table->string('pan_number')->nullable()->change();
        });

        // --- Re-enable FK checks ---
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
