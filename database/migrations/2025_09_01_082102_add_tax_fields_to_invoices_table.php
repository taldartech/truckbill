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
            $table->float('net_amount')->nullable()->after('total_amount');
            $table->float('sgst')->nullable()->after('net_amount');
            $table->float('cgst')->nullable()->after('sgst');
            $table->float('igst')->nullable()->after('cgst');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['net_amount', 'sgst', 'cgst', 'igst']);
        });
    }
};
