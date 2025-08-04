<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixAddAddressToTransportersTable extends Migration
{
    public function up()
    {
        Schema::table('transporters', function (Blueprint $table) {
            if (!Schema::hasColumn('transporters', 'address')) {
                $table->text('address')->nullable(); // removed after('city_id')
            }
        });
    }

    public function down()
    {
        Schema::table('transporters', function (Blueprint $table) {
            if (Schema::hasColumn('transporters', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
}
