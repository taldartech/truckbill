<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            // Add foreign key constraint to existing freight_booking_id column
            $table->foreign('freight_booking_id')->references('id')->on('freight_bookings')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            // Remove the foreign key constraint
            $table->dropForeign(['freight_booking_id']);
        });
    }
};
