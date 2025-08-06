<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, handle duplicate PAN numbers by appending a suffix to duplicates
        $duplicates = DB::select("
            SELECT pan_number, COUNT(*) as count 
            FROM transporters 
            WHERE pan_number IS NOT NULL AND pan_number != '' 
            GROUP BY pan_number 
            HAVING count > 1
        ");
        
        foreach ($duplicates as $duplicate) {
            $transporters = DB::table('transporters')
                ->where('pan_number', $duplicate->pan_number)
                ->orderBy('id')
                ->get();
                
            // Keep the first one as is, modify the rest
            foreach ($transporters->skip(1) as $index => $transporter) {
                DB::table('transporters')
                    ->where('id', $transporter->id)
                    ->update(['pan_number' => $duplicate->pan_number . '_' . ($index + 2)]);
            }
        }
        
        Schema::table('transporters', function (Blueprint $table) {
            $table->unique('pan_number');
        });
    }

    public function down(): void
    {
        Schema::table('transporters', function (Blueprint $table) {
            // Check if unique constraint exists before dropping
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('transporters');
            if (isset($indexes['transporters_pan_number_unique'])) {
                $table->dropUnique(['pan_number']);
            }
        });
    }
};

