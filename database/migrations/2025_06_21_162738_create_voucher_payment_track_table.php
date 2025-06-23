<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('voucher_payment_track', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
            $table->enum('payment_type', ['advance', 'remaining', 'full']);
            $table->enum('payment_mode', ['cash', 'online', 'bank_transfer', 'cheque']);
            $table->string('transaction_id')->nullable();
            $table->text('remark')->nullable();
            $table->enum('payment_status', ['pending', 'in-progress', 'paid'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('voucher_payment_track');
    }
};
