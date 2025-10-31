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
        Schema::table('transporters', function (Blueprint $table) {
            $table->dropUnique('transporters_pan_number_unique');
            $table->dropUnique('transporters_mobile_number_unique');
            $table->dropUnique('transporters_short_name_unique');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('transporters', function (Blueprint $table) {
        $table->unique('pan_number');
        $table->unique('mobile_number');
        $table->unique('short_name');
        
    });
}
};
