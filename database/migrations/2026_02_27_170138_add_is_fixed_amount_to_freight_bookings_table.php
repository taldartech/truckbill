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
            $table->boolean('is_fixed_amount')
                ->default(false)
                ->after('tr_fr_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->dropColumn('is_fixed_amount');
        });
    }
};
