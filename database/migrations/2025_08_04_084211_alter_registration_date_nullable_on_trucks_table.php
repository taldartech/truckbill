<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRegistrationDateNullableOnTrucksTable extends Migration
{
    public function up()
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->date('registration_date')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('trucks', function (Blueprint $table) {
            // Adjust according to your previous default (if any)
            $table->date('registration_date')->nullable(false)->change();
        });
    }
}

