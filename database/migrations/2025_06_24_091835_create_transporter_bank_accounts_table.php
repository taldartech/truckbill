<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transporter_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained('transporters')->onDelete('cascade');
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('ifsc_code');
            $table->string('beneficiary_name');
            $table->string('bulk_payment_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transporter_bank_accounts');
    }
};
