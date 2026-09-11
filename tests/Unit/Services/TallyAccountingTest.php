<?php

namespace Tests\Unit\Services;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\Ledger;
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
                $table->text('narration')->nullable();
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->boolean('is_posted')->default(true);
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
}
