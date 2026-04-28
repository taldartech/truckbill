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
            if (!Schema::hasColumn('freight_bookings', 'negative_adjusted_amount')) {
                $table->decimal('negative_adjusted_amount', 12, 2)->default(0)->after('balance_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('freight_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('freight_bookings', 'negative_adjusted_amount')) {
                $table->dropColumn('negative_adjusted_amount');
            }
        });
    }
};
