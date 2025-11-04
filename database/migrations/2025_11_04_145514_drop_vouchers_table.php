<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('vouchers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the vouchers table as it existed prior to this drop,
        // based on 2025_06_21_010700_create_vouchers_table.php and subsequent
        // column additions in later migrations.
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();

            // From 2025_07_11_113229_add_company_id_to_vouchers_table.php
            $table->foreignId('company_id')->nullable();

            // From 2025_06_24_153100_add_voucher_number_to_vouchers_table.php
            $table->unsignedBigInteger('voucher_number');
            $table->string('formatted_voucher_number');

            // From 2025_06_21_010700_create_vouchers_table.php
            $table->foreignId('freight_booking_id');
            $table->foreignId('truck_id');
            $table->foreignId('transporter_id');
            $table->boolean('pan_verified')->default(false);
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('freight_booking_id')->references('id')->on('freight_bookings')->onDelete('cascade');
            $table->foreign('truck_id')->references('id')->on('trucks')->onDelete('cascade');
            $table->foreign('transporter_id')->references('id')->on('transporters')->onDelete('cascade');
        });
    }
};
