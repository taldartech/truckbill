<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressToTransportersTable extends Migration
{
    public function up()
    {
        Schema::table('transporters', function (Blueprint $table) {
            $table->text('address')->nullable()->after('city_id'); 
        });
    }

    public function down()
    {
        Schema::table('transporters', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
}

