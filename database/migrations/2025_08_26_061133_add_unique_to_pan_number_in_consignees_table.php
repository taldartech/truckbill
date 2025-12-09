<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Remove duplicate PAN numbers first (keep the first occurrence)
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('
                DELETE c1 FROM consignees c1
                INNER JOIN consignees c2 
                WHERE c1.id > c2.id AND c1.pan_number = c2.pan_number AND c1.pan_number IS NOT NULL
            ');
        } else {
            $duplicateIds = DB::table('consignees')
                ->select('id', 'pan_number')
                ->whereNotNull('pan_number')
                ->orderBy('pan_number')
                ->orderBy('id')
                ->get()
                ->groupBy('pan_number')
                ->flatMap(fn ($rows) => $rows->skip(1)->pluck('id'));

            if ($duplicateIds->isNotEmpty()) {
                DB::table('consignees')->whereIn('id', $duplicateIds->all())->delete();
            }
        }

        // Step 2: Add unique index
        Schema::table('consignees', function (Blueprint $table) {
            $table->unique('pan_number');
        });
    }

    public function down(): void
    {
        Schema::table('consignees', function (Blueprint $table) {
            $table->dropUnique(['pan_number']);
        });
    }
};
