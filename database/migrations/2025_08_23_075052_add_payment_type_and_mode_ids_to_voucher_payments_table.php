<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_type_id')->nullable()->after('payment_type');
            $table->unsignedBigInteger('payment_mode_id')->nullable()->after('payment_mode');
            
            // Add foreign key constraints
            $table->foreign('payment_type_id')->references('id')->on('payment_types')->onDelete('set null');
            $table->foreign('payment_mode_id')->references('id')->on('payment_modes')->onDelete('set null');
        });
        
        // Populate the new columns with data from existing records
        $this->populatePaymentTypeAndModeIds();
    }
    
    /**
     * Populate payment_type_id and payment_mode_id from existing payment_type and payment_mode values
     */
    private function populatePaymentTypeAndModeIds(): void
    {
        $voucherPayments = DB::table('voucher_payments')->get();
        $paymentTypes = DB::table('payment_types')->get()->keyBy('name');
        $paymentModes = DB::table('payment_modes')->get()->keyBy('name');
        
        foreach ($voucherPayments as $payment) {
            $updates = [];
            
            if (!empty($payment->payment_type) && isset($paymentTypes[$payment->payment_type])) {
                $updates['payment_type_id'] = $paymentTypes[$payment->payment_type]->id;
            }
            
            if (!empty($payment->payment_mode) && isset($paymentModes[$payment->payment_mode])) {
                $updates['payment_mode_id'] = $paymentModes[$payment->payment_mode]->id;
            }
            
            if (!empty($updates)) {
                DB::table('voucher_payments')->where('id', $payment->id)->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->dropForeign(['payment_type_id']);
            $table->dropForeign(['payment_mode_id']);
            $table->dropColumn('payment_type_id');
            $table->dropColumn('payment_mode_id');
        });
    }
};
