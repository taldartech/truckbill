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
            $table->enum('custom_invoice', ['rate_difference', 'shortage_damage'])->nullable()->after('invoice_type');
            $table->text('custom_invoice_remark')->nullable()->after('custom_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['custom_invoice', 'custom_invoice_remark']);
        });
    }
};
