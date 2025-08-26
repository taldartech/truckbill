<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
            
        // Convert any 'in progress' to 'in-progress' to match the ENUM
        if ($statuses->contains('in progress')) {
            DB::table('voucher_payments')
                ->where('payment_status', 'in progress')
                ->update(['payment_status' => 'in-progress']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Revert back if needed (though this shouldn't be necessary as it's fixing to match ENUM)
        DB::table('voucher_payments')
            ->where('payment_status', 'in-progress')
            ->update(['payment_status' => 'in progress']);
    }
}
