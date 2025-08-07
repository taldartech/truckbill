<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AllowNullAndRemoveDefaultForAdvanceAmountInFreightBookings extends Migration
{
    public function up()
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->float('advance_amount')->nullable()->default(null)->change();
        });
    }

    public function down()
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->float('advance_amount')->default(0)->nullable(false)->change();
        });
    }
}
