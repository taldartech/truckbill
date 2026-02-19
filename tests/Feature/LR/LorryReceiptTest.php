<?php

namespace Tests\Feature\LR;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\User;
use Carbon\Carbon;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LorryReceiptTest extends TestCase
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
        UserRole::create(['id' => 5, 'name' => 'accountant']);
    }

    protected function makeAdmin(): User
    {
        $company = Company::create([
            'name' => 'Main Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        return User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);
    }

    protected function seedConsignees(User $admin): array
    {
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Customer A',
            'address' => 'Addr',
        ]);
        $consignor = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Consignor A',
            'address' => 'Addr',
        ]);
        $consignee = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Consignee A',
            'address' => 'Addr',
        ]);

        return [$customer, $consignor, $consignee];
    }

    protected function makeTruck(User $admin): array
    {
        $transporter = Transporter::factory()
            ->forCompany($admin->company_id)
            ->create([
                'mobile_number' => '9999999999',
                'declaration_given' => false,
                'tds_percentage' => 20,
                'total_trucks' => 0,
                'advance_amount' => 0,
            ]);

        $truck = Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1234',
            'truck_type' => 'Flatbed',
        ]);

        return [$transporter, $truck];
    }

    protected function validPayload(User $admin): array
    {
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        return [
            'customer_id' => $customer->id,
            'lr_number' => 'LR001',
            'gr_number' => 'GR001',
            'invoice_number' => 'INV001',
            'memo_id' => null,
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'reference_by' => $truck->transporter_id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'gr_date' => now()->toDateString(),
            'company_id' => $admin->company_id,
            'delivery_status' => 'Pending',
            'source_city' => 'Ahmedabad',
            'source_state' => 'Gujarat',
            'destination_city' => 'Mumbai',
            'destination_state' => 'Maharashtra',
            'unloading_station' => 'Station A',
            'lane' => 'Lane 1',
            'good_type' => 'Cement',
            'weight' => 10,
            'no_of_units' => 1,
            'fr_rate' => 1000,
            'actual_amount' => 10000,
            'amount' => 10000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ];
    }

    protected function createLorryReceipt(User $admin, array $overrides = []): void
    {
        $payload = array_merge($this->validPayload($admin), $overrides);
        $this->actingAs($admin)->post('/lorry_receipts', $payload);
    }

    protected function createLrWithBooking(User $admin, float $trAmount = 1000, float $advance = 0, string $voucherNumber = 'VB-1001'): array
    {
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $lr = \App\Models\LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-BULK-' . uniqid(),
            'gr_number' => 'GR-BULK-' . uniqid(),
            'invoice_number' => 'INV-BULK-' . uniqid(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'reference_by' => $truck->transporter_id,
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'delivery_status' => 'Pending',
        ]);

        $booking = \App\Models\FreightBooking::create([
            'lr_id' => $lr->id,
            'tr_fr_rate' => 100,
            'tr_amount' => $trAmount,
            'advance_amount' => $advance,
            'balance_amount' => $trAmount - $advance,
            'tds_amount' => 0,
            'company_id' => $admin->company_id,
            'voucher_number' => $voucherNumber,
        ]);

        return [$lr, $booking];
    }

    public function test_store_creates_lorry_receipt(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload($admin);

        $response = $this->actingAs($admin)->post('/lorry_receipts', $payload);

        $response->assertRedirect(route('lorry_receipts.index'));
        $this->assertDatabaseHas('lorry_receipts', [
            'lr_number' => 'LR001',
            'company_id' => $admin->company_id,
            'customer_id' => $payload['customer_id'],
        ]);
    }

    public function test_store_rejects_duplicate_lr_number_within_company(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload($admin);

        // First create
        $this->actingAs($admin)->post('/lorry_receipts', $payload);

        // Second attempt same lr_number
        $response = $this->actingAs($admin)->from('/lorry_receipts/create')->post('/lorry_receipts', $payload);

        $response->assertRedirect('/lorry_receipts/create');
        $response->assertSessionHasErrors(['lr_number']);
    }

    public function test_store_rejects_same_consignor_and_consignee(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor] = $this->seedConsignees($admin); // reuse consignor as consignee
        [, $truck] = $this->makeTruck($admin);

        $payload = [
            'customer_id' => $customer->id,
            'lr_number' => 'LR002',
            'gr_number' => 'GR002',
            'invoice_number' => 'INV002',
            'memo_id' => null,
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'reference_by' => $truck->transporter_id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignor->id, // same => should fail
            'gr_date' => now()->toDateString(),
            'company_id' => $admin->company_id,
            'delivery_status' => 'Pending',
            'source_city' => 'Ahmedabad',
            'source_state' => 'Gujarat',
            'destination_city' => 'Mumbai',
            'destination_state' => 'Maharashtra',
            'unloading_station' => 'Station A',
            'lane' => 'Lane 1',
            'good_type' => 'Cement',
            'weight' => 5,
            'no_of_units' => 1,
            'fr_rate' => 500,
            'amount' => 2500,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ];

        $response = $this->actingAs($admin)->from('/lorry_receipts/create')->post('/lorry_receipts', $payload);

        $response->assertRedirect('/lorry_receipts/create');
        $response->assertSessionHasErrors(['consignor_id', 'consignee_id']);
    }

    public function test_store_rejects_duplicate_gr_and_invoice_numbers(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload($admin);

        $this->actingAs($admin)->post('/lorry_receipts', $payload);

        $response = $this->actingAs($admin)->from('/lorry_receipts/create')->post('/lorry_receipts', $payload);

        $response->assertRedirect('/lorry_receipts/create');
        $response->assertSessionHasErrors(['gr_number', 'invoice_number']);
    }

    public function test_check_lr_invoice_gr_uniqueness_endpoints(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload($admin);
        $this->actingAs($admin)->post('/lorry_receipts', $payload);

        $lrCheck = $this->actingAs($admin)->post('/api/check-lr-number-uniqueness', [
            'lr_number' => 'LR001',
        ]);
        $lrCheck->assertOk();
        $this->assertFalse($lrCheck->json('valid'));

        $invCheck = $this->actingAs($admin)->post('/api/check-invoice-number-uniqueness', [
            'invoice_number' => 'INV001',
        ]);
        $invCheck->assertOk();
        $this->assertFalse($invCheck->json('valid'));

        $grCheck = $this->actingAs($admin)->post('/api/check-gr-number-uniqueness', [
            'gr_number' => 'GR001',
        ]);
        $grCheck->assertOk();
        $this->assertFalse($grCheck->json('available'));
    }

    public function test_batch_store_creates_linked_lrs_with_shared_link_id(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $payload = [
            'gr_date' => now()->toDateString(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'company_id' => $admin->company_id,
            'lrs' => [
                [
                    'lr_number' => 'LRB001',
                    'gr_number' => 'GRB001',
                    'invoice_number' => 'INVB001',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lane' => 'Lane 1',
                    'good_type' => 'Steel',
                    'weight' => 5,
                    'fr_rate' => 500,
                    'amount' => 2500,
                ],
                [
                    'lr_number' => 'LRB002',
                    'gr_number' => 'GRB002',
                    'invoice_number' => 'INVB002',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lane' => 'Lane 2',
                    'good_type' => 'Cement',
                    'weight' => 7,
                    'fr_rate' => 700,
                    'amount' => 4900,
                ],
            ],
        ];

        $response = $this->actingAs($admin)->post('/lorry_receipts', $payload);

        $response->assertRedirect(route('lorry_receipts.index'));

        $linkedId = \App\Models\LorryReceipt::max('linked_lr_id');
        $this->assertDatabaseHas('lorry_receipts', ['lr_number' => 'LRB001', 'linked_lr_id' => $linkedId]);
        $this->assertDatabaseHas('lorry_receipts', ['lr_number' => 'LRB002', 'linked_lr_id' => $linkedId]);
    }

    public function test_get_linked_lrs_endpoint_returns_results(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        \App\Models\LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LRX1',
            'gr_number' => 'GRX1',
            'invoice_number' => 'INVX1',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'reference_by' => $truck->transporter_id,
            'gr_date' => now(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'L1',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'delivery_status' => 'Pending',
            'linked_lr_id' => 10,
        ]);

        \App\Models\LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LRX2',
            'gr_number' => 'GRX2',
            'invoice_number' => 'INVX2',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'reference_by' => $truck->transporter_id,
            'gr_date' => now(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'L2',
            'good_type' => 'Steel',
            'weight' => 2,
            'fr_rate' => 100,
            'amount' => 200,
            'delivery_status' => 'Pending',
            'linked_lr_id' => 10,
        ]);

        $response = $this->actingAs($admin)->get('/lorryreceipts/by-linked/10');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    protected function makeUser(string $roleName): User
    {
        $company = Company::create([
            'name' => 'Role Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        $roleId = UserRole::where('name', $roleName)->value('id');

        return User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);
    }

    public function test_admin_and_moderator_can_access_index_and_data(): void
    {
        $admin = $this->makeUser('admin');
        $moderator = $this->makeUser('moderator');

        $this->actingAs($admin)->get('/lorry_receipts')->assertOk();
        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/lorry_receipts')
            ->assertOk();

        $this->actingAs($moderator)->get('/lorry_receipts')->assertOk();
        $this->actingAs($moderator)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/lorry_receipts')
            ->assertOk();
    }

    public function test_admin_can_edit_update_destroy_but_moderator_cannot(): void
    {
        $admin = $this->makeUser('admin');
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);
        $payload = $this->validPayload($admin);
        $this->actingAs($admin)->post('/lorry_receipts', $payload);
        $lr = \App\Models\LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        // Admin edit ok
        $this->actingAs($admin)->get("/lorry_receipts/{$lr->id}/edit")->assertOk();
        $this->actingAs($admin)->put("/lorry_receipts/{$lr->id}", array_merge($payload, ['lr_number' => 'LR001-EDIT']))->assertRedirect(route('lorry_receipts.index'));

        // Moderator forbidden
        $moderator = $this->makeUser('moderator');
        $this->actingAs($moderator)->get("/lorry_receipts/{$lr->id}/edit")->assertForbidden();
        $this->actingAs($moderator)->put("/lorry_receipts/{$lr->id}", array_merge($payload, ['lr_number' => 'LR001-MOD']))->assertForbidden();
        $this->actingAs($moderator)->delete("/lorry_receipts/{$lr->id}")->assertForbidden();

        // Admin can delete
        $this->actingAs($admin)->delete("/lorry_receipts/{$lr->id}")->assertRedirect(route('lorry_receipts.index'));
    }

    public function test_update_blocks_transporter_change_when_tds_submitted(): void
    {
        Carbon::setTestNow('2025-02-05 12:00:00');

        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [$transporter1, $truck1] = $this->makeTruck($admin);
        $transporter2 = Transporter::factory()->forCompany($admin->company_id)->create([
            'mobile_number' => '8888888888',
        ]);
        $truck2 = Truck::create([
            'transporter_id' => $transporter2->id,
            'truck_number' => 'GJ02CD5678',
            'truck_type' => 'Flatbed',
        ]);

        $lr = \App\Models\LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-TDS-001',
            'gr_number' => 'GR-TDS-001',
            'invoice_number' => 'INV-TDS-001',
            'truck_id' => $truck1->id,
            'transporter_id' => $transporter1->id,
            'reference_by' => $transporter1->id,
            'gr_date' => '2025-01-15',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 10,
            'fr_rate' => 100,
            'actual_amount' => 1000,
            'amount' => 1000,
            'delivery_status' => 'Pending',
        ]);

        FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $transporter1->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 1000,
            'advance_amount' => 0,
            'balance_amount' => 500,
            'tds_amount' => 20,
            'company_id' => $admin->company_id,
            'transporter_tds_percentage' => 2,
            'voucher_number' => 'VB-TDS-001',
        ]);

        $payload = $this->validPayload($admin);
        $payload['lr_number'] = 'LR-TDS-001';
        $payload['gr_number'] = 'GR-TDS-001';
        $payload['invoice_number'] = 'INV-TDS-001';
        $payload['transporter_id'] = $transporter2->id;
        $payload['truck_id'] = $truck2->id;
        $payload['reference_by'] = $transporter2->id;

        $response = $this->actingAs($admin)->from("/lorry_receipts/{$lr->id}/edit")
            ->put("/lorry_receipts/{$lr->id}", $payload);

        $response->assertRedirect("/lorry_receipts/{$lr->id}/edit");
        $response->assertSessionHasErrors(['transporter_id']);
        $this->assertEquals($transporter1->id, $lr->fresh()->transporter_id);

        Carbon::setTestNow();
    }

    public function test_super_admin_forbidden_and_guest_redirected(): void
    {
        $super = $this->makeUser('super_admin');

        $this->actingAs($super)->get('/lorry_receipts')->assertForbidden();
        $this->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/lorry_receipts')
            ->assertForbidden();

        // log out to simulate guest
        $this->post(route('logout'));
        $guest = $this->get('/lorry_receipts');
        $guest->assertRedirect(route('login'));
    }
}

