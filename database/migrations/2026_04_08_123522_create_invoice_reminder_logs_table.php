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
        Schema::create('invoice_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('reminder_key', 50); // due_in_3_days | due_in_1_day
            $table->date('sent_on');
            $table->timestamps();

            $table->index(['company_id', 'sent_on']);
            $table->unique(['invoice_id', 'reminder_key', 'sent_on'], 'invoice_reminder_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_reminder_logs');
    }
};
