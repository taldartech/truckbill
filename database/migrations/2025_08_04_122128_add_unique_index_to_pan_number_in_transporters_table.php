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
            // Try to drop the unique constraint if it exists
            try {
                $table->dropUnique(['pan_number']);
            } catch (\Exception $e) {
                // Ignore if constraint doesn't exist
            }
        });
    }
};

