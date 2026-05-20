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
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->index(['company_id', 'gr_date', 'deleted_at'], 'lorry_receipts_company_gr_date_deleted_idx');
            $table->index(['company_id', 'transporter_id', 'deleted_at'], 'lorry_receipts_company_transporter_deleted_idx');
            $table->index(['company_id', 'invoice_number', 'deleted_at'], 'lorry_receipts_company_invoice_number_deleted_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['company_id', 'generated_date', 'deleted_at'], 'invoices_company_generated_date_deleted_idx');
            $table->index(['company_id', 'payment_status', 'deleted_at'], 'invoices_company_payment_status_deleted_idx');
            $table->index(['company_id', 'customer_id', 'deleted_at'], 'invoices_company_customer_deleted_idx');
        });

        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'freight_bookings_company_created_idx');
            $table->index(['company_id', 'transporter_id'], 'freight_bookings_company_transporter_idx');
            $table->index(['company_id', 'eway_bill_expiry'], 'freight_bookings_company_eway_expiry_idx');
        });

        Schema::table('trucks', function (Blueprint $table) {
            $table->index(['company_id', 'deleted_at'], 'trucks_company_deleted_idx');
            $table->index(['company_id', 'transporter_id', 'deleted_at'], 'trucks_company_transporter_deleted_idx');
        });

        Schema::table('consignees', function (Blueprint $table) {
            $table->index(['company_id', 'deleted_at'], 'consignees_company_deleted_idx');
            $table->index(['company_id', 'name', 'deleted_at'], 'consignees_company_name_deleted_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->dropIndex('lorry_receipts_company_gr_date_deleted_idx');
            $table->dropIndex('lorry_receipts_company_transporter_deleted_idx');
            $table->dropIndex('lorry_receipts_company_invoice_number_deleted_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_company_generated_date_deleted_idx');
            $table->dropIndex('invoices_company_payment_status_deleted_idx');
            $table->dropIndex('invoices_company_customer_deleted_idx');
        });

        Schema::table('freight_bookings', function (Blueprint $table) {
            $table->dropIndex('freight_bookings_company_created_idx');
            $table->dropIndex('freight_bookings_company_transporter_idx');
            $table->dropIndex('freight_bookings_company_eway_expiry_idx');
        });

        Schema::table('trucks', function (Blueprint $table) {
            $table->dropIndex('trucks_company_deleted_idx');
            $table->dropIndex('trucks_company_transporter_deleted_idx');
        });

        Schema::table('consignees', function (Blueprint $table) {
            $table->dropIndex('consignees_company_deleted_idx');
            $table->dropIndex('consignees_company_name_deleted_idx');
        });
    }
};
