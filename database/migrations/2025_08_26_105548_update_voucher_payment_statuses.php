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
        // Update 'in-progress' to 'in progress'
        DB::table('voucher_payments')
            ->where('payment_status', 'in-progress')
            ->update(['payment_status' => 'in progress']);
            
        // Update 'cancelled' to 'pending'
        DB::table('voucher_payments')
            ->where('payment_status', 'cancelled')
            ->update(['payment_status' => 'pending']);
            
        // Update any other status that's not in the allowed list to 'pending'
        DB::table('voucher_payments')
            ->whereNotIn('payment_status', ['pending', 'in progress', 'paid'])
            ->update(['payment_status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert 'in progress' back to 'in-progress' for backward compatibility
        DB::table('voucher_payments')
            ->where('payment_status', 'in progress')
            ->update(['payment_status' => 'in-progress']);
            
        // Note: We don't restore 'cancelled' status as we don't know which ones were previously cancelled
    }
};
