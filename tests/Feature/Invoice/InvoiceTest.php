<?php

namespace Tests\Feature\Invoice;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    protected function seedRoles(): void
    {
        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
    }

    protected function makeUser(string $role): User
    {
        $company = Company::create([
            'name' => 'Company '.$role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $roleId = UserRole::where('name', $role)->value('id');

        return User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);
    }

    protected function seedConsignees(User $user): Consignee
    {
        return Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Customer',
        ]);
    }

    protected function seedLrs(User $user, Consignee $customer): array
    {
        $lr1 = LorryReceipt::create([
            'company_id' => $user->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-INV-1',
            'memo_id' => 1001,
            'invoice_generated' => 0,
            'delivery_status' => 'Pending',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane 1',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'gr_date' => now()->toDateString(),
        ]);
        $lr2 = LorryReceipt::create([
            'company_id' => $user->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-INV-2',
            'memo_id' => 1002,
            'invoice_generated' => 0,
            'delivery_status' => 'Pending',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane 2',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'gr_date' => now()->toDateString(),
        ]);

        return [$lr1, $lr2];
    }

    protected function createInvoice(User $user, Consignee $customer, string $number): Invoice
    {
        return Invoice::create([
            'manual_invoice_number' => $number,
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 1000,
            'base_amount' => 1000,
            'net_amount' => 1000,
            'payment_status' => 'pending',
            'company_id' => $user->company_id,
            'memo_ids' => [],
            'lr_ids' => [],
        ]);
    }

    public function test_admin_and_moderator_can_access_index_and_data(): void
    {
        $admin = $this->makeUser('admin');
        $mod = $this->makeUser('moderator');

        $this->actingAs($admin)->get('/invoices')->assertOk();
        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/invoices')
            ->assertOk();

        $this->actingAs($mod)->get('/invoices')->assertOk();
        $this->actingAs($mod)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/invoices')
            ->assertOk();
    }

    public function test_super_admin_forbidden_and_guest_redirected(): void
    {
        $super = $this->makeUser('super_admin');
        $this->actingAs($super)->get('/invoices')->assertRedirect(route('company.index'));
        $this->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/invoices')
            ->assertRedirect(route('company.index'));

        $this->post(route('logout'));
        $this->get('/invoices')->assertRedirect(route('login'));
    }

    public function test_invoice_create_page_loads_without_embedding_all_consignees(): void
    {
        $admin = $this->makeUser('admin');
        for ($i = 0; $i < 30; $i++) {
            Consignee::create([
                'company_id' => $admin->company_id,
                'name' => 'Bulk Invoice Create Customer '.$i,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('invoices.create'));

        $response->assertOk();
        $response->assertDontSee('Bulk Invoice Create Customer 0', false);
    }

    public function test_admin_can_store_invoice_and_marks_lrs_invoiced(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1, $lr2] = $this->seedLrs($admin, $customer);

        $payload = [
            'manual_invoice_number' => 'INV-M-001',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 5000,
            'memo_ids' => [$lr1->memo_id, $lr2->memo_id],
        ];

        $response = $this->actingAs($admin)->post('/invoices', $payload);

        $response->assertRedirect(route('invoices.index'));
        $this->assertDatabaseHas('invoices', [
            'manual_invoice_number' => 'INV-M-001',
            'company_id' => $admin->company_id,
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseHas('lorry_receipts', ['id' => $lr1->id, 'invoice_generated' => 1]);
        $this->assertDatabaseHas('lorry_receipts', ['id' => $lr2->id, 'invoice_generated' => 1]);
    }

    public function test_store_rejects_duplicate_manual_invoice_number_within_company(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);

        $payload = [
            'manual_invoice_number' => 'INV-M-002',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 1000,
            'memo_ids' => [$lr1->memo_id],
        ];

        $this->actingAs($admin)->post('/invoices', $payload);

        $response = $this->actingAs($admin)->from('/invoices/create')->post('/invoices', $payload);
        $response->assertRedirect('/invoices/create');
        $this->assertEquals(1, Invoice::where('manual_invoice_number', 'INV-M-002')->count());
    }

    public function test_admin_can_add_payment_and_update_status(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-M-003',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 1000,
            'base_amount' => 1000,
            'net_amount' => 1000,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'lr_ids' => [$lr1->id],
        ]);

        $response = $this->actingAs($admin)->post("/invoices/{$invoice->id}/payments", [
            'mode' => 'cash',
            'amount' => 500,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertOk();
        $invoice->refresh();
        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id' => $invoice->id,
            'amount' => 500,
        ]);
        $this->assertEquals('partial', $invoice->payment_status);
    }

    public function test_store_payment_rejects_overpayment(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-M-004',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 100,
            'base_amount' => 100,
            'net_amount' => 100,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'lr_ids' => [$lr1->id],
        ]);

        $response = $this->actingAs($admin)->post("/invoices/{$invoice->id}/payments", [
            'mode' => 'cash',
            'amount' => 200,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, InvoicePayment::where('invoice_id', $invoice->id)->count());
    }

    public function test_admin_can_add_payment_with_tds_and_deductions_clearing_invoice(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-M-004B',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 100000,
            'base_amount' => 100000,
            'net_amount' => 100000,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'lr_ids' => [$lr1->id],
        ]);

        $response = $this->actingAs($admin)->post("/invoices/{$invoice->id}/payments", [
            'mode' => 'Bank Transfer',
            'amount' => 97000,
            'tds_amount' => 2000,
            'deduction_amount' => 1000,
            'deduction_reason' => 'Quality shortage',
            'remark' => 'Full settlement with TDS & deductions',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $invoice->refresh();
        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id' => $invoice->id,
            'amount' => 97000,
            'tds_amount' => 2000,
            'deduction_amount' => 1000,
            'deduction_reason' => 'Quality shortage',
        ]);

        $this->assertEquals(100000, $invoice->paid_amount);
        $this->assertEquals(0, $invoice->remaining_amount);
        $this->assertEquals('paid', $invoice->payment_status);

        // Verify accounting voucher was generated
        $payment = InvoicePayment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $voucher = \App\Models\AccountingVoucher::where('source_type', InvoicePayment::class)
            ->where('source_id', $payment->id)
            ->first();
        $this->assertNotNull($voucher);
        $this->assertEquals(100000, $voucher->total_amount);
        $this->assertTrue($voucher->isBalanced());
    }

    public function test_store_payment_rejects_overpayment_when_settlement_with_deductions_exceeds_remaining(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-M-004C',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 5000,
            'base_amount' => 5000,
            'net_amount' => 5000,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'lr_ids' => [$lr1->id],
        ]);

        // Remit 4800 + TDS 200 + Deduction 500 = 5500 > 5000 remaining
        $response = $this->actingAs($admin)->post("/invoices/{$invoice->id}/payments", [
            'mode' => 'Bank Transfer',
            'amount' => 4800,
            'tds_amount' => 200,
            'deduction_amount' => 500,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, InvoicePayment::where('invoice_id', $invoice->id)->count());
    }

    public function test_invoice_ajax_response_includes_gst_breakup(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-GST-001',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 1180,
            'base_amount' => 1000,
            'net_amount' => 1180,
            'sgst' => 90,
            'cgst' => 90,
            'igst' => 0,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'lr_ids' => [$lr1->id],
        ]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get("/invoices/{$invoice->id}");

        $response->assertOk();
        $data = $response->json();
        $this->assertSame(1000.0, (float) $data['base_amount']);
        $this->assertSame(1180.0, (float) $data['net_amount']);
        $this->assertSame(90.0, (float) $data['sgst']);
        $this->assertSame(90.0, (float) $data['cgst']);
        $this->assertSame(0.0, (float) $data['igst']);
        $this->assertSame(9.0, (float) $data['sgst_rate']);
        $this->assertSame(9.0, (float) $data['cgst_rate']);
        $this->assertSame(0.0, (float) $data['igst_rate']);
        $this->assertSame(180.0, (float) $data['total_gst']);
    }

    public function test_invoice_show_page_displays_tax_breakup(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-SHOW-001',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 1180,
            'base_amount' => 1000,
            'net_amount' => 1180,
            'sgst' => 90,
            'cgst' => 90,
            'igst' => 0,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'lr_ids' => [$lr1->id],
        ]);

        $response = $this->actingAs($admin)->get("/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertSee('Tax Breakup')
            ->assertSee('Base Amount')
            ->assertSee('SGST')
            ->assertSee('CGST')
            ->assertSee('Net Amount (After GST)');
    }

    public function test_invoice_edit_page_loads_when_lr_ids_is_null(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-NULL-LR',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 1000,
            'base_amount' => 1000,
            'net_amount' => 1000,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'memo_ids' => null,
            'lr_ids' => null,
        ]);

        $this->actingAs($admin)->get("/invoices/{$invoice->id}/edit")->assertOk();
    }

    public function test_admin_can_update_invoice_memo_selection(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1, $lr2] = $this->seedLrs($admin, $customer);

        $lr3 = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-INV-3',
            'memo_id' => 1003,
            'invoice_generated' => 0,
            'delivery_status' => 'Pending',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane 3',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 150,
            'amount' => 150,
            'gr_date' => now()->toDateString(),
        ]);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-MEMO-EDIT',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 200,
            'base_amount' => 200,
            'net_amount' => 200,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'memo_ids' => [$lr1->memo_id, $lr2->memo_id],
            'lr_ids' => [$lr1->id, $lr2->id],
        ]);

        $lr1->invoice_generated = 1;
        $lr1->save();
        $lr2->invoice_generated = 1;
        $lr2->save();

        $response = $this->actingAs($admin)->put("/invoices/{$invoice->id}", [
            'manual_invoice_number' => 'INV-MEMO-EDIT',
            'generated_date' => now()->toDateString(),
            'memo_ids' => [$lr2->memo_id, $lr3->memo_id],
        ]);

        $response->assertRedirect(route('invoices.index'));
        $invoice->refresh();
        $this->assertSame([$lr2->memo_id, $lr3->memo_id], $invoice->memo_ids);
        $this->assertEqualsCanonicalizing([$lr2->id, $lr3->id], $invoice->lr_ids);
        $this->assertDatabaseHas('lorry_receipts', ['id' => $lr1->id, 'invoice_generated' => 0]);
        $this->assertDatabaseHas('lorry_receipts', ['id' => $lr2->id, 'invoice_generated' => 1]);
        $this->assertDatabaseHas('lorry_receipts', ['id' => $lr3->id, 'invoice_generated' => 1]);
        $this->assertSame(250.0, (float) $invoice->base_amount);
    }

    public function test_admin_can_clear_manual_invoice_number_on_update(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-CLEAR-REF',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 100,
            'base_amount' => 100,
            'net_amount' => 100,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'memo_ids' => [$lr1->memo_id],
            'lr_ids' => [$lr1->id],
        ]);

        $lr1->invoice_generated = 1;
        $lr1->save();

        $response = $this->actingAs($admin)->put("/invoices/{$invoice->id}", [
            'manual_invoice_number' => '',
            'generated_date' => now()->toDateString(),
            'memo_ids' => [$lr1->memo_id],
        ]);

        $response->assertRedirect(route('invoices.index'));
        $this->assertNull($invoice->fresh()->manual_invoice_number);
    }

    public function test_moderator_cannot_edit_or_destroy_invoice(): void
    {
        $admin = $this->makeUser('admin');
        $mod = $this->makeUser('moderator');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-M-005',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 500,
            'base_amount' => 500,
            'net_amount' => 500,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'lr_ids' => [$lr1->id],
        ]);

        $this->actingAs($mod)->get("/invoices/{$invoice->id}/edit")->assertForbidden();
        $this->actingAs($mod)->put("/invoices/{$invoice->id}", [
            'manual_invoice_number' => 'INV-M-005-EDIT',
            'generated_date' => now()->toDateString(),
        ])->assertForbidden();
        $this->actingAs($mod)->delete("/invoices/{$invoice->id}")->assertForbidden();
    }

    public function test_admin_cannot_modify_invoice_after_payment(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-M-006',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 300,
            'base_amount' => 300,
            'net_amount' => 300,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'lr_ids' => [$lr1->id],
        ]);

        // add a payment to make it partial
        $invoice->payments()->create([
            'mode' => 'cash',
            'amount' => 100,
            'payment_date' => now()->toDateString(),
        ]);
        $invoice->payment_status = 'partial';
        $invoice->save();

        $this->actingAs($admin)->from("/invoices/{$invoice->id}/edit")->put("/invoices/{$invoice->id}", [
            'manual_invoice_number' => 'INV-M-006-EDIT',
            'generated_date' => now()->toDateString(),
        ])->assertRedirect("/invoices/{$invoice->id}/edit")->assertSessionHas('error');

        $this->actingAs($admin)->from('/invoices')->delete("/invoices/{$invoice->id}")
            ->assertRedirect('/invoices')->assertSessionHas('error');
    }

    public function test_destroy_resets_invoice_generated_for_regular_invoice(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);
        $lr1->invoice_generated = 1;
        $lr1->save();

        $invoice = Invoice::create([
            'invoice_number' => 'VGC/2026/0001',
            'manual_invoice_number' => 'INV-DEL-001',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 100,
            'base_amount' => 100,
            'net_amount' => 100,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'lr_ids' => [$lr1->id],
            'custom_invoice' => null,
        ]);

        $this->actingAs($admin)
            ->delete("/invoices/{$invoice->id}")
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('lorry_receipts', [
            'id' => $lr1->id,
            'invoice_generated' => 0,
        ]);
    }

    public function test_destroy_custom_invoice_does_not_reset_invoice_generated(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);
        $lr1->invoice_generated = 1;
        $lr1->save();

        $invoice = Invoice::create([
            'invoice_number' => 'VGC/2026/0002',
            'manual_invoice_number' => 'INV-DEL-CUSTOM-001',
            'invoice_type' => 'Reverse',
            'custom_invoice' => 'rate_difference',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 100,
            'base_amount' => 100,
            'net_amount' => 100,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'lr_ids' => [$lr1->id],
        ]);

        $this->actingAs($admin)
            ->delete("/invoices/{$invoice->id}")
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('lorry_receipts', [
            'id' => $lr1->id,
            'invoice_generated' => 1,
        ]);
    }

    public function test_invoice_numbers_api_returns_company_scoped_results(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        $this->createInvoice($admin, $customer, 'INV-ADMIN-1');

        $other = $this->makeUser('master_admin');
        $otherCustomer = $this->seedConsignees($other);
        $this->createInvoice($other, $otherCustomer, 'INV-OTHER-1');

        $response = $this->actingAs($admin)->get('/api/invoice-numbers');

        $response->assertOk()
            ->assertJsonFragment(['manual_invoice_number' => 'INV-ADMIN-1'])
            ->assertJsonMissing(['manual_invoice_number' => 'INV-OTHER-1']);
    }

    public function test_invoice_numbers_api_filters_by_customer(): void
    {
        $admin = $this->makeUser('admin');
        $customerA = $this->seedConsignees($admin);
        $customerB = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Other Customer',
        ]);

        $invoiceA = $this->createInvoice($admin, $customerA, 'INV-CUST-A');
        $this->createInvoice($admin, $customerB, 'INV-CUST-B');

        $response = $this->actingAs($admin)->get("/api/invoice-numbers/{$customerA->id}");

        $response->assertOk()
            ->assertJsonFragment(['manual_invoice_number' => $invoiceA->manual_invoice_number])
            ->assertJsonMissing(['manual_invoice_number' => 'INV-CUST-B']);
    }

    public function test_get_lrs_supports_memo_ids_with_slashes(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        $memoId = '9387429/9387429';

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-SLASH-1',
            'memo_id' => $memoId,
            'invoice_generated' => 0,
            'delivery_status' => 'Pending',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane 1',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 250,
            'gr_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/invoices/lrs?'.http_build_query(['memo_ids' => [$memoId]]));

        $response->assertOk()
            ->assertJsonPath($memoId.'.0.lr_number', 'LR-SLASH-1')
            ->assertJsonPath($memoId.'.0.amount', 250);
    }

    public function test_get_invoices_data_reflects_full_paid_and_zero_remaining_for_invoice_settled_with_tds(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsignees($admin);
        [$lr1] = $this->seedLrs($admin, $customer);

        $invoice = Invoice::create([
            'manual_invoice_number' => 'INV-TDS-DT-1',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 10000,
            'base_amount' => 10000,
            'net_amount' => 10000,
            'payment_status' => 'paid',
            'company_id' => $admin->company_id,
            'lr_ids' => [$lr1->id],
        ]);

        $invoice->payments()->create([
            'mode' => 'Bank Transfer',
            'amount' => 9500,
            'tds_amount' => 300,
            'deduction_amount' => 200,
            'deduction_reason' => 'Transit shortage',
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/invoices');

        $response->assertOk();
        $json = $response->json();
        $this->assertNotEmpty($json['data']);
        $matched = collect($json['data'])->firstWhere('id', $invoice->id);
        $this->assertNotNull($matched);
        $this->assertEquals(10000, (float) $matched['paid_amount_sum']);
        $this->assertEquals(0, (float) $matched['remaining_amount_value']);
        $this->assertStringContainsString('10,000.00', $matched['paid_amount_formatted']);
        $this->assertStringContainsString('0.00', $matched['remaining_amount_formatted']);
    }
}

