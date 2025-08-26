<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateVoucherPaymentStatusesFix extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Update 'in-progress' to 'in progress' with proper string quoting
        DB::statement("UPDATE `voucher_payments` SET `payment_status` = 'in progress' WHERE `payment_status` = 'in-progress'");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Revert back to 'in-progress' if needed
        DB::statement("UPDATE `voucher_payments` SET `payment_status` = 'in-progress' WHERE `payment_status` = 'in progress'");
    }
}
