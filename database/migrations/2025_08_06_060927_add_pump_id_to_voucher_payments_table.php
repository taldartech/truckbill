<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPumpIdToVoucherPaymentsTable extends Migration
{
    public function up()
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('pump_id')->nullable()->after('id'); // Place it after 'id' or wherever appropriate
            $table->foreign('pump_id')->references('id')->on('pumps')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->dropForeign(['pump_id']);
            $table->dropColumn('pump_id');
        });
    }
}
