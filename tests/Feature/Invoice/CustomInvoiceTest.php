<?php

namespace Tests\Feature\Invoice;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\CustomInvoiceDetail;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomInvoiceTest extends TestCase
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
            'gst_scheme' => 'reverse charge',
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $roleId = UserRole::where('name', $role)->value('id');

        return User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);
    }

    protected function seedConsigneeWithState(User $user): Consignee
    {
        $country = Country::firstOrCreate(['name' => 'India'], ['code' => 'IN']);
        $state = State::firstOrCreate(
            ['name' => 'Rajasthan', 'country_id' => $country->id],
            ['code' => '08']
        );

        return Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Test Customer',
            'address' => 'Address 1',
            'state_id' => $state->id,
        ]);
    }

    protected function seedLr(User $user, Consignee $customer): LorryReceipt
    {
        return LorryReceipt::create([
            'company_id' => $user->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-CUSTOM-001',
            'memo_id' => 9001,
            'invoice_generated' => 0,
            'delivery_status' => 'Pending',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane 1',
            'good_type' => 'Steel',
            'weight' => 10,
            'fr_rate' => 50,
            'amount' => 500,
            'actual_amount' => 500,
            'gr_date' => now()->toDateString(),
        ]);
    }

    public function test_admin_can_access_custom_invoice_create_page(): void
    {
        $admin = $this->makeUser('admin');
        $response = $this->actingAs($admin)->get(route('custom-invoices.create'));
        $response->assertOk();
    }

    public function test_custom_invoice_create_page_shows_remark_label(): void
    {
        $admin = $this->makeUser('admin');
        $response = $this->actingAs($admin)->get(route('custom-invoices.create'));
        $response->assertOk();
        $response->assertSee('Remark');
        $response->assertDontSee('Line remark');
    }

    public function test_moderator_can_access_custom_invoice_create_page(): void
    {
        $mod = $this->makeUser('moderator');
        $response = $this->actingAs($mod)->get(route('custom-invoices.create'));
        $response->assertOk();
    }

    public function test_lr_by_number_returns_customer_and_lr_details(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsigneeWithState($admin);
        $lr = $this->seedLr($admin, $customer);

        $response = $this->actingAs($admin)->getJson(
            route('custom-invoices.lr-by-number', ['lr_number' => 'LR-CUSTOM-001'])
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'lr_number' => 'LR-CUSTOM-001',
                'customer' => ['name' => 'Test Customer'],
            ]);
    }

    public function test_lr_by_number_returns_404_when_not_found(): void
    {
        $admin = $this->makeUser('admin');
        $response = $this->actingAs($admin)->getJson(
            route('custom-invoices.lr-by-number', ['lr_number' => 'LR-NOT-EXISTS'])
        );

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_admin_can_store_rate_difference_custom_invoice(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsigneeWithState($admin);
        $lr = $this->seedLr($admin, $customer);

        $payload = [
            'custom_invoice' => 'rate_difference',
            'manual_invoice_number' => 'CINV-RD-001',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 100,
            'lr_number' => 'LR-CUSTOM-001',
            'amount' => 100,
            'portal_rate' => 45,
            'diff' => 5,
            'remark' => 'Rate diff',
        ];

        $response = $this->actingAs($admin)->post(route('custom-invoices.store'), $payload);

        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'manual_invoice_number' => 'CINV-RD-001',
            'custom_invoice' => 'rate_difference',
            'company_id' => $admin->company_id,
        ]);

        $invoice = Invoice::where('manual_invoice_number', 'CINV-RD-001')->first();
        $this->assertNotNull($invoice);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+\/\d{4}\/\d{4}$/', $invoice->invoice_number);
        $detail = CustomInvoiceDetail::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($detail);
        $this->assertSame(45.0, (float) $detail->portal_rate);
        $this->assertSame(5.0, (float) $detail->diff);
    }

    public function test_invoice_show_displays_custom_invoice_details_with_remark(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsigneeWithState($admin);
        $this->seedLr($admin, $customer);

        $this->actingAs($admin)->post(route('custom-invoices.store'), [
            'custom_invoice' => 'rate_difference',
            'manual_invoice_number' => 'CINV-SHOW-001',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 150,
            'lr_number' => 'LR-CUSTOM-001',
            'amount' => 150,
            'portal_rate' => 40,
            'diff' => 10,
            'remark' => 'Rate difference note',
        ]);

        $invoice = Invoice::where('manual_invoice_number', 'CINV-SHOW-001')->first();
        $response = $this->actingAs($admin)->get(route('invoices.show', $invoice));

        $response->assertOk();
        $response->assertSee('Custom invoice details');
        $response->assertSee('LR No');
        $response->assertSee('Remark');
        $response->assertSee('Rate difference note');
    }

    public function test_admin_can_store_shortage_damage_custom_invoice(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsigneeWithState($admin);
        $lr = $this->seedLr($admin, $customer);

        $payload = [
            'custom_invoice' => 'shortage_damage',
            'manual_invoice_number' => 'CINV-SD-001',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 200,
            'lr_number' => 'LR-CUSTOM-001',
            'amount' => 200,
            'damage_qty' => 2,
            'damage_rate' => 50,
            'shortage_qty' => 1,
            'remark' => 'Shortage & damage claim',
        ];

        $response = $this->actingAs($admin)->post(route('custom-invoices.store'), $payload);

        $response->assertRedirect(route('invoices.index'));
        $this->assertDatabaseHas('invoices', [
            'manual_invoice_number' => 'CINV-SD-001',
            'custom_invoice' => 'shortage_damage',
        ]);

        $invoice = Invoice::where('manual_invoice_number', 'CINV-SD-001')->first();
        $detail = CustomInvoiceDetail::where('invoice_id', $invoice->id)->first();
        $this->assertSame(2.0, (float) $detail->damage_qty);
        $this->assertSame(50.0, (float) $detail->damage_rate);
        $this->assertSame(1.0, (float) $detail->shortage_qty);
    }

    public function test_admin_can_store_custom_invoice_without_manual_reference_and_gets_auto_invoice_number(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsigneeWithState($admin);
        $this->seedLr($admin, $customer);

        $payload = [
            'custom_invoice' => 'rate_difference',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 100,
            'lr_number' => 'LR-CUSTOM-001',
            'amount' => 100,
        ];

        $response = $this->actingAs($admin)->post(route('custom-invoices.store'), $payload);

        $response->assertRedirect(route('invoices.index'));
        $invoice = Invoice::query()->where('company_id', $admin->company_id)->whereNull('manual_invoice_number')->first();
        $this->assertNotNull($invoice);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+\/\d{4}\/\d{4}$/', (string) $invoice->invoice_number);
    }

    public function test_store_rejects_duplicate_manual_invoice_number(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsigneeWithState($admin);
        $lr = $this->seedLr($admin, $customer);

        $payload = [
            'custom_invoice' => 'rate_difference',
            'manual_invoice_number' => 'CINV-DUP',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 100,
            'lr_number' => 'LR-CUSTOM-001',
            'amount' => 100,
        ];

        $this->actingAs($admin)->post(route('custom-invoices.store'), $payload);
        $response = $this->actingAs($admin)->post(route('custom-invoices.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors('manual_invoice_number');
    }

    public function test_store_rejects_invalid_lr_number(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsigneeWithState($admin);

        $payload = [
            'custom_invoice' => 'rate_difference',
            'manual_invoice_number' => 'CINV-BAD-LR',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 100,
            'lr_number' => 'LR-DOES-NOT-EXIST',
            'amount' => 100,
        ];

        $response = $this->actingAs($admin)->post(route('custom-invoices.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors('lr_number');
    }

    public function test_invoices_index_shows_create_custom_invoice_button(): void
    {
        $admin = $this->makeUser('admin');
        $response = $this->actingAs($admin)->get(route('invoices.index'));
        $response->assertOk();
        $response->assertSee('Create Custom Invoice');
        $response->assertSee(route('custom-invoices.create'));
    }

    public function test_create_form_preserves_old_input_after_validation_error(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsigneeWithState($admin);
        $this->seedLr($admin, $customer);

        $this->actingAs($admin)
            ->from(route('custom-invoices.create'))
            ->post(route('custom-invoices.store'), [
                'custom_invoice' => 'rate_difference',
                'invoice_type' => 'Reverse',
                'generated_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'lr_number' => 'LR-CUSTOM-001',
                'portal_rate' => 11.5,
                'diff' => 2.25,
                'remark' => 'Keep this remark after error',
            ]);

        $response = $this->actingAs($admin)->get(route('custom-invoices.create'));

        $response->assertOk();
        $response->assertSee('LR-CUSTOM-001', false);
        $response->assertSee('Keep this remark after error', false);
        $response->assertSee('11.5', false);
        $response->assertSee('2.25', false);
    }

    public function test_create_renders_when_user_has_no_company(): void
    {
        $userNoCo = \App\Models\User::factory()->create([
            'user_role_id' => 3,
            'company_id' => null,
        ]);
        $response = $this->actingAs($userNoCo)->get(route('custom-invoices.create'));
        $response->assertOk();
    }

    public function test_lr_by_number_with_freight_booking_damages(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsigneeWithState($admin);
        $lr = $this->seedLr($admin, $customer);
        $lr->update([
            'destination_city' => 'Jaipur',
            'weight' => 1500,
            'invoice_number' => 'INV-009',
            'gr_date' => now()->toDateString(),
        ]);

        \App\Models\FreightBooking::create([
            'company_id' => $admin->company_id,
            'lr_id' => $lr->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 5000,
            'advance_amount' => 1000,
            'balance_amount' => 4000,
        ]);

        $response = $this->actingAs($admin)->get(route('custom-invoices.lr-by-number', ['lr_number' => $lr->lr_number]));
        $response->assertOk();
        $response->assertJsonPath('lr_details.truck_number', '');
        $response->assertJsonPath('lr_details.destination_city', 'Jaipur');
    }

    public function test_store_handles_shortage_damage_and_forward_tax_variations(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsigneeWithState($admin);
        $lr = $this->seedLr($admin, $customer);

        // Forward invoice with shortage_damage
        $payload = [
            'custom_invoice' => 'shortage_damage',
            'manual_invoice_number' => 'CINV-SHORT-01',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 1000,
            'lr_number' => $lr->lr_number,
            'amount' => 1000,
            'damage_qty' => 2,
            'damage_rate' => 200,
            'shortage_qty' => 1,
            'remark' => 'Shortage damage invoice',
        ];

        $response = $this->actingAs($admin)->post(route('custom-invoices.store'), $payload);
        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'company_id' => $admin->company_id,
            'manual_invoice_number' => 'CINV-SHORT-01',
            'custom_invoice' => 'shortage_damage',
            'invoice_type' => 'Forward',
        ]);

        $this->assertDatabaseHas('custom_invoice_details', [
            'lr_id' => $lr->id,
            'damage_qty' => 2,
            'damage_rate' => 200,
            'shortage_qty' => 1,
        ]);
    }

    public function test_store_handles_forward_intrastate_and_generic_exception(): void
    {
        $admin = $this->makeUser('admin');
        $customer = $this->seedConsigneeWithState($admin);
        $lr = $this->seedLr($admin, $customer);

        // Match company state with customer state for intrastate Forward
        $company = \App\Models\Company::find($admin->company_id);
        $company->state_id = $customer->state_id;
        $company->save();

        $payload = [
            'custom_invoice' => 'rate_difference',
            'manual_invoice_number' => '',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 2000,
            'lr_number' => $lr->lr_number,
            'amount' => 2000,
            'portal_rate' => 50,
            'diff' => 10,
        ];

        $response = $this->actingAs($admin)->post(route('custom-invoices.store'), $payload);
        $response->assertRedirect(route('invoices.index'));

        // Test exception branch directly on controller with mock
        $mockNumberService = \Mockery::mock(\App\Services\InvoiceNumberService::class);
        $mockNumberService->shouldReceive('generate')->andThrow(new \Exception('Mocked generate failure'));

        $controller = new \App\Http\Controllers\CustomInvoiceController($mockNumberService);

        $request = \Illuminate\Http\Request::create(route('custom-invoices.store'), 'POST', [
            'custom_invoice' => 'rate_difference',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 1000,
            'lr_number' => $lr->lr_number,
            'amount' => 1000,
            'portal_rate' => 10,
            'diff' => 2,
        ]);
        $request->setUserResolver(fn () => $admin);

        $this->actingAs($admin);
        $responseFail = $controller->store($request);

        $this->assertTrue($responseFail->isRedirection());
        $this->assertArrayHasKey('save', session('errors')->getMessages());
    }
}


