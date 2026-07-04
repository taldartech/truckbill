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
        if (!Schema::hasColumn('freight_bookings', 'voucher_number')) {
            Schema::table('freight_bookings', function (Blueprint $table) {
                $table->integer('voucher_number')->unique()->after('id');
            });
        }
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->dropColumn('voucher_number');
        });
    }
};
