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
        Schema::create('eway_bill_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freight_booking_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('reminder_key', 50);
            $table->date('sent_on');
            $table->timestamps();

            $table->index(['company_id', 'sent_on']);
            $table->unique(['freight_booking_id', 'reminder_key', 'sent_on'], 'eway_reminder_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eway_bill_reminder_logs');
    }
};
