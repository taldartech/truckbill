<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMobileNumberToTransportersTable extends Migration
{
    public function up()
    {
        Schema::table('transporters', function (Blueprint $table) {
            // Add nullable mobile_number column and make it unique
            $table->string('mobile_number')->nullable()->unique();
        });
    }

    public function down()
    {
        Schema::table('transporters', function (Blueprint $table) {
            $table->dropUnique(['mobile_number']); // Drop unique constraint first
            $table->dropColumn('mobile_number');
        });
    }
}
