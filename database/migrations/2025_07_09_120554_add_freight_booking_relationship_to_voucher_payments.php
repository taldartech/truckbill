<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            // Add freight_booking_id as foreign key
            $table->foreignId('freight_booking_id')->constrained('freight_bookings')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            // Remove the foreign key constraint and column
            $table->dropForeign(['freight_booking_id']);
            $table->dropColumn('freight_booking_id');
        });
    }
};
