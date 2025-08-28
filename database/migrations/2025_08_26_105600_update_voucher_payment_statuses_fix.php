<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateVoucherPaymentStatusesFix extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Increase column length first to prevent truncation
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->string('payment_status', 20)->change();
        });
        
        // Then update the values
        DB::statement("UPDATE voucher_payments SET payment_status = 'in progress' WHERE payment_status = 'in-progress'");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Revert back to 'in-progress' if needed
        DB::statement("UPDATE voucher_payments SET payment_status = 'in-progress' WHERE payment_status = 'in progress'");
    }
}
