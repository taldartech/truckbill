<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transporter_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained('transporters')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('transaction_type', ['debit', 'credit']);
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transporter_ledgers');
    }
};
