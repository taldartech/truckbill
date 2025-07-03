<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First update any existing NULL values to a default date
        DB::table('lorry_receipts')
            ->whereNull('gr_date')
            ->update(['gr_date' => now()->toDateString()]);

        // Then change the column to be required
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->date('gr_date')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->date('gr_date')->nullable()->change();
        });
    }
};
