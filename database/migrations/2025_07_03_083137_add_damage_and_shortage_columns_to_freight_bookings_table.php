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
            $table->decimal('damage_qty', 10, 2)->nullable()->default(null);
            $table->decimal('damage_rate', 10, 2)->nullable()->default(null);
            $table->decimal('damage_amount', 10, 2)->nullable()->default(null);
            $table->decimal('shortage_qty', 10, 2)->nullable()->default(null);
            $table->decimal('shortage_rate', 10, 2)->nullable()->default(null);
            $table->decimal('shortage_amount', 10, 2)->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->dropColumn('damage_qty');
            $table->dropColumn('damage_rate');
            $table->dropColumn('damage_amount');
            $table->dropColumn('shortage_qty');
            $table->dropColumn('shortage_rate');
            $table->dropColumn('shortage_amount');
        });
    }
};
