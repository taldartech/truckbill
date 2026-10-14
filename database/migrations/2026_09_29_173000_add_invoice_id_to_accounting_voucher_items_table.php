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
        Schema::table('accounting_voucher_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounting_voucher_items', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('cost_centre_id')->constrained('invoices')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_voucher_items', function (Blueprint $table): void {
            if (Schema::hasColumn('accounting_voucher_items', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
        });
    }
};
