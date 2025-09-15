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
        Schema::create('voucher_linkeds', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->json('voucher_ids');
            $table->foreignId('pump_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('payment_type');
            $table->foreignId('payment_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_mode');
            $table->foreignId('payment_mode_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('reference_transaction_id')->nullable();
            $table->text('remark')->nullable();
            $table->string('payment_status')->default('pending');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_linkeds');
    }
};
