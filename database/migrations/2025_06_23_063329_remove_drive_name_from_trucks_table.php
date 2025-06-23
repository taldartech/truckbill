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
        if (Schema::hasColumn('trucks', 'drive_name')) {
            Schema::table('trucks', function (Blueprint $table) {
                $table->dropColumn('drive_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->string('drive_name')->nullable(); // Optional, adjust as per original type
        });
    }
};
