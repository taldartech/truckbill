<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyAddressNullableInConsigneesTable extends Migration
{
    public function up()
    {
        Schema::table('consignees', function (Blueprint $table) {
            $table->text('address')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('consignees', function (Blueprint $table) {
            $table->text('address')->nullable(false)->change();
        });
    }
}
