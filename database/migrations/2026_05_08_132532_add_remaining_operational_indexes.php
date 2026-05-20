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
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->index(['company_id', 'date', 'linked_voucher_id'], 'voucher_payments_company_date_linked_idx');
            $table->index(['company_id', 'payment_status', 'created_at'], 'voucher_payments_company_status_created_idx');
            $table->index(['freight_booking_id', 'created_at'], 'voucher_payments_booking_created_idx');
        });

        Schema::table('voucher_linkeds', function (Blueprint $table) {
            $table->index(['company_id', 'date', 'payment_status'], 'voucher_linkeds_company_date_status_idx');
            $table->index(['company_id', 'created_at'], 'voucher_linkeds_company_created_idx');
            $table->index('freight_booking_id', 'voucher_linkeds_booking_idx');
        });

        Schema::table('advance_payments', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'advance_payments_company_created_idx');
            $table->index(['transporter_id', 'created_at'], 'advance_payments_transporter_created_idx');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->index(['company_id', 'deleted_at'], 'drivers_company_deleted_idx');
            $table->index(['company_id', 'mobile', 'deleted_at'], 'drivers_company_mobile_deleted_idx');
            $table->index(['company_id', 'license_number', 'deleted_at'], 'drivers_company_license_deleted_idx');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->index(['company_id', 'head_office', 'deleted_at'], 'branches_company_head_office_deleted_idx');
        });

        Schema::table('payment_ledgers', function (Blueprint $table) {
            $table->index(['company_id', 'created_at'], 'payment_ledgers_company_created_idx');
            $table->index(['company_id', 'status'], 'payment_ledgers_company_status_idx');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->index(['user_id', 'deleted_at'], 'companies_user_deleted_idx');
        });

        Schema::table('pumps', function (Blueprint $table) {
            $table->index(['user_id', 'pump_name'], 'pumps_user_name_idx');
        });

        Schema::table('good_types', function (Blueprint $table) {
            $table->index(['user_id', 'name'], 'good_types_user_name_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_payments', function (Blueprint $table) {
            $table->dropIndex('voucher_payments_company_date_linked_idx');
            $table->dropIndex('voucher_payments_company_status_created_idx');
            $table->dropIndex('voucher_payments_booking_created_idx');
        });

        Schema::table('voucher_linkeds', function (Blueprint $table) {
            $table->dropIndex('voucher_linkeds_company_date_status_idx');
            $table->dropIndex('voucher_linkeds_company_created_idx');
            $table->dropIndex('voucher_linkeds_booking_idx');
        });

        Schema::table('advance_payments', function (Blueprint $table) {
            $table->dropIndex('advance_payments_company_created_idx');
            $table->dropIndex('advance_payments_transporter_created_idx');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex('drivers_company_deleted_idx');
            $table->dropIndex('drivers_company_mobile_deleted_idx');
            $table->dropIndex('drivers_company_license_deleted_idx');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropIndex('branches_company_head_office_deleted_idx');
        });

        Schema::table('payment_ledgers', function (Blueprint $table) {
            $table->dropIndex('payment_ledgers_company_created_idx');
            $table->dropIndex('payment_ledgers_company_status_idx');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_user_deleted_idx');
        });

        Schema::table('pumps', function (Blueprint $table) {
            $table->dropIndex('pumps_user_name_idx');
        });

        Schema::table('good_types', function (Blueprint $table) {
            $table->dropIndex('good_types_user_name_idx');
        });
    }
};
