<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixVoucherPaymentStatusesFinal extends Migration
{
    public function up()
    {
        // First, modify the ENUM to temporarily allow the new status
        DB::statement("ALTER TABLE `voucher_payments` 
            MODIFY COLUMN `payment_status` 
            ENUM('pending', 'in-progress', 'in progress', 'paid') 
            NOT NULL DEFAULT 'pending'");

        // Update 'in progress' (with space) to 'in-progress' (with hyphen)
        DB::table('voucher_payments')
            ->where('payment_status', 'in progress')
            ->update(['payment_status' => 'in-progress']);

        // Now remove the temporary status from the ENUM
        DB::statement("ALTER TABLE `voucher_payments` 
            MODIFY COLUMN `payment_status` 
            ENUM('pending', 'in-progress', 'paid') 
            NOT NULL DEFAULT 'pending'");

        // Handle any other status values
        DB::table('voucher_payments')
            ->whereNotIn('payment_status', ['pending', 'in-progress', 'paid'])
            ->update(['payment_status' => 'pending']);
    }

    public function down()
    {
        // Reverse the changes if needed
        DB::statement("ALTER TABLE `voucher_payments` 
            MODIFY COLUMN `payment_status` 
            ENUM('pending', 'in-progress', 'in progress', 'paid') 
            NOT NULL DEFAULT 'pending'");

        DB::table('voucher_payments')
            ->where('payment_status', 'in-progress')
            ->update(['payment_status' => 'in progress']);

        DB::statement("ALTER TABLE `voucher_payments` 
            MODIFY COLUMN `payment_status` 
            ENUM('pending', 'in progress', 'paid') 
            NOT NULL DEFAULT 'pending'");
    }
}
