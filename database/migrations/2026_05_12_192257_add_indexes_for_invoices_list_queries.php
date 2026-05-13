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
            $table->index(['company_id', 'deleted_at', 'id'], 'invoices_list_company_deleted_id_idx');
            $table->index(['company_id', 'deleted_at', 'generated_date'], 'invoices_list_company_deleted_date_idx');
            $table->index(['company_id', 'deleted_at', 'payment_status'], 'invoices_list_company_deleted_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_list_company_deleted_id_idx');
            $table->dropIndex('invoices_list_company_deleted_date_idx');
            $table->dropIndex('invoices_list_company_deleted_status_idx');
        });
    }
};
