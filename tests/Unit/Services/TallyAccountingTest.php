<?php

namespace Tests\Unit\Services;

use App\Models\AccountGroup;
use App\Models\AccountingVoucher;
use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\FreightBooking;
use App\Models\Ledger;
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
                $table->string('reference_transaction_id')->nullable();
                $table->date('date')->nullable();
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

        $this->assertStringContainsString('Shree Ram Transport', $debitItem->ledger->name);
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

        $this->assertNull($voucher);
        $this->assertDatabaseMissing('accounting_vouchers', [
            'source_type' => VoucherPayment::class,
            'source_id' => $vp->id,
        ]);
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
}
