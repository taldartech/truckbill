<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_reminder_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('reminder_key');
            $table->date('sent_on');
            $table->timestamps();

            $table->index(['company_id', 'sent_on']);
            $table->unique(['company_id', 'reminder_key', 'sent_on'], 'company_reminder_logs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_reminder_logs');
    }
};
