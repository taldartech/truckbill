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
       Schema::create('eway_bills', function (Blueprint $table) {
    $table->id();

    $table->foreignId('freight_booking_id')
        ->constrained('freight_bookings')
        ->cascadeOnDelete();

    $table->string('eway_bill_number')->nullable();
    $table->date('eway_bill_expiry')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eway_bills');
    }
};
