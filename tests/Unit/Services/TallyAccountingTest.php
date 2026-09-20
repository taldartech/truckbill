<?php

namespace Tests\Unit\Services;

use App\Models\AccountGroup;
use App\Models\AccountingVoucher;
use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Ledger;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\VoucherPayment;
use App\Services\AccountingPostingService;
use App\Services\ChartOfAccountsProvisioner;
use App\Services\FinancialReportService;
use App\Services\TallyXmlExportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
                $table->string('short_name')->nullable();
                $table->string('dummy_pan_number')->nullable();
                $table->string('bank')->nullable();
                $table->string('account_number')->nullable();
                $table->timestamps();
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
}

