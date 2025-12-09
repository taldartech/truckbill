<?php

namespace Tests\Feature\Invoice;

use App\Models\Company;
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
            'name' => 'Company ' . $role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
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
        $this->actingAs($super)->get('/invoices')->assertForbidden();
        $this->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/invoices')
            ->assertForbidden();

        $this->post(route('logout'));
        $this->get('/invoices')->assertRedirect(route('login'));
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
}

