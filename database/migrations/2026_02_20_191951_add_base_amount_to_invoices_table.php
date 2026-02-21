<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('base_amount', 12, 2)->nullable()->after('total_amount');
        });

        DB::table('invoices')->where('invoice_type', 'Forward')->update([
            'base_amount' => DB::raw('total_amount - COALESCE(sgst,0) - COALESCE(cgst,0) - COALESCE(igst,0)'),
        ]);

        DB::table('invoices')->where('invoice_type', 'Reverse')->update([
            'base_amount' => DB::raw('total_amount'),
            'net_amount' => DB::raw('total_amount'),
            'total_amount' => DB::raw('total_amount + COALESCE(sgst,0) + COALESCE(cgst,0) + COALESCE(igst,0)'),
        ]);
    }

    public function down(): void
    {
        DB::table('invoices')->where('invoice_type', 'Reverse')->update([
            'total_amount' => DB::raw('net_amount'),
        ]);

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('base_amount');
        });
    }
};
