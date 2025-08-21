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
            $table->dropColumn([
                'shortage',
                'damage_qty',
                'damage_rate',
                'shortage_qty',
                'shortage_rate',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            // Add the columns back with assumed types
            $table->string('shortage')->nullable();
            $table->integer('damage_qty')->nullable();
            $table->decimal('damage_rate', 10, 2)->nullable();
            $table->integer('shortage_qty')->nullable();
            $table->decimal('shortage_rate', 10, 2)->nullable();
        });
    }
};
