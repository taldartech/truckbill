<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->decimal('actual_amount', 12, 2)->nullable()->after('amount');
        });

        // Set the actual_amount to the weight * fr_rate for existing records
        DB::table('lorry_receipts')->update(['actual_amount' => DB::raw('weight * fr_rate')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->dropColumn('actual_amount');
        });
    }
};
