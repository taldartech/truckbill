<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('advance_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('transporter_id')->after('id');
            $table->decimal('advance_amount', 10, 2)->after('transporter_id');
            $table->date('payment_date')->after('advance_amount');
    
            $table->foreign('transporter_id')->references('id')->on('transporters')->onDelete('cascade');
        });
    }
    
    public function down()
    {
        Schema::table('advance_payments', function (Blueprint $table) {
            $table->dropForeign(['transporter_id']);
            $table->dropColumn(['transporter_id', 'advance_amount', 'payment_date']);
        });
    }
    
};
