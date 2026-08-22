<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('freight_bookings')
            ->whereNotNull('eway_bill_number')
            ->where('eway_bill_number', '!=', '')
            ->get()
            ->each(function ($freightBooking) {
                DB::table('eway_bills')->insert([
                    'freight_booking_id' => $freightBooking->id,
                    'eway_bill_number' => $freightBooking->eway_bill_number,
                    'eway_bill_expiry' => $freightBooking->eway_bill_expiry,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('eway_bills')->delete();
    }
};