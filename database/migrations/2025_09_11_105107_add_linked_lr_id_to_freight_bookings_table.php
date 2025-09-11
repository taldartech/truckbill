<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('linked_lr_id')->nullable()->after('lr_id'); 
            
        });
    }

    public function down(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->dropColumn('linked_lr_id');
        });
    }
};
