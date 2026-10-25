<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountGroup;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\CostCentre;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Ledger;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingVoucherControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Ledger $bankLedger;
    private Ledger $salesLedger;
    private Ledger $customerLedger;
    private Ledger $tdsLedger;
    private Ledger $discountLedger;
    private CostCentre $costCentre;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        $role = UserRole::create(['id' => 3, 'name' => 'admin']);

        $this->company = Company::create([
            'name' => 'Accounting Voucher Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 10,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
        ]);

        CompanySubscription::create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $this->user = User::factory()->create([
            'user_role_id' => $role->id,
            'company_id' => $this->company->id,
        ]);

        $this->company->update(['user_id' => $this->user->id]);

        $group = AccountGroup::create([
            'company_id' => $this->company->id,
            'name' => 'Current Assets',
            'code' => 'CA-01',
            'primary_type' => 'asset',
        ]);

        $this->bankLedger = Ledger::create([
            'company_id' => $this->company->id,
            'account_group_id' => $group->id,
            'name' => 'HDFC Bank Account',
            'is_active' => true,
        ]);

        $this->salesLedger = Ledger::create([
            'company_id' => $this->company->id,
            'account_group_id' => $group->id,
            'name' => 'Freight Sales Account',
            'is_active' => true,
        ]);

        $this->customerLedger = Ledger::create([
            'company_id' => $this->company->id,
            'account_group_id' => $group->id,
            'name' => 'Alpha Logistics (customer)',
            'is_active' => true,
        ]);

        $this->tdsLedger = Ledger::create([
            'company_id' => $this->company->id,
            'account_group_id' => $group->id,
            'name' => 'TDS Receivable A/C',
            'is_active' => true,
        ]);

        $this->discountLedger = Ledger::create([
            'company_id' => $this->company->id,
            'account_group_id' => $group->id,
            'name' => 'Shortage and Claim Discount',
            'is_active' => true,
        ]);

        $this->costCentre = CostCentre::create([
            'company_id' => $this->company->id,
            'category' => 'branch',
            'name' => 'Main Hub',
            'code' => 'MHUB-01',
        ]);
    }

    public function test_index_renders_and_filters_vouchers(): void
    {
        $voucher1 = AccountingVoucher::create([
            'company_id' => $this->company->id,
            'voucher_type' => 'receipt',
            'voucher_number' => 'RCT-261025-1001',
            'voucher_date' => '2026-10-20',
            'total_amount' => 5000,
            'is_posted' => true,
            'user_id' => $this->user->id,
        ]);

        $voucher2 = AccountingVoucher::create([
            'company_id' => $this->company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'PAY-261025-1002',
            'voucher_date' => '2026-10-24',
            'total_amount' => 3000,
            'is_posted' => true,
            'user_id' => $this->user->id,
        ]);

        // Unposted voucher should not show
        AccountingVoucher::create([
            'company_id' => $this->company->id,
            'voucher_type' => 'payment',
            'voucher_number' => 'PAY-DRAFT',
            'voucher_date' => '2026-10-24',
            'total_amount' => 1000,
            'is_posted' => false,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('accounting.vouchers.index'));
        $response->assertOk();
        $response->assertViewIs('accounting.vouchers.index');
        $response->assertSee('RCT-261025-1001');
        $response->assertSee('PAY-261025-1002');
        $response->assertDontSee('PAY-DRAFT');

        // Filter by voucher_type, from_date, to_date
        $filterResponse = $this->actingAs($this->user)->get(route('accounting.vouchers.index', [
            'voucher_type' => 'receipt',
            'from_date' => '2026-10-19',
            'to_date' => '2026-10-21',
        ]));
        $filterResponse->assertOk();
        $filterResponse->assertSee('RCT-261025-1001');
        $filterResponse->assertDontSee('PAY-261025-1002');
    }

    public function test_super_admin_session_company_in_active_company(): void
    {
        $superAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $response = $this->actingAs($superAdmin)
            ->withSession(['super_admin_company_id' => $this->company->id])
            ->get(route('accounting.vouchers.index'));

        $response->assertOk();
    }

    public function test_create_renders_form_with_all_groups_and_ledgers(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.vouchers.create'));
        $response->assertOk();
        $response->assertViewIs('accounting.vouchers.create');
        $response->assertViewHas(['company', 'ledgers', 'costCentres', 'allGroups']);
    }

    public function test_store_validates_unbalanced_voucher_ajax_and_non_ajax(): void
    {
        $payload = [
            'voucher_type' => 'receipt',
            'voucher_date' => now()->toDateString(),
            'items' => [
                ['ledger_id' => $this->bankLedger->id, 'type' => 'debit', 'amount' => 5000],
                ['ledger_id' => $this->salesLedger->id, 'type' => 'credit', 'amount' => 4500],
            ],
        ];

        // Non-AJAX
        $response = $this->actingAs($this->user)->post(route('accounting.vouchers.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHasErrors('items');

        // AJAX
        $ajaxResponse = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('accounting.vouchers.store'), $payload);
        $ajaxResponse->assertStatus(422);
        $ajaxResponse->assertJsonFragment(['success' => false]);
    }

    public function test_store_validates_circular_entry_ajax_and_non_ajax(): void
    {
        $payload = [
            'voucher_type' => 'contra',
            'voucher_date' => now()->toDateString(),
            'items' => [
                ['ledger_id' => $this->bankLedger->id, 'type' => 'debit', 'amount' => 2000],
                ['ledger_id' => $this->bankLedger->id, 'type' => 'credit', 'amount' => 2000],
            ],
        ];

        // Non-AJAX
        $response = $this->actingAs($this->user)->post(route('accounting.vouchers.store'), $payload);
        $response->assertRedirect();
        $response->assertSessionHasErrors('items');

        // AJAX
        $ajaxResponse = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('accounting.vouchers.store'), $payload);
        $ajaxResponse->assertStatus(422);
        $ajaxResponse->assertJsonFragment(['success' => false]);
    }

    public function test_store_creates_voucher_non_ajax_and_ajax_with_prefixes(): void
    {
        // 1. Credit note
        $creditPayload = [
            'voucher_type' => 'credit_note',
            'voucher_date' => now()->toDateString(),
            'reference_number' => 'REF-CR-01',
            'narration' => 'Credit note posted',
            'items' => [
                ['ledger_id' => $this->bankLedger->id, 'type' => 'debit', 'amount' => 1500, 'cost_centre_id' => $this->costCentre->id, 'particulars_note' => 'Debit item note'],
                ['ledger_id' => $this->salesLedger->id, 'type' => 'credit', 'amount' => 1500],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('accounting.vouchers.store'), $creditPayload);
        $response->assertRedirect(route('accounting.vouchers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('accounting_vouchers', [
            'company_id' => $this->company->id,
            'voucher_type' => 'credit_note',
            'total_amount' => 1500,
        ]);
        $voucherCr = AccountingVoucher::where('voucher_type', 'credit_note')->first();
        $this->assertStringStartsWith('CRN-', $voucherCr->voucher_number);

        // 2. Debit note (AJAX)
        $debitPayload = [
            'voucher_type' => 'debit_note',
            'voucher_date' => now()->toDateString(),
            'items' => [
                ['ledger_id' => $this->salesLedger->id, 'type' => 'debit', 'amount' => 800],
                ['ledger_id' => $this->bankLedger->id, 'type' => 'credit', 'amount' => 800],
            ],
        ];

        $ajaxResponse = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('accounting.vouchers.store'), $debitPayload);

        $ajaxResponse->assertOk();
        $ajaxResponse->assertJsonFragment(['success' => true]);
        $voucherDb = AccountingVoucher::where('voucher_type', 'debit_note')->first();
        $this->assertStringStartsWith('DBN-', $voucherDb->voucher_number);
    }

    public function test_store_adjusts_invoice_with_tds_and_deductions(): void
    {
        $customer = Consignee::create([
            'company_id' => $this->company->id,
            'name' => 'Alpha Logistics',
        ]);

        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-ADJUST-001',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 10000,
            'net_amount' => 10000,
            'payment_status' => 'pending',
        ]);

        // Voucher with Bank (Debit), TDS (Debit), Discount/Claim (Debit) against Customer (Credit)
        $payload = [
            'voucher_type' => 'receipt',
            'voucher_date' => now()->toDateString(),
            'narration' => 'Invoice full settlement with TDS and shortage claim',
            'challan_bsr_code' => '0510304',
            'challan_serial_no' => 'CH-999',
            'challan_tender_date' => now()->toDateString(),
            'challan_head' => 'TDS Sec 194C',
            'items' => [
                ['ledger_id' => $this->bankLedger->id, 'type' => 'debit', 'amount' => 8500],
                ['ledger_id' => $this->tdsLedger->id, 'type' => 'debit', 'amount' => 1000],
                ['ledger_id' => $this->discountLedger->id, 'type' => 'debit', 'amount' => 500],
                ['ledger_id' => $this->customerLedger->id, 'type' => 'credit', 'amount' => 10000, 'invoice_id' => $invoice->id],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('accounting.vouchers.store'), $payload);

        $response->assertOk();
        $response->assertJsonFragment(['success' => true]);

        // Verify InvoicePayment was created for the invoice
        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id' => $invoice->id,
        ]);
        $invPayment = InvoicePayment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($invPayment);
    }

    public function test_get_pending_invoices_authorization_and_mapping(): void
    {
        // 1. Foreign company ledger throws 403
        $foreignCompany = Company::create(['name' => 'Foreign Co', 'user_id' => null, 'gst_slab' => 5]);
        $foreignGroup = AccountGroup::create([
            'company_id' => $foreignCompany->id,
            'name' => 'Foreign Group',
            'code' => 'FG-01',
            'primary_type' => 'asset',
        ]);
        $foreignLedger = Ledger::create([
            'company_id' => $foreignCompany->id,
            'account_group_id' => $foreignGroup->id,
            'name' => 'Foreign Bank',
        ]);
        $this->actingAs($this->user)
            ->get(route('accounting.ledgers.pending-invoices', $foreignLedger))
            ->assertForbidden();

        // 2. Ledger with entity_type Consignee
        $consignee = Consignee::create([
            'company_id' => $this->company->id,
            'name' => 'Beta Freight Corp',
        ]);
        $consigneeLedger = Ledger::create([
            'company_id' => $this->company->id,
            'account_group_id' => $this->bankLedger->account_group_id,
            'name' => 'Beta Freight Ledger',
            'entity_type' => Consignee::class,
            'entity_id' => $consignee->id,
        ]);

        $inv1 = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $consignee->id,
            'invoice_number' => 'INV-PEND-01',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 5000,
            'net_amount' => 5000,
            'payment_status' => 'pending',
        ]);

        $responseEntity = $this->actingAs($this->user)
            ->get(route('accounting.ledgers.pending-invoices', $consigneeLedger));
        $responseEntity->assertOk();
        $responseEntity->assertJsonFragment(['is_customer' => true, 'customer_name' => 'Beta Freight Corp']);

        // 3. Ledger with name match (without entity_type)
        Consignee::create([
            'company_id' => $this->company->id,
            'name' => 'Alpha Logistics',
        ]);
        $responseNameMatch = $this->actingAs($this->user)
            ->get(route('accounting.ledgers.pending-invoices', $this->customerLedger));
        $responseNameMatch->assertOk();
        $responseNameMatch->assertJsonFragment(['is_customer' => true, 'customer_name' => 'Alpha Logistics']);

        // 4. Ledger without customer match
        $responseNoCustomer = $this->actingAs($this->user)
            ->get(route('accounting.ledgers.pending-invoices', $this->salesLedger));
        $responseNoCustomer->assertOk();
        $responseNoCustomer->assertJsonFragment(['is_customer' => false, 'invoices' => []]);
    }

    public function test_show_voucher_authorization_and_view(): void
    {
        $voucher = AccountingVoucher::create([
            'company_id' => $this->company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JRN-261025-001',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 1000,
            'is_posted' => true,
            'user_id' => $this->user->id,
        ]);

        // Foreign company voucher throws 403
        $foreignCompany = Company::create(['name' => 'Foreign Co 2', 'user_id' => null, 'gst_slab' => 5]);
        $foreignVoucher = AccountingVoucher::create([
            'company_id' => $foreignCompany->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JRN-FOR-001',
            'voucher_date' => now()->toDateString(),
            'total_amount' => 1000,
            'is_posted' => true,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('accounting.vouchers.show', $foreignVoucher))
            ->assertForbidden();

        // Own voucher success
        $response = $this->actingAs($this->user)
            ->get(route('accounting.vouchers.show', $voucher));
        $response->assertOk();
        $response->assertViewIs('accounting.vouchers.show');
        $response->assertViewHas(['company', 'voucher']);
    }
}
