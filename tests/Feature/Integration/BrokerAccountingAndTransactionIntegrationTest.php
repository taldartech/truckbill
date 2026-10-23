<?php

namespace Tests\Feature\Integration;

use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Ledger;
use App\Models\LorryReceipt;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AccountingPostingService;
use App\Services\ChartOfAccountsProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BrokerAccountingAndTransactionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function seedRoles(): void
    {
        if (! UserRole::where('id', 3)->exists()) {
            UserRole::create(['id' => 1, 'name' => 'master_admin']);
            UserRole::create(['id' => 2, 'name' => 'super_admin']);
            UserRole::create(['id' => 3, 'name' => 'admin']);
            UserRole::create(['id' => 4, 'name' => 'moderator']);
        }
    }

    private function setupBrokerCompany(): array
    {
        $admin = User::factory()->create(['user_role_id' => 3]);
        $company = Company::create([
            'name' => 'Integrate Logistics',
            'short_name' => 'INTL',
            'user_id' => $admin->id,
            'is_blocked' => false,
        ]);
        $admin->company_id = $company->id;
        $admin->save();

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
            'lr_credits_remaining' => 100,
        ]);

        // Provision chart of accounts
        $provisioner = app(ChartOfAccountsProvisioner::class);
        $provisioner->provisionForCompany($company);

        return [$admin, $company];
    }

    public function test_invoice_creation_generates_balanced_double_entry_voucher(): void
    {
        [$admin, $company] = $this->setupBrokerCompany();
        $postingService = app(AccountingPostingService::class);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Mega Corp Ltd',
            'address' => 'Mumbai',
            'gst_number' => '27ABCDE1234F1Z5',
            'pan_number' => 'ABCDE1234F',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV/2026/0101',
            'invoice_type' => 'regular',
            'generated_date' => now()->toDateString(),
            'base_amount' => 10000.0,
            'cgst' => 900.0,
            'sgst' => 900.0,
            'net_amount' => 11800.0,
            'total_amount' => 11800.0,
        ]);

        $voucher = $postingService->postInvoice($invoice);
        $this->assertNotNull($voucher);
        $this->assertSame($company->id, $voucher->company_id);

        // Verify Debits == Credits in voucher items
        $items = AccountingVoucherItem::where('accounting_voucher_id', $voucher->id)->get();
        $totalDebit = $items->where('type', 'debit')->sum('amount');
        $totalCredit = $items->where('type', 'credit')->sum('amount');

        $this->assertEqualsWithDelta(11800.0, $totalDebit, 0.01);
        $this->assertEqualsWithDelta(11800.0, $totalCredit, 0.01);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
    }

    public function test_invoice_payment_generates_balanced_receipt_voucher(): void
    {
        [$admin, $company] = $this->setupBrokerCompany();
        $postingService = app(AccountingPostingService::class);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Tata Steel',
            'address' => 'Jamshedpur',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV/2026/0202',
            'invoice_type' => 'regular',
            'generated_date' => now()->toDateString(),
            'total_amount' => 50000.0,
        ]);

        $bankLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'BANK_PRIMARY')
            ->first() ?? Ledger::where('company_id', $company->id)->where('code', 'CASH')->first();

        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 25000.0,
            'payment_date' => now()->toDateString(),
            'mode' => 'bank',
            'bank_ledger_id' => $bankLedger->id,
        ]);

        $voucher = $postingService->postInvoicePayment($payment);
        $this->assertNotNull($voucher);

        $items = AccountingVoucherItem::where('accounting_voucher_id', $voucher->id)->get();
        $totalDebit = $items->where('type', 'debit')->sum('amount');
        $totalCredit = $items->where('type', 'credit')->sum('amount');

        $this->assertEqualsWithDelta(25000.0, $totalDebit, 0.01);
        $this->assertEqualsWithDelta(25000.0, $totalCredit, 0.01);
    }

    public function test_freight_booking_memo_generates_balanced_hire_voucher(): void
    {
        [$admin, $company] = $this->setupBrokerCompany();
        $postingService = app(AccountingPostingService::class);

        $master = TransporterMaster::factory()->create([
            'name' => 'Express Cargo Carrier',
            'pan_number' => 'AAAP0000A',
        ]);
        $transporter = Transporter::factory()->forCompany($company->id)->create([
            'transporter_master_id' => $master->id,
            'mobile_number' => '9811223344',
            'tds_percentage' => 1.0,
        ]);
        $truck = Truck::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'truck_number' => 'HR55AA1122',
            'truck_type' => 'Trailer',
        ]);

        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'Shipper Co', 'address' => 'Delhi']);
        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'lr_number' => 'LR-BOOK-001',
            'gr_number' => 'GR-BOOK-001',
            'source_city' => 'Delhi',
            'source_state' => 'DL',
            'destination_city' => 'Kolkata',
            'destination_state' => 'WB',
            'lane' => 'Delhi-Kolkata',
            'weight' => 20,
            'no_of_units' => 1,
            'good_type' => 'Steel',
            'fr_rate' => 2000,
            'amount' => 40000,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
        ]);

        $booking = FreightBooking::create([
            'lr_id' => $lr->id,
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 1800,
            'tr_amount' => 36000.0,
            'advance_amount' => 6000.0,
            'tds_amount' => 360.0, // 1% of 36000
            'balance_amount' => 29640.0, // 36000 - 6000 - 360
        ]);

        $voucher = $postingService->postFreightBooking($booking);
        $this->assertNotNull($voucher);

        $items = AccountingVoucherItem::where('accounting_voucher_id', $voucher->id)->get();
        $totalDebit = $items->where('type', 'debit')->sum('amount');
        $totalCredit = $items->where('type', 'credit')->sum('amount');

        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
        $this->assertGreaterThan(0, $totalDebit);
    }

    public function test_advance_payment_to_fuel_pump_generates_balanced_entries(): void
    {
        [$admin, $company] = $this->setupBrokerCompany();
        $postingService = app(AccountingPostingService::class);

        $master = TransporterMaster::factory()->create(['name' => 'Rapid Transport']);
        $transporter = Transporter::factory()->forCompany($company->id)->create([
            'transporter_master_id' => $master->id,
            'mobile_number' => '9899001122',
        ]);

        $bankLedger = Ledger::where('company_id', $company->id)
            ->where('code', 'BANK_PRIMARY')
            ->first() ?? Ledger::where('company_id', $company->id)->where('code', 'CASH')->first();

        $advance = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 8000.0,
            'payment_date' => now()->toDateString(),
            'bank_ledger_id' => $bankLedger->id,
            'remark' => 'Advance for diesel and trip expenses',
        ]);

        $voucher = $postingService->postAdvancePayment($advance);
        $this->assertNotNull($voucher);

        $items = AccountingVoucherItem::where('accounting_voucher_id', $voucher->id)->get();
        $totalDebit = $items->where('type', 'debit')->sum('amount');
        $totalCredit = $items->where('type', 'credit')->sum('amount');

        $this->assertEqualsWithDelta(8000.0, $totalDebit, 0.01);
        $this->assertEqualsWithDelta(8000.0, $totalCredit, 0.01);
    }

    public function test_database_transaction_rolls_back_on_unhandled_failure(): void
    {
        [$admin, $company] = $this->setupBrokerCompany();

        $initialVoucherCount = AccountingVoucher::count();

        // Simulate multi-step transaction where 2nd step fails
        try {
            DB::transaction(function () use ($company) {
                AccountingVoucher::create([
                    'company_id' => $company->id,
                    'voucher_type' => 'journal',
                    'voucher_number' => 'VOUCH-TEST-ROLLBACK',
                    'voucher_date' => now()->toDateString(),
                ]);

                // Deliberate error to trigger rollback
                throw new \RuntimeException('Database transaction integrity test failure.');
            });
        } catch (\RuntimeException $e) {
            // Expected exception
        }

        // Verify database rolled back cleanly
        $this->assertSame($initialVoucherCount, AccountingVoucher::count());
        $this->assertDatabaseMissing('accounting_vouchers', [
            'voucher_number' => 'VOUCH-TEST-ROLLBACK',
        ]);
    }
}
