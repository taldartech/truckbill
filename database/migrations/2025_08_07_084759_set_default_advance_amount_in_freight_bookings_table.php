<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetDefaultAdvanceAmountInFreightBookingsTable extends Migration
{
    public function up()
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->float('advance_amount')->default(0)->change();
        });
    }

    public function down()
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->float('advance_amount')->nullable()->change(); // or remove default if needed
        });
    }
}
