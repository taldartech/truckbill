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
        // 1. Transporter Section 194C(6) declaration date
        if (Schema::hasTable('transporters') && ! Schema::hasColumn('transporters', 'declaration_194c6_date')) {
            Schema::table('transporters', function (Blueprint $table) {
                $table->date('declaration_194c6_date')->nullable()->after('declaration_given');
            });
        }

        // 2. Accounting Vouchers Challan ITNS 281 fields
        if (Schema::hasTable('accounting_vouchers')) {
            Schema::table('accounting_vouchers', function (Blueprint $table) {
                if (! Schema::hasColumn('accounting_vouchers', 'challan_bsr_code')) {
                    $table->string('challan_bsr_code', 20)->nullable()->after('reference_date');
                }
                if (! Schema::hasColumn('accounting_vouchers', 'challan_serial_no')) {
                    $table->string('challan_serial_no', 30)->nullable()->after('challan_bsr_code');
                }
                if (! Schema::hasColumn('accounting_vouchers', 'challan_tender_date')) {
                    $table->date('challan_tender_date')->nullable()->after('challan_serial_no');
                }
                if (! Schema::hasColumn('accounting_vouchers', 'challan_head')) {
                    $table->string('challan_head', 50)->nullable()->after('challan_tender_date');
                }
            });
        }

        // 3. Multi-bank account / fleet card selection in invoice_payments
        if (Schema::hasTable('invoice_payments') && ! Schema::hasColumn('invoice_payments', 'bank_ledger_id')) {
            Schema::table('invoice_payments', function (Blueprint $table) {
                $table->foreignId('bank_ledger_id')->nullable()->after('mode')->constrained('ledgers')->nullOnDelete();
            });
        }

        // 4. Multi-bank account / fleet card selection in voucher_payments
        if (Schema::hasTable('voucher_payments') && ! Schema::hasColumn('voucher_payments', 'bank_ledger_id')) {
            Schema::table('voucher_payments', function (Blueprint $table) {
                $table->foreignId('bank_ledger_id')->nullable()->after('payment_mode')->constrained('ledgers')->nullOnDelete();
            });
        }

        // 5. Multi-bank account selection in advance_payments
        if (Schema::hasTable('advance_payments') && ! Schema::hasColumn('advance_payments', 'bank_ledger_id')) {
            Schema::table('advance_payments', function (Blueprint $table) {
                $table->foreignId('bank_ledger_id')->nullable()->after('advance_amount')->constrained('ledgers')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transporters') && Schema::hasColumn('transporters', 'declaration_194c6_date')) {
            Schema::table('transporters', function (Blueprint $table) {
                $table->dropColumn('declaration_194c6_date');
            });
        }

        if (Schema::hasTable('accounting_vouchers')) {
            Schema::table('accounting_vouchers', function (Blueprint $table) {
                $table->dropColumn([
                    'challan_bsr_code',
                    'challan_serial_no',
                    'challan_tender_date',
                    'challan_head',
                ]);
            });
        }

        if (Schema::hasTable('invoice_payments') && Schema::hasColumn('invoice_payments', 'bank_ledger_id')) {
            Schema::table('invoice_payments', function (Blueprint $table) {
                $table->dropForeign(['bank_ledger_id']);
                $table->dropColumn('bank_ledger_id');
            });
        }

        if (Schema::hasTable('voucher_payments') && Schema::hasColumn('voucher_payments', 'bank_ledger_id')) {
            Schema::table('voucher_payments', function (Blueprint $table) {
                $table->dropForeign(['bank_ledger_id']);
                $table->dropColumn('bank_ledger_id');
            });
        }

        if (Schema::hasTable('advance_payments') && Schema::hasColumn('advance_payments', 'bank_ledger_id')) {
            Schema::table('advance_payments', function (Blueprint $table) {
                $table->dropForeign(['bank_ledger_id']);
                $table->dropColumn('bank_ledger_id');
            });
        }
    }
};
