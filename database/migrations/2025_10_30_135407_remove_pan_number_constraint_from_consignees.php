<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        try {
            DB::statement('ALTER TABLE `consignees` DROP INDEX `consignees_company_pan_unique`');
            \Log::info('Successfully dropped consignees_company_pan_unique constraint');
        } catch (\Exception $e) {
            \Log::error('Error dropping PAN constraint: ' . $e->getMessage());
            throw $e;
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\Schema::table('consignees', function ($table) {
            $table->unique(['company_id', 'pan_number'], 'consignees_company_pan_unique');
        });
    }
};
