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
        Schema::create('email_dispatch_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_template_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('event_key', 120)->nullable();
            $table->string('recipient_email');
            $table->string('subject_snapshot');
            $table->longText('body_snapshot')->nullable();
            $table->string('status', 30)->default('queued');
            $table->string('triggered_by_type', 20)->default('system');
            $table->unsignedBigInteger('triggered_by_id')->nullable();
            $table->string('source', 80)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_dispatch_logs');
    }
};
