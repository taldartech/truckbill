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
        Schema::table('voucher_linkeds', function (Blueprint $table) {
            $table->foreignId('freight_booking_id')
                  ->nullable()
                  ->constrained()
                  ->after('linked_voucher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_linkeds', function (Blueprint $table) {
            $table->dropForeign(['freight_booking_id']);
            $table->dropColumn('freight_booking_id');
        });
    }
};
