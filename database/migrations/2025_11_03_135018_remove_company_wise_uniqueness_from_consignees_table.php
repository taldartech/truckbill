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
        $driver = Schema::getConnection()->getDriverName();

        // Disable FK checks per driver
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        // Drop the unique index if it exists
        try {
            Schema::table('consignees', function (Blueprint $table) {
                $table->dropUnique('consignees_company_pan_unique');
            });
        } catch (\Throwable $e) {
            // Index may already be missing; ignore
        }

        // Modify the column
        Schema::table('consignees', function (Blueprint $table) {
            $table->string('pan_number')->nullable()->change();
        });

        // Re-enable FK checks
        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            // Recreate the unique constraint if rolled back
            $table->unique(['company_id', 'pan_number'], 'consignees_company_pan_unique');
        });
    }
};
