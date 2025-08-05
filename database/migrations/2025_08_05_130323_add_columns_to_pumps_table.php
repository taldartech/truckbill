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
    Schema::table('pumps', function (Blueprint $table) {
        $table->string('pump_name');
        $table->string('contact_name');
        $table->string('contact_number')->unique();
    });
}

public function down()
{
    Schema::table('pumps', function (Blueprint $table) {
        $table->dropColumn(['pump_name', 'contact_name', 'contact_number']);
    });
}

};
