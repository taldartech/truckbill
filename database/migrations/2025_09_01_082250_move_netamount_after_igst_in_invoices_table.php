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
        Schema::table('invoices', function (Blueprint $table) {
            // Move net_amount after igst
            $table->float('net_amount')->nullable()->after('igst')->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Move net_amount back after total_amount (original position)
            $table->float('net_amount')->nullable()->after('total_amount')->change();
        });
    }
};
