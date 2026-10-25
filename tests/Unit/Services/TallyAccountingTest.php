<?php

namespace Tests\Unit\Services;

use App\Models\AccountGroup;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\CostCentre;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Ledger;
use App\Models\LorryReceipt;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\VoucherPayment;
use App\Observers\Accounting\AdvancePaymentObserver;
use App\Observers\Accounting\FreightBookingObserver;
use App\Observers\Accounting\InvoiceObserver;
use App\Observers\Accounting\InvoicePaymentObserver;
use App\Observers\Accounting\VoucherPaymentObserver;
use App\Services\AccountingPostingService;
use App\Services\ChartOfAccountsProvisioner;
use App\Services\FinancialReportService;
use App\Services\TallyXmlExportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mockery;
use SimpleXMLElement;
use Tests\TestCase;

class TallyAccountingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('user_id')->nullable();
                $table->string('short_name')->nullable();
                $table->string('dummy_pan_number')->nullable();
                $table->string('gst_number')->nullable();
                $table->string('pan_number')->nullable();
                $table->text('address')->nullable();
                $table->string('contact_number')->nullable();
                $table->string('bank')->nullable();
                $table->string('account_number')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->foreignId('company_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } elseif (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('company_settings')) {
            Schema::create('company_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->string('template_name')->nullable();
                $table->string('template_address')->nullable();
                $table->string('template_header')->nullable();
                $table->string('template_note')->nullable();
                $table->string('template_jurisdictional_boundary')->nullable();
                $table->string('invoice_template')->nullable();
                $table->string('lr_copy_template')->nullable();
                $table->string('freight_challan_template')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('account_groups')) {
            Schema::create('account_groups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable();
                $table->foreignId('parent_id')->nullable();
                $table->string('name');
                $table->string('primary_type');
                $table->boolean('is_builtin')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('ledgers')) {
            Schema::create('ledgers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->foreignId('account_group_id')->nullable();
                $table->string('name');
                $table->string('code')->nullable();
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->string('opening_balance_type')->default('debit');
                $table->string('opening_balance_reference')->nullable();
                $table->string('gstin')->nullable();
                $table->string('pan_number')->nullable();
                $table->string('tds_section')->nullable();
                $table->decimal('tds_rate', 5, 2)->nullable();
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->boolean('is_builtin')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('accounting_vouchers')) {
            Schema::create('accounting_vouchers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->string('voucher_type');
                $table->string('voucher_number');
                $table->date('voucher_date');
                $table->string('reference_number')->nullable();
                $table->date('reference_date')->nullable();
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->text('narration')->nullable();
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->boolean('is_posted')->default(true);
                $table->foreignId('user_id')->nullable();
                $table->string('challan_bsr_code', 20)->nullable();
                $table->string('challan_serial_no', 30)->nullable();
                $table->date('challan_tender_date')->nullable();
                $table->string('challan_head', 20)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('accounting_voucher_items')) {
            Schema::create('accounting_voucher_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->foreignId('accounting_voucher_id');
                $table->foreignId('ledger_id');
                $table->string('type');
                $table->decimal('amount', 15, 2);
                $table->foreignId('cost_centre_id')->nullable();
                $table->foreignId('invoice_id')->nullable();
                $table->string('particulars_note')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('cost_centres')) {
            Schema::create('cost_centres', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->string('category')->default('other');
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('entity_type')->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('transporter_masters')) {
            Schema::create('transporter_masters', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('pan_number')->nullable();
                $table->string('gst_number')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('transporters')) {
            Schema::create('transporters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable();
                $table->foreignId('transporter_master_id')->nullable();
                $table->string('contact_person_name')->nullable();
                $table->string('mobile_number')->nullable();
                $table->decimal('tds_percentage', 5, 2)->default(1.0);
                $table->decimal('advance_amount', 15, 2)->default(0.0);
                $table->boolean('is_owner')->default(false);
                $table->boolean('declaration_given')->default(false);
                $table->date('declaration_194c6_date')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('advance_payments')) {
            Schema::create('advance_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->foreignId('transporter_id');
                $table->decimal('advance_amount', 15, 2);
                $table->date('payment_date')->nullable();
                $table->string('remark')->nullable();
                $table->foreignId('bank_ledger_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('trucks')) {
            Schema::create('trucks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable();
                $table->string('truck_number');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('freight_bookings')) {
            Schema::create('freight_bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->foreignId('transporter_id')->nullable();
                $table->foreignId('truck_id')->nullable();
                $table->foreignId('lr_id')->nullable();
                $table->decimal('tr_amount', 15, 2)->default(0.0);
                $table->decimal('detention', 15, 2)->default(0.0);
                $table->decimal('deviation', 15, 2)->default(0.0);
                $table->decimal('misc_expense', 15, 2)->default(0.0);
                $table->decimal('damage_amount', 15, 2)->default(0.0);
                $table->decimal('shortage_amount', 15, 2)->default(0.0);
                $table->decimal('tds_amount', 15, 2)->default(0.0);
                $table->string('voucher_number')->nullable();
                $table->boolean('is_cancelled')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('voucher_payments')) {
            Schema::create('voucher_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->foreignId('freight_booking_id')->nullable();
                $table->foreignId('pump_id')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->string('voucher_number')->nullable();
                $table->decimal('amount', 15, 2)->default(0.0);
                $table->string('payment_mode')->nullable();
                $table->string('payment_type')->nullable();
                $table->string('reference_transaction_id')->nullable();
                $table->date('date')->nullable();
                $table->foreignId('bank_ledger_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('freight_booking_custom_amounts')) {
            Schema::create('freight_booking_custom_amounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->foreignId('freight_booking_id');
                $table->string('description')->nullable();
                $table->decimal('amount', 15, 2)->default(0.0);
                $table->foreignId('user_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('pumps')) {
            Schema::create('pumps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->string('pump_name');
                $table->string('contact_name')->nullable();
                $table->string('contact_number')->nullable();
                $table->string('remark')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('consignees')) {
            Schema::create('consignees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable();
                $table->string('name');
                $table->string('address')->nullable();
                $table->string('contact_number')->nullable();
                $table->string('gst_number')->nullable();
                $table->string('pan_number')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id');
                $table->string('invoice_number')->nullable();
                $table->string('manual_invoice_number')->nullable();
                $table->string('invoice_type')->default('Forward');
                $table->date('generated_date')->nullable();
                $table->foreignId('customer_id');
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->decimal('base_amount', 15, 2)->default(0);
                $table->decimal('cgst', 15, 2)->default(0);
                $table->decimal('sgst', 15, 2)->default(0);
                $table->decimal('igst', 15, 2)->default(0);
                $table->decimal('net_amount', 15, 2)->nullable();
                $table->string('payment_status')->default('pending');
                $table->foreignId('created_by')->nullable();
                $table->foreignId('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('invoice_payments')) {
            Schema::create('invoice_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id');
                $table->date('payment_date')->nullable();
                $table->string('mode');
                $table->decimal('amount', 15, 2);
                $table->decimal('tds_amount', 15, 2)->default(0);
                $table->decimal('deduction_amount', 15, 2)->default(0);
                $table->string('deduction_reason')->nullable();
                $table->text('remark')->nullable();
                $table->foreignId('bank_ledger_id')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->foreignId('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable();
                $table->foreignId('user_id')->nullable();
                $table->string('action', 32);
                $table->string('subject_type');
                $table->unsignedBigInteger('subject_id');
                $table->string('subject_label')->nullable();
                $table->string('subject_type_name', 80);
                $table->text('summary');
                $table->json('changes')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_log_changes')) {
            Schema::create('audit_log_changes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('audit_log_id');
                $table->string('attribute_name');
                $table->string('field_label')->nullable();
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('company_subscriptions')) {
            Schema::create('company_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->unique();
                $table->string('status')->default('trial');
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->date('trial_ends_on')->nullable();
                $table->unsignedSmallInteger('included_branches')->default(5);
                $table->unsignedSmallInteger('users_per_branch')->default(5);
                $table->unsignedInteger('included_lr_credits')->default(100);
                $table->unsignedInteger('included_trucks')->default(0);
                $table->unsignedSmallInteger('addon_branches')->default(0);
                $table->unsignedSmallInteger('addon_users')->default(0);
                $table->unsignedSmallInteger('addon_trucks')->default(0);
                $table->unsignedInteger('addon_lr_credits')->default(0);
                $table->unsignedInteger('lr_credits_remaining')->default(100);
                $table->timestamp('last_renewed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lorry_receipts')) {
            Schema::create('lorry_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable();
                $table->foreignId('truck_id')->nullable();
                $table->foreignId('transporter_id')->nullable();
                $table->string('lr_number')->nullable();
                $table->string('delivery_status')->default('Pending');
                $table->timestamps();
                $table->softDeletes();
            });
        } elseif (! Schema::hasColumn('lorry_receipts', 'delivery_status')) {
            Schema::table('lorry_receipts', function (Blueprint $table) {
                $table->string('delivery_status')->default('Pending');
            });
        }

        if (Schema::hasTable('trucks') && ! Schema::hasColumn('trucks', 'transporter_id')) {
            Schema::table('trucks', function (Blueprint $table) {
                $table->foreignId('transporter_id')->nullable();
            });
        }

        if (Schema::hasTable('freight_bookings')) {
            if (! Schema::hasColumn('freight_bookings', 'balance_amount')) {
                Schema::table('freight_bookings', function (Blueprint $table) {
                    $table->decimal('balance_amount', 15, 2)->nullable();
                });
            }
            if (! Schema::hasColumn('freight_bookings', 'total_amount')) {
                Schema::table('freight_bookings', function (Blueprint $table) {
                    $table->decimal('total_amount', 15, 2)->nullable();
                });
            }
        }

        if (Schema::hasTable('voucher_payments') && ! Schema::hasColumn('voucher_payments', 'remark')) {
            Schema::table('voucher_payments', function (Blueprint $table) {
                $table->text('remark')->nullable();
            });
        }
    }

    public function test_tally_xml_exporter_generates_valid_envelope_and_structure(): void
    {
        $exporter = new TallyXmlExportService;

        $company = Company::create(['name' => 'Test Freight Logistics Pvt Ltd']);

        // Verify Masters XML export
        $xmlContent = $exporter->exportMastersXml($company);
        $this->assertNotEmpty($xmlContent);

        $parsed = simplexml_load_string($xmlContent);
        $this->assertInstanceOf(SimpleXMLElement::class, $parsed);
        $this->assertEquals('Import Data', (string) $parsed->HEADER->TALLYREQUEST);
        $this->assertEquals('All Masters', (string) $parsed->BODY->IMPORTDATA->REQUESTDESC->REPORTNAME);

        // Verify Vouchers XML export
        $vchXmlContent = $exporter->exportVouchersXml($company, '2026-09-01', '2026-09-11');
        $this->assertNotEmpty($vchXmlContent);

        $parsedVch = simplexml_load_string($vchXmlContent);
        $this->assertInstanceOf(SimpleXMLElement::class, $parsedVch);
        $this->assertEquals('Vouchers', (string) $parsedVch->BODY->IMPORTDATA->REQUESTDESC->REPORTNAME);
    }

    public function test_tally_xml_export_masters_with_parent_groups_and_active_filters(): void
    {
        $exporter = new TallyXmlExportService;
        $company = Company::create(['name' => 'Fast Cargo Pvt Ltd']);
        $otherCompany = Company::create(['name' => 'Other Co']);

        $group = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Sundry Debtors',
            'primary_type' => 'assets',
        ]);

        // Ledger with group
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $group->id,
            'name' => 'Client ABC',
            'is_active' => true,
        ]);

        // Ledger without group (fallback to Primary)
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => null,
            'name' => 'General Cash',
            'is_active' => true,
        ]);

        // Inactive ledger (should NOT appear)
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $group->id,
            'name' => 'Inactive Vendor',
            'is_active' => false,
        ]);

        // Other company ledger (should NOT appear)
        Ledger::create([
            'company_id' => $otherCompany->id,
            'account_group_id' => null,
            'name' => 'Other Co Ledger',
            'is_active' => true,
        ]);

        $xml = $exporter->exportMastersXml($company);
        $parsed = simplexml_load_string($xml);

        $messages = $parsed->BODY->IMPORTDATA->REQUESTDATA->TALLYMESSAGE;
        $this->assertCount(2, $messages);

        $names = [];
        $parents = [];
        foreach ($messages as $msg) {
            $ledgerNode = $msg->LEDGER;
            $this->assertEquals('Create', (string) $ledgerNode['ACTION']);
            $name = (string) $ledgerNode['NAME'];
            $names[] = $name;
            $parents[$name] = (string) $ledgerNode->PARENT;
            $this->assertEquals($name, (string) $ledgerNode->{'NAME.LIST'}->NAME);
        }

        $this->assertContains('Client ABC', $names);
        $this->assertContains('General Cash', $names);
        $this->assertNotContains('Inactive Vendor', $names);
        $this->assertNotContains('Other Co Ledger', $names);

        $this->assertEquals('Sundry Debtors', $parents['Client ABC']);
        $this->assertEquals('Primary', $parents['General Cash']);
    }

    public function test_tally_xml_export_masters_opening_balances_debit_credit_and_zero(): void
    {
        $exporter = new TallyXmlExportService;
        $company = Company::create(['name' => 'Balance Logistics']);

        // Debit opening balance -> multiplied by -1
        Ledger::create([
            'company_id' => $company->id,
            'name' => 'Bank Debit Account',
            'opening_balance' => 15000.50,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        // Credit opening balance -> multiplied by 1
        Ledger::create([
            'company_id' => $company->id,
            'name' => 'Supplier Credit Account',
            'opening_balance' => 8500.00,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        // Zero opening balance -> no OPENINGBALANCE tag
        Ledger::create([
            'company_id' => $company->id,
            'name' => 'Zero Balance Account',
            'opening_balance' => 0.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        $xml = $exporter->exportMastersXml($company);
        $parsed = simplexml_load_string($xml);

        $messages = $parsed->BODY->IMPORTDATA->REQUESTDATA->TALLYMESSAGE;
        $this->assertCount(3, $messages);

        $balances = [];
        foreach ($messages as $msg) {
            $name = (string) $msg->LEDGER['NAME'];
            if (isset($msg->LEDGER->OPENINGBALANCE)) {
                $balances[$name] = (string) $msg->LEDGER->OPENINGBALANCE;
            } else {
                $balances[$name] = null;
            }
        }

        $this->assertEquals('-15000.5', $balances['Bank Debit Account']);
        $this->assertEquals('8500', $balances['Supplier Credit Account']);
        $this->assertNull($balances['Zero Balance Account']);
    }

    public function test_tally_xml_export_masters_gstin_pan_and_special_characters_escaping(): void
    {
        $exporter = new TallyXmlExportService;
        $company = Company::create(['name' => 'Alpha & Beta <Transport> "LLP"']);

        Ledger::create([
            'company_id' => $company->id,
            'name' => 'Dealer & Sons <North>',
            'gstin' => '27ABCDE1234F1Z5',
            'pan_number' => 'ABCDE1234F',
            'is_active' => true,
        ]);

        Ledger::create([
            'company_id' => $company->id,
            'name' => 'Plain Ledger',
            'is_active' => true,
        ]);

        $xml = $exporter->exportMastersXml($company);
        $this->assertNotEmpty($xml);

        $parsed = simplexml_load_string($xml);
        $this->assertInstanceOf(SimpleXMLElement::class, $parsed);

        // Verify company name escaping
        $companyNameInXml = (string) $parsed->BODY->IMPORTDATA->REQUESTDESC->STATICVARIABLES->SVCURRENTCOMPANY;
        $this->assertEquals('Alpha & Beta <Transport> "LLP"', $companyNameInXml);

        $messages = $parsed->BODY->IMPORTDATA->REQUESTDATA->TALLYMESSAGE;
        $this->assertCount(2, $messages);

        $taxNode = $messages[0]->LEDGER;
        $this->assertEquals('Dealer & Sons <North>', htmlspecialchars_decode((string) $taxNode['NAME']));
        $this->assertEquals('27ABCDE1234F1Z5', (string) $taxNode->PARTYGSTIN);
        $this->assertEquals('ABCDE1234F', (string) $taxNode->PANNUMBER);

        $plainNode = $messages[1]->LEDGER;
        $this->assertFalse(isset($plainNode->PARTYGSTIN));
        $this->assertFalse(isset($plainNode->PANNUMBER));
    }

    public function test_tally_xml_export_vouchers_default_dates_current_month(): void
    {
        $exporter = new TallyXmlExportService;
        $company = Company::create(['name' => 'Apex Monthly Logistics']);

        $ledger = Ledger::create([
            'company_id' => $company->id,
            'name' => 'Office Expense',
            'is_active' => true,
        ]);

        // Current month voucher (within default range startOfMonth to today)
        $todayVoucher = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'VCH-CURR-01',
            'voucher_date' => now()->startOfMonth()->toDateString(),
            'is_posted' => true,
            'total_amount' => 500,
        ]);

        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $todayVoucher->id,
            'ledger_id' => $ledger->id,
            'type' => 'debit',
            'amount' => 500,
        ]);

        // Voucher from 3 months ago (outside current month default)
        $oldVoucher = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'VCH-OLD-01',
            'voucher_date' => now()->subMonths(3)->toDateString(),
            'is_posted' => true,
            'total_amount' => 300,
        ]);

        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $oldVoucher->id,
            'ledger_id' => $ledger->id,
            'type' => 'debit',
            'amount' => 300,
        ]);

        // Call without dates
        $xml = $exporter->exportVouchersXml($company);
        $parsed = simplexml_load_string($xml);

        $messages = $parsed->BODY->IMPORTDATA->REQUESTDATA->TALLYMESSAGE;
        $this->assertCount(1, $messages);
        $this->assertEquals('VCH-CURR-01', (string) $messages[0]->VOUCHER->VOUCHERNUMBER);
    }

    public function test_tally_xml_export_vouchers_filtering_unposted_and_date_ranges(): void
    {
        $exporter = new TallyXmlExportService;
        $company = Company::create(['name' => 'Filter Test Co']);
        $otherCompany = Company::create(['name' => 'Other Co']);

        $ledger = Ledger::create([
            'company_id' => $company->id,
            'name' => 'General Ledger',
            'is_active' => true,
        ]);

        // Posted voucher in range
        $postedVch = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JRN-01',
            'voucher_date' => '2026-05-15',
            'is_posted' => true,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $postedVch->id,
            'ledger_id' => $ledger->id,
            'type' => 'credit',
            'amount' => 1000,
        ]);

        // Later voucher in range (for order check)
        $laterVch = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JRN-02',
            'voucher_date' => '2026-05-20',
            'is_posted' => true,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $laterVch->id,
            'ledger_id' => $ledger->id,
            'type' => 'credit',
            'amount' => 2000,
        ]);

        // Unposted voucher in range (should NOT be exported)
        $unpostedVch = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JRN-UNPOSTED',
            'voucher_date' => '2026-05-16',
            'is_posted' => false,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $unpostedVch->id,
            'ledger_id' => $ledger->id,
            'type' => 'credit',
            'amount' => 500,
        ]);

        // Voucher before fromDate
        AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JRN-BEFORE',
            'voucher_date' => '2026-05-01',
            'is_posted' => true,
        ]);

        // Voucher after toDate
        AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JRN-AFTER',
            'voucher_date' => '2026-05-25',
            'is_posted' => true,
        ]);

        // Other company voucher in range
        AccountingVoucher::create([
            'company_id' => $otherCompany->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JRN-OTHER',
            'voucher_date' => '2026-05-15',
            'is_posted' => true,
        ]);

        $xml = $exporter->exportVouchersXml($company, '2026-05-10', '2026-05-22');
        $parsed = simplexml_load_string($xml);

        $messages = $parsed->BODY->IMPORTDATA->REQUESTDATA->TALLYMESSAGE;
        $this->assertCount(2, $messages);
        $this->assertEquals('JRN-01', (string) $messages[0]->VOUCHER->VOUCHERNUMBER);
        $this->assertEquals('JRN-02', (string) $messages[1]->VOUCHER->VOUCHERNUMBER);
    }

    public function test_tally_xml_export_vouchers_header_and_optional_fields(): void
    {
        $exporter = new TallyXmlExportService;
        $company = Company::create(['name' => 'Expressway & Transport Co']);

        $ledger = Ledger::create([
            'company_id' => $company->id,
            'name' => 'Bank Account',
            'is_active' => true,
        ]);

        // Voucher with reference and narration
        $vchFull = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'VCH-FULL-01',
            'voucher_date' => '2026-06-15',
            'reference_number' => 'CHQ-987654',
            'narration' => 'Paid advance via Cheque & RTGS <Ref:123>',
            'is_posted' => true,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $vchFull->id,
            'ledger_id' => $ledger->id,
            'type' => 'debit',
            'amount' => 12000,
        ]);

        // Voucher without reference and narration
        $vchMinimal = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'receipt',
            'voucher_number' => 'VCH-MIN-02',
            'voucher_date' => '2026-06-16',
            'reference_number' => null,
            'narration' => null,
            'is_posted' => true,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $vchMinimal->id,
            'ledger_id' => $ledger->id,
            'type' => 'credit',
            'amount' => 5000,
        ]);

        $xml = $exporter->exportVouchersXml($company, '2026-06-01', '2026-06-30');
        $parsed = simplexml_load_string($xml);

        $messages = $parsed->BODY->IMPORTDATA->REQUESTDATA->TALLYMESSAGE;
        $this->assertCount(2, $messages);

        $node1 = $messages[0]->VOUCHER;
        $this->assertEquals('Payment', (string) $node1['VCHTYPE']);
        $this->assertEquals('Create', (string) $node1['ACTION']);
        $this->assertEquals('20260615', (string) $node1->DATE);
        $this->assertEquals('Payment', (string) $node1->VOUCHERTYPENAME);
        $this->assertEquals('VCH-FULL-01', (string) $node1->VOUCHERNUMBER);
        $this->assertEquals('CHQ-987654', (string) $node1->REFERENCE);
        $this->assertEquals('Paid advance via Cheque & RTGS <Ref:123>', (string) $node1->NARRATION);

        $node2 = $messages[1]->VOUCHER;
        $this->assertEquals('Receipt', (string) $node2['VCHTYPE']);
        $this->assertEquals('20260616', (string) $node2->DATE);
        $this->assertEquals('Receipt', (string) $node2->VOUCHERTYPENAME);
        $this->assertEquals('VCH-MIN-02', (string) $node2->VOUCHERNUMBER);
        $this->assertFalse(isset($node2->REFERENCE));
        $this->assertFalse(isset($node2->NARRATION));
    }

    public function test_tally_xml_export_vouchers_debit_credit_amounts_and_fallback_ledger_name(): void
    {
        $exporter = new TallyXmlExportService;
        $company = Company::create(['name' => 'Ledger Item Test Co']);

        $drLedger = Ledger::create([
            'company_id' => $company->id,
            'name' => 'Freight Expense & Charges',
            'is_active' => true,
        ]);

        $voucher = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JRN-MULTI-01',
            'voucher_date' => '2026-07-01',
            'is_posted' => true,
        ]);

        // Debit item
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher->id,
            'ledger_id' => $drLedger->id,
            'type' => 'debit',
            'amount' => 4500.25,
        ]);

        // Credit item with nonexistent ledger (testing 'Unknown Ledger' fallback)
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher->id,
            'ledger_id' => 999999,
            'type' => 'credit',
            'amount' => 4500.25,
        ]);

        $xml = $exporter->exportVouchersXml($company, '2026-07-01', '2026-07-05');
        $parsed = simplexml_load_string($xml);

        $vch = $parsed->BODY->IMPORTDATA->REQUESTDATA->TALLYMESSAGE->VOUCHER;
        $entries = $vch->{'ALLLEDGERENTRIES.LIST'};
        $this->assertCount(2, $entries);

        // Debit entry checks: Tally convention debits are negative
        $this->assertEquals('Freight Expense & Charges', (string) $entries[0]->LEDGERNAME);
        $this->assertEquals('Yes', (string) $entries[0]->ISDEEMEDPOSITIVE);
        $this->assertEquals('-4500.25', (string) $entries[0]->AMOUNT);

        // Credit entry checks: credits are positive
        $this->assertEquals('Unknown Ledger', (string) $entries[1]->LEDGERNAME);
        $this->assertEquals('No', (string) $entries[1]->ISDEEMEDPOSITIVE);
        $this->assertEquals('4500.25', (string) $entries[1]->AMOUNT);
    }

    public function test_tally_xml_export_vouchers_with_cost_centre_allocations(): void
    {
        $exporter = new TallyXmlExportService;
        $company = Company::create(['name' => 'Cost Centre Fleet Co']);

        $fuelLedger = Ledger::create([
            'company_id' => $company->id,
            'name' => 'Diesel Expense',
            'is_active' => true,
        ]);
        $cashLedger = Ledger::create([
            'company_id' => $company->id,
            'name' => 'Cash Account',
            'is_active' => true,
        ]);

        $costCentre = CostCentre::create([
            'company_id' => $company->id,
            'category' => 'truck',
            'name' => 'Truck DL01AB9999 & Trailer',
        ]);

        $voucher = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'VCH-CC-01',
            'voucher_date' => '2026-08-10',
            'is_posted' => true,
        ]);

        // Item 1: with Cost Centre (debit)
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher->id,
            'ledger_id' => $fuelLedger->id,
            'type' => 'debit',
            'amount' => 7000.00,
            'cost_centre_id' => $costCentre->id,
        ]);

        // Item 2: without Cost Centre (credit)
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher->id,
            'ledger_id' => $cashLedger->id,
            'type' => 'credit',
            'amount' => 7000.00,
            'cost_centre_id' => null,
        ]);

        $xml = $exporter->exportVouchersXml($company, '2026-08-01', '2026-08-15');
        $parsed = simplexml_load_string($xml);

        $vch = $parsed->BODY->IMPORTDATA->REQUESTDATA->TALLYMESSAGE->VOUCHER;
        $entries = $vch->{'ALLLEDGERENTRIES.LIST'};
        $this->assertCount(2, $entries);

        // Entry 1 has Cost Centre Allocation
        $this->assertTrue(isset($entries[0]->{'CATEGORYALLOCATIONS.LIST'}));
        $catAlloc = $entries[0]->{'CATEGORYALLOCATIONS.LIST'};
        $this->assertEquals('Primary Cost Category', (string) $catAlloc->CATEGORY);

        $costAlloc = $catAlloc->{'COSTCENTREALLOCATIONS.LIST'};
        $this->assertEquals('Truck DL01AB9999 & Trailer', (string) $costAlloc->NAME);
        $this->assertEquals('-7000', (string) $costAlloc->AMOUNT);

        // Entry 2 does NOT have Cost Centre Allocation
        $this->assertFalse(isset($entries[1]->{'CATEGORYALLOCATIONS.LIST'}));
    }

    public function test_double_entry_balance_calculation_math(): void
    {
        $ledger = new Ledger;
        $ledger->opening_balance = 1000.00;
        $ledger->opening_balance_type = 'debit';

        $this->assertSame('debit', $ledger->opening_balance_type);
        $this->assertEquals(1000.00, $ledger->opening_balance);
    }

    public function test_chart_of_accounts_provisioning(): void
    {
        $company = Company::create(['name' => 'Apex Transport Co']);
        $provisioner = new ChartOfAccountsProvisioner;
        $provisioner->provisionForCompany($company);

        $this->assertTrue(AccountGroup::where('company_id', $company->id)->where('name', 'Current Assets')->exists());
        $this->assertTrue(AccountGroup::where('company_id', $company->id)->where('name', 'Sundry Debtors')->exists());
        $this->assertTrue(AccountGroup::where('company_id', $company->id)->where('name', 'Sundry Creditors')->exists());
        $this->assertTrue(AccountGroup::where('company_id', $company->id)->where('name', 'Direct Incomes')->exists());
        $this->assertTrue(AccountGroup::where('company_id', $company->id)->where('name', 'Direct Expenses')->exists());

        // Check system ledgers
        $this->assertTrue(Ledger::where('company_id', $company->id)->where('code', 'CASH')->exists());
        $this->assertTrue(Ledger::where('company_id', $company->id)->where('code', 'FR_INCOME')->exists());
        $this->assertTrue(Ledger::where('company_id', $company->id)->where('code', 'FR_EXPENSE')->exists());
        $this->assertTrue(Ledger::where('company_id', $company->id)->where('code', 'TDS_194C')->exists());
        $this->assertTrue(Ledger::where('company_id', $company->id)->where('code', 'TDS_194C_REC')->exists());
        $this->assertTrue(Ledger::where('company_id', $company->id)->where('code', 'CUSTOMER_DEDUCTIONS')->exists());
    }

    public function test_voucher_balance_validation(): void
    {
        $company = Company::create(['name' => 'Fast Express']);
        $provisioner = new ChartOfAccountsProvisioner;
        $provisioner->provisionForCompany($company);

        $cashLedger = Ledger::where('company_id', $company->id)->where('code', 'CASH')->first();
        $expenseLedger = Ledger::where('company_id', $company->id)->where('code', 'FR_EXPENSE')->first();

        // 1. Balanced Voucher
        $voucher = \App\Models\AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'TEST-001',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 5000.00,
            'is_posted' => true,
        ]);

        \App\Models\AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher->id,
            'ledger_id' => $expenseLedger->id,
            'type' => 'debit',
            'amount' => 5000.00,
        ]);

        \App\Models\AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher->id,
            'ledger_id' => $cashLedger->id,
            'type' => 'credit',
            'amount' => 5000.00,
        ]);

        $this->assertTrue($voucher->isBalanced());

        // 2. Unbalanced Voucher
        $unbalanced = \App\Models\AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'TEST-002',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 5000.00,
            'is_posted' => true,
        ]);

        \App\Models\AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $unbalanced->id,
            'ledger_id' => $expenseLedger->id,
            'type' => 'debit',
            'amount' => 5000.00,
        ]);

        \App\Models\AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $unbalanced->id,
            'ledger_id' => $cashLedger->id,
            'type' => 'credit',
            'amount' => 4500.00,
        ]);

        $this->assertFalse($unbalanced->isBalanced());
    }

    public function test_financial_report_day_book_and_trial_balance(): void
    {
        $company = Company::create(['name' => 'Global Logistics']);
        $provisioner = new ChartOfAccountsProvisioner;
        $provisioner->provisionForCompany($company);

        $cashLedger = Ledger::where('company_id', $company->id)->where('code', 'CASH')->first();
        $incomeLedger = Ledger::where('company_id', $company->id)->where('code', 'FR_INCOME')->first();

        $voucher = \App\Models\AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'receipt',
            'voucher_number' => 'RCPT-999',
            'voucher_date' => '2026-09-11',
            'total_amount' => 15000.00,
            'is_posted' => true,
        ]);

        \App\Models\AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher->id,
            'ledger_id' => $cashLedger->id,
            'type' => 'debit',
            'amount' => 15000.00,
        ]);

        \App\Models\AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher->id,
            'ledger_id' => $incomeLedger->id,
            'type' => 'credit',
            'amount' => 15000.00,
        ]);

        $reportService = new FinancialReportService;
        $dayBook = $reportService->getDayBook($company, '2026-09-11', '2026-09-11');
        $this->assertCount(1, $dayBook);
        $this->assertEquals(15000.00, $dayBook->first()['amount']);

        $tb = $reportService->getTrialBalance($company, '2026-09-11');
        $this->assertTrue($tb['is_balanced']);
        $this->assertEquals(15000.00, $tb['total_debit']);
        $this->assertEquals(15000.00, $tb['total_credit']);
    }

    public function test_post_advance_payment_creates_balanced_payment_voucher(): void
    {
        $company = Company::create(['name' => 'Fast Cargo Pvt Ltd']);
        $master = TransporterMaster::create(['name' => 'Shree Ram Transport']);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'advance_amount' => 25000.00,
        ]);

        $advancePayment = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 25000.00,
            'payment_date' => '2026-09-15',
            'remark' => 'Hiring advance for trip',
        ]);

        $provisioner = new ChartOfAccountsProvisioner;
        $postingService = new AccountingPostingService($provisioner);

        $voucher = $postingService->postAdvancePayment($advancePayment);

        $this->assertNotNull($voucher);
        $this->assertEquals('payment', $voucher->voucher_type);
        $this->assertEquals(25000.00, $voucher->total_amount);
        $this->assertTrue($voucher->isBalanced());

        $debitItem = $voucher->items->where('type', 'debit')->first();
        $creditItem = $voucher->items->where('type', 'credit')->first();

        $this->assertEquals('ADV_TRANSPORTER', $debitItem->ledger->code);
        $this->assertEquals(25000.00, $debitItem->amount);
        $this->assertEquals(25000.00, $creditItem->amount);
    }

    public function test_post_freight_booking_accounts_for_shortage_damage_deductions_and_tds(): void
    {
        $company = Company::create(['name' => 'Northern Carriers']);
        $master = TransporterMaster::create(['name' => 'Kisan Logistics']);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'tds_percentage' => 1.0,
        ]);
        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'RJ14-GA-1234',
        ]);

        $booking = FreightBooking::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'truck_id' => $truck->id,
            'tr_amount' => 50000.00,
            'detention' => 2000.00,
            'deviation' => 1000.00,
            'damage_amount' => 1500.00,
            'shortage_amount' => 500.00,
            'tds_amount' => 500.00,
            'voucher_number' => 'LR-9988',
        ]);

        $provisioner = new ChartOfAccountsProvisioner;
        $postingService = new AccountingPostingService($provisioner);

        $voucher = $postingService->postFreightBooking($booking);

        $this->assertNotNull($voucher);
        $this->assertEquals('purchase', $voucher->voucher_type);
        // Total hiring expense = 50000 + 2000 + 1000 = 53000
        $this->assertEquals(53000.00, $voucher->total_amount);
        $this->assertTrue($voucher->isBalanced());

        // Debits: Freight expense = 53000
        $debitItem = $voucher->items->where('type', 'debit')->first();
        $this->assertEquals(53000.00, $debitItem->amount);

        // Credits:
        // Recovery (Damage 1500 + Shortage 500) = 2000
        $recoveryItem = $voucher->items->where('type', 'credit')->first(fn ($i) => $i->ledger->code === 'SHORTAGE_REC');
        $this->assertNotNull($recoveryItem);
        $this->assertEquals(2000.00, $recoveryItem->amount);

        // TDS = 500
        $tdsItem = $voucher->items->where('type', 'credit')->first(fn ($i) => $i->ledger->code === 'TDS_194C');
        $this->assertNotNull($tdsItem);
        $this->assertEquals(500.00, $tdsItem->amount);

        // Net Transporter Credit = 53000 - 2000 - 500 = 50500
        $transporterItem = $voucher->items->where('type', 'credit')->first(fn ($i) => str_contains($i->ledger->name, 'Kisan Logistics'));
        $this->assertNotNull($transporterItem);
        $this->assertEquals(50500.00, $transporterItem->amount);
    }

    public function test_post_voucher_payment_adjust_in_advance_does_not_credit_bank(): void
    {
        $company = Company::create(['name' => 'Express Line Logistics']);
        $master = TransporterMaster::create(['name' => 'Royal Transport']);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
        ]);
        $booking = FreightBooking::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'tr_amount' => 40000.00,
        ]);

        $vp = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'amount' => 10000.00,
            'payment_mode' => 'adjust in advance',
        ]);

        $provisioner = new ChartOfAccountsProvisioner;
        $postingService = new AccountingPostingService($provisioner);

        $voucher = $postingService->postVoucherPayment($vp);

        $this->assertNotNull($voucher);
        $this->assertEquals('journal', $voucher->voucher_type);
        $this->assertTrue($voucher->isBalanced());
        $bankItem = $voucher->items->first(fn ($item) => $item->ledger?->code === 'BANK_PRIMARY');
        $this->assertNull($bankItem);
    }

    public function test_fuel_pump_payment_credits_pump_ledger_not_bank(): void
    {
        $company = Company::create(['name' => 'Indian Oil Logistics']);
        $pump = Pump::create([
            'pump_name' => 'Highway Fuel Station',
        ]);

        $master = TransporterMaster::create(['name' => 'Speedy Cargo']);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
        ]);
        $booking = FreightBooking::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'tr_amount' => 50000.00,
        ]);

        $vp = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'pump_id' => $pump->id,
            'amount' => 15000.00,
            'payment_mode' => 'fuel(paid to pump)',
        ]);

        $provisioner = new ChartOfAccountsProvisioner;
        $postingService = new AccountingPostingService($provisioner);

        $voucher = $postingService->postVoucherPayment($vp);

        $this->assertNotNull($voucher);
        $this->assertTrue($voucher->isBalanced());

        // Find credit item - it MUST be the Pump Ledger, NOT the Bank!
        $creditItem = $voucher->items->firstWhere('type', 'credit');
        $this->assertNotNull($creditItem);
        $this->assertStringContainsString('Highway Fuel Station', $creditItem->ledger->name);
        $this->assertNotEquals('BANK_PRIMARY', $creditItem->ledger->code);
    }

    public function test_profit_and_loss_direct_classification_and_trading_balance(): void
    {
        $company = Company::create(['name' => 'Star Roadlines']);
        $provisioner = new ChartOfAccountsProvisioner;
        $provisioner->provisionForCompany($company);

        // Provisioner creates Freight Revenue and Transporter Freight Hiring Charges
        $freightIncomeLedger = Ledger::where('company_id', $company->id)->where('code', 'FR_INCOME')->first();
        $freightHiringLedger = Ledger::where('company_id', $company->id)->where('code', 'FR_EXPENSE')->first();

        $this->assertNotNull($freightIncomeLedger);
        $this->assertNotNull($freightHiringLedger);

        // Record a Sales Voucher (Direct Income)
        $voucher1 = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'sales',
            'voucher_number' => 'SALES-01',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 100000.00,
            'is_posted' => true,
        ]);
        $cashLedger = Ledger::where('company_id', $company->id)->where('code', 'CASH')->first();
        $voucher1->items()->create(['company_id' => $company->id, 'ledger_id' => $cashLedger->id, 'type' => 'debit', 'amount' => 100000.00]);
        $voucher1->items()->create(['company_id' => $company->id, 'ledger_id' => $freightIncomeLedger->id, 'type' => 'credit', 'amount' => 100000.00]);

        // Record a Purchase/Hiring Voucher (Direct Expense)
        $voucher2 = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'purchase',
            'voucher_number' => 'PUR-01',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 70000.00,
            'is_posted' => true,
        ]);
        $voucher2->items()->create(['company_id' => $company->id, 'ledger_id' => $freightHiringLedger->id, 'type' => 'debit', 'amount' => 70000.00]);
        $voucher2->items()->create(['company_id' => $company->id, 'ledger_id' => $cashLedger->id, 'type' => 'credit', 'amount' => 70000.00]);

        $reportService = new FinancialReportService;
        $pnl = $reportService->getProfitAndLoss($company);

        // Freight income must be classified as Direct Income (100,000)
        $this->assertEquals(100000.00, $pnl['total_direct_income']);
        // Freight hiring must be classified as Direct Expense (70,000)
        $this->assertEquals(70000.00, $pnl['total_direct_expense']);
        // Gross profit = 30,000
        $this->assertEquals(30000.00, $pnl['gross_profit']);
        // Trading total = 100,000
        $this->assertEquals(100000.00, $pnl['trading_total']);
        // Net profit = 30,000
        $this->assertEquals(30000.00, $pnl['net_profit']);
    }

    public function test_negative_cash_balance_and_ledger_search_query(): void
    {
        $company = Company::create(['name' => 'Fast Wheels Transport']);
        $provisioner = new ChartOfAccountsProvisioner;
        $provisioner->provisionForCompany($company);

        $cashLedger = Ledger::where('company_id', $company->id)->where('code', 'CASH')->first();
        $ownerGroup = AccountGroup::where('company_id', $company->id)->where('name', 'Truck Owners')->first();
        $this->assertNotNull($ownerGroup);

        $truckOwnerLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $ownerGroup->id,
            'name' => 'Ramesh Trucking (Truck Owner)',
            'opening_balance' => 5000.00,
            'opening_balance_type' => 'credit',
            'opening_balance_reference' => 'OB-TRUCK-01',
        ]);

        // Cash spent without enough opening balance -> negative cash
        $voucher = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'PMT-01',
            'voucher_date' => now()->toDateString(),
            'reference_number' => 'Truck: MH12AB1234 | LR: 9988',
            'narration' => 'Trip payment for Truck MH12AB1234',
            'total_amount' => 15000.00,
            'is_posted' => true,
        ]);
        $voucher->items()->create(['company_id' => $company->id, 'ledger_id' => $truckOwnerLedger->id, 'type' => 'debit', 'amount' => 15000.00]);
        $voucher->items()->create(['company_id' => $company->id, 'ledger_id' => $cashLedger->id, 'type' => 'credit', 'amount' => 15000.00]);

        $reportService = new FinancialReportService;

        // 1. Cash statement has is_negative_cash = true
        $cashStmt = $reportService->getLedgerStatement($cashLedger, now()->startOfMonth()->toDateString(), now()->toDateString());
        $this->assertTrue($cashStmt['closing_balance']['is_negative_cash']);

        // 2. Search statement by Truck number
        $ownerStmtFiltered = $reportService->getLedgerStatement($truckOwnerLedger, now()->startOfMonth()->toDateString(), now()->toDateString(), 'MH12AB1234');
        $this->assertTrue($ownerStmtFiltered['is_filtered']);
        $this->assertCount(1, $ownerStmtFiltered['rows']);
        $this->assertEquals('MH12AB1234', $ownerStmtFiltered['rows'][0]['truck_number']);
        $this->assertEquals('9988', $ownerStmtFiltered['rows'][0]['lr_number']);

        // 3. Search statement with non-matching query returns 0 rows
        $ownerStmtNoMatch = $reportService->getLedgerStatement($truckOwnerLedger, now()->startOfMonth()->toDateString(), now()->toDateString(), 'NONEXISTENT');
        $this->assertCount(0, $ownerStmtNoMatch['rows']);
        $this->assertEquals(1, $ownerStmtNoMatch['unfiltered_count']);
    }

    public function test_advance_payment_observer_and_deletion(): void
    {
        $company = Company::create(['name' => 'Observer Test Transport']);
        $master = TransporterMaster::create(['name' => 'Observer Transporter Co']);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
        ]);

        // Creating should trigger AdvancePaymentObserver
        $advance = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 12000.00,
            'payment_date' => '2026-09-15',
            'remark' => 'Test advance for observer',
        ]);

        $voucher = AccountingVoucher::where('source_type', AdvancePayment::class)
            ->where('source_id', $advance->id)
            ->first();

        $this->assertNotNull($voucher);
        $this->assertEquals(12000.00, $voucher->total_amount);

        // Deleting should trigger observer to remove the voucher
        $advance->delete();

        $this->assertSoftDeleted('accounting_vouchers', [
            'id' => $voucher->id,
        ]);
        $this->assertNull(AccountingVoucher::find($voucher->id));
    }

    public function test_invoice_payment_with_tds_and_deductions_posts_balanced_voucher_and_clears_customer(): void
    {
        $company = Company::create(['name' => 'CA Approved Freight Ltd']);
        $provisioner = new ChartOfAccountsProvisioner;
        $provisioner->provisionForCompany($company);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Tata Steel Logistics',
            'contact_number' => '9876543210',
            'gst_number' => '27AAAAA0000A1Z5',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2026-001',
            'manual_invoice_number' => 'INV-001',
            'total_amount' => 100000.00,
            'base_amount' => 100000.00,
            'generated_date' => '2026-09-01',
            'payment_status' => 'pending',
        ]);

        // Remittance: 95,000, TDS u/s 194C (2%): 2,000, Shortage / Damage: 3,000 => Total Settled = 100,000
        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-09-15',
            'mode' => 'Bank Transfer',
            'amount' => 95000.00,
            'tds_amount' => 2000.00,
            'deduction_amount' => 3000.00,
            'deduction_reason' => 'Transit shortage 2 bags',
            'remark' => 'Full clearance after deductions',
        ]);

        $postingService = new AccountingPostingService($provisioner);
        $voucher = $postingService->postInvoicePayment($payment);

        $this->assertNotNull($voucher);
        $this->assertEquals(100000.00, $voucher->total_amount);
        $this->assertTrue($voucher->isBalanced());

        $items = $voucher->items()->get();
        $this->assertCount(4, $items);

        // Verify Debits sum to ₹100,000
        $debits = $items->where('type', 'debit');
        $this->assertEquals(100000.00, $debits->sum('amount'));

        // Verify Credits sum to ₹100,000
        $credits = $items->where('type', 'credit');
        $this->assertEquals(100000.00, $credits->sum('amount'));

        // Verify individual ledgers
        $bankItem = $items->firstWhere('amount', 95000.00);
        $this->assertEquals('debit', $bankItem->type);

        $tdsItem = $items->firstWhere('amount', 2000.00);
        $this->assertEquals('debit', $tdsItem->type);
        $tdsLedger = Ledger::find($tdsItem->ledger_id);
        $this->assertEquals('TDS_194C_REC', $tdsLedger->code);

        $deductionItem = $items->firstWhere('amount', 3000.00);
        $this->assertEquals('debit', $deductionItem->type);
        $dedLedger = Ledger::find($deductionItem->ledger_id);
        $this->assertEquals('CUSTOMER_DEDUCTIONS', $dedLedger->code);

        $customerItem = $credits->first();
        $this->assertEquals(100000.00, $customerItem->amount);
        $customerLedger = Ledger::find($customerItem->ledger_id);
        $this->assertEquals($customer->id, $customerLedger->entity_id);

        // Verify Invoice paid amount and remaining balance
        $refreshedInvoice = Invoice::find($invoice->id);
        $this->assertEquals(100000.00, $refreshedInvoice->paid_amount);
        $this->assertEquals(0.00, $refreshedInvoice->remaining_amount);
    }

    public function test_transporter_advance_disbursement_debits_loans_and_advances_asset(): void
    {
        $provisioner = new ChartOfAccountsProvisioner;
        $company = Company::create(['name' => 'Advance Test Logistics', 'bank' => 'HDFC Bank', 'account_number' => '1234567890']);
        $provisioner->provisionForCompany($company);

        $master = TransporterMaster::create(['name' => 'Fast Wheels Transport']);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'advance_amount' => 5000.00,
        ]);

        $advancePayment = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 5000.00,
            'payment_date' => '2026-09-18',
        ]);

        $postingService = new AccountingPostingService($provisioner);
        $voucher = $postingService->postAdvancePayment($advancePayment);

        $this->assertNotNull($voucher);
        $this->assertEquals('payment', $voucher->voucher_type);
        $this->assertTrue($voucher->isBalanced());

        $debitItem = $voucher->items()->where('type', 'debit')->first();
        $this->assertNotNull($debitItem);
        $this->assertEquals(5000.00, $debitItem->amount);
        $advLedger = Ledger::find($debitItem->ledger_id);
        $this->assertEquals('ADV_TRANSPORTER', $advLedger->code);

        $creditItem = $voucher->items()->where('type', 'credit')->first();
        $this->assertNotNull($creditItem);
        $bankLedger = Ledger::find($creditItem->ledger_id);
        $this->assertEquals('BANK_PRIMARY', $bankLedger->code);
    }

    public function test_freight_booking_adjust_in_advance_debits_payable_and_credits_advance_asset(): void
    {
        $provisioner = new ChartOfAccountsProvisioner;
        $company = Company::create(['name' => 'Advance Adjustment Logistics']);
        $provisioner->provisionForCompany($company);

        $master = TransporterMaster::create(['name' => 'Om Roadlines']);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
        ]);

        $truck = Truck::create(['company_id' => $company->id, 'truck_number' => 'MH12AB1234']);
        $booking = FreightBooking::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'truck_id' => $truck->id,
            'tr_amount' => 20000.00,
        ]);

        $voucherPayment = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'amount' => 5000.00,
            'payment_type' => 'Advance',
            'payment_mode' => 'adjust in advance',
            'date' => '2026-09-19',
        ]);

        $postingService = new AccountingPostingService($provisioner);
        $voucher = $postingService->postVoucherPayment($voucherPayment);

        $this->assertNotNull($voucher);
        $this->assertEquals('journal', $voucher->voucher_type);
        $this->assertTrue($voucher->isBalanced());

        $debitItem = $voucher->items()->where('type', 'debit')->first();
        $creditItem = $voucher->items()->where('type', 'credit')->first();

        // Debit should be Transporter Payable (liability)
        $transporterLedger = Ledger::find($debitItem->ledger_id);
        $this->assertEquals($transporter->id, $transporterLedger->entity_id);

        // Credit should be Advance to Transporter (clearing the asset, NOT a wash entry)
        $advAssetLedger = Ledger::find($creditItem->ledger_id);
        $this->assertEquals('ADV_TRANSPORTER', $advAssetLedger->code);
        $this->assertNotEquals($debitItem->ledger_id, $creditItem->ledger_id);
    }

    public function test_transporter_with_194c6_declaration_receives_zero_tds_ledger_rate(): void
    {
        $provisioner = new ChartOfAccountsProvisioner;
        $company = Company::create(['name' => 'TDS Exemption Logistics']);
        $provisioner->provisionForCompany($company);

        $master = TransporterMaster::create([
            'name' => 'Sharma Fleet Carrier',
            'pan_number' => 'ABCPS1234F',
        ]);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'declaration_given' => true,
            'declaration_194c6_date' => '2026-04-01',
            'tds_percentage' => 0.00,
        ]);

        $postingService = new AccountingPostingService($provisioner);
        $transporterLedger = $postingService->getOrCreatePartyLedger($company, $transporter);

        $this->assertEquals(0.00, (float) $transporterLedger->tds_rate);
    }

    public function test_statutory_tds_payment_challan_itns_281_persists_challan_fields(): void
    {
        $provisioner = new ChartOfAccountsProvisioner;
        $company = Company::create(['name' => 'Statutory Challan Logistics', 'bank' => 'SBI Bank', 'account_number' => '99887766']);
        $provisioner->provisionForCompany($company);

        $tdsPayableLedger = Ledger::where('company_id', $company->id)->where('code', 'TDS_194C')->first();
        $bankLedger = Ledger::where('company_id', $company->id)->where('code', 'BANK_PRIMARY')->first();

        $voucher = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'PAY-ITNS281-001',
            'voucher_date' => '2026-09-19',
            'total_amount' => 15000.00,
            'narration' => 'TDS deposit u/s 194C for August 2026',
            'challan_bsr_code' => '0210045',
            'challan_serial_no' => '00482',
            'challan_tender_date' => '2026-09-19',
            'challan_head' => '0021',
        ]);

        $voucher->items()->create([
            'company_id' => $company->id,
            'ledger_id' => $tdsPayableLedger->id,
            'type' => 'debit',
            'amount' => 15000.00,
        ]);
        $voucher->items()->create([
            'company_id' => $company->id,
            'ledger_id' => $bankLedger->id,
            'type' => 'credit',
            'amount' => 15000.00,
        ]);

        $this->assertEquals('0210045', $voucher->challan_bsr_code);
        $this->assertEquals('00482', $voucher->challan_serial_no);
        $this->assertEquals('0021', $voucher->challan_head);
        $this->assertTrue($voucher->isBalanced());
    }

    public function test_gstr1_report_correctly_partitions_fcm_table_4a_and_rcm_table_4b(): void
    {
        $company = Company::create(['name' => 'GSTR1 Logistics']);
        $customer1 = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Tata Motors Ltd',
            'gst_number' => '27AAACT1234F1Z1',
        ]);
        $customer2 = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Reliance Retail Ltd',
            'gst_number' => '27AAACR5678G1Z2',
        ]);

        // Invoice 1: Forward Charge (FCM Table 4A)
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer1->id,
            'invoice_number' => 'INV-FCM-101',
            'invoice_type' => 'Forward',
            'generated_date' => '2026-09-10',
            'base_amount' => 50000.00,
            'cgst' => 3000.00,
            'sgst' => 3000.00,
            'igst' => 0.00,
            'total_amount' => 56000.00,
            'net_amount' => 56000.00,
        ]);

        // Invoice 2: Reverse Charge (GTA RCM Table 4B)
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer2->id,
            'invoice_number' => 'INV-RCM-202',
            'invoice_type' => 'Reverse',
            'generated_date' => '2026-09-12',
            'base_amount' => 80000.00,
            'cgst' => 0.00,
            'sgst' => 0.00,
            'igst' => 0.00,
            'total_amount' => 80000.00,
            'net_amount' => 80000.00,
        ]);

        $reportService = new FinancialReportService;
        $report = $reportService->getGstr1Report($company, '2026-09-01', '2026-09-30');

        $this->assertCount(1, $report['table_4a']);
        $this->assertCount(1, $report['table_4b']);

        $row4A = $report['table_4a']->first();
        $this->assertEquals('INV-FCM-101', $row4A['invoice_number']);
        $this->assertEquals('No', $row4A['reverse_charge']);
        $this->assertEquals(50000.00, $row4A['taxable_value']);
        $this->assertEquals(6000.00, $row4A['total_tax']);

        $row4B = $report['table_4b']->first();
        $this->assertEquals('INV-RCM-202', $row4B['invoice_number']);
        $this->assertEquals('Yes', $row4B['reverse_charge']);
        $this->assertEquals(80000.00, $row4B['taxable_value']);
        $this->assertEquals(80000.00, $row4B['invoice_value']);
    }

    public function test_multi_bank_ledger_selection_for_invoice_and_voucher_payments(): void
    {
        $provisioner = new ChartOfAccountsProvisioner;
        $company = Company::create(['name' => 'Multi Bank Logistics']);
        $provisioner->provisionForCompany($company);

        $bankGroup = AccountGroup::where('company_id', $company->id)->where('name', 'Bank Accounts')->first();
        $hdfcLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $bankGroup->id,
            'name' => 'HDFC Current A/c 9876',
            'code' => 'HDFC_CURRENT',
            'opening_balance_type' => 'debit',
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Ultratech Cement',
        ]);
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'total_amount' => 40000.00,
            'net_amount' => 40000.00,
            'generated_date' => '2026-09-05',
        ]);

        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'payment_date' => '2026-09-15',
            'mode' => 'Bank Transfer',
            'amount' => 40000.00,
            'bank_ledger_id' => $hdfcLedger->id,
        ]);

        $postingService = new AccountingPostingService($provisioner);
        $voucher = $postingService->postInvoicePayment($payment);

        $this->assertNotNull($voucher);
        $debitItem = $voucher->items()->where('type', 'debit')->first();
        $this->assertEquals($hdfcLedger->id, $debitItem->ledger_id);
    }

    public function test_credit_note_and_debit_note_accounting_posting(): void
    {
        $provisioner = new ChartOfAccountsProvisioner;
        $company = Company::create(['name' => 'Notes Logistics']);
        $provisioner->provisionForCompany($company);

        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'JSW Steel Ltd']);
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2026-999',
            'total_amount' => 50000.00,
            'net_amount' => 50000.00,
            'generated_date' => '2026-09-01',
        ]);

        $postingService = new AccountingPostingService($provisioner);

        // Post Credit Note: ₹5,000 rebate/adjustment given to customer
        $crVoucher = $postingService->postCreditNote($invoice, 5000.00, 'Rate difference agreed discount');
        $this->assertNotNull($crVoucher);
        $this->assertEquals('credit_note', $crVoucher->voucher_type);
        $this->assertEquals(5000.00, $crVoucher->total_amount);
        $this->assertTrue($crVoucher->isBalanced());

        $crDebit = $crVoucher->items()->where('type', 'debit')->first();
        $crCredit = $crVoucher->items()->where('type', 'credit')->first();
        $this->assertEquals('FR_INCOME', Ledger::find($crDebit->ledger_id)->code);
        $this->assertEquals($customer->id, Ledger::find($crCredit->ledger_id)->entity_id);

        // Post Debit Note: ₹2,000 deduction on transporter for goods damaged
        $master = TransporterMaster::create(['name' => 'Khurana Roadways']);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
        ]);

        $dbVoucher = $postingService->postDebitNote($transporter, 2000.00, 'Damage in transit LR #4567');
        $this->assertNotNull($dbVoucher);
        $this->assertEquals('debit_note', $dbVoucher->voucher_type);
        $this->assertEquals(2000.00, $dbVoucher->total_amount);
        $this->assertTrue($dbVoucher->isBalanced());

        $dbDebit = $dbVoucher->items()->where('type', 'debit')->first();
        $dbCredit = $dbVoucher->items()->where('type', 'credit')->first();
        $this->assertEquals($transporter->id, Ledger::find($dbDebit->ledger_id)->entity_id);
        $this->assertEquals('SHORTAGE_REC', Ledger::find($dbCredit->ledger_id)->code);
    }

    public function test_debtors_aging_report_categorizes_buckets_and_triggers_msme_alert(): void
    {
        $company = Company::create(['name' => 'Aging Logistics']);
        $customer1 = Consignee::create(['company_id' => $company->id, 'name' => 'Fresh Supplies']);
        $customer2 = Consignee::create(['company_id' => $company->id, 'name' => 'Overdue Industries (MSME)']);

        // Invoice 1: 15 days old (generated 2026-09-04, as of 2026-09-19) -> 0-30 days
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer1->id,
            'invoice_number' => 'INV-RECENT-01',
            'generated_date' => '2026-09-04',
            'total_amount' => 25000.00,
            'net_amount' => 25000.00,
            'payment_status' => 'pending',
        ]);

        // Invoice 2: 60 days old (generated 2026-07-21, as of 2026-09-19) -> > 45 days MSME alert
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer2->id,
            'invoice_number' => 'INV-OLD-02',
            'generated_date' => '2026-07-21',
            'total_amount' => 75000.00,
            'net_amount' => 75000.00,
            'payment_status' => 'pending',
        ]);

        $reportService = new FinancialReportService;
        $report = $reportService->getDebtorsAgingReport($company, '2026-09-19');

        $this->assertEquals(100000.00, $report['totals']['total_receivables']);
        $this->assertEquals(25000.00, $report['totals']['total_0_30']);
        $this->assertEquals(75000.00, $report['totals']['total_31_60']);
        $this->assertEquals(75000.00, $report['totals']['total_msme_overdue']);
        $this->assertEquals(1, $report['totals']['msme_alert_customers_count']);

        $msmeCustomer = $report['customers']->firstWhere('customer_id', $customer2->id);
        $this->assertTrue($msmeCustomer['has_msme_alert']);
        $this->assertEquals(75000.00, $msmeCustomer['msme_overdue_amount']);
    }

    public function test_bill_to_bill_outstandings_and_unfiltered_count(): void
    {
        $company = Company::create(['name' => 'Bill Outstandings Transport']);
        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Outstandings Customer',
        ]);

        $ledger = Ledger::create([
            'company_id' => $company->id,
            'name' => 'Outstandings Customer Ledger',
            'entity_type' => Consignee::class,
            'entity_id' => $customer->id,
        ]);

        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-OUT-01',
            'generated_date' => '2026-09-05',
            'total_amount' => 50000.00,
            'net_amount' => 50000.00,
            'payment_status' => 'pending',
        ]);

        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-OUT-02',
            'generated_date' => '2026-09-10',
            'total_amount' => 30000.00,
            'net_amount' => 30000.00,
            'payment_status' => 'pending',
        ]);

        $reportService = new FinancialReportService;
        $outstandings = $reportService->getBillToBillOutstandings($ledger);

        $this->assertNotNull($outstandings);
        $this->assertEquals('Customer', $outstandings['entity_type']);
        $this->assertArrayHasKey('unfiltered_count', $outstandings);
        $this->assertEquals(2, $outstandings['unfiltered_count']);
        $this->assertEquals(2, $outstandings['total_count']);
        $this->assertEquals(80000.00, $outstandings['total_billed']);
        $this->assertEquals(80000.00, $outstandings['total_pending']);

        // Check with filter
        $filteredPending = $reportService->getBillToBillOutstandings($ledger, 'pending');
        $this->assertEquals(2, $filteredPending['unfiltered_count']);
        $this->assertCount(2, $filteredPending['bills']);

        $filteredSettled = $reportService->getBillToBillOutstandings($ledger, 'settled');
        $this->assertEquals(2, $filteredSettled['unfiltered_count']);
        $this->assertCount(0, $filteredSettled['bills']);
    }

    public function test_ledger_statement_view_renders_with_outstandings(): void
    {
        $company = Company::create(['name' => 'View Render Transport']);
        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'View Customer',
        ]);

        $ledger = Ledger::create([
            'company_id' => $company->id,
            'name' => 'View Customer Ledger',
            'entity_type' => Consignee::class,
            'entity_id' => $customer->id,
        ]);

        $reportService = new FinancialReportService;
        $statement = $reportService->getLedgerStatement($ledger, '2026-09-01', '2026-09-21');
        $outstandings = $reportService->getBillToBillOutstandings($ledger);

        $view = view('accounting.ledger_statement', [
            'company' => $company,
            'ledgers' => collect([$ledger]),
            'selectedLedgerId' => $ledger->id,
            'statement' => $statement,
            'outstandings' => $outstandings,
            'fromDate' => '2026-09-01',
            'toDate' => '2026-09-21',
            'searchQuery' => '',
            'billFilter' => null,
        ])->render();

        $this->assertStringContainsString('Bill-to-Bill Outstandings', $view);
        $this->assertStringContainsString('All (0)', $view);
        $this->assertStringContainsString('d-none d-print-block ledger-statement-print', $view);
        $this->assertStringContainsString('tally-print-header', $view);
        $this->assertStringContainsString('tally-statement-table', $view);
        $this->assertStringContainsString('tally-op-row', $view);
        $this->assertStringContainsString('tally-current-total-row', $view);
        $this->assertStringContainsString('tally-closing-row', $view);
        $this->assertStringContainsString('tally-total-row', $view);
        $this->assertStringContainsString('tally-signatures-row', $view);
        $this->assertStringContainsString('tally-print-footer', $view);
        $this->assertStringContainsString('Statement of Account', $view);
        $this->assertStringContainsString('d-print-none', $view);
        $this->assertStringContainsString('@media print', $view);
    }

    public function test_balance_sheet_view_renders_with_standard_balance_sheet_print_format(): void
    {
        $company = Company::create([
            'name' => 'Taldar Tech Consultancy Private Limited',
            'address' => 'Udaipur',
            'gst_number' => '08AAACA1234A1Z5',
            'pan_number' => 'AAACA1234A',
            'contact_number' => '9876543210',
        ]);

        $reportService = new FinancialReportService;
        $balanceSheet = $reportService->getBalanceSheet($company, '2026-09-21');

        $view = view('accounting.balance_sheet', [
            'company' => $company,
            'balanceSheet' => $balanceSheet,
            'asOfDate' => '2026-09-21',
        ])->render();

        // Ensure print view and headers matching reference image are present
        $this->assertStringContainsString('d-none d-print-block balance-sheet-print', $view);
        $this->assertStringContainsString('Taldar Tech Consultancy Private Limited', $view);
        $this->assertStringContainsString('Udaipur', $view);
        $this->assertStringContainsString('Balance Sheet', $view);
        $this->assertStringContainsString('Balance Sheet (Detailed Schedule)', $view);
        $this->assertStringContainsString('bs-summary-sheet', $view);
        $this->assertStringContainsString('bs-detailed-sheet', $view);
        $this->assertStringContainsString('tally-row-tr', $view);
        $this->assertStringContainsString('1-Apr-26 to 21-Sep-26', $view);
        $this->assertStringContainsString('L i a b i l i t i e s', $view);
        $this->assertStringContainsString('A s s e t s', $view);
        $this->assertStringContainsString('as at 21-Sep-26', $view);
        $this->assertStringContainsString('T o t a l', $view);
        $this->assertStringContainsString('tally-bs-table', $view);
        $this->assertStringContainsString('printBoth()', $view);
        $this->assertStringContainsString('printSummary()', $view);
        $this->assertStringContainsString('printDetailed()', $view);

        // Ensure screen elements are marked d-print-none
        $this->assertStringContainsString('card shadow-sm border-0 mb-3 d-print-none', $view);
        $this->assertStringContainsString('card app-index-list-card shadow-sm d-print-none', $view);
        $this->assertStringContainsString('@media print', $view);
    }

    public function test_profit_and_loss_view_renders_with_standard_profit_and_loss_print_format(): void
    {
        $company = Company::create([
            'name' => 'Taldar Tech Consultancy Private Limited',
            'address' => 'Udaipur',
            'gst_number' => '08AAACA1234A1Z5',
            'pan_number' => 'AAACA1234A',
            'contact_number' => '9876543210',
        ]);

        $reportService = new FinancialReportService;
        $pnl = $reportService->getProfitAndLoss($company, '2024-04-01', '2025-03-31');

        $this->assertArrayHasKey('expense_groups', $pnl);
        $this->assertArrayHasKey('income_groups', $pnl);
        $this->assertArrayHasKey('grand_total', $pnl);

        $view = view('accounting.profit_and_loss', [
            'company' => $company,
            'pnl' => $pnl,
            'fromDate' => '2024-04-01',
            'toDate' => '2025-03-31',
        ])->render();

        // Ensure print view and headers matching reference image are present
        $this->assertStringContainsString('d-none d-print-block pnl-print', $view);
        $this->assertStringContainsString('Taldar Tech Consultancy Private Limited', $view);
        $this->assertStringContainsString('<u>Udaipur</u>', $view);
        $this->assertStringContainsString('Profit & Loss A/c', $view);
        $this->assertStringContainsString('pnl-summary-sheet', $view);
        $this->assertStringContainsString('pnl-detailed-sheet', $view);
        $this->assertStringContainsString('tally-row-tr', $view);
        $this->assertStringContainsString('1-Apr-24 to 31-Mar-25', $view);
        $this->assertStringContainsString('P a r t i c u l a r s', $view);
        $this->assertStringContainsString('Purchase Accounts', $view);
        $this->assertStringContainsString('Nett Profit', $view);
        $this->assertStringContainsString('T o t a l', $view);
        $this->assertStringContainsString('tally-pnl-table', $view);
        $this->assertStringContainsString('printBoth()', $view);
        $this->assertStringContainsString('printSummary()', $view);
        $this->assertStringContainsString('printDetailed()', $view);

        // Ensure screen elements are marked d-print-none
        $this->assertStringContainsString('card shadow-sm border-0 mb-3 d-print-none', $view);
        $this->assertStringContainsString('card app-index-list-card shadow-sm d-print-none', $view);
        $this->assertStringContainsString('@media print', $view);
    }

    public function test_debtors_aging_report_print_format_and_structure(): void
    {
        $company = Company::create([
            'name' => 'Taldar Tech Consultancy Private Limited',
            'address' => 'Udaipur',
            'gst_number' => '08AAACA1234A1Z5',
            'pan_number' => 'AAACA1234A',
            'contact_number' => '9876543210',
        ]);

        $reportService = new FinancialReportService;
        $report = $reportService->getDebtorsAgingReport($company, '2026-09-21');

        $this->assertArrayHasKey('customers', $report);
        $this->assertArrayHasKey('totals', $report);

        $view = view('accounting.debtors_aging', [
            'company' => $company,
            'report' => $report,
            'asOfDate' => '2026-09-21',
        ])->render();

        // Dedicated print container
        $this->assertStringContainsString('d-none d-print-block aging-print', $view);

        // Header elements matching Tally authentic style
        $this->assertStringContainsString('Taldar Tech Consultancy Private Limited', $view);
        $this->assertStringContainsString('<u>Udaipur</u>', $view);
        $this->assertStringContainsString('Sundry Debtors Aging Schedule', $view);
        $this->assertStringContainsString('as at 21-Sep-26', $view);

        // Table headers and columns
        $this->assertStringContainsString('tally-aging-table', $view);
        $this->assertStringContainsString('Particulars', $view);
        $this->assertStringContainsString('GSTIN / Date', $view);
        $this->assertStringContainsString('Total Due', $view);
        $this->assertStringContainsString('0 - 30 Days', $view);
        $this->assertStringContainsString('31 - 60 Days', $view);
        $this->assertStringContainsString('61 - 90 Days', $view);
        $this->assertStringContainsString('&gt; 90 Days', $view);
        $this->assertStringContainsString('MSME', $view);

        // Footer total
        $this->assertStringContainsString('T o t a l', $view);
        $this->assertStringContainsString('tally-total-row', $view);

        // Ensure screen interactive elements are hidden from print
        $this->assertStringContainsString('card shadow-sm border-0 mb-3 d-print-none', $view);
        $this->assertStringContainsString('row g-3 mb-3 d-print-none', $view);
        $this->assertStringContainsString('card app-index-list-card shadow-sm d-print-none', $view);
        $this->assertStringContainsString('@media print', $view);
    }

    public function test_gstr1_report_print_format_and_structure(): void
    {
        $company = Company::create([
            'name' => 'Taldar Tech Consultancy Private Limited',
            'address' => 'Udaipur',
            'gst_number' => '08AAACA1234A1Z5',
            'pan_number' => 'AAACA1234A',
            'contact_number' => '9876543210',
        ]);

        $reportService = new FinancialReportService;
        $report = $reportService->getGstr1Report($company, '2026-04-01', '2026-09-30');

        $this->assertArrayHasKey('table_4a', $report);
        $this->assertArrayHasKey('table_4b', $report);
        $this->assertArrayHasKey('overall_totals', $report);

        $view = view('accounting.gstr1', [
            'company' => $company,
            'report' => $report,
            'fromDate' => '2026-04-01',
            'toDate' => '2026-09-30',
        ])->render();

        // Dedicated print container
        $this->assertStringContainsString('d-none d-print-block gstr1-print', $view);

        // Header elements matching Tally authentic style
        $this->assertStringContainsString('Taldar Tech Consultancy Private Limited', $view);
        $this->assertStringContainsString('<u>Udaipur</u>', $view);
        $this->assertStringContainsString('GSTIN: <strong>08AAACA1234A1Z5</strong>', $view);
        $this->assertStringContainsString('GSTR-1 Return Statement (Outward Supplies)', $view);
        $this->assertStringContainsString('1-Apr-26 to 30-Sep-26', $view);

        // Summary and table sections
        $this->assertStringContainsString('tally-gstr1-table', $view);
        $this->assertStringContainsString('1. Return Summary of Outward Supplies', $view);
        $this->assertStringContainsString('Table 4A - Taxable Outward Supplies (Forward Charge / GTA FCM)', $view);
        $this->assertStringContainsString('Table 4B - Supplies Attracting Reverse Charge (GTA RCM u/s 9(3))', $view);
        $this->assertStringContainsString('Total Outward Supplies', $view);

        // Detailed schedules
        $this->assertStringContainsString('2. Table 4A - Taxable Outward Supplies (Forward Charge / GTA FCM)', $view);
        $this->assertStringContainsString('3. Table 4B - Supplies Attracting Reverse Charge (GTA RCM u/s 9(3))', $view);
        $this->assertStringContainsString('Table 4A Total', $view);
        $this->assertStringContainsString('Table 4B Total', $view);
        $this->assertStringContainsString('Statutory GTA Declaration', $view);

        // Ensure screen interactive elements are hidden from print
        $this->assertStringContainsString('card shadow-sm border-0 mb-3 d-print-none', $view);
        $this->assertStringContainsString('row g-3 mb-3 d-print-none', $view);
        $this->assertStringContainsString('id="gstr1Tabs"', $view);
        $this->assertStringContainsString('d-print-none', $view);
        $this->assertStringContainsString('@media print', $view);
    }

    public function test_trial_balance_print_format_and_structure(): void
    {
        $company = Company::create([
            'name' => 'Taldar Tech Consultancy Private Limited',
            'address' => 'Udaipur',
            'gst_number' => '08AAACA1234A1Z5',
            'pan_number' => 'AAACA1234A',
            'contact_number' => '9876543210',
        ]);

        $reportService = new FinancialReportService;
        $trialBalance = $reportService->getTrialBalance($company, '2026-09-21');

        $this->assertArrayHasKey('groups', $trialBalance);
        $this->assertArrayHasKey('total_debit', $trialBalance);
        $this->assertArrayHasKey('total_credit', $trialBalance);

        $view = view('accounting.trial_balance', [
            'company' => $company,
            'trialBalance' => $trialBalance,
            'asOfDate' => '2026-09-21',
        ])->render();

        // Dedicated print container
        $this->assertStringContainsString('d-none d-print-block trial-balance-print', $view);

        // Header elements matching Tally authentic style
        $this->assertStringContainsString('Taldar Tech Consultancy Private Limited', $view);
        $this->assertStringContainsString('<u>Udaipur</u>', $view);
        $this->assertStringContainsString('Trial Balance', $view);
        $this->assertStringContainsString('1-Apr-26 to 21-Sep-26', $view);

        // Table headers and columns
        $this->assertStringContainsString('tally-tb-table', $view);
        $this->assertStringContainsString('P a r t i c u l a r s', $view);
        $this->assertStringContainsString('D e b i t', $view);
        $this->assertStringContainsString('C r e d i t', $view);

        // Footer total
        $this->assertStringContainsString('T o t a l', $view);
        $this->assertStringContainsString('tally-total-row', $view);

        // Ensure screen interactive elements are hidden from print
        $this->assertStringContainsString('card shadow-sm border-0 mb-3 d-print-none', $view);
        $this->assertStringContainsString('card app-index-list-card shadow-sm d-print-none', $view);
        $this->assertStringContainsString('@media print', $view);
    }

    public function test_voucher_create_view_and_store_workflow(): void
    {
        $company = Company::create(['name' => 'Demo Company']);
        $provisioner = new ChartOfAccountsProvisioner;
        $provisioner->provisionForCompany($company);

        $ledgers = Ledger::where('company_id', $company->id)->get();
        $costCentres = \App\Models\CostCentre::where('company_id', $company->id)->get();

        $view = view('accounting.vouchers.create', [
            'company' => $company,
            'ledgers' => $ledgers,
            'costCentres' => $costCentres,
        ])->render();

        $this->assertStringContainsString('id="saveVoucherBtn"', $view);
        $this->assertStringContainsString('id="totalDebitDisplay"', $view);
        $this->assertStringContainsString('id="totalCreditDisplay"', $view);
        $this->assertStringContainsString('id="balanceAlert"', $view);
        $this->assertStringNotContainsString("document.getElementById('diffDisplay')", $view);
        $this->assertStringContainsString('Voucher is perfectly balanced!', $view);

        // Test posting payment voucher directly
        $cashLedger = Ledger::where('company_id', $company->id)->where('code', 'CASH')->first();
        $officeRentLedger = Ledger::where('company_id', $company->id)->where('name', 'like', '%Rent%')->first();

        if (! $officeRentLedger) {
            $indirectExpGroup = AccountGroup::where('company_id', $company->id)->where('name', 'Indirect Expenses')->first();
            $officeRentLedger = Ledger::create([
                'company_id' => $company->id,
                'account_group_id' => $indirectExpGroup->id,
                'name' => 'Office Rent',
                'code' => 'OFFICE_RENT',
                'is_active' => true,
            ]);
        }

        $user = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin_vch_test@truckbill.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        $this->actingAs($user);

        $controller = new \App\Http\Controllers\AccountingVoucherController;
        $request = new \Illuminate\Http\Request([
            'voucher_type' => 'payment',
            'voucher_date' => '2026-09-21',
            'reference_number' => 'CHQ-10029',
            'narration' => 'Rent August',
            'items' => [
                [
                    'type' => 'debit',
                    'ledger_id' => $officeRentLedger->id,
                    'amount' => 10000.00,
                    'particulars_note' => 'Office Rent for August',
                ],
                [
                    'type' => 'credit',
                    'ledger_id' => $cashLedger->id,
                    'amount' => 10000.00,
                    'particulars_note' => 'Cash paid',
                ],
            ],
        ]);

        $response = $controller->store($request);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);

        $createdVoucher = \App\Models\AccountingVoucher::where('company_id', $company->id)
            ->where('narration', 'Rent August')
            ->first();

        $this->assertNotNull($createdVoucher);
        $this->assertEquals('payment', $createdVoucher->voucher_type);
        $this->assertEquals(10000.00, (float) $createdVoucher->total_amount);
        $this->assertTrue($createdVoucher->isBalanced());
        $this->assertCount(2, $createdVoucher->items);
    }

    public function test_voucher_show_view_renders_standard_print_format(): void
    {
        $company = Company::create([
            'name' => 'Demo Company',
            'address' => 'Building 5, DLF Cyber Hub, Sector 24',
            'gst_number' => '29AAACH7409R1ZX',
            'pan_number' => 'AABAD1452A',
            'contact_number' => '7838833900',
        ]);

        $provisioner = new ChartOfAccountsProvisioner;
        $provisioner->provisionForCompany($company);

        $cashLedger = Ledger::where('company_id', $company->id)->where('code', 'CASH')->first();
        $indirectExpGroup = AccountGroup::where('company_id', $company->id)->where('name', 'Indirect Expenses')->first();
        $teaExpenseLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $indirectExpGroup->id,
            'name' => 'Tea/coffee/Snacks',
            'code' => 'TEA_COFFEE',
            'is_active' => true,
        ]);

        $voucher = \App\Models\AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'PAY-260921-4955',
            'voucher_date' => '2026-09-21',
            'total_amount' => 500.00,
            'narration' => 'Tea 21st sept',
            'is_posted' => true,
        ]);

        \App\Models\AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher->id,
            'ledger_id' => $teaExpenseLedger->id,
            'type' => 'debit',
            'amount' => 500.00,
        ]);

        \App\Models\AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher->id,
            'ledger_id' => $cashLedger->id,
            'type' => 'credit',
            'amount' => 500.00,
        ]);

        $voucher->load(['items.ledger.accountGroup', 'items.costCentre', 'user']);

        $view = view('accounting.vouchers.show', [
            'company' => $company,
            'voucher' => $voucher,
        ])->render();

        // Dedicated print container
        $this->assertStringContainsString('d-none d-print-block voucher-print', $view);

        // Header elements
        $this->assertStringContainsString('Demo Company', $view);
        $this->assertStringContainsString('Building 5, DLF Cyber Hub, Sector 24', $view);
        $this->assertStringContainsString('PAYMENT VOUCHER', $view);

        // Meta and Particulars table
        $this->assertStringContainsString('tally-meta-table', $view);
        $this->assertStringContainsString('PAY-260921-4955', $view);
        $this->assertStringContainsString('21-Sep-2026', $view);
        $this->assertStringContainsString('tally-voucher-table', $view);
        $this->assertStringContainsString('Tea/coffee/Snacks', $view);
        $this->assertStringContainsString('Cash A/c', $view);
        $this->assertStringContainsString('500.00', $view);

        // Amount in words and Narration
        $this->assertStringContainsString('Amount (in words):', $view);
        $this->assertStringContainsString('Five Hundred Rupees', $view);
        $this->assertStringContainsString('Tea 21st sept', $view);

        // Signature row
        $this->assertStringContainsString('tally-signatures-row', $view);
        $this->assertStringContainsString('Prepared by:', $view);
        $this->assertStringContainsString('Authorised Signatory', $view);

        // Ensure screen interactive elements are hidden from print
        $this->assertStringContainsString('d-print-none', $view);
        $this->assertStringContainsString('@media print', $view);
    }

    public function test_voucher_posting_against_invoice_reduces_balance_due(): void
    {
        $company = Company::create(['name' => 'Demo Transport', 'short_name' => 'DT']);
        $user = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin_vch_adj@truckbill.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);
        $this->actingAs($user);
        session(['super_admin_company_id' => $company->id]);

        $provisioner = app(ChartOfAccountsProvisioner::class);
        $postingService = app(AccountingPostingService::class);
        $provisioner->provisionForCompany($company);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'ABC Ltd',
            'mobile_number' => '9876543210',
            'is_active' => true,
        ]);

        $customerLedger = $postingService->getOrCreatePartyLedger($company, $customer);
        $tdsLedger = Ledger::where('company_id', $company->id)->where('name', 'like', '%TDS Receivable%')->first();

        // Create an open invoice of ₹33,000
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2026-0099',
            'invoice_type' => 'forward',
            'total_amount' => 33000.00,
            'net_amount' => 33000.00,
            'generated_date' => '2026-09-01',
        ]);

        $this->assertEquals(33000.00, $invoice->remaining_amount);

        // Post a journal voucher: Dr TDS Receivable ₹500, Cr ABC Ltd ₹500 (against INV-2026-0099)
        $request = new \Illuminate\Http\Request([
            'voucher_type' => 'journal',
            'voucher_date' => '2026-09-15',
            'narration' => 'TDS deducted by customer on INV-2026-0099',
            'items' => [
                [
                    'ledger_id' => $tdsLedger->id,
                    'type' => 'debit',
                    'amount' => 500.00,
                ],
                [
                    'ledger_id' => $customerLedger->id,
                    'type' => 'credit',
                    'amount' => 500.00,
                    'invoice_id' => $invoice->id,
                ],
            ],
        ]);

        $controller = app(\App\Http\Controllers\AccountingVoucherController::class);
        $controller->store($request);

        $invoice->refresh();

        // Balance due should now be ₹32,500.00
        $this->assertEquals(500.00, $invoice->paid_amount);
        $this->assertEquals(32500.00, $invoice->remaining_amount);
    }

    public function test_customer_pending_invoices_lookup_endpoint(): void
    {
        $company = Company::create(['name' => 'Demo Transport', 'short_name' => 'DT']);
        $user = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin_vch_lookup@truckbill.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);
        $this->actingAs($user);
        session(['super_admin_company_id' => $company->id]);

        $provisioner = app(ChartOfAccountsProvisioner::class);
        $postingService = app(AccountingPostingService::class);
        $provisioner->provisionForCompany($company);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'ABC Ltd',
            'mobile_number' => '9876543210',
            'is_active' => true,
        ]);

        $customerLedger = $postingService->getOrCreatePartyLedger($company, $customer);

        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2026-0099',
            'invoice_type' => 'forward',
            'total_amount' => 33000.00,
            'net_amount' => 33000.00,
            'generated_date' => '2026-09-01',
        ]);

        $controller = app(\App\Http\Controllers\AccountingVoucherController::class);
        $response = $controller->getPendingInvoices($customerLedger);

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['is_customer']);
        $this->assertEquals('ABC Ltd', $data['customer_name']);
        $this->assertCount(1, $data['invoices']);
        $this->assertEquals(33000.00, $data['invoices'][0]['remaining_amount']);
    }

    public function test_voucher_disallows_identical_ledger_on_debit_and_credit_and_autosuggest_view(): void
    {
        $company = Company::create(['name' => 'Auto Suggest Company']);
        $provisioner = new ChartOfAccountsProvisioner;
        $provisioner->provisionForCompany($company);

        $cashLedger = Ledger::where('company_id', $company->id)->where('code', 'CASH')->first();

        $user = \App\Models\User::create([
            'name' => 'Test Accountant',
            'email' => 'acct_autosuggest@truckbill.test',
            'password' => bcrypt('secret'),
            'company_id' => $company->id,
        ]);
        $this->actingAs($user);

        // Test creating a voucher where Dr and Cr are the exact same ledger
        $controller = new \App\Http\Controllers\AccountingVoucherController;
        $request = new \Illuminate\Http\Request([
            'voucher_type' => 'contra',
            'voucher_date' => '2026-09-29',
            'items' => [
                ['type' => 'debit', 'ledger_id' => $cashLedger->id, 'amount' => 5000.00],
                ['type' => 'credit', 'ledger_id' => $cashLedger->id, 'amount' => 5000.00],
            ],
        ]);

        $response = $controller->store($request);
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertTrue(session()->has('errors'));
        $errors = session()->get('errors')->getBag('default');
        $this->assertTrue($errors->has('items'));
        $this->assertStringContainsString('Debit and Credit ledgers cannot be identical', $errors->first('items'));

        // Test view contains auto-suggestion and circular detection JavaScript
        auth()->logout();
        $view = view('accounting.vouchers.create', [
            'company' => $company,
            'ledgers' => collect([$cashLedger]),
            'costCentres' => collect(),
        ])->render();

        $this->assertStringContainsString('autoSuggestOppositeRow', $view);
        $this->assertStringContainsString('handleAmountFocus', $view);
        $this->assertStringContainsString('Circular Entry Warning', $view);
    }

    public function test_chart_of_accounts_provisioner_fuel_pumps_sync_and_edge_cases(): void
    {
        $user = \App\Models\User::create([
            'name' => 'Owner Admin',
            'email' => 'owner_admin@truckbill.test',
            'password' => bcrypt('secret'),
        ]);

        $company = Company::create([
            'name' => 'Pump Sync Transport Co',
            'user_id' => $user->id,
            'bank' => 'HDFC Bank',
            'account_number' => '1234567890',
        ]);

        // 1. Pump with blank name and contact
        $blankPump = Pump::create([
            'user_id' => $user->id,
            'pump_name' => '',
            'contact_name' => '',
        ]);

        // 2. Valid pump
        $validPump = Pump::create([
            'user_id' => $user->id,
            'pump_name' => 'City Center Petrol Pump',
            'contact_name' => 'Manager Ram',
        ]);

        $provisioner = new ChartOfAccountsProvisioner;
        $provisioner->provisionForCompany($company);

        // Blank pump ledger should not be created
        $this->assertNull(
            Ledger::where('company_id', $company->id)
                ->where('entity_type', Pump::class)
                ->where('entity_id', $blankPump->id)
                ->first()
        );

        // Valid pump ledger created
        $pumpLedger = Ledger::where('company_id', $company->id)
            ->where('entity_type', Pump::class)
            ->where('entity_id', $validPump->id)
            ->first();
        $this->assertNotNull($pumpLedger);
        $this->assertSame('City Center Petrol Pump (Fuel Pump)', $pumpLedger->name);

        // Bank ledger created
        $bankLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'BANK_PRIMARY')
            ->first();
        $this->assertNotNull($bankLedger);
        $this->assertSame('HDFC Bank (A/c 1234567890)', $bankLedger->name);

        // Update pump name and re-provision -> ledger should be updated
        $validPump->update(['pump_name' => 'Highway Center Petrol Pump']);
        $provisioner->provisionForCompany($company);

        $pumpLedger->refresh();
        $this->assertSame('Highway Center Petrol Pump (Fuel Pump)', $pumpLedger->name);
    }

    public function test_accounting_observers_all_scenarios_and_error_handling(): void
    {
        $posting = Mockery::mock(AccountingPostingService::class);
        $company = Company::create(['name' => 'Observer Co']);

        // 1. AdvancePaymentObserver
        $advObserver = new AdvancePaymentObserver($posting);
        $advance = new AdvancePayment(['company_id' => $company->id]);
        $advance->id = 101;
        $advance->setRelation('company', $company);
        $advance->setRelation('transporter', null);

        // saved with company
        $posting->shouldReceive('postAdvancePayment')->with($advance)->once();
        $advObserver->saved($advance);

        // saved without company
        $advanceNoCompany = new AdvancePayment(['company_id' => null]);
        $advanceNoCompany->id = 102;
        $advanceNoCompany->setRelation('company', null);
        $advanceNoCompany->setRelation('transporter', null);
        $advObserver->saved($advanceNoCompany);

        // saved with exception
        Log::shouldReceive('error')->once()->with(Mockery::pattern('/Accounting sync error for advance payment #101/'));
        $posting->shouldReceive('postAdvancePayment')->with($advance)->andThrow(new \RuntimeException('Advance error'));
        $advObserver->saved($advance);

        // deleted normal & exception
        $posting->shouldReceive('deleteVoucherFor')->with(AdvancePayment::class, 101, $company->id)->once();
        $advObserver->deleted($advance);

        Log::shouldReceive('error')->once()->with(Mockery::pattern('/Accounting delete error for advance payment #101/'));
        $posting->shouldReceive('deleteVoucherFor')->with(AdvancePayment::class, 101, $company->id)->andThrow(new \RuntimeException('Delete error'));
        $advObserver->deleted($advance);

        // 2. FreightBookingObserver
        $fbObserver = new FreightBookingObserver($posting);
        $booking = new FreightBooking(['company_id' => $company->id]);
        $booking->id = 201;
        $booking->setRelation('company', $company);
        $booking->setRelation('transporter', null);
        $booking->setRelation('truck', null);
        $booking->setRelation('lr', null);
        $booking->setRelation('customAmounts', collect());

        // saved with company
        $posting->shouldReceive('postFreightBooking')->with($booking)->once();
        $fbObserver->saved($booking);

        // saved without company
        $bookingNoCompany = new FreightBooking(['company_id' => null]);
        $bookingNoCompany->id = 202;
        $bookingNoCompany->setRelation('company', null);
        $bookingNoCompany->setRelation('transporter', null);
        $bookingNoCompany->setRelation('truck', null);
        $bookingNoCompany->setRelation('lr', null);
        $bookingNoCompany->setRelation('customAmounts', collect());
        $fbObserver->saved($bookingNoCompany);

        // saved with exception
        Log::shouldReceive('error')->once()->with(Mockery::pattern('/Accounting sync error for freight booking #201/'));
        $posting->shouldReceive('postFreightBooking')->with($booking)->andThrow(new \RuntimeException('Booking error'));
        $fbObserver->saved($booking);

        // deleted normal & exception
        $posting->shouldReceive('deleteVoucherFor')->with(FreightBooking::class, 201, $company->id)->once();
        $fbObserver->deleted($booking);

        Log::shouldReceive('error')->once()->with(Mockery::pattern('/Accounting delete error for freight booking #201/'));
        $posting->shouldReceive('deleteVoucherFor')->with(FreightBooking::class, 201, $company->id)->andThrow(new \RuntimeException('Delete error'));
        $fbObserver->deleted($booking);

        // 3. InvoiceObserver
        $invObserver = new InvoiceObserver($posting);

        // saved without company_id (early return)
        $invNoId = new Invoice(['company_id' => null]);
        $invObserver->saved($invNoId);

        // saved with company
        $invoice = new Invoice(['company_id' => $company->id]);
        $invoice->id = 301;
        $invoice->setRelation('company', $company);
        $invoice->setRelation('consignee', null);
        $invoice->setRelation('payments', collect());
        $posting->shouldReceive('postInvoice')->with($invoice)->once();
        $invObserver->saved($invoice);

        // saved without company relation loaded
        $invoiceNoCo = new Invoice(['company_id' => 999]);
        $invoiceNoCo->id = 302;
        $invoiceNoCo->setRelation('company', null);
        $invoiceNoCo->setRelation('consignee', null);
        $invoiceNoCo->setRelation('payments', collect());
        $invObserver->saved($invoiceNoCo);

        // saved with exception
        Log::shouldReceive('error')->once()->with(Mockery::pattern('/Accounting sync error for invoice #301/'));
        $posting->shouldReceive('postInvoice')->with($invoice)->andThrow(new \RuntimeException('Invoice error'));
        $invObserver->saved($invoice);

        // deleted normal & exception
        $posting->shouldReceive('deleteVoucherFor')->with(Invoice::class, 301, $company->id)->once();
        $invObserver->deleted($invoice);

        Log::shouldReceive('error')->once()->with(Mockery::pattern('/Accounting delete error for invoice #301/'));
        $posting->shouldReceive('deleteVoucherFor')->with(Invoice::class, 301, $company->id)->andThrow(new \RuntimeException('Delete error'));
        $invObserver->deleted($invoice);

        // 4. InvoicePaymentObserver
        $payObserver = new InvoicePaymentObserver($posting);
        $payment = new InvoicePayment();
        $payment->id = 401;
        $payment->setRelation('invoice', $invoice);

        // saved with invoice company
        $posting->shouldReceive('postInvoicePayment')->with($payment)->once();
        $payObserver->saved($payment);

        // saved without invoice
        $paymentNoInvoice = new InvoicePayment();
        $paymentNoInvoice->id = 402;
        $paymentNoInvoice->setRelation('invoice', null);
        $payObserver->saved($paymentNoInvoice);

        // saved with exception
        Log::shouldReceive('error')->once()->with(Mockery::pattern('/Accounting sync error for payment #401/'));
        $posting->shouldReceive('postInvoicePayment')->with($payment)->andThrow(new \RuntimeException('Payment error'));
        $payObserver->saved($payment);

        // deleted normal & exception
        $posting->shouldReceive('deleteVoucherFor')->with(InvoicePayment::class, 401)->once();
        $payObserver->deleted($payment);

        Log::shouldReceive('error')->once()->with(Mockery::pattern('/Accounting delete error for payment #401/'));
        $posting->shouldReceive('deleteVoucherFor')->with(InvoicePayment::class, 401)->andThrow(new \RuntimeException('Delete error'));
        $payObserver->deleted($payment);

        // 5. VoucherPaymentObserver
        $vchObserver = new VoucherPaymentObserver($posting);
        $voucher = new VoucherPayment(['company_id' => $company->id]);
        $voucher->id = 501;
        $voucher->setRelation('company', $company);
        $voucher->setRelation('freight_booking', null);
        $voucher->setRelation('pump', null);

        // saved with company
        $posting->shouldReceive('postVoucherPayment')->with($voucher)->once();
        $vchObserver->saved($voucher);

        // saved without company
        $voucherNoCo = new VoucherPayment(['company_id' => null]);
        $voucherNoCo->id = 502;
        $voucherNoCo->setRelation('company', null);
        $voucherNoCo->setRelation('freight_booking', null);
        $voucherNoCo->setRelation('pump', null);
        $vchObserver->saved($voucherNoCo);

        // saved with exception
        Log::shouldReceive('error')->once()->with(Mockery::pattern('/Accounting sync error for voucher payment #501/'));
        $posting->shouldReceive('postVoucherPayment')->with($voucher)->andThrow(new \RuntimeException('Voucher error'));
        $vchObserver->saved($voucher);

        // deleted normal & exception
        $posting->shouldReceive('deleteVoucherFor')->with(VoucherPayment::class, 501, $company->id)->once();
        $vchObserver->deleted($voucher);

        Log::shouldReceive('error')->once()->with(Mockery::pattern('/Accounting delete error for voucher payment #501/'));
        $posting->shouldReceive('deleteVoucherFor')->with(VoucherPayment::class, 501, $company->id)->andThrow(new \RuntimeException('Delete error'));
        $vchObserver->deleted($voucher);

        $this->assertTrue(true);
    }

    public function test_accounting_posting_service_all_edge_cases_and_branches(): void
    {
        $user = \App\Models\User::create([
            'name' => 'Posting Service User',
            'email' => 'post_srv_user@truckbill.test',
            'password' => bcrypt('secret'),
        ]);

        $company = Company::create([
            'name' => 'Edge Case Transport Co',
            'user_id' => $user->id,
            'bank' => 'State Bank of India',
            'account_number' => '9876543210',
        ]);
        $provisioner = app(\App\Services\ChartOfAccountsProvisioner::class);
        $provisioner->provisionForCompany($company);
        $postingService = new \App\Services\AccountingPostingService($provisioner);

        // 1. getOrCreatePartyLedger - Unsupported entity throws InvalidArgumentException
        try {
            $postingService->getOrCreatePartyLedger($company, new \stdClass());
            $this->fail('Expected InvalidArgumentException for unsupported entity');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Unsupported entity', $e->getMessage());
        }

        // 1b. Transporter: owner, declaration_given, and existing ledger update
        $ownerMaster = TransporterMaster::create(['name' => 'Owner Transport Corp']);
        $ownerTransporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $ownerMaster->id,
            'is_owner' => true,
            'declaration_given' => true,
            'tds_percentage' => 1.0,
        ]);
        $ownerLedger = $postingService->getOrCreatePartyLedger($company, $ownerTransporter);
        $this->assertStringContainsString('(Owner)', $ownerLedger->name);
        $this->assertEquals(0.00, $ownerLedger->tds_rate);

        // Update existing transporter ledger
        $ownerMaster->update(['name' => 'Owner Transport Corp Updated']);
        $ownerTransporter->unsetRelation('transporterMaster');
        $ownerTransporter->declaration_given = false;
        $ownerTransporter->tds_percentage = 2.0;
        $ownerTransporter->save();
        $updatedOwnerLedger = $postingService->getOrCreatePartyLedger($company, $ownerTransporter);
        $this->assertEquals($ownerLedger->id, $updatedOwnerLedger->id);
        $this->assertStringContainsString('Updated', $updatedOwnerLedger->name);
        $this->assertEquals(2.0, $updatedOwnerLedger->tds_rate);

        // 1c. Pump: blank name/pump_name fallback & existing pump ledger update
        $blankPump = Pump::create([
            'user_id' => $company->user_id,
            'pump_name' => '',
            'name' => '',
            'contact_name' => '',
        ]);
        $pumpLedger = $postingService->getOrCreatePartyLedger($company, $blankPump);
        $this->assertStringContainsString('Pump #'.$blankPump->id.' (Fuel Pump)', $pumpLedger->name);

        $blankPump->update(['pump_name' => 'Highway IOCL Station']);
        $updatedPumpLedger = $postingService->getOrCreatePartyLedger($company, $blankPump);
        $this->assertEquals($pumpLedger->id, $updatedPumpLedger->id);
        $this->assertStringContainsString('Highway IOCL Station (Fuel Pump)', $updatedPumpLedger->name);

        // 2. postInvoice - null company & round-off debit ($diff < 0)
        $invoiceNoCo = new Invoice(['company_id' => null]);
        $this->assertNull($postingService->postInvoice($invoiceNoCo));

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Global Logistics Client',
        ]);
        $roundOffInvoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'total_amount' => 999.00,
            'base_amount' => 1000.00,
            'cgst' => 0.00,
            'sgst' => 0.00,
            'igst' => 0.00,
        ]);
        $roundOffVoucher = $postingService->postInvoice($roundOffInvoice);
        $this->assertNotNull($roundOffVoucher);
        $roundOffItem = $roundOffVoucher->items->where('type', 'debit')->firstWhere('amount', 1.00);
        $this->assertNotNull($roundOffItem);

        // 3. postInvoicePayment - null company & custom bank_ledger_id, deduction_reason, remark
        $paymentNoCo = new InvoicePayment();
        $paymentNoCo->setRelation('invoice', null);
        $this->assertNull($postingService->postInvoicePayment($paymentNoCo));

        $bankLedger = \App\Models\Ledger::where('company_id', $company->id)->where('code', 'BANK_PRIMARY')->first();
        $invoicePayment = InvoicePayment::create([
            'invoice_id' => $roundOffInvoice->id,
            'amount' => 500.00,
            'tds_amount' => 20.00,
            'deduction_amount' => 30.00,
            'deduction_reason' => 'Damaged cartons',
            'remark' => 'Settled via NEFT',
            'mode' => 'bank',
            'bank_ledger_id' => $bankLedger->id,
            'payment_date' => now()->toDateString(),
        ]);
        $paymentVoucher = $postingService->postInvoicePayment($invoicePayment);
        $this->assertNotNull($paymentVoucher);
        $this->assertStringContainsString('Damaged cartons', $paymentVoucher->narration);
        $this->assertStringContainsString('Settled via NEFT', $paymentVoucher->narration);

        // 4. postFreightBooking - null company, cancelled booking, missing transporter, negative netCredited
        $bookingNoCo = new FreightBooking(['company_id' => null]);
        $this->assertNull($postingService->postFreightBooking($bookingNoCo));

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'DL01AB9999',
        ]);
        $transMaster = TransporterMaster::create(['name' => 'Speedline Carriers']);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $transMaster->id,
            'tds_percentage' => 1.0,
        ]);
        $booking = FreightBooking::create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'tr_amount' => 1000.00,
            'damage_amount' => 0.00,
            'is_cancelled' => false,
        ]);
        $postedFb = $postingService->postFreightBooking($booking);
        $this->assertNotNull($postedFb);

        // Cancelled booking removes voucher and returns null
        $booking->is_cancelled = true;
        $this->assertNull($postingService->postFreightBooking($booking));
        $this->assertSoftDeleted('accounting_vouchers', ['id' => $postedFb->id]);

        // Missing transporter returns null
        $bookingNoTr = FreightBooking::create([
            'company_id' => $company->id,
            'truck_id' => null,
            'transporter_id' => null,
            'is_cancelled' => false,
        ]);
        $bookingNoTr->setRelation('lr', null);
        $bookingNoTr->setRelation('truck', null);
        $this->assertNull($postingService->postFreightBooking($bookingNoTr));

        // Negative net payable (recovery > total hiring expense)
        $negativeBooking = FreightBooking::create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'tr_amount' => 500.00,
            'damage_amount' => 1200.00,
            'is_cancelled' => false,
        ]);
        $negVoucher = $postingService->postFreightBooking($negativeBooking);
        $this->assertNotNull($negVoucher);
        $transporterDebit = $negVoucher->items->where('type', 'debit')->firstWhere('ledger_id', $postingService->getOrCreatePartyLedger($company, $transporter)->id);
        $this->assertNotNull($transporterDebit);

        // 5. postVoucherPayment - null company, missing transporter, pump matched by remark, unknown pump, custom bank_ledger_id
        $vpNoCo = new VoucherPayment(['company_id' => null]);
        $this->assertNull($postingService->postVoucherPayment($vpNoCo));

        $vpNoTr = new VoucherPayment(['company_id' => $company->id]);
        $vpNoTr->setRelation('company', $company);
        $vpNoTr->setRelation('freight_booking', $bookingNoTr);
        $this->assertNull($postingService->postVoucherPayment($vpNoTr));

        // Pump matched by remark string
        $namedPump = Pump::create([
            'user_id' => $company->user_id,
            'pump_name' => 'HPCL Green Valley',
        ]);
        $vpPumpRemark = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'amount' => 450.00,
            'payment_mode' => 'fuel',
            'remark' => 'Refueled at HPCL Green Valley fuel station',
            'date' => now()->toDateString(),
        ]);
        $vpPumpVoucher = $postingService->postVoucherPayment($vpPumpRemark);
        $this->assertNotNull($vpPumpVoucher);

        // Unknown pump fallback to Fuel Pump Payable A/c
        Ledger::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Fuel Pump Payable A/c'],
            ['opening_balance' => 0.00, 'opening_balance_type' => 'credit', 'is_active' => true]
        );
        $vpUnknownPump = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'amount' => 300.00,
            'payment_mode' => 'pump',
            'remark' => 'Unknown highway diesel dispenser',
            'date' => now()->toDateString(),
        ]);
        $vpUnknownVoucher = $postingService->postVoucherPayment($vpUnknownPump);
        $this->assertNotNull($vpUnknownVoucher);

        // With bank_ledger_id
        $vpBank = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'amount' => 200.00,
            'payment_mode' => 'neft',
            'bank_ledger_id' => $bankLedger->id,
            'date' => now()->toDateString(),
        ]);
        $vpBankVoucher = $postingService->postVoucherPayment($vpBank);
        $this->assertNotNull($vpBankVoucher);

        // 6. postAdvancePayment - zero/negative/remark check, null company, null transporter, bank_ledger_id & cash remark
        $zeroAdv = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 0.00,
        ]);
        $this->assertNull($postingService->postAdvancePayment($zeroAdv));

        $negativeAdv = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 100.00,
            'remark' => 'negative balance adjustment from previous ledger',
        ]);
        $this->assertNull($postingService->postAdvancePayment($negativeAdv));

        $advNoCo = new AdvancePayment(['advance_amount' => 100.00, 'company_id' => null]);
        $this->assertNull($postingService->postAdvancePayment($advNoCo));

        $advNoTr = new AdvancePayment(['advance_amount' => 100.00, 'company_id' => $company->id, 'transporter_id' => null]);
        $advNoTr->setRelation('company', $company);
        $this->assertNull($postingService->postAdvancePayment($advNoTr));

        $advWithBank = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 750.00,
            'bank_ledger_id' => $bankLedger->id,
            'remark' => 'Paid cash advance to driver at yard',
        ]);
        $advVoucher = $postingService->postAdvancePayment($advWithBank);
        $this->assertNotNull($advVoucher);

        // 7. postCreditNote - with taxAmount > 0
        $crnVoucher = $postingService->postCreditNote($roundOffInvoice, 200.00, 'Rate difference', 36.00);
        $this->assertNotNull($crnVoucher);
        $this->assertEquals(236.00, $crnVoucher->total_amount);
        $this->assertNotNull($crnVoucher->items->where('type', 'debit')->firstWhere('amount', 36.00));

        // 8. postDebitNote - with FreightBooking passed
        $dbnVoucher = $postingService->postDebitNote($transporter, 150.00, 'Excess diesel consumption penalty', $booking);
        $this->assertNotNull($dbnVoucher);
        $this->assertEquals(FreightBooking::class, $dbnVoucher->source_type);
        $this->assertEquals($booking->id, $dbnVoucher->source_id);
        $this->assertStringContainsString('Truck: ', $dbnVoucher->reference_number);

        // 9. deleteVoucherFor - with and without companyId
        $postingService->deleteVoucherFor(FreightBooking::class, $booking->id);
        $this->assertSoftDeleted('accounting_vouchers', ['source_type' => FreightBooking::class, 'source_id' => $booking->id]);

        $postingService->deleteVoucherFor(Invoice::class, $roundOffInvoice->id, $company->id);
        $this->assertSoftDeleted('accounting_vouchers', ['source_type' => Invoice::class, 'source_id' => $roundOffInvoice->id]);
    }

    public function test_accounting_posting_service_comprehensive_missing_branches_coverage(): void
    {
        $user = \App\Models\User::create([
            'name' => 'Posting Full Branches User',
            'email' => 'post_full_user@truckbill.test',
            'password' => bcrypt('secret'),
        ]);

        $company = Company::create([
            'name' => 'Full Branch Transport Co',
            'user_id' => $user->id,
            'bank' => 'HDFC Bank',
            'account_number' => '1122334455',
        ]);
        $provisioner = new class extends \App\Services\ChartOfAccountsProvisioner {
            public bool $skipIgst = false;
            public bool $skipEnsureLedgers = false;

            public function provisionForCompany(Company $company): void
            {
                parent::provisionForCompany($company);
                if ($this->skipIgst) {
                    Ledger::where('company_id', $company->id)->where('code', 'IGST_OUT')->delete();
                }
                if ($this->skipEnsureLedgers) {
                    Ledger::where('company_id', $company->id)->whereIn('code', ['ADV_TRANSPORTER', 'SHORTAGE_REC'])->delete();
                    Ledger::where('company_id', $company->id)->where('name', 'Fuel Pump Payable A/c')->delete();
                }
            }

            public function ensureLedger(Company $company, string $name, string $groupName, string $code, string $type): Ledger
            {
                $group = AccountGroup::firstOrCreate(
                    ['company_id' => $company->id, 'name' => $groupName],
                    ['primary_type' => $type === 'debit' ? 'asset' : 'liability']
                );

                return Ledger::firstOrCreate(
                    ['company_id' => $company->id, 'name' => $name],
                    ['account_group_id' => $group->id, 'code' => $code, 'opening_balance_type' => $type]
                );
            }
        };
        $provisioner->provisionForCompany($company);
        $postingService = new \App\Services\AccountingPostingService($provisioner);

        $dummyGroup = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Temporary Group',
            'primary_type' => 'liability',
        ]);

        // 1. getOrCreatePartyLedger - Transporter existing ledger with wrong account_group_id (line 78)
        $transMaster = TransporterMaster::create(['name' => 'Branch Transporter']);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $transMaster->id,
            'tds_percentage' => 1.0,
        ]);
        $transLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $dummyGroup->id,
            'name' => $transporter->name.' (Transporter)',
            'entity_type' => Transporter::class,
            'entity_id' => $transporter->id,
            'opening_balance' => 0.00,
            'opening_balance_type' => 'credit',
        ]);
        $resolvedTransLedger = $postingService->getOrCreatePartyLedger($company, $transporter);
        $this->assertEquals($transLedger->id, $resolvedTransLedger->id);
        $this->assertNotEquals($dummyGroup->id, $resolvedTransLedger->fresh()->account_group_id);

        // 2. getOrCreatePartyLedger - Pump existing ledger with wrong account_group_id & changed name (lines 125, 128)
        $fuelPumpsGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Fuel Pumps'],
            ['primary_type' => 'liability']
        );
        $pump = Pump::create([
            'user_id' => $company->user_id,
            'pump_name' => 'Original Pump Name',
        ]);
        $pumpLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $dummyGroup->id,
            'name' => 'Outdated Pump Name (Fuel Pump)',
            'entity_type' => Pump::class,
            'entity_id' => $pump->id,
            'opening_balance' => 0.00,
            'opening_balance_type' => 'credit',
        ]);
        $resolvedPumpLedger = $postingService->getOrCreatePartyLedger($company, $pump);
        $this->assertEquals($pumpLedger->id, $resolvedPumpLedger->id);
        $this->assertEquals($fuelPumpsGroup->id, $resolvedPumpLedger->fresh()->account_group_id);
        $this->assertEquals('Original Pump Name (Fuel Pump)', $resolvedPumpLedger->fresh()->name);

        // 3. getOrCreateTruckCostCentre (lines 158-168)
        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'MH12AB1234',
        ]);
        $costCentre = $postingService->getOrCreateTruckCostCentre($company, $truck);
        $this->assertNotNull($costCentre);
        $this->assertEquals('MH12AB1234', $costCentre->name);
        $this->assertEquals('truck', $costCentre->category);

        // 4. postInvoice with IGST > 0 (lines 274-281)
        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Interstate Client',
        ]);
        $igstInvoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'total_amount' => 1180.00,
            'base_amount' => 1000.00,
            'cgst' => 0.00,
            'sgst' => 0.00,
            'igst' => 180.00,
        ]);
        $igstVoucher = $postingService->postInvoice($igstInvoice);
        $this->assertNotNull($igstVoucher);
        $this->assertNotNull($igstVoucher->items->where('type', 'credit')->firstWhere('amount', 180.00));

        // 5. postInvoicePayment with invalid bank_ledger_id (line 325)
        $cashPayment = InvoicePayment::create([
            'invoice_id' => $igstInvoice->id,
            'amount' => 200.00,
            'mode' => 'cash',
            'bank_ledger_id' => 999999, // non-existent
            'payment_date' => now()->toDateString(),
        ]);
        $cashPaymentVoucher = $postingService->postInvoicePayment($cashPayment);
        $this->assertNotNull($cashPaymentVoucher);
        $cashLedger = Ledger::where('company_id', $company->id)->where('code', 'CASH')->firstOrFail();
        $this->assertNotNull($cashPaymentVoucher->items->where('type', 'debit')->firstWhere('ledger_id', $cashLedger->id));

        $bankPayment = InvoicePayment::create([
            'invoice_id' => $igstInvoice->id,
            'amount' => 300.00,
            'mode' => 'bank',
            'bank_ledger_id' => 999999, // non-existent
            'payment_date' => now()->toDateString(),
        ]);
        $bankPaymentVoucher = $postingService->postInvoicePayment($bankPayment);
        $this->assertNotNull($bankPaymentVoucher);
        $bankLedger = Ledger::where('company_id', $company->id)->where('code', 'BANK_PRIMARY')->firstOrFail();
        $this->assertNotNull($bankPaymentVoucher->items->where('type', 'debit')->firstWhere('ledger_id', $bankLedger->id));

        // 6. postFreightBooking with relationLoaded('customAmounts') (line 507)
        $booking = FreightBooking::create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'tr_amount' => 1500.00,
            'detention' => 200.00,
            'is_cancelled' => false,
        ]);
        $booking->load('customAmounts');
        $fbVoucher = $postingService->postFreightBooking($booking);
        $this->assertNotNull($fbVoucher);

        // 7. postVoucherPayment - missing ADV_TRANSPORTER ledger, missing Fuel Pump Payable, invalid bank_ledger_id, mode cash & bank (lines 655, 701, 705, 707, 709)
        // 7a. ADV_TRANSPORTER missing when adjusting in advance (line 655)
        $provisioner->skipEnsureLedgers = true;
        Ledger::where('company_id', $company->id)->where('code', 'ADV_TRANSPORTER')->delete();
        $vpAdvanceAdj = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'amount' => 400.00,
            'payment_type' => 'Bank',
            'payment_mode' => 'adjust in advance',
            'date' => now()->toDateString(),
        ]);
        $vpAdvVoucher = $postingService->postVoucherPayment($vpAdvanceAdj);
        $this->assertNotNull($vpAdvVoucher);
        $this->assertDatabaseHas('ledgers', ['company_id' => $company->id, 'code' => 'ADV_TRANSPORTER']);

        // 7b. Fuel pump unknown & Fuel Pump Payable A/c missing (line 701)
        Ledger::where('company_id', $company->id)->where('name', 'Fuel Pump Payable A/c')->delete();
        $vpFuelMissing = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'amount' => 250.00,
            'payment_type' => 'Bank',
            'payment_mode' => 'fuel',
            'remark' => 'Random roadside bunk with no match',
            'date' => now()->toDateString(),
        ]);
        $vpFuelVoucher = $postingService->postVoucherPayment($vpFuelMissing);
        $this->assertNotNull($vpFuelVoucher);
        $this->assertDatabaseHas('ledgers', ['company_id' => $company->id, 'name' => 'Fuel Pump Payable A/c']);
        $provisioner->skipEnsureLedgers = false;

        // 7c. invalid bank_ledger_id fallback to cash and bank (line 705)
        $vpInvalidBankCash = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'amount' => 100.00,
            'payment_type' => 'Cash',
            'payment_mode' => 'cash',
            'bank_ledger_id' => 999999,
            'date' => now()->toDateString(),
        ]);
        $vpCashVoucher = $postingService->postVoucherPayment($vpInvalidBankCash);
        $this->assertNotNull($vpCashVoucher);
        $this->assertNotNull($vpCashVoucher->items->where('type', 'credit')->firstWhere('ledger_id', $cashLedger->id));

        // 7d. No bank_ledger_id, mode = 'cash' (line 707)
        $vpModeCash = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'amount' => 150.00,
            'payment_type' => 'Cash',
            'payment_mode' => 'cash',
            'bank_ledger_id' => null,
            'date' => now()->toDateString(),
        ]);
        $vpModeCashVoucher = $postingService->postVoucherPayment($vpModeCash);
        $this->assertNotNull($vpModeCashVoucher);
        $this->assertNotNull($vpModeCashVoucher->items->where('type', 'credit')->firstWhere('ledger_id', $cashLedger->id));

        // 7e. No bank_ledger_id, mode = 'bank transfer' (line 709)
        $vpModeBank = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'amount' => 175.00,
            'payment_type' => 'Bank',
            'payment_mode' => 'bank transfer',
            'bank_ledger_id' => null,
            'date' => now()->toDateString(),
        ]);
        $vpModeBankVoucher = $postingService->postVoucherPayment($vpModeBank);
        $this->assertNotNull($vpModeBankVoucher);
        $this->assertNotNull($vpModeBankVoucher->items->where('type', 'credit')->firstWhere('ledger_id', $bankLedger->id));

        // 8. postAdvancePayment - missing ADV_TRANSPORTER, invalid bank_ledger_id, non-cash remark without bank_ledger_id (lines 802, 814, 816)
        // 8a. missing ADV_TRANSPORTER (line 802)
        $provisioner->skipEnsureLedgers = true;
        Ledger::where('company_id', $company->id)->where('code', 'ADV_TRANSPORTER')->delete();
        $advMissingAsset = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 500.00,
            'remark' => 'Standard bank advance',
        ]);
        $advVoucherMissing = $postingService->postAdvancePayment($advMissingAsset);
        $this->assertNotNull($advVoucherMissing);
        $this->assertDatabaseHas('ledgers', ['company_id' => $company->id, 'code' => 'ADV_TRANSPORTER']);
        $provisioner->skipEnsureLedgers = false;

        // 8b. invalid bank_ledger_id with cash remark and non-cash remark (line 814)
        $advInvalidBankCash = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 220.00,
            'bank_ledger_id' => 999999,
            'remark' => 'urgent cash advance',
        ]);
        $advInvalidCashVoucher = $postingService->postAdvancePayment($advInvalidBankCash);
        $this->assertNotNull($advInvalidCashVoucher);
        $this->assertNotNull($advInvalidCashVoucher->items->where('type', 'credit')->firstWhere('ledger_id', $cashLedger->id));

        $advInvalidBankNonCash = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 330.00,
            'bank_ledger_id' => 999999,
            'remark' => 'IMPS transfer advance',
        ]);
        $advInvalidBankVoucher = $postingService->postAdvancePayment($advInvalidBankNonCash);
        $this->assertNotNull($advInvalidBankVoucher);
        $this->assertNotNull($advInvalidBankVoucher->items->where('type', 'credit')->firstWhere('ledger_id', $bankLedger->id));

        // 8c. No bank_ledger_id, non-cash remark (line 816)
        $advNoBankNonCash = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 440.00,
            'bank_ledger_id' => null,
            'remark' => 'Direct transfer to account',
        ]);
        $advNoBankVoucher = $postingService->postAdvancePayment($advNoBankNonCash);
        $this->assertNotNull($advNoBankVoucher);
        $this->assertNotNull($advNoBankVoucher->items->where('type', 'credit')->firstWhere('ledger_id', $bankLedger->id));

        // 9. postCreditNote - fallback to CGST_OUT when IGST_OUT is absent (line 928)
        $provisioner->skipIgst = true;
        Ledger::where('company_id', $company->id)->where('code', 'IGST_OUT')->delete();
        $cgstLedger = Ledger::where('company_id', $company->id)->where('code', 'CGST_OUT')->firstOrFail();
        $crnCgstVoucher = $postingService->postCreditNote($igstInvoice, 100.00, 'IGST missing fallback', 18.00);
        $this->assertNotNull($crnCgstVoucher);
        $this->assertNotNull($crnCgstVoucher->items->where('type', 'debit')->firstWhere('ledger_id', $cgstLedger->id));

        // 10. postDebitNote - missing SHORTAGE_REC & booking is null (lines 968, 970-971)
        $provisioner->skipEnsureLedgers = true;
        Ledger::where('company_id', $company->id)->where('code', 'SHORTAGE_REC')->delete();
        $dbnNullBookingVoucher = $postingService->postDebitNote($transporter, 80.00, 'Direct debit note without booking', null);
        $this->assertNotNull($dbnNullBookingVoucher);
        $this->assertDatabaseHas('ledgers', ['company_id' => $company->id, 'code' => 'SHORTAGE_REC']);
        $this->assertEquals('Truck: General | LR: -', $dbnNullBookingVoucher->reference_number);
        $provisioner->skipEnsureLedgers = false;
    }

    public function test_financial_report_service_get_day_book_all_branches(): void
    {
        $company = Company::create(['name' => 'DayBook Test Logistics']);
        $group = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'General Accounts',
            'primary_type' => 'asset',
        ]);
        $cash = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $group->id,
            'name' => 'Cash A/c',
            'is_active' => true,
        ]);
        $party = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $group->id,
            'name' => 'Party A/c',
            'is_active' => true,
        ]);

        $user = \App\Models\User::create([
            'name' => 'DB User',
            'email' => 'daybook_user_' . uniqid() . '@truckbill.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        // 1. Sales voucher with debit item and without debit item
        $vchSalesWithDebit = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'sales',
            'voucher_number' => 'SALES-01',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'is_posted' => true,
            'user_id' => $user->id,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $vchSalesWithDebit->id,
            'ledger_id' => $party->id,
            'type' => 'debit',
            'amount' => 1000.00,
        ]);

        $vchSalesNoDebit = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'sales',
            'voucher_number' => 'SALES-02',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 500.00,
            'is_posted' => true,
        ]);

        // 2. Purchase voucher with credit item and without credit item
        $vchPurWithCredit = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'purchase',
            'voucher_number' => 'PUR-01',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 2000.00,
            'is_posted' => true,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $vchPurWithCredit->id,
            'ledger_id' => $party->id,
            'type' => 'credit',
            'amount' => 2000.00,
        ]);

        $vchPurNoCredit = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'purchase',
            'voucher_number' => 'PUR-02',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 600.00,
            'is_posted' => true,
        ]);

        // 3. Payment voucher with debit item and without debit item
        $vchPayWithDebit = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'PAY-01',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 300.00,
            'is_posted' => true,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $vchPayWithDebit->id,
            'ledger_id' => $party->id,
            'type' => 'debit',
            'amount' => 300.00,
        ]);

        $vchPayNoDebit = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'PAY-02',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 150.00,
            'is_posted' => true,
        ]);

        // 4. Receipt voucher with credit item and without credit item
        $vchRecWithCredit = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'receipt',
            'voucher_number' => 'REC-01',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 700.00,
            'is_posted' => true,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $vchRecWithCredit->id,
            'ledger_id' => $party->id,
            'type' => 'credit',
            'amount' => 700.00,
        ]);

        $vchRecNoCredit = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'receipt',
            'voucher_number' => 'REC-02',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 250.00,
            'is_posted' => true,
        ]);

        // 5. Default voucher type (journal) with multiple ledger items
        $vchJournal = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JRN-01',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 400.00,
            'is_posted' => true,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $vchJournal->id,
            'ledger_id' => $cash->id,
            'type' => 'debit',
            'amount' => 400.00,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $vchJournal->id,
            'ledger_id' => $party->id,
            'type' => 'credit',
            'amount' => 400.00,
        ]);

        $reportService = new FinancialReportService;

        // Test with null fromDate and toDate (defaults to today)
        $allDayBook = $reportService->getDayBook($company);
        $this->assertCount(9, $allDayBook);

        // Test particulars fallbacks
        $salesDebitRow = $allDayBook->firstWhere('voucher_number', 'SALES-01');
        $this->assertEquals('Party A/c', $salesDebitRow['particulars']);
        $salesNoDebitRow = $allDayBook->firstWhere('voucher_number', 'SALES-02');
        $this->assertEquals('Sales', $salesNoDebitRow['particulars']);

        $purCreditRow = $allDayBook->firstWhere('voucher_number', 'PUR-01');
        $this->assertEquals('Party A/c', $purCreditRow['particulars']);
        $purNoCreditRow = $allDayBook->firstWhere('voucher_number', 'PUR-02');
        $this->assertEquals('Purchase', $purNoCreditRow['particulars']);

        $payDebitRow = $allDayBook->firstWhere('voucher_number', 'PAY-01');
        $this->assertEquals('Party A/c', $payDebitRow['particulars']);
        $payNoDebitRow = $allDayBook->firstWhere('voucher_number', 'PAY-02');
        $this->assertEquals('Payment', $payNoDebitRow['particulars']);

        $recCreditRow = $allDayBook->firstWhere('voucher_number', 'REC-01');
        $this->assertEquals('Party A/c', $recCreditRow['particulars']);
        $recNoCreditRow = $allDayBook->firstWhere('voucher_number', 'REC-02');
        $this->assertEquals('Receipt', $recNoCreditRow['particulars']);

        $jrnRow = $allDayBook->firstWhere('voucher_number', 'JRN-01');
        $this->assertStringContainsString('Cash A/c', $jrnRow['particulars']);
        $this->assertStringContainsString('Party A/c', $jrnRow['particulars']);

        // Test voucher_type filter
        $salesOnly = $reportService->getDayBook($company, null, null, 'sales');
        $this->assertCount(2, $salesOnly);

        $purchaseOnly = $reportService->getDayBook($company, null, null, 'purchase');
        $this->assertCount(2, $purchaseOnly);

        $paymentOnly = $reportService->getDayBook($company, null, null, 'payment');
        $this->assertCount(2, $paymentOnly);

        $receiptOnly = $reportService->getDayBook($company, null, null, 'receipt');
        $this->assertCount(2, $receiptOnly);
    }

    public function test_financial_report_service_get_trial_balance_modes_and_orphan_ledgers(): void
    {
        $company = Company::create(['name' => 'TB Orphan & Modes Company']);

        // Root group (parent_id = null)
        $rootAsset = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Current Assets',
            'primary_type' => 'asset',
            'parent_id' => null,
        ]);
        $subAsset = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Bank Accounts',
            'primary_type' => 'asset',
            'parent_id' => $rootAsset->id,
        ]);

        $bankLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $subAsset->id,
            'name' => 'HDFC Bank',
            'opening_balance' => 10000.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        // Zero-balance ledger (skipped in trial balance loop)
        $zeroLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $subAsset->id,
            'name' => 'Zero Activity Ledger',
            'opening_balance' => 0.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        // Orphan group: parent_id points to a non-existent group (e.g. 999999), so it has no root group!
        $orphanGroup = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Unlinked Detached Group',
            'primary_type' => 'asset',
            'parent_id' => 999999,
        ]);

        $orphanDebitLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $orphanGroup->id,
            'name' => 'Orphan Debit Ledger',
            'opening_balance' => 2500.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        $orphanCreditLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $orphanGroup->id,
            'name' => 'Orphan Credit Ledger',
            'opening_balance' => 1500.00,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $orphanZeroLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $orphanGroup->id,
            'name' => 'Orphan Zero Ledger',
            'opening_balance' => 0.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        // Add a voucher on the orphan debit ledger to test orphan period transactions
        $vch = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'ORPH-01',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 500.00,
            'is_posted' => true,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $vch->id,
            'ledger_id' => $orphanDebitLedger->id,
            'type' => 'credit',
            'amount' => 500.00,
        ]);

        $reportService = new FinancialReportService;

        // 1. Mode 'all' (closing balance) with null asOfDate
        $tbAll = $reportService->getTrialBalance($company, null, 'all');
        $this->assertEquals('all', $tbAll['mode']);
        $this->assertNotEmpty($tbAll['groups']);

        // Check that 'Other Accounts' exists for orphan ledgers
        $otherAccountsGroup = collect($tbAll['groups'])->firstWhere('group_name', 'Other Accounts');
        $this->assertNotNull($otherAccountsGroup);
        $this->assertNull($otherAccountsGroup['group_id']);
        $this->assertEquals('asset', $otherAccountsGroup['primary_type']);
        $orphanLedgers = collect($otherAccountsGroup['ledgers']);
        $this->assertTrue($orphanLedgers->contains('name', 'Orphan Debit Ledger'));
        $this->assertTrue($orphanLedgers->contains('name', 'Orphan Credit Ledger'));
        $this->assertFalse($orphanLedgers->contains('name', 'Orphan Zero Ledger'));

        // 2. Mode 'opening'
        $tbOpening = $reportService->getTrialBalance($company, now()->toDateString(), 'opening');
        $this->assertEquals('opening', $tbOpening['mode']);
        $this->assertGreaterThan(0, $tbOpening['total_debit']);

        // 3. Mode 'transactions'
        $tbTrans = $reportService->getTrialBalance($company, now()->toDateString(), 'transactions');
        $this->assertEquals('transactions', $tbTrans['mode']);

        // 4. Mode invalid fallback to 'all'
        $tbInvalid = $reportService->getTrialBalance($company, now()->toDateString(), 'unknown_mode');
        $this->assertEquals('all', $tbInvalid['mode']);

        // Check difference and totals keys
        $this->assertArrayHasKey('opening_totals', $tbAll);
        $this->assertArrayHasKey('period_totals', $tbAll);
        $this->assertArrayHasKey('closing_totals', $tbAll);
        $this->assertArrayHasKey('is_balanced', $tbAll);
    }

    public function test_financial_report_service_get_bill_to_bill_outstandings_customer_and_transporter(): void
    {
        $company = Company::create(['name' => 'Bill Outstandings Full Test']);

        // 1. Customer Ledger Outstandings
        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Deepak Industries',
        ]);
        $customerLedger = Ledger::create([
            'company_id' => $company->id,
            'name' => 'Deepak Industries A/c',
            'entity_type' => Consignee::class,
            'entity_id' => $customer->id,
            'is_active' => true,
        ]);

        // Invoice 1: Partially settled with TDS and Deduction
        $invPartial = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PARTIAL-01',
            'generated_date' => '2026-09-01',
            'total_amount' => 10000.00,
            'net_amount' => 10000.00,
            'payment_status' => 'partial',
        ]);
        InvoicePayment::create([
            'invoice_id' => $invPartial->id,
            'payment_date' => '2026-09-05',
            'mode' => 'neft',
            'amount' => 6000.00,
            'tds_amount' => 200.00,
            'deduction_amount' => 150.00,
            'deduction_reason' => 'Rate adjustment',
            'remark' => 'Partial settlement received',
        ]);

        // Invoice 2: Settled invoice
        $invSettled = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-SETTLED-02',
            'generated_date' => '2026-09-02',
            'total_amount' => 5000.00,
            'net_amount' => 5000.00,
            'payment_status' => 'settled',
        ]);
        InvoicePayment::create([
            'invoice_id' => $invSettled->id,
            'payment_date' => '2026-09-06',
            'mode' => 'cash',
            'amount' => 5000.00,
            'tds_amount' => 0.00,
            'deduction_amount' => 0.00,
        ]);

        // Invoice 3: Pending invoice with fallback manual invoice number and null generated date
        $invPendingManual = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => null,
            'manual_invoice_number' => 'MAN-INV-999',
            'generated_date' => null,
            'total_amount' => 3000.00,
            'net_amount' => null,
            'payment_status' => 'pending',
        ]);

        // Invoice 4: Pending invoice with null manual invoice number (fallback to INV-id)
        $invPendingFallback = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => null,
            'manual_invoice_number' => null,
            'generated_date' => '2026-09-04',
            'total_amount' => 2000.00,
            'payment_status' => 'pending',
        ]);

        $reportService = new FinancialReportService;

        // Test Customer bill-to-bill without filter
        $custOutstandings = $reportService->getBillToBillOutstandings($customerLedger);
        $this->assertNotNull($custOutstandings);
        $this->assertEquals('Customer', $custOutstandings['entity_type']);
        $this->assertEquals(4, $custOutstandings['total_count']);
        $this->assertEquals(4, $custOutstandings['unfiltered_count']);
        $this->assertEquals(1, $custOutstandings['settled_count']);
        $this->assertEquals(3, $custOutstandings['pending_count']);

        // Check payment display text with TDS and Ded
        $partialBill = collect($custOutstandings['bills'])->firstWhere('id', $invPartial->id);
        $this->assertEquals('partial', $partialBill['status']);
        $this->assertNotEmpty($partialBill['payments']);
        $this->assertStringContainsString('TDS: ₹', $partialBill['payments'][0]['display_text']);
        $this->assertStringContainsString('Ded: ₹', $partialBill['payments'][0]['display_text']);

        // Check fallback bill_no and date
        $manualBill = collect($custOutstandings['bills'])->firstWhere('id', $invPendingManual->id);
        $this->assertEquals('MAN-INV-999', $manualBill['bill_no']);
        $this->assertEquals('-', $manualBill['bill_date']);

        $fallbackBill = collect($custOutstandings['bills'])->firstWhere('id', $invPendingFallback->id);
        $this->assertEquals('INV-' . $invPendingFallback->id, $fallbackBill['bill_no']);

        // Filters: pending, partial, settled
        $custPending = $reportService->getBillToBillOutstandings($customerLedger, 'pending');
        $this->assertCount(2, $custPending['bills']);

        $custPartial = $reportService->getBillToBillOutstandings($customerLedger, 'partial');
        $this->assertCount(1, $custPartial['bills']);

        $custSettled = $reportService->getBillToBillOutstandings($customerLedger, 'settled');
        $this->assertCount(1, $custSettled['bills']);

        // 2. Transporter Ledger Outstandings
        $master = TransporterMaster::create([
            'name' => 'Royal Highway Fleet',
            'pan_number' => 'ABCDE9999F',
        ]);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'contact_person_name' => 'Royal Manager',
        ]);
        $transporterLedger = Ledger::create([
            'company_id' => $company->id,
            'name' => 'Royal Highway Fleet A/c',
            'entity_type' => Transporter::class,
            'entity_id' => $transporter->id,
            'is_active' => true,
        ]);

        $truck = Truck::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'truck_number' => 'RJ27GB8888',
        ]);
        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'lr_number' => 'LR-8888',
        ]);

        // Booking 1: Direct transporter_id, with voucher_number, partial status
        $fbDirect = FreightBooking::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'lr_id' => $lr->id,
            'tr_amount' => 8000.00,
            'balance_amount' => 3000.00,
            'voucher_number' => 'VCH-FB-01',
        ]);
        VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $fbDirect->id,
            'amount' => 5000.00,
            'payment_mode' => 'bank',
            'date' => '2026-09-08',
            'remark' => 'Transporter advance payment',
        ]);

        // Booking 2: Matched via lr.truck.transporter_id, settled status
        $fbTruckMatch = FreightBooking::create([
            'company_id' => $company->id,
            'transporter_id' => null,
            'lr_id' => $lr->id,
            'tr_amount' => 4000.00,
            'balance_amount' => 0.00,
            'voucher_number' => null,
        ]);
        VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $fbTruckMatch->id,
            'amount' => 4000.00,
            'payment_mode' => 'cash',
            'date' => null,
            'remark' => 'Cash trip clear',
        ]);

        // Booking 3: Pending booking with tr_amount = 0 (falls back to total_amount), no lr, balance_amount = null
        $fbPending = FreightBooking::withoutEvents(function () use ($company, $transporter) {
            $fb = new FreightBooking([
                'company_id' => $company->id,
                'transporter_id' => $transporter->id,
                'lr_id' => null,
                'tr_amount' => 0.00,
                'voucher_number' => null,
                'balance_amount' => null,
            ]);
            $fb->forceFill(['total_amount' => 6000.00]);
            $fb->save();

            return $fb;
        });

        $transOutstandings = $reportService->getBillToBillOutstandings($transporterLedger);
        $this->assertNotNull($transOutstandings);
        $this->assertEquals('Transporter', $transOutstandings['entity_type']);
        $this->assertEquals(3, $transOutstandings['total_count']);

        $directFbRow = collect($transOutstandings['bills'])->firstWhere('id', $fbDirect->id);
        $this->assertEquals('VCH-FB-01', $directFbRow['bill_no']);
        $this->assertEquals('RJ27GB8888', $directFbRow['truck_no']);
        $this->assertEquals('LR-8888', $directFbRow['lr_no']);
        $this->assertEquals('partial', $directFbRow['status']);

        $pendingFbRow = collect($transOutstandings['bills'])->firstWhere('id', $fbPending->id);
        $this->assertEquals('LR-N/A', $pendingFbRow['bill_no']);
        $this->assertEquals('N/A', $pendingFbRow['truck_no']);
        $this->assertEquals('N/A', $pendingFbRow['lr_no']);
        $this->assertEquals('pending', $pendingFbRow['status']);

        // 3. Unsupported entity type with empty bills returns null
        $unsupportedLedger = Ledger::create([
            'company_id' => $company->id,
            'name' => 'Internal Expense Ledger',
            'entity_type' => 'App\Models\SomeOtherEntity',
            'entity_id' => 999,
            'is_active' => true,
        ]);
        $this->assertNull($reportService->getBillToBillOutstandings($unsupportedLedger));
    }

    public function test_financial_report_service_is_direct_group_and_profit_and_loss_hierarchy(): void
    {
        $reportService = new FinancialReportService;

        // 1. isDirectGroup branches
        $this->assertFalse($reportService->isDirectGroup(null, 'income'));

        $directExpenseGroup = new AccountGroup(['name' => 'Direct Expenses', 'parent_id' => null]);
        $this->assertTrue($reportService->isDirectGroup($directExpenseGroup, 'expense'));

        $freightRevenue = new AccountGroup(['name' => 'Freight Revenue', 'parent_id' => null]);
        $this->assertTrue($reportService->isDirectGroup($freightRevenue, 'income'));
        $this->assertFalse($reportService->isDirectGroup($freightRevenue, 'expense'));

        $detentionGroup = new AccountGroup(['name' => 'Detention & Demurrage Charges', 'parent_id' => null]);
        $this->assertTrue($reportService->isDirectGroup($detentionGroup, 'income'));

        $hiringGroup = new AccountGroup(['name' => 'Transporter Freight Hiring Charges', 'parent_id' => null]);
        $this->assertTrue($reportService->isDirectGroup($hiringGroup, 'expense'));
        $this->assertFalse($reportService->isDirectGroup($hiringGroup, 'income'));

        $dieselGroup = new AccountGroup(['name' => 'Diesel & Fuel Expenses', 'parent_id' => null]);
        $this->assertTrue($reportService->isDirectGroup($dieselGroup, 'expense'));

        $tollGroup = new AccountGroup(['name' => 'Toll & Border Taxes', 'parent_id' => null]);
        $this->assertTrue($reportService->isDirectGroup($tollGroup, 'expense'));

        $driverBhattaGroup = new AccountGroup(['name' => 'Driver Bhatta & Expenses', 'parent_id' => null]);
        $this->assertTrue($reportService->isDirectGroup($driverBhattaGroup, 'expense'));

        // Parent group contains 'direct'
        $company = Company::create(['name' => 'PnL Test Company']);
        $parentDirect = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Direct Operations',
            'primary_type' => 'expense',
            'parent_id' => null,
        ]);
        $childOfDirect = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Loading & Unloading Labour',
            'primary_type' => 'expense',
            'parent_id' => $parentDirect->id,
        ]);
        $this->assertTrue($reportService->isDirectGroup($childOfDirect, 'expense'));

        $parentNonDirect = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Administrative Overheads',
            'primary_type' => 'expense',
            'parent_id' => null,
        ]);
        $childOfNonDirect = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Office Refreshment',
            'primary_type' => 'expense',
            'parent_id' => $parentNonDirect->id,
        ]);
        $this->assertFalse($reportService->isDirectGroup($childOfNonDirect, 'expense'));

        // 2. Profit & Loss: Test Gross Loss and Net Loss scenario with multi-level group hierarchy
        // Grandchild group under parentNonDirect
        $grandChildGroup = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Tea & Snacks',
            'primary_type' => 'expense',
            'parent_id' => $childOfNonDirect->id,
        ]);

        $grandChildLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $grandChildGroup->id,
            'name' => 'Staff Tea Expenses',
            'opening_balance' => 5000.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        // Direct ledger attached directly to parentNonDirect root group
        $directRootLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $parentNonDirect->id,
            'name' => 'Audit Fees',
            'opening_balance' => 12000.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        // Direct expense group with large debit to create Gross Loss
        $directExpLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $childOfDirect->id,
            'name' => 'Direct Labour Ledger',
            'opening_balance' => 50000.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        // Direct Income group with smaller credit
        $incomeRoot = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Freight Revenue',
            'primary_type' => 'income',
            'parent_id' => null,
        ]);
        $incomeLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $incomeRoot->id,
            'name' => 'Trip Revenue Ledger',
            'opening_balance' => 20000.00,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        // Indirect income group & ledger to cover line 699 ($indirectIncomes[])
        $indirectIncomeRoot = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Commission & Other Incomes',
            'primary_type' => 'income',
            'parent_id' => null,
        ]);
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $indirectIncomeRoot->id,
            'name' => 'Brokerage Commission Received',
            'opening_balance' => 4000.00,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        // Zero-balance expense ledger (skipped)
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $parentNonDirect->id,
            'name' => 'Zero Balance Expense',
            'opening_balance' => 0.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        // Call getProfitAndLoss with null dates
        $pnlLoss = $reportService->getProfitAndLoss($company);

        $this->assertEquals(20000.00, $pnlLoss['total_direct_income']);
        $this->assertEquals(50000.00, $pnlLoss['total_direct_expense']);
        $this->assertNotEmpty($pnlLoss['indirect_incomes']);
        $this->assertLessThan(0, $pnlLoss['gross_profit']); // Gross loss: 20000 - 50000 = -30000
        $this->assertLessThan(0, $pnlLoss['net_profit']); // Net loss
        $this->assertGreaterThan(0, $pnlLoss['grand_total']);
        $this->assertGreaterThan(0, $pnlLoss['pnl_total']);

        // Canonical groups: verify Purchase Accounts and Sales Accounts are present
        $this->assertTrue(collect($pnlLoss['expense_groups'])->contains('name', 'Purchase Accounts'));
        $this->assertTrue(collect($pnlLoss['expense_groups'])->contains('name', 'Direct Expenses'));
        $this->assertTrue(collect($pnlLoss['expense_groups'])->contains('name', 'Indirect Expenses'));
        $this->assertTrue(collect($pnlLoss['income_groups'])->contains('name', 'Sales Accounts'));
        $this->assertTrue(collect($pnlLoss['income_groups'])->contains('name', 'Direct Incomes'));
        $this->assertTrue(collect($pnlLoss['income_groups'])->contains('name', 'Indirect Incomes'));

        // Test with company that ALREADY HAS canonical groups (to cover the true branches of hasPurchase, etc.)
        $canonicalCompany = Company::create(['name' => 'Canonical Groups Corp']);
        AccountGroup::create(['company_id' => $canonicalCompany->id, 'name' => 'Purchase Accounts', 'primary_type' => 'expense', 'parent_id' => null]);
        AccountGroup::create(['company_id' => $canonicalCompany->id, 'name' => 'Direct Expenses', 'primary_type' => 'expense', 'parent_id' => null]);
        AccountGroup::create(['company_id' => $canonicalCompany->id, 'name' => 'Indirect Expenses', 'primary_type' => 'expense', 'parent_id' => null]);
        AccountGroup::create(['company_id' => $canonicalCompany->id, 'name' => 'Sales Accounts', 'primary_type' => 'income', 'parent_id' => null]);
        AccountGroup::create(['company_id' => $canonicalCompany->id, 'name' => 'Direct Incomes', 'primary_type' => 'income', 'parent_id' => null]);
        AccountGroup::create(['company_id' => $canonicalCompany->id, 'name' => 'Indirect Incomes', 'primary_type' => 'income', 'parent_id' => null]);

        $canonicalPnl = $reportService->getProfitAndLoss($canonicalCompany, now()->startOfYear()->toDateString(), now()->toDateString());
        $this->assertEquals(0.0, $canonicalPnl['net_profit']);
    }

    public function test_financial_report_service_balance_sheet_and_debtors_aging_full_branches(): void
    {
        $company = Company::create(['name' => 'BS & Aging Full Test Company']);

        // 1. Balance Sheet: Hierarchy for Liabilities and Assets
        $liabRoot = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Loans (Liability)',
            'primary_type' => 'liability',
            'parent_id' => null,
        ]);
        $liabSub = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Bank Overdraft',
            'primary_type' => 'liability',
            'parent_id' => $liabRoot->id,
        ]);
        $liabGrandChild = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'SBI OD A/c',
            'primary_type' => 'liability',
            'parent_id' => $liabSub->id,
        ]);

        // Subgroup ledger with credit balance
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $liabGrandChild->id,
            'name' => 'SBI OD Facility',
            'opening_balance' => 40000.00,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);
        // Direct ledger on liability root with debit balance (val < 0)
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $liabRoot->id,
            'name' => 'Advance Repayment to Bank',
            'opening_balance' => 5000.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        // Zero balance liability ledger directly on root (skipped)
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $liabRoot->id,
            'name' => 'Dormant Loan',
            'opening_balance' => 0.00,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);
        // Subgroup liability ledger with zero balance (covers line 1010)
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $liabSub->id,
            'name' => 'Zero Subgroup Liability',
            'opening_balance' => 0.00,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        // Asset hierarchy
        $assetRoot = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Current Assets Primary',
            'primary_type' => 'asset',
            'parent_id' => null,
        ]);
        $assetSub = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Liquid Assets',
            'primary_type' => 'asset',
            'parent_id' => $assetRoot->id,
        ]);
        $assetGrandChild = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Petty Cash Centers',
            'primary_type' => 'asset',
            'parent_id' => $assetSub->id,
        ]);

        // Asset ledger with normal debit balance
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $assetGrandChild->id,
            'name' => 'Prepaid Insurance',
            'opening_balance' => 15000.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        // Cash ledger with negative balance (is_negative_cash = true) via credit opening balance
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $assetGrandChild->id,
            'name' => 'Main Cash In Hand',
            'code' => 'CASH',
            'opening_balance' => 3000.00,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        // Direct ledger on asset root with credit balance (val < 0) and is_negative_cash = true
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $assetRoot->id,
            'name' => 'Yard Cash Counter',
            'opening_balance' => 2000.00,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        // Direct ledger on asset root with normal positive balance
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $assetRoot->id,
            'name' => 'Security Deposit with Port',
            'opening_balance' => 25000.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        // Zero balance asset ledger directly on root (skipped)
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $assetRoot->id,
            'name' => 'Zero Asset',
            'opening_balance' => 0.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        // Subgroup asset ledger with zero balance (covers line 1125)
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $assetSub->id,
            'name' => 'Zero Subgroup Asset',
            'opening_balance' => 0.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        // Revenue ledger so $company has Net Profit > 0 (covers lines 1070-1085: Profit & Loss A/c in Liabilities)
        $bsSalesGroup = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Sales Accounts',
            'primary_type' => 'income',
            'parent_id' => null,
        ]);
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $bsSalesGroup->id,
            'name' => 'Transport Sales Income',
            'opening_balance' => 10000.00,
            'opening_balance_type' => 'credit',
            'is_active' => true,
        ]);

        $reportService = new FinancialReportService;

        // Test Balance Sheet with null asOfDate
        $bs = $reportService->getBalanceSheet($company);

        $this->assertNotEmpty($bs['liability_groups']);
        $this->assertNotEmpty($bs['asset_groups']);
        $this->assertArrayHasKey('total_assets', $bs);
        $this->assertArrayHasKey('total_liabilities', $bs);
        $this->assertArrayHasKey('difference', $bs);

        // Verify is_negative_cash flag
        $negativeCashAsset = collect($bs['assets'])->firstWhere('name', 'Main Cash In Hand');
        $this->assertNotNull($negativeCashAsset);
        $this->assertTrue($negativeCashAsset['is_negative_cash']);

        $negativeYardCash = collect($bs['assets'])->firstWhere('name', 'Yard Cash Counter');
        $this->assertNotNull($negativeYardCash);
        $this->assertTrue($negativeYardCash['is_negative_cash']);

        $positiveAsset = collect($bs['assets'])->firstWhere('name', 'Security Deposit with Port');
        $this->assertNotNull($positiveAsset);
        $this->assertFalse($positiveAsset['is_negative_cash']);

        // Verify Profit & Loss A/c under liabilities when Net Profit > 0
        $pnlGroupProfit = collect($bs['liability_groups'])->firstWhere('id', 'pnl');
        $this->assertNotNull($pnlGroupProfit);
        $this->assertSame('Profit & Loss A/c', $pnlGroupProfit['name']);
        $this->assertSame('Current Period Net Profit', $pnlGroupProfit['direct_ledgers'][0]['name']);
        $this->assertEquals(10000.00, $pnlGroupProfit['subtotal']);

        // Test Balance Sheet with Net Loss to cover 'Current Period Net Loss' on line 1078
        $lossCompany = Company::create(['name' => 'BS Loss Test Company']);
        $lossExpenseGroup = AccountGroup::create([
            'company_id' => $lossCompany->id,
            'name' => 'Direct Expenses',
            'primary_type' => 'expense',
            'parent_id' => null,
        ]);
        Ledger::create([
            'company_id' => $lossCompany->id,
            'account_group_id' => $lossExpenseGroup->id,
            'name' => 'Operating Loss Expense',
            'opening_balance' => 15000.00,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);
        $bsLoss = $reportService->getBalanceSheet($lossCompany);
        $pnlGroupLoss = collect($bsLoss['liability_groups'])->firstWhere('id', 'pnl');
        $this->assertNotNull($pnlGroupLoss);
        $this->assertSame('Current Period Net Loss', $pnlGroupLoss['direct_ledgers'][0]['name']);
        $this->assertEquals(-15000.00, $pnlGroupLoss['subtotal']);

        $asOf = \Carbon\Carbon::parse('2026-10-25');

        // 2. Debtors Aging Report: All buckets, walk-in customer, MSME alerts, payments
        $walkInInvoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => 0, // Walk-in / Direct Debtors
            'invoice_number' => null,
            'manual_invoice_number' => null, // Fallback INV-id
            'generated_date' => $asOf->toDateString(),
            'total_amount' => 5000.00,
            'net_amount' => 5000.00,
        ]);

        $agingCustomer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Global Logistics Buyer',
            'gst_number' => '08AAACA5555B1Z1',
            'contact_number' => '9111222333',
        ]);

        // Bucket 0-30 days
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $agingCustomer->id,
            'invoice_number' => 'INV-AGE-10',
            'generated_date' => $asOf->copy()->subDays(15)->toDateString(),
            'total_amount' => 10000.00,
            'net_amount' => 10000.00,
        ]);

        // Bucket 31-60 days (age = 40 days <= 45 days, not MSME overdue)
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $agingCustomer->id,
            'invoice_number' => 'INV-AGE-40',
            'generated_date' => $asOf->copy()->subDays(40)->toDateString(),
            'total_amount' => 8000.00,
            'net_amount' => 8000.00,
        ]);

        // Bucket 31-60 days (age = 50 days > 45 days, MSME overdue)
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $agingCustomer->id,
            'invoice_number' => 'INV-AGE-50',
            'generated_date' => $asOf->copy()->subDays(50)->toDateString(),
            'total_amount' => 6000.00,
            'net_amount' => 6000.00,
        ]);

        // Bucket 61-90 days (age = 75 days, MSME overdue)
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $agingCustomer->id,
            'invoice_number' => 'INV-AGE-75',
            'generated_date' => $asOf->copy()->subDays(75)->toDateString(),
            'total_amount' => 4000.00,
            'net_amount' => 4000.00,
        ]);

        // Bucket > 90 days (age = 110 days, MSME overdue)
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $agingCustomer->id,
            'invoice_number' => 'INV-AGE-110',
            'generated_date' => $asOf->copy()->subDays(110)->toDateString(),
            'total_amount' => 7000.00,
            'net_amount' => 7000.00,
        ]);

        // Fully paid invoice (pending <= 0.01 -> skipped)
        $paidInv = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $agingCustomer->id,
            'invoice_number' => 'INV-PAID-FULL',
            'generated_date' => $asOf->copy()->subDays(20)->toDateString(),
            'total_amount' => 3000.00,
            'net_amount' => 3000.00,
        ]);
        InvoicePayment::create([
            'invoice_id' => $paidInv->id,
            'payment_date' => $asOf->copy()->subDays(10)->toDateString(),
            'mode' => 'bank',
            'amount' => 3000.00,
        ]);

        // Invoice with payments that have TDS, deductions, and future date payment (future date ignored)
        $invWithPartial = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $agingCustomer->id,
            'invoice_number' => 'INV-TDS-DED',
            'generated_date' => $asOf->copy()->subDays(25)->toDateString(),
            'total_amount' => 5000.00,
            'net_amount' => 5000.00,
        ]);
        InvoicePayment::create([
            'invoice_id' => $invWithPartial->id,
            'payment_date' => $asOf->copy()->subDays(5)->toDateString(),
            'mode' => 'neft',
            'amount' => 2000.00,
            'tds_amount' => 100.00,
            'deduction_amount' => 50.00,
        ]);
        InvoicePayment::create([
            'invoice_id' => $invWithPartial->id,
            'payment_date' => $asOf->copy()->addDays(5)->toDateString(), // after asOfDate, ignored
            'mode' => 'cash',
            'amount' => 1000.00,
        ]);

        // Run debtors aging with null asOfDate and with explicit asOfDate
        $agingReportNullDate = $reportService->getDebtorsAgingReport($company);
        $this->assertNotEmpty($agingReportNullDate['customers']);

        $agingReport = $reportService->getDebtorsAgingReport($company, $asOf->toDateString());
        $this->assertNotEmpty($agingReport['customers']);

        // Check walk-in customer
        $walkInGroup = collect($agingReport['customers'])->firstWhere('customer_id', 0);
        $this->assertNotNull($walkInGroup);
        $this->assertEquals('Walk-in / Direct Debtors', $walkInGroup['customer_name']);
        $this->assertEquals('-', $walkInGroup['gst_number']);
        $this->assertEquals('-', $walkInGroup['contact_number']);
        $this->assertEquals('INV-' . $walkInInvoice->id, $walkInGroup['invoices'][0]['invoice_number']);
        $this->assertEquals($asOf->format('d-m-Y'), $walkInGroup['invoices'][0]['date']);

        // Check aging customer totals and buckets
        $customerData = collect($agingReport['customers'])->firstWhere('customer_id', $agingCustomer->id);
        $this->assertNotNull($customerData);
        $this->assertTrue($customerData['has_msme_alert']);
        $this->assertGreaterThan(0, $customerData['msme_overdue_amount']);
        $this->assertGreaterThan(0, $customerData['bucket_0_30']);
        $this->assertGreaterThan(0, $customerData['bucket_31_60']);
        $this->assertGreaterThan(0, $customerData['bucket_61_90']);
        $this->assertGreaterThan(0, $customerData['bucket_over_90']);

        // Overall report totals
        $this->assertGreaterThan(0, $agingReport['totals']['total_receivables']);
        $this->assertGreaterThan(0, $agingReport['totals']['total_0_30']);
        $this->assertGreaterThan(0, $agingReport['totals']['total_31_60']);
        $this->assertGreaterThan(0, $agingReport['totals']['total_61_90']);
        $this->assertGreaterThan(0, $agingReport['totals']['total_over_90']);
        $this->assertGreaterThan(0, $agingReport['totals']['total_msme_overdue']);
        $this->assertEquals(1, $agingReport['totals']['msme_alert_customers_count']);
    }
}


