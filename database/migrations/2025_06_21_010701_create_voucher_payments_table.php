<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voucher_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->string('payment_type');
            $table->string('payment_mode');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('transaction_id');
            $table->text('remark')->nullable();
            $table->enum('payment_status', ['pending', 'in-progress', 'paid'])->default('pending');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('voucher_payments');
    }
};
