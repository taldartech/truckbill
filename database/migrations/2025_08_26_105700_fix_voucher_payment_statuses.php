<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixVoucherPaymentStatuses extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // First, let's check what values actually exist in the database
        $statuses = DB::table('voucher_payments')
            ->select('payment_status')
            ->distinct()
            ->pluck('payment_status');
            
        // If there are records with 'in progress' (with space), update them to 'in-progress'
        if ($statuses->contains('in progress')) {
            // Temporarily modify the column to accept any string value
            DB::statement("ALTER TABLE voucher_payments MODIFY payment_status VARCHAR(20) NOT NULL DEFAULT 'pending'");
            
            // Update the values
            DB::table('voucher_payments')
                ->where('payment_status', 'in progress')
                ->update(['payment_status' => 'in-progress']);
                
            // Change the column back to ENUM with correct values
            DB::statement("ALTER TABLE voucher_payments MODIFY payment_status ENUM('pending', 'in-progress', 'paid') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // This is a one-way migration to fix data integrity
        // No need to implement down() as we don't want to go back to invalid data
    }
}
