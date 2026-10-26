<?php

namespace Tests\Feature\LR;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Http\Middleware\EnsureActiveSubscription;
use App\Services\Notifications\LorryReceiptNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            'lr_credits' => 5,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
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

    protected function makeOwnerlessTruck(User $admin): Truck
    {
        return Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => null,
            'truck_number' => 'MH10AA1234',
            'truck_type' => 'Flatbed',
        ]);
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

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-BULK-'.uniqid(),
            'gr_number' => 'GR-BULK-'.uniqid(),
            'invoice_number' => 'INV-BULK-'.uniqid(),
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

        $booking = FreightBooking::create([
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

        $response->assertRedirectContains(route('lorry_receipts.index'));
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

    public function test_store_rejects_duplicate_gr_number_but_allows_duplicate_invoice_number(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload($admin);

        $this->actingAs($admin)->post('/lorry_receipts', $payload);

        $duplicateInvoicePayload = $payload;
        $duplicateInvoicePayload['lr_number'] = 'LR-DUP-INV-001';
        $duplicateInvoicePayload['gr_number'] = 'GR-DUP-INV-001';

        $allowedInvoiceResponse = $this->actingAs($admin)->post('/lorry_receipts', $duplicateInvoicePayload);
        $allowedInvoiceResponse->assertRedirectContains(route('lorry_receipts.index'));

        $duplicateGrPayload = $payload;
        $duplicateGrPayload['lr_number'] = 'LR-DUP-GR-001';

        $response = $this->actingAs($admin)->from('/lorry_receipts/create')->post('/lorry_receipts', $duplicateGrPayload);

        $response->assertRedirect('/lorry_receipts/create');
        $response->assertSessionHasErrors(['gr_number']);
        $response->assertSessionDoesntHaveErrors(['invoice_number']);
    }

    public function test_store_forces_agency_payment_type_for_truck_without_owner(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        $truck = $this->makeOwnerlessTruck($admin);
        [$agency] = $this->makeTruck($admin);

        $payload = [
            'customer_id' => $customer->id,
            'lr_number' => 'LR-AGENCY-001',
            'gr_number' => 'GR-AGENCY-001',
            'invoice_number' => 'INV-AGENCY-001',
            'memo_id' => null,
            'truck_id' => $truck->id,
            'transporter_id' => $agency->id,
            'reference_by' => $agency->id,
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
            'transporter_type' => 'owner',
        ];

        $response = $this->actingAs($admin)->post('/lorry_receipts', $payload);

        $response->assertRedirectContains(route('lorry_receipts.index'));
        $this->assertDatabaseHas('lorry_receipts', [
            'lr_number' => 'LR-AGENCY-001',
            'truck_id' => $truck->id,
            'transporter_type' => 'agency',
        ]);
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
        $this->assertTrue($invCheck->json('valid'));

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

        $response->assertRedirectContains(route('lorry_receipts.index'));

        $linkedId = LorryReceipt::max('linked_lr_id');
        $this->assertDatabaseHas('lorry_receipts', ['lr_number' => 'LRB001', 'linked_lr_id' => $linkedId]);
        $this->assertDatabaseHas('lorry_receipts', ['lr_number' => 'LRB002', 'linked_lr_id' => $linkedId]);
    }

    public function test_batch_store_rejects_fewer_than_two_linked_lrs(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $response = $this->actingAs($admin)->from('/lorry_receipts/create-linked')->post('/lorry_receipts', [
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
            ],
        ]);

        $response->assertRedirect('/lorry_receipts/create-linked');
        $response->assertSessionHasErrors('lrs');
        $this->assertDatabaseMissing('lorry_receipts', ['lr_number' => 'LRB001']);
    }

    public function test_get_linked_lrs_endpoint_returns_results(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        LorryReceipt::create([
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

        LorryReceipt::create([
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
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        // Admin edit ok
        $this->actingAs($admin)->get("/lorry_receipts/{$lr->id}/edit")->assertOk();
        $this->actingAs($admin)->put("/lorry_receipts/{$lr->id}", array_merge($payload, ['lr_number' => 'LR001-EDIT']))->assertRedirectContains(route('lorry_receipts.index'));

        // Moderator forbidden
        $moderator = $this->makeUser('moderator');
        $this->actingAs($moderator)->get("/lorry_receipts/{$lr->id}/edit")->assertForbidden();
        $this->actingAs($moderator)->put("/lorry_receipts/{$lr->id}", array_merge($payload, ['lr_number' => 'LR001-MOD']))->assertForbidden();
        $this->actingAs($moderator)->delete("/lorry_receipts/{$lr->id}")->assertForbidden();

        // Admin can delete pending LR with remark
        $this->actingAs($admin)->delete("/lorry_receipts/{$lr->id}", [
            'delete_remark' => 'Entered in error',
        ])->assertRedirectContains(route('lorry_receipts.index'));
        $this->assertSoftDeleted('lorry_receipts', ['id' => $lr->id]);
    }

    public function test_destroy_rejects_non_pending_lorry_receipt(): void
    {
        $admin = $this->makeUser('admin');
        $payload = $this->validPayload($admin);
        $this->actingAs($admin)->post('/lorry_receipts', $payload);
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();
        $lr->update(['delivery_status' => 'Delivered']);

        $this->actingAs($admin)
            ->from(route('lorry_receipts.index'))
            ->delete("/lorry_receipts/{$lr->id}", ['delete_remark' => 'Should not delete'])
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('lorry_receipts', ['id' => $lr->id, 'deleted_at' => null]);
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

        $lr = LorryReceipt::create([
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

    public function test_update_syncs_related_freight_booking_when_lr_weight_changes(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [$transporter, $truck] = $this->makeTruck($admin);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-SYNC-001',
            'gr_number' => 'GR-SYNC-001',
            'invoice_number' => 'INV-SYNC-001',
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 10,
            'unloading_weight' => 10,
            'fr_rate' => 100,
            'actual_amount' => 1000,
            'amount' => 1000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'delivery_status' => 'Pending',
        ]);

        $freightBooking = FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'transporter_name' => $transporter->name,
            'transporter_tds_percentage' => 2,
            'tr_fr_rate' => 100,
            'tr_amount' => 1000,
            'advance_amount' => 0,
            'balance_amount' => 980,
            'tds_amount' => 20,
            'company_id' => $admin->company_id,
            'voucher_number' => 'VB-SYNC-001',
        ]);

        $lr->freight_booking_id = $freightBooking->id;
        $lr->save();

        $payload = [
            'customer_id' => $customer->id,
            'lr_number' => 'LR-SYNC-001',
            'gr_number' => 'GR-SYNC-001',
            'invoice_number' => 'INV-SYNC-001',
            'memo_id' => null,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'gr_date' => now()->toDateString(),
            'company_id' => $admin->company_id,
            'delivery_status' => 'Pending',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'unloading_station' => null,
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'goods_value' => null,
            'weight' => 8,
            'no_of_units' => 1,
            'fr_rate' => 100,
            'is_fixed_amount' => false,
            'actual_amount' => 1000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ];

        $this->actingAs($admin)
            ->put("/lorry_receipts/{$lr->id}", $payload)
            ->assertRedirectContains(route('lorry_receipts.index'));

        $lr->refresh();
        $freightBooking->refresh();

        $this->assertSame(8.0, (float) $lr->weight);
        $this->assertSame(8.0, (float) $lr->unloading_weight);
        $this->assertSame(800.0, (float) $lr->actual_amount);
        $this->assertSame(800.0, (float) $freightBooking->tr_amount);
        $this->assertSame(160.0, (float) $freightBooking->tds_amount);
        $this->assertSame(640.0, (float) $freightBooking->balance_amount);
    }

    public function test_update_syncs_freight_booking_when_tds_amount_cannot_change(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [$transporter, $truck] = $this->makeTruck($admin);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-SYNC-TDS-001',
            'gr_number' => 'GR-SYNC-TDS-001',
            'invoice_number' => 'INV-SYNC-TDS-001',
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 42,
            'unloading_weight' => 42,
            'fr_rate' => 1354,
            'actual_amount' => 56868,
            'amount' => 56868,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'delivery_status' => 'In-Progress',
        ]);

        $freightBooking = FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'transporter_name' => $transporter->name,
            'transporter_tds_percentage' => 2,
            'tr_fr_rate' => 1354,
            'tr_amount' => 56868,
            'advance_amount' => 56868,
            'balance_amount' => 0,
            'tds_amount' => 1137.36,
            'company_id' => $admin->company_id,
            'voucher_number' => 'VB-SYNC-TDS-001',
        ]);

        $lr->freight_booking_id = $freightBooking->id;
        $lr->save();

        $payload = [
            'customer_id' => $customer->id,
            'lr_number' => 'LR-SYNC-TDS-001',
            'gr_number' => 'GR-SYNC-TDS-001',
            'invoice_number' => 'INV-SYNC-TDS-001',
            'memo_id' => null,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'gr_date' => now()->toDateString(),
            'company_id' => $admin->company_id,
            'delivery_status' => 'In-Progress',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'unloading_station' => null,
            'lane' => 'Lane',
            'good_type' => 'PUTTY',
            'goods_value' => null,
            'weight' => 40,
            'no_of_units' => 1050,
            'fr_rate' => 1354,
            'is_fixed_amount' => false,
            'actual_amount' => 56868,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'owner',
        ];

        $this->actingAs($admin)
            ->put("/lorry_receipts/{$lr->id}", $payload)
            ->assertRedirectContains(route('lorry_receipts.index'));

        $lr->refresh();
        $freightBooking->refresh();

        $this->assertFalse($freightBooking->canChangeTdsAmount());
        $this->assertSame(40.0, (float) $lr->weight);
        $this->assertSame(40.0, (float) $lr->unloading_weight);
        $this->assertSame(54160.0, (float) $freightBooking->tr_amount);
        $this->assertSame(1137.36, (float) $freightBooking->tds_amount);
        $this->assertSame(0.0, (float) $freightBooking->balance_amount);
        $this->assertEqualsWithDelta(3845.36, (float) $freightBooking->negative_adjusted_amount, 0.01);
    }

    public function test_lorry_receipt_views_contain_declaration_warning_markup(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $responseCreate = $this->actingAs($admin)->get('/lorry_receipts/create');
        $responseCreate->assertOk();
        $responseCreate->assertSee('id="declarationWarning"', false);
        $responseCreate->assertSee('Declaration missing');
        $responseCreate->assertSee('Edit Truck Owner');

        $responseCreateLinked = $this->actingAs($admin)->get('/lorry_receipts/create-linked');
        $responseCreateLinked->assertOk();
        $responseCreateLinked->assertSee('id="declarationWarning"', false);
        $responseCreateLinked->assertSee('Declaration missing');
        $responseCreateLinked->assertSee('Edit Truck Owner');
        $responseCreateLinked->assertSee('lrf-page', false);
        $responseCreateLinked->assertSee('Add Linked Good Receipts (Bilty)', false);
        $responseCreateLinked->assertSee('linked-lr-form', false);
        $responseCreateLinked->assertSee('id="linked-lr-submit"', false);
        $responseCreateLinked->assertSee('disabled', false);
        $responseCreateLinked->assertSee('Minimum 2 LRs required', false);
        $responseCreateLinked->assertSee('id="quickAddCustomerModal"', false);
        $responseCreateLinked->assertSee('id="quickAddTruckModal"', false);
        $responseCreateLinked->assertSee('data-bs-target="#quickAddTruckModal"', false);
        $responseCreateLinked->assertSee('data-bs-target="#quickAddCustomerModal"', false);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-DECL',
            'gr_number' => 'GR-DECL',
            'invoice_number' => 'INV-DECL',
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

        $responseEdit = $this->actingAs($admin)->get("/lorry_receipts/{$lr->id}/edit");
        $responseEdit->assertOk();
        $responseEdit->assertSee('id="declarationWarning"', false);
        $responseEdit->assertSee('Declaration missing');
        $responseEdit->assertSee('Edit Truck Owner');
    }

    public function test_edit_linked_lorry_receipt_shows_combined_form_and_updates_group(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $payload = [
            'gr_date' => now()->toDateString(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'transporter_type' => 'agency',
            'company_id' => $admin->company_id,
            'lrs' => [
                [
                    'lr_number' => 'LRB101',
                    'gr_number' => 'GRB101',
                    'invoice_number' => 'INVB101',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'consignor_contact_number' => '9000000001',
                    'consignee_contact_number' => '9000000002',
                    'source_city' => 'City A',
                    'source_state' => 'State A',
                    'destination_city' => 'City B',
                    'destination_state' => 'State B',
                    'lane' => 'Road',
                    'good_type' => 'Steel',
                    'weight' => 5,
                    'fr_rate' => 500,
                    'amount' => 2500,
                ],
                [
                    'lr_number' => 'LRB102',
                    'gr_number' => 'GRB102',
                    'invoice_number' => 'INVB102',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'consignor_contact_number' => '9000000001',
                    'consignee_contact_number' => '9000000002',
                    'source_city' => 'City A',
                    'source_state' => 'State A',
                    'destination_city' => 'City B',
                    'destination_state' => 'State B',
                    'lane' => 'Road',
                    'good_type' => 'Cement',
                    'weight' => 7,
                    'fr_rate' => 700,
                    'amount' => 4900,
                ],
            ],
        ];

        $this->actingAs($admin)->post('/lorry_receipts', $payload)->assertRedirectContains(route('lorry_receipts.index'));

        $linkedId = (int) LorryReceipt::max('linked_lr_id');
        $firstLr = LorryReceipt::query()->where('lr_number', 'LRB101')->firstOrFail();
        $secondLr = LorryReceipt::query()->where('lr_number', 'LRB102')->firstOrFail();

        $this->actingAs($admin)
            ->get("/lorry_receipts/{$firstLr->id}/edit")
            ->assertOk()
            ->assertSee('Edit Linked Good Receipts (Bilty)', false)
            ->assertSee('Linked ID: '.$linkedId, false)
            ->assertSee('lrf-page', false)
            ->assertSee('linked-lr-form', false)
            ->assertSee('LRB101', false)
            ->assertSee('LRB102', false)
            ->assertSee('id="quickAddCustomerModal"', false)
            ->assertSee('id="quickAddTruckModal"', false)
            ->assertSee('data-bs-target="#quickAddTruckModal"', false)
            ->assertSee('data-bs-target="#quickAddCustomerModal"', false);

        $newGrDate = now()->addDay()->toDateString();

        $updatePayload = [
            'gr_date' => $newGrDate,
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'transporter_type' => 'agency',
            'company_id' => $admin->company_id,
            'lrs' => [
                [
                    'id' => $firstLr->id,
                    'lr_number' => 'LRB101',
                    'gr_number' => 'GRB101',
                    'invoice_number' => 'INVB101',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'consignor_contact_number' => '9000000001',
                    'consignee_contact_number' => '9000000002',
                    'source_city' => 'City A',
                    'source_state' => 'State A',
                    'destination_city' => 'City B',
                    'destination_state' => 'State B',
                    'lane' => 'Road',
                    'good_type' => 'Steel',
                    'weight' => 6,
                    'fr_rate' => 500,
                    'amount' => 3000,
                ],
                [
                    'id' => $secondLr->id,
                    'lr_number' => 'LRB102-UPDATED',
                    'gr_number' => 'GRB102',
                    'invoice_number' => 'INVB102',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'consignor_contact_number' => '9000000001',
                    'consignee_contact_number' => '9000000002',
                    'source_city' => 'City A',
                    'source_state' => 'State A',
                    'destination_city' => 'City B',
                    'destination_state' => 'State B',
                    'lane' => 'Road',
                    'good_type' => 'Cement',
                    'weight' => 7,
                    'fr_rate' => 700,
                    'amount' => 4900,
                ],
            ],
        ];

        $this->actingAs($admin)
            ->put("/lorry_receipts/{$firstLr->id}", $updatePayload)
            ->assertRedirectContains(route('lorry_receipts.index'));

        $this->assertDatabaseHas('lorry_receipts', [
            'id' => $firstLr->id,
            'gr_date' => $newGrDate,
            'weight' => 6,
            'amount' => 3000,
            'linked_lr_id' => $linkedId,
        ]);

        $this->assertDatabaseHas('lorry_receipts', [
            'id' => $secondLr->id,
            'lr_number' => 'LRB102-UPDATED',
            'gr_date' => $newGrDate,
            'linked_lr_id' => $linkedId,
        ]);
    }

    public function test_super_admin_forbidden_and_guest_redirected(): void
    {
        $super = $this->makeUser('super_admin');

        $this->actingAs($super)->get('/lorry_receipts')->assertRedirect(route('company.index'));
        $this->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/lorry_receipts')
            ->assertRedirect(route('company.index'));

        UserRole::firstOrCreate(['id' => 6, 'name' => 'driver']);
        $driver = User::factory()->create(['user_role_id' => 6, 'company_id' => null]);
        $this->actingAs($driver)->get('/lorry_receipts')->assertForbidden();

        // log out to simulate guest
        $this->post(route('logout'));
        $guest = $this->get('/lorry_receipts');
        $guest->assertRedirect(route('login'));
    }

    protected function lrReadyForBulkMemo(User $admin, array $overrides = []): LorryReceipt
    {
        [$lr, $booking] = $this->createLrWithBooking($admin, 1000, 0, 'VB-BULK-'.uniqid());
        $lr->freight_booking_id = $booking->id;
        $lr->fill($overrides);
        $lr->save();

        return $lr->fresh();
    }

    public function test_bulk_payment_assigns_memo_and_marks_delivered(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        $shared = [
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
        ];
        $lr1 = $this->lrReadyForBulkMemo($admin, array_merge($shared, ['lr_number' => 'LR-BP-1']));
        $lr2 = $this->lrReadyForBulkMemo($admin, array_merge($shared, ['lr_number' => 'LR-BP-2']));

        $response = $this->actingAs($admin)->postJson(route('lorry_receipts.bulkPayment'), [
            'memo_id' => 'MEMO-9001',
            'lr_ids' => [$lr1->id, $lr2->id],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('lorry_receipts', [
            'id' => $lr1->id,
            'memo_id' => 'MEMO-9001',
            'delivery_status' => 'Delivered',
        ]);
        $this->assertDatabaseHas('freight_bookings', [
            'id' => $lr1->freight_booking_id,
            'is_delivered' => 1,
        ]);
    }

    public function test_bulk_payment_rejects_mixed_customers_and_existing_memo(): void
    {
        $admin = $this->makeAdmin();
        [$customerA, , $consigneeA] = $this->seedConsignees($admin);
        $customerB = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Customer B',
            'address' => 'Addr',
        ]);

        $lrA = $this->lrReadyForBulkMemo($admin, ['customer_id' => $customerA->id, 'consignor_id' => $customerA->id, 'consignee_id' => $consigneeA->id]);
        $lrB = $this->lrReadyForBulkMemo($admin, ['customer_id' => $customerB->id, 'consignor_id' => $customerB->id, 'consignee_id' => $consigneeA->id]);

        $this->actingAs($admin)->postJson(route('lorry_receipts.bulkPayment'), [
            'memo_id' => 'MEMO-MIX',
            'lr_ids' => [$lrA->id, $lrB->id],
        ])->assertStatus(422)->assertJsonPath('success', false);

        $lrWithMemo = $this->lrReadyForBulkMemo($admin, ['memo_id' => 'EXISTING']);
        $lrFresh = $this->lrReadyForBulkMemo($admin);

        $this->actingAs($admin)->postJson(route('lorry_receipts.bulkPayment'), [
            'memo_id' => 'MEMO-NEW',
            'lr_ids' => [$lrWithMemo->id, $lrFresh->id],
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_lorry_receipt_details_includes_linked_lrs_data(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);
        $linkedId = 42;

        $lr1 = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-DET-1',
            'gr_number' => 'GR-DET-1',
            'invoice_number' => 'INV-DET-1',
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
            'weight' => 5,
            'fr_rate' => 100,
            'amount' => 500,
            'delivery_status' => 'Pending',
            'linked_lr_id' => $linkedId,
        ]);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-DET-2',
            'gr_number' => 'GR-DET-2',
            'invoice_number' => 'INV-DET-2',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'reference_by' => $truck->transporter_id,
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Cement',
            'weight' => 7,
            'fr_rate' => 100,
            'amount' => 700,
            'delivery_status' => 'Pending',
            'linked_lr_id' => $linkedId,
        ]);

        $response = $this->actingAs($admin)->getJson("/api/lorry-receipts/{$lr1->id}/details");

        $response->assertOk()
            ->assertJsonPath('lr_number', 'LR-DET-1')
            ->assertJsonPath('linked_lrs_data.common.truck_number', $truck->truck_number)
            ->assertJsonCount(2, 'linked_lrs_data.lrs');
    }

    public function test_lorry_receipts_datatable_returns_filtered_rows_and_action_buttons(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $admin->company->subscription->update(['lr_credits_remaining' => 0]);

        $withBooking = $this->lrReadyForBulkMemo($admin, [
            'lr_number' => 'LR-DT-FILTER',
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'reference_by' => $truck->transporter_id,
            'invoice_generated' => 1,
            'delivery_status' => 'Delivered',
        ]);

        $headers = ['X-Requested-With' => 'XMLHttpRequest'];

        $response = $this->actingAs($admin)->getJson('/lorry_receipts?'.http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'lr_number' => 'LR-DT-FILTER',
            'customer_id' => $customer->id,
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'gr_date_from' => now()->subDay()->toDateString(),
            'gr_date_to' => now()->addDay()->toDateString(),
        ]), $headers);

        $response->assertOk();
        $row = collect($response->json('data'))->first(fn (array $item) => ($item['lr_number'] ?? null) === 'LR-DT-FILTER');
        $this->assertNotNull($row);
        $this->assertStringContainsString('Invoiced', $row['status_badge']);
        $this->assertStringContainsString('freight_bookings/', $row['actions']);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-DT-NO-HIRE',
            'gr_number' => 'GR-DT-NO-HIRE',
            'invoice_number' => 'INV-DT-NO-HIRE',
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
            'freight_booking_id' => null,
        ]);

        $noHireResponse = $this->actingAs($admin)->getJson('/lorry_receipts?'.http_build_query([
            'draw' => 2,
            'start' => 0,
            'length' => 50,
            'lr_number' => 'LR-DT-NO-HIRE',
        ]), $headers);

        $noHireRow = collect($noHireResponse->json('data'))->first(fn (array $item) => ($item['lr_number'] ?? null) === 'LR-DT-NO-HIRE');
        $this->assertNotNull($noHireRow);
        $this->assertStringContainsString('Insufficient Credits', $noHireRow['actions']);
    }

    public function test_create_and_create_linked_redirect_when_plan_cannot_write(): void
    {
        $admin = $this->makeAdmin();
        $admin->company->subscription->update([
            'status' => 'active',
            'ends_on' => now()->subDays(3)->toDateString(),
        ]);

        $this->actingAs($admin)->get('/lorry_receipts/create')
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');

        $this->actingAs($admin)->get('/lorry_receipts/create-linked')
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_index_renders_selected_filter_entities(): void
    {
        $admin = $this->makeAdmin();
        [$customer, , ] = $this->seedConsignees($admin);
        [$transporter, $truck] = $this->makeTruck($admin);

        $this->actingAs($admin)->get('/lorry_receipts?'.http_build_query([
            'customer_id' => $customer->id,
            'transporter_id' => $transporter->id,
            'truck_id' => $truck->id,
        ]))->assertOk()
            ->assertViewHas('selectedCustomer')
            ->assertViewHas('selectedTransporter')
            ->assertViewHas('selectedTruck');
    }

    public function test_api_returns_lorry_receipts_for_customer(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-CUST-API',
            'gr_number' => 'GR-CUST-API',
            'invoice_number' => 'INV-CUST-API',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'reference_by' => $truck->transporter_id,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
            'source_city' => 'Ahmedabad',
            'source_state' => 'Gujarat',
            'destination_city' => 'Mumbai',
            'destination_state' => 'Maharashtra',
            'lane' => 'Lane 1',
            'good_type' => 'Cement',
            'weight' => 10,
            'fr_rate' => 1000,
            'amount' => 10000,
            'transporter_type' => 'agency',
        ]);

        $otherCustomer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Other Customer',
        ]);
        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $otherCustomer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-OTHER-CUST',
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'transporter_type' => 'agency',
        ]);

        $response = $this->actingAs($admin)->getJson("/api/lorry-receipts/customer/{$customer->id}");

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $lr->id, 'lr_number' => 'LR-CUST-API']);
    }

    public function test_create_pages_load_selected_entities_from_old_input(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload($admin);
        $this->actingAs($admin)->post('/lorry_receipts', $payload);

        $this->actingAs($admin)
            ->from('/lorry_receipts/create')
            ->post('/lorry_receipts', $payload)
            ->assertRedirect(route('lorry_receipts.create'));

        $this->actingAs($admin)
            ->get('/lorry_receipts/create')
            ->assertOk()
            ->assertViewHas('consignees', fn ($consignees) => $consignees->count() === 3)
            ->assertViewHas('trucks', fn ($trucks) => $trucks->count() === 1)
            ->assertViewHas('payeeTransporter', fn ($t) => (int) $t->id === (int) $payload['transporter_id']);

        $this->actingAs($admin)
            ->from('/lorry_receipts/create-linked')
            ->post('/lorry_receipts', $payload)
            ->assertRedirect('/lorry_receipts/create-linked');

        $this->actingAs($admin)
            ->get('/lorry_receipts/create-linked')
            ->assertOk()
            ->assertViewHas('consignees', fn ($consignees) => $consignees->count() === 3)
            ->assertViewHas('trucks', fn ($trucks) => $trucks->count() === 1);
    }

    public function test_lorry_receipts_datatable_column_search_filters_by_customer_name(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-COL-FILTER',
            'gr_number' => 'GR-COL-FILTER',
            'invoice_number' => 'INV-COL-FILTER',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'reference_by' => $truck->transporter_id,
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Cement',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'delivery_status' => 'Pending',
        ]);

        $headers = ['X-Requested-With' => 'XMLHttpRequest'];
        $response = $this->actingAs($admin)->getJson('/lorry_receipts?'.http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 50,
            'columns' => [
                2 => ['name' => 'customer_name', 'search' => ['value' => 'Customer A']],
            ],
        ]), $headers);

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, (int) $response->json('recordsFiltered'));
        $this->assertStringContainsString('LR-COL-FILTER', json_encode($response->json('data')));
    }

    public function test_lorry_receipts_datatable_status_badges_cover_delivery_states(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);
        $headers = ['X-Requested-With' => 'XMLHttpRequest'];

        $statuses = [
            'LR-ST-PEND' => 'Pending',
            'LR-ST-DEL' => 'Delivered',
            'LR-ST-CANC' => 'Cancelled',
            'LR-ST-PROG' => 'In-Progress',
        ];

        foreach ($statuses as $lrNumber => $status) {
            LorryReceipt::create([
                'company_id' => $admin->company_id,
                'customer_id' => $customer->id,
                'consignor_id' => $consignor->id,
                'consignee_id' => $consignee->id,
                'lr_number' => $lrNumber,
                'gr_number' => 'GR-'.$lrNumber,
                'invoice_number' => 'INV-'.$lrNumber,
                'truck_id' => $truck->id,
                'transporter_id' => $truck->transporter_id,
                'reference_by' => $truck->transporter_id,
                'gr_date' => now()->toDateString(),
                'source_city' => 'A',
                'source_state' => 'B',
                'destination_city' => 'C',
                'destination_state' => 'D',
                'lane' => 'Lane',
                'good_type' => 'Cement',
                'weight' => 1,
                'fr_rate' => 100,
                'amount' => 100,
                'delivery_status' => $status,
            ]);
        }

        foreach ($statuses as $lrNumber => $status) {
            $response = $this->actingAs($admin)->getJson('/lorry_receipts?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'lr_number' => $lrNumber,
            ]), $headers);

            $response->assertOk();
            $row = collect($response->json('data'))->first(fn (array $item) => ($item['lr_number'] ?? null) === $lrNumber);
            $this->assertNotNull($row);
            $this->assertStringContainsString($status, $row['status_badge']);
        }
    }

    public function test_lorry_receipt_action_shows_plan_expired_when_subscription_read_only(): void
    {
        $admin = $this->makeAdmin();
        $admin->company->subscription->update([
            'status' => 'expired',
            'ends_on' => now()->subDays(2)->toDateString(),
        ]);
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-PLAN-EXP',
            'gr_number' => 'GR-PLAN-EXP',
            'invoice_number' => 'INV-PLAN-EXP',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'reference_by' => $truck->transporter_id,
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Cement',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'delivery_status' => 'Pending',
            'freight_booking_id' => null,
        ]);

        $response = $this->actingAs($admin)->getJson('/lorry_receipts?'.http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'lr_number' => 'LR-PLAN-EXP',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk();
        $row = collect($response->json('data'))->first();
        $this->assertStringContainsString('Plan Expired', $row['actions'] ?? '');
    }

    public function test_destroy_requires_delete_remark(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload($admin);
        $this->actingAs($admin)->post('/lorry_receipts', $payload);
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('lorry_receipts.index'))
            ->delete("/lorry_receipts/{$lr->id}", [])
            ->assertSessionHasErrors('delete_remark');
    }

    public function test_destroy_linked_group_deletes_all_pending_lrs(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $this->actingAs($admin)->post('/lorry_receipts', [
            'gr_date' => now()->toDateString(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'company_id' => $admin->company_id,
            'lrs' => [
                [
                    'lr_number' => 'LR-LINK-DEL-1',
                    'gr_number' => 'GR-LINK-DEL-1',
                    'invoice_number' => 'INV-LINK-DEL-1',
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
                    'lr_number' => 'LR-LINK-DEL-2',
                    'gr_number' => 'GR-LINK-DEL-2',
                    'invoice_number' => 'INV-LINK-DEL-2',
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
        ]);

        $lr1 = LorryReceipt::where('lr_number', 'LR-LINK-DEL-1')->firstOrFail();

        $this->actingAs($admin)
            ->delete("/lorry_receipts/{$lr1->id}", ['delete_remark' => 'Batch entry error'])
            ->assertRedirectContains(route('lorry_receipts.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('lorry_receipts', ['lr_number' => 'LR-LINK-DEL-1']);
        $this->assertSoftDeleted('lorry_receipts', ['lr_number' => 'LR-LINK-DEL-2']);
    }

    public function test_update_rejects_duplicate_lr_number_for_another_lr(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr1 = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        $payloadTwo = $this->validPayload($admin);
        $payloadTwo['lr_number'] = 'LR002';
        $payloadTwo['gr_number'] = 'GR002';
        $payloadTwo['invoice_number'] = 'INV002';
        $this->actingAs($admin)->post('/lorry_receipts', $payloadTwo);
        $lr2 = LorryReceipt::where('lr_number', 'LR002')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('lorry_receipts.edit', $lr2))
            ->put("/lorry_receipts/{$lr2->id}", array_merge($this->validPayload($admin), [
                'lr_number' => 'LR001',
                'gr_number' => 'GR002',
                'invoice_number' => 'INV002',
            ]))
            ->assertRedirect(route('lorry_receipts.edit', $lr2))
            ->assertSessionHasErrors('lr_number');
    }

    public function test_index_ajax_datatables_column_search_filters_parties_truck_and_lr(): void
    {
        $admin = $this->makeAdmin();
        [$transporter, $truck] = $this->makeTruck($admin);

        $customerAlpha = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Alpha Ledger Customer',
            'address' => 'Addr',
        ]);
        $consignorBeta = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Beta Consignor Party',
            'address' => 'Addr',
        ]);
        $consigneeGamma = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Gamma Consignee Party',
            'address' => 'Addr',
        ]);
        $otherCustomer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Other Customer Only',
            'address' => 'Addr',
        ]);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customerAlpha->id,
            'consignor_id' => $consignorBeta->id,
            'consignee_id' => $consigneeGamma->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'lr_number' => 'LR-DT-FILTER-1',
            'gr_number' => 'GR-DT-1',
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'delivery_status' => 'Pending',
        ]);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $otherCustomer->id,
            'consignor_id' => $otherCustomer->id,
            'consignee_id' => $otherCustomer->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'lr_number' => 'LR-DT-OTHER-9',
            'gr_number' => 'GR-DT-9',
            'gr_date' => now()->toDateString(),
            'source_city' => 'X',
            'source_state' => 'Y',
            'destination_city' => 'Z',
            'destination_state' => 'W',
            'lane' => 'Lane',
            'good_type' => 'Cement',
            'weight' => 5,
            'fr_rate' => 50,
            'amount' => 500,
            'delivery_status' => 'Pending',
        ]);

        $ajaxHeaders = [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ];
        $baseQuery = 'draw=1&start=0&length=25';

        $this->actingAs($admin)->withHeaders($ajaxHeaders)
            ->get('/lorry_receipts?'.$baseQuery.'&columns[2][name]=customer_name&columns[2][search][value]=Alpha')
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $this->actingAs($admin)->withHeaders($ajaxHeaders)
            ->get('/lorry_receipts?'.$baseQuery.'&columns[3][name]=consignor_name&columns[3][search][value]=Beta')
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $this->actingAs($admin)->withHeaders($ajaxHeaders)
            ->get('/lorry_receipts?'.$baseQuery.'&columns[4][name]=consignee_name&columns[4][search][value]=Gamma')
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $this->actingAs($admin)->withHeaders($ajaxHeaders)
            ->get('/lorry_receipts?'.$baseQuery.'&columns[5][name]=truck_number&columns[5][search][value]='.urlencode($truck->truck_number))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2);

        $this->actingAs($admin)->withHeaders($ajaxHeaders)
            ->get('/lorry_receipts?'.$baseQuery.'&columns[1][name]=lr_number&columns[1][search][value]=LR-DT-FILTER')
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);
    }

    public function test_bulk_payment_rejects_lr_from_other_company(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();
        $foreignLr = $this->lrReadyForBulkMemo($other);

        $this->actingAs($admin)->postJson(route('lorry_receipts.bulkPayment'), [
            'memo_id' => 'MEMO-FOREIGN',
            'lr_ids' => [$foreignLr->id],
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_bulk_payment_rejects_lr_without_freight_booking(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-NO-FB-'.uniqid(),
            'gr_number' => 'GR-NO-FB-'.uniqid(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
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
            'freight_booking_id' => null,
        ]);

        $this->actingAs($admin)->postJson(route('lorry_receipts.bulkPayment'), [
            'memo_id' => 'MEMO-NO-FB',
            'lr_ids' => [$lr->id],
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_bulk_payment_rejects_memo_used_by_other_customer(): void
    {
        $admin = $this->makeAdmin();
        [$customerA, , $consigneeA] = $this->seedConsignees($admin);
        $customerB = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Customer Memo Conflict',
            'address' => 'Addr',
        ]);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customerB->id,
            'consignor_id' => $customerB->id,
            'consignee_id' => $consigneeA->id,
            'lr_number' => 'LR-MEMO-OTHER-'.uniqid(),
            'gr_number' => 'GR-MEMO-OTHER',
            'memo_id' => 'MEMO-TAKEN',
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

        $lr = $this->lrReadyForBulkMemo($admin, [
            'customer_id' => $customerA->id,
            'consignor_id' => $customerA->id,
            'consignee_id' => $consigneeA->id,
        ]);

        $this->actingAs($admin)->postJson(route('lorry_receipts.bulkPayment'), [
            'memo_id' => 'MEMO-TAKEN',
            'lr_ids' => [$lr->id],
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_bulk_payment_validation_error_returns_422_json(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->postJson(route('lorry_receipts.bulkPayment'), [
            'lr_ids' => [1],
        ])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_update_rejects_expired_subscription(): void
    {
        $admin = $this->makeAdmin();
        $admin->company->subscription->update([
            'status' => 'expired',
            'ends_on' => now()->subDays(2)->toDateString(),
        ]);
        $payload = $this->validPayload($admin);
        $lr = LorryReceipt::create(array_merge($payload, [
            'delivery_status' => 'Pending',
        ]));

        $this->actingAs($admin)
            ->put("/lorry_receipts/{$lr->id}", $this->validPayload($admin))
            ->assertRedirect(route('dashboard'));
    }

    public function test_update_rejects_invoice_generated_lorry_receipt(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();
        $lr->update(['invoice_generated' => 1]);

        $this->actingAs($admin)
            ->put("/lorry_receipts/{$lr->id}", $this->validPayload($admin))
            ->assertRedirectContains(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_update_delivered_lorry_receipt_only_updates_memo_id(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();
        $lr->update(['delivery_status' => 'Delivered', 'memo_id' => 'MEMO-OLD']);

        $this->actingAs($admin)
            ->put("/lorry_receipts/{$lr->id}", [
                'memo_id' => 'MEMO-NEW-DELIVERED',
                'customer_id' => $lr->customer_id,
            ])
            ->assertRedirectContains(route('lorry_receipts.index'))
            ->assertSessionHas('success');

        $this->assertSame('MEMO-NEW-DELIVERED', $lr->fresh()->memo_id);
    }

    public function test_update_delivered_lorry_receipt_rejects_memo_used_by_other_customer(): void
    {
        $admin = $this->makeAdmin();
        [$customerA, , $consigneeA] = $this->seedConsignees($admin);
        $customerB = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Delivered Memo Other',
            'address' => 'Addr',
        ]);

        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();
        $lr->update([
            'delivery_status' => 'Delivered',
            'customer_id' => $customerA->id,
            'consignor_id' => $customerA->id,
            'consignee_id' => $consigneeA->id,
        ]);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customerB->id,
            'consignor_id' => $customerB->id,
            'consignee_id' => $consigneeA->id,
            'lr_number' => 'LR-MEMO-DEL-'.uniqid(),
            'gr_number' => 'GR-MEMO-DEL',
            'memo_id' => 'MEMO-DEL-CONFLICT',
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
            'delivery_status' => 'Delivered',
        ]);

        $this->actingAs($admin)
            ->from(route('lorry_receipts.edit', $lr))
            ->put("/lorry_receipts/{$lr->id}", [
                'memo_id' => 'MEMO-DEL-CONFLICT',
                'customer_id' => $customerA->id,
            ])
            ->assertRedirect(route('lorry_receipts.edit', $lr))
            ->assertSessionHasErrors('memo_id');
    }

    public function test_edit_linked_redirects_when_invoice_generated(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);
        $linkedId = 5050;

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-LINK-INV',
            'gr_number' => 'GR-LINK-INV',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
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
            'linked_lr_id' => $linkedId,
            'invoice_generated' => 1,
        ]);

        $this->actingAs($admin)
            ->get("/lorry_receipts/{$lr->id}/edit")
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_edit_linked_redirects_when_one_lr_is_delivered(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);
        $linkedId = 5051;

        $delivered = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-LINK-DEL-1',
            'gr_number' => 'GR-LINK-DEL-1',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
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
            'delivery_status' => 'Delivered',
            'linked_lr_id' => $linkedId,
        ]);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-LINK-DEL-2',
            'gr_number' => 'GR-LINK-DEL-2',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
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
            'linked_lr_id' => $linkedId,
        ]);

        $this->actingAs($admin)
            ->get("/lorry_receipts/{$delivered->id}/edit")
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_update_linked_rejects_lr_outside_linked_group(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $this->actingAs($admin)->post('/lorry_receipts', [
            'gr_date' => now()->toDateString(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'transporter_type' => 'agency',
            'company_id' => $admin->company_id,
            'lrs' => [
                [
                    'lr_number' => 'LR-LINK-OUT-1',
                    'gr_number' => 'GR-LINK-OUT-1',
                    'invoice_number' => 'INV-LINK-OUT-1',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'source_city' => 'A',
                    'source_state' => 'B',
                    'destination_city' => 'C',
                    'destination_state' => 'D',
                    'lane' => 'Lane',
                    'good_type' => 'Steel',
                    'weight' => 5,
                    'fr_rate' => 100,
                    'amount' => 500,
                ],
                [
                    'lr_number' => 'LR-LINK-OUT-2',
                    'gr_number' => 'GR-LINK-OUT-2',
                    'invoice_number' => 'INV-LINK-OUT-2',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'source_city' => 'A',
                    'source_state' => 'B',
                    'destination_city' => 'C',
                    'destination_state' => 'D',
                    'lane' => 'Lane',
                    'good_type' => 'Cement',
                    'weight' => 6,
                    'fr_rate' => 100,
                    'amount' => 600,
                ],
            ],
        ])->assertRedirectContains(route('lorry_receipts.index'));

        $firstLr = LorryReceipt::where('lr_number', 'LR-LINK-OUT-1')->firstOrFail();
        $foreignLr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-FOREIGN-GROUP',
            'gr_number' => 'GR-FOREIGN-GROUP',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
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
            'linked_lr_id' => 99999,
        ]);

        $this->actingAs($admin)
            ->from("/lorry_receipts/{$firstLr->id}/edit")
            ->put("/lorry_receipts/{$firstLr->id}", [
                'gr_date' => now()->toDateString(),
                'truck_id' => $truck->id,
                'transporter_id' => $truck->transporter_id,
                'company_id' => $admin->company_id,
                'lrs' => [
                    [
                        'id' => $foreignLr->id,
                        'lr_number' => 'LR-FOREIGN-GROUP',
                        'gr_number' => 'GR-FOREIGN-GROUP',
                        'customer_id' => $customer->id,
                        'consignor_id' => $consignor->id,
                        'consignee_id' => $consignee->id,
                        'source_city' => 'A',
                        'source_state' => 'B',
                        'destination_city' => 'C',
                        'destination_state' => 'D',
                        'lane' => 'Lane',
                        'good_type' => 'Steel',
                        'weight' => 1,
                        'fr_rate' => 100,
                        'amount' => 100,
                    ],
                ],
            ])
            ->assertSessionHasErrors('lrs');
    }

    public function test_update_linked_rejects_same_consignor_and_consignee(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $this->actingAs($admin)->post('/lorry_receipts', [
            'gr_date' => now()->toDateString(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'transporter_type' => 'agency',
            'company_id' => $admin->company_id,
            'lrs' => [
                [
                    'lr_number' => 'LR-SAME-PARTY-1',
                    'gr_number' => 'GR-SAME-PARTY-1',
                    'invoice_number' => 'INV-SAME-PARTY-1',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'source_city' => 'A',
                    'source_state' => 'B',
                    'destination_city' => 'C',
                    'destination_state' => 'D',
                    'lane' => 'Lane',
                    'good_type' => 'Steel',
                    'weight' => 5,
                    'fr_rate' => 100,
                    'amount' => 500,
                ],
                [
                    'lr_number' => 'LR-SAME-PARTY-2',
                    'gr_number' => 'GR-SAME-PARTY-2',
                    'invoice_number' => 'INV-SAME-PARTY-2',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'source_city' => 'A',
                    'source_state' => 'B',
                    'destination_city' => 'C',
                    'destination_state' => 'D',
                    'lane' => 'Lane',
                    'good_type' => 'Cement',
                    'weight' => 6,
                    'fr_rate' => 100,
                    'amount' => 600,
                ],
            ],
        ])->assertRedirectContains(route('lorry_receipts.index'));

        $firstLr = LorryReceipt::where('lr_number', 'LR-SAME-PARTY-1')->firstOrFail();

        $this->actingAs($admin)
            ->from("/lorry_receipts/{$firstLr->id}/edit")
            ->put("/lorry_receipts/{$firstLr->id}", [
                'gr_date' => now()->toDateString(),
                'truck_id' => $truck->id,
                'transporter_id' => $truck->transporter_id,
                'transporter_type' => 'agency',
                'company_id' => $admin->company_id,
                'lrs' => [
                    [
                        'id' => $firstLr->id,
                        'lr_number' => 'LR-SAME-PARTY-1',
                        'gr_number' => 'GR-SAME-PARTY-1',
                        'customer_id' => $customer->id,
                        'consignor_id' => $consignor->id,
                        'consignee_id' => $consignor->id,
                        'source_city' => 'A',
                        'source_state' => 'B',
                        'destination_city' => 'C',
                        'destination_state' => 'D',
                        'lane' => 'Lane',
                        'good_type' => 'Steel',
                        'weight' => 5,
                        'fr_rate' => 100,
                        'amount' => 500,
                    ],
                ],
            ])
            ->assertSessionHasErrors('lrs');
    }

    public function test_update_linked_auto_generates_gr_number_when_missing(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $this->actingAs($admin)->post('/lorry_receipts', [
            'gr_date' => now()->toDateString(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'transporter_type' => 'agency',
            'company_id' => $admin->company_id,
            'lrs' => [
                [
                    'lr_number' => 'LR-GR-AUTO-1',
                    'gr_number' => 'GR-GR-AUTO-1',
                    'invoice_number' => 'INV-GR-AUTO-1',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'source_city' => 'A',
                    'source_state' => 'B',
                    'destination_city' => 'C',
                    'destination_state' => 'D',
                    'lane' => 'Lane',
                    'good_type' => 'Steel',
                    'weight' => 5,
                    'fr_rate' => 100,
                    'amount' => 500,
                ],
                [
                    'lr_number' => 'LR-GR-AUTO-2',
                    'gr_number' => 'GR-GR-AUTO-2',
                    'invoice_number' => 'INV-GR-AUTO-2',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'source_city' => 'A',
                    'source_state' => 'B',
                    'destination_city' => 'C',
                    'destination_state' => 'D',
                    'lane' => 'Lane',
                    'good_type' => 'Cement',
                    'weight' => 6,
                    'fr_rate' => 100,
                    'amount' => 600,
                ],
            ],
        ])->assertRedirectContains(route('lorry_receipts.index'));

        $firstLr = LorryReceipt::where('lr_number', 'LR-GR-AUTO-1')->firstOrFail();

        $this->actingAs($admin)
            ->put("/lorry_receipts/{$firstLr->id}", [
                'gr_date' => now()->toDateString(),
                'truck_id' => $truck->id,
                'transporter_id' => $truck->transporter_id,
                'transporter_type' => 'agency',
                'company_id' => $admin->company_id,
                'lrs' => [
                    [
                        'id' => $firstLr->id,
                        'lr_number' => 'LR0099',
                        'gr_number' => '',
                        'invoice_number' => 'INV-GR-AUTO-1',
                        'customer_id' => $customer->id,
                        'consignor_id' => $consignor->id,
                        'consignee_id' => $consignee->id,
                        'source_city' => 'A',
                        'source_state' => 'B',
                        'destination_city' => 'C',
                        'destination_state' => 'D',
                        'lane' => 'Lane',
                        'good_type' => 'Steel',
                        'weight' => 5,
                        'fr_rate' => 100,
                        'amount' => 500,
                    ],
                ],
            ])
            ->assertRedirectContains(route('lorry_receipts.index'));

        $this->assertSame('99', $firstLr->fresh()->gr_number);
    }

    public function test_batch_store_rejects_duplicate_lr_number_in_company(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $response = $this->actingAs($admin)->from('/lorry_receipts/create-linked')->post('/lorry_receipts', [
            'gr_date' => now()->toDateString(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'company_id' => $admin->company_id,
            'lrs' => [
                [
                    'lr_number' => 'LR001',
                    'gr_number' => 'GRB-DUP-1',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lane' => 'Lane',
                    'good_type' => 'Steel',
                    'weight' => 5,
                    'fr_rate' => 100,
                    'amount' => 500,
                ],
                [
                    'lr_number' => 'LRB-DUP-2',
                    'gr_number' => 'GRB-DUP-2',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lane' => 'Lane',
                    'good_type' => 'Cement',
                    'weight' => 6,
                    'fr_rate' => 100,
                    'amount' => 600,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('lrs.0.lr_number');
    }

    public function test_check_gr_number_uniqueness_honors_exclude_id(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        $this->actingAs($admin)
            ->post('/api/check-gr-number-uniqueness', [
                'gr_number' => 'GR001',
                'exclude_id' => $lr->id,
            ])
            ->assertOk()
            ->assertJsonPath('available', true);
    }

    public function test_get_by_linked_id_includes_freight_booking_balances_sorted(): void
    {
        $admin = $this->makeAdmin();
        [$lr, $booking] = $this->createLrWithBooking($admin, 5000, 1000, 'VB-LINKED-API');
        $lr->update(['linked_lr_id' => 6060]);
        $booking->update(['balance_amount' => 3000, 'shortage_amount' => 50, 'damage_amount' => 25]);

        $response = $this->actingAs($admin)->get('/lorryreceipts/by-linked/6060');

        $response->assertOk();
        $payload = $response->json();
        $this->assertCount(1, $payload);
        $this->assertSame(3000.0, (float) $payload[0]['balance_amount']);
        $this->assertSame(50.0, (float) $payload[0]['shortage_amount']);
        $this->assertSame(25.0, (float) $payload[0]['damage_amount']);
    }

    public function test_store_redirects_to_freight_booking_create_when_requested(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload($admin);
        $payload['redirect_to_fr'] = '1';
        $payload['lr_number'] = 'LR-REDIR-FR';
        $payload['gr_number'] = 'GR-REDIR-FR';

        $response = $this->actingAs($admin)->post('/lorry_receipts', $payload);

        $lr = LorryReceipt::where('lr_number', 'LR-REDIR-FR')->firstOrFail();
        $response->assertRedirect(route('freight_bookings.create', ['lorry_receipt_id' => $lr->id]));
        $response->assertSessionHas('success');
    }

    public function test_batch_store_rejects_same_consignor_and_consignee_per_lr(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $this->actingAs($admin)->from('/lorry_receipts/create-linked')->post('/lorry_receipts', [
            'gr_date' => now()->toDateString(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'company_id' => $admin->company_id,
            'lrs' => [
                [
                    'lr_number' => 'LR-SAME-CE-1',
                    'gr_number' => 'GR-SAME-CE-1',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignor->id,
                    'lane' => 'Lane',
                    'good_type' => 'Steel',
                    'weight' => 5,
                    'fr_rate' => 100,
                    'amount' => 500,
                ],
                [
                    'lr_number' => 'LR-SAME-CE-2',
                    'gr_number' => 'GR-SAME-CE-2',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lane' => 'Lane',
                    'good_type' => 'Cement',
                    'weight' => 6,
                    'fr_rate' => 100,
                    'amount' => 600,
                ],
            ],
        ])->assertSessionHasErrors('lrs');
    }

    public function test_batch_store_auto_generates_gr_number_from_lr_number(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $this->actingAs($admin)->post('/lorry_receipts', [
            'gr_date' => now()->toDateString(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'company_id' => $admin->company_id,
            'lrs' => [
                [
                    'lr_number' => 'LR00077',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lane' => 'Lane 1',
                    'good_type' => 'Steel',
                    'weight' => 5,
                    'fr_rate' => 100,
                    'amount' => 500,
                ],
                [
                    'lr_number' => 'LR00078',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lane' => 'Lane 2',
                    'good_type' => 'Cement',
                    'weight' => 6,
                    'fr_rate' => 100,
                    'amount' => 600,
                ],
            ],
        ])->assertRedirectContains(route('lorry_receipts.index'));

        $this->assertDatabaseHas('lorry_receipts', ['lr_number' => 'LR00077', 'gr_number' => '77']);
    }

    public function test_store_auto_generates_gr_number_when_gr_number_missing(): void
    {
        $admin = $this->makeAdmin();
        $payload = $this->validPayload($admin);
        $payload['lr_number'] = 'LR00088';
        unset($payload['gr_number']);

        $this->actingAs($admin)->post('/lorry_receipts', $payload)->assertRedirectContains(route('lorry_receipts.index'));

        $this->assertDatabaseHas('lorry_receipts', ['lr_number' => 'LR00088', 'gr_number' => '88']);
    }

    public function test_batch_store_rejects_duplicate_gr_number_in_company(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        $this->actingAs($admin)->from('/lorry_receipts/create-linked')->post('/lorry_receipts', [
            'gr_date' => now()->toDateString(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'company_id' => $admin->company_id,
            'lrs' => [
                [
                    'lr_number' => 'LR-GRDUP-1',
                    'gr_number' => 'GR001',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lane' => 'Lane',
                    'good_type' => 'Steel',
                    'weight' => 5,
                    'fr_rate' => 100,
                    'amount' => 500,
                ],
                [
                    'lr_number' => 'LR-GRDUP-2',
                    'gr_number' => 'GR-GRDUP-2',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lane' => 'Lane',
                    'good_type' => 'Cement',
                    'weight' => 6,
                    'fr_rate' => 100,
                    'amount' => 600,
                ],
            ],
        ])->assertSessionHasErrors('lrs.0.gr_number');
    }

    public function test_datatable_renders_default_status_badge_for_unknown_delivery_status(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-UNK-STATUS',
            'gr_number' => 'GR-UNK-STATUS',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
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
            'delivery_status' => 'Scheduled',
        ]);

        $response = $this->actingAs($admin)->getJson('/lorry_receipts?'.http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'lr_number' => 'LR-UNK-STATUS',
        ]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk();
        $row = collect($response->json('data'))->first();
        $this->assertStringContainsString('bg-secondary', $row['status_badge']);
        $this->assertStringContainsString('Scheduled', $row['status_badge']);
    }

    public function test_datatable_returns_500_when_query_fails(): void
    {
        Log::spy();
        $admin = $this->makeAdmin();

        \Yajra\DataTables\Facades\DataTables::shouldReceive('of')
            ->once()
            ->andThrow(new \RuntimeException('Simulated LR DataTables failure'));

        $this->actingAs($admin)
            ->getJson('/lorry_receipts?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertStatus(500)
            ->assertJsonStructure(['error']);

        Log::shouldHaveReceived('error')->atLeast()->once();
    }

    public function test_bulk_payment_returns_500_when_persist_throws(): void
    {
        $admin = $this->makeAdmin();
        $lr = $this->lrReadyForBulkMemo($admin);

        LorryReceipt::saving(static function (): void {
            throw new \RuntimeException('Simulated bulk memo failure');
        });

        $this->actingAs($admin)->postJson(route('lorry_receipts.bulkPayment'), [
            'memo_id' => 'MEMO-THROW',
            'lr_ids' => [$lr->id],
        ])->assertStatus(500)->assertJsonPath('success', false);
    }

    public function test_edit_linked_redirects_when_linked_group_missing_in_active_company_context(): void
    {
        $companyA = Company::create([
            'name' => 'Company A',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
        ]);
        $companyB = Company::create([
            'name' => 'Company B',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
        ]);
        foreach ([$companyA, $companyB] as $company) {
            CompanySubscription::create([
                'company_id' => $company->id,
                'status' => 'active',
                'starts_on' => now()->subMonth(),
                'ends_on' => now()->addYear(),
                'included_branches' => 2,
                'users_per_branch' => 3,
            ]);
        }

        $super = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $companyA->update(['user_id' => $super->id]);
        $companyB->update(['user_id' => $super->id]);

        $adminB = User::factory()->create(['user_role_id' => 3, 'company_id' => $companyB->id]);
        [$customer, $consignor, $consignee] = $this->seedConsignees($adminB);
        [, $truck] = $this->makeTruck($adminB);

        $this->actingAs($adminB)->post('/lorry_receipts', [
            'gr_date' => now()->toDateString(),
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'company_id' => $companyB->id,
            'lrs' => [
                [
                    'lr_number' => 'LR-CTX-LINK-1',
                    'gr_number' => 'GR-CTX-LINK-1',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lane' => 'Lane',
                    'good_type' => 'Steel',
                    'weight' => 5,
                    'fr_rate' => 100,
                    'amount' => 500,
                ],
                [
                    'lr_number' => 'LR-CTX-LINK-2',
                    'gr_number' => 'GR-CTX-LINK-2',
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lane' => 'Lane',
                    'good_type' => 'Cement',
                    'weight' => 6,
                    'fr_rate' => 100,
                    'amount' => 600,
                ],
            ],
        ]);

        $firstLr = LorryReceipt::where('lr_number', 'LR-CTX-LINK-1')->firstOrFail();

        $this->actingAs($super)
            ->withSession(['super_admin_company_id' => $companyA->id])
            ->get("/lorry_receipts/{$firstLr->id}/edit")
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_edit_linked_renders_when_truck_and_transporter_are_unset(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);

        $linkedId = (int) (LorryReceipt::where('company_id', $admin->company_id)->max('linked_lr_id') ?? 0) + 1;
        $lr1 = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-MIN-LINK-1',
            'gr_number' => 'GR-MIN-LINK-1',
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
            'linked_lr_id' => $linkedId,
            'truck_id' => null,
            'transporter_id' => null,
            'reference_by' => null,
        ]);
        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-MIN-LINK-2',
            'gr_number' => 'GR-MIN-LINK-2',
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Cement',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'delivery_status' => 'Pending',
            'linked_lr_id' => $linkedId,
            'truck_id' => null,
            'transporter_id' => null,
            'reference_by' => null,
        ]);

        $this->actingAs($admin)
            ->get("/lorry_receipts/{$lr1->id}/edit")
            ->assertOk()
            ->assertSee('Edit Linked Good Receipts (Bilty)', false);
    }

    public function test_update_rejects_same_consignor_and_consignee_on_single_lr(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();
        [$customer, $consignor] = $this->seedConsignees($admin);

        $payload = $this->validPayload($admin);
        $payload['consignor_id'] = $consignor->id;
        $payload['consignee_id'] = $consignor->id;
        $payload['customer_id'] = $customer->id;

        $this->actingAs($admin)
            ->from(route('lorry_receipts.edit', $lr))
            ->put("/lorry_receipts/{$lr->id}", $payload)
            ->assertSessionHasErrors(['consignor_id', 'consignee_id']);
    }

    public function test_update_rejects_duplicate_gr_and_memo_on_single_lr(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr1 = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        $payloadTwo = $this->validPayload($admin);
        $payloadTwo['lr_number'] = 'LR-MEMO-DUP';
        $payloadTwo['gr_number'] = 'GR-MEMO-DUP';
        $payloadTwo['memo_id'] = 'MEMO-UNIQUE-1';
        $this->actingAs($admin)->post('/lorry_receipts', $payloadTwo);
        $lr2 = LorryReceipt::where('lr_number', 'LR-MEMO-DUP')->firstOrFail();

        $update = $this->validPayload($admin);
        $update['gr_number'] = 'GR001';
        $this->actingAs($admin)->from(route('lorry_receipts.edit', $lr2))->put("/lorry_receipts/{$lr2->id}", $update)
            ->assertSessionHasErrors('gr_number');

        $update['gr_number'] = 'GR-MEMO-DUP';
        $update['memo_id'] = $lr1->memo_id ?? 'MEMO-FROM-LR1';
        if ($lr1->memo_id === null) {
            $lr1->update(['memo_id' => 'MEMO-SHARED']);
            $update['memo_id'] = 'MEMO-SHARED';
        }

        $this->actingAs($admin)->from(route('lorry_receipts.edit', $lr2))->put("/lorry_receipts/{$lr2->id}", $update)
            ->assertSessionHasErrors('memo_id');
    }

    public function test_update_notifies_when_delivery_status_changes(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        $mock = $this->mock(LorryReceiptNotificationService::class);
        $mock->shouldReceive('notifyStatusChange')->once();

        $payload = array_merge($this->validPayload($admin), [
            'delivery_status' => 'In-Progress',
        ]);

        $this->actingAs($admin)->put("/lorry_receipts/{$lr->id}", $payload)->assertRedirectContains(route('lorry_receipts.index'));
    }

    public function test_destroy_surfaces_error_when_delete_fails(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        LorryReceipt::deleting(static function (): void {
            throw new \RuntimeException('Simulated delete failure');
        });

        $this->actingAs($admin)
            ->from(route('lorry_receipts.index'))
            ->delete("/lorry_receipts/{$lr->id}", ['delete_remark' => 'Try delete'])
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_get_by_linked_id_returns_500_when_query_fails(): void
    {
        Log::spy();
        $admin = $this->makeAdmin();
        $connection = DB::connection();
        $dispatcher = $connection->getEventDispatcher();
        $connection->listen(function ($query): void {
            if (str_contains($query->sql, 'from "lorry_receipts"')) {
                throw new \RuntimeException('Simulated linked LR API failure');
            }
        });

        $this->actingAs($admin)
            ->getJson('/lorryreceipts/by-linked/1234')
            ->assertStatus(500)
            ->assertJsonPath('error', 'An error occurred while fetching linked LRs');

        $connection->setEventDispatcher($dispatcher);
    }

    public function test_edit_single_lr_renders_when_truck_and_transporter_unset(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-NO-TRUCK',
            'gr_number' => 'GR-NO-TRUCK',
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
            'truck_id' => null,
            'transporter_id' => null,
            'reference_by' => null,
        ]);

        $this->actingAs($admin)->get("/lorry_receipts/{$lr->id}/edit")->assertOk();
    }

    public function test_edit_linked_prefills_agencies_from_old_reference_by_input(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [$agency, $truck] = $this->makeTruck($admin);
        $linkedId = (int) (LorryReceipt::where('company_id', $admin->company_id)->max('linked_lr_id') ?? 0) + 1;

        $lr1 = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-OLD-REF-1',
            'gr_number' => 'GR-OLD-REF-1',
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
            'linked_lr_id' => $linkedId,
            'truck_id' => $truck->id,
            'transporter_id' => $agency->id,
            'reference_by' => null,
        ]);
        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-OLD-REF-2',
            'gr_number' => 'GR-OLD-REF-2',
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Cement',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'delivery_status' => 'Pending',
            'linked_lr_id' => $linkedId,
            'truck_id' => $truck->id,
            'transporter_id' => $agency->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['_old_input' => ['reference_by' => $agency->id]])
            ->get("/lorry_receipts/{$lr1->id}/edit")
            ->assertOk();
    }

    public function test_update_linked_rejects_when_lr_is_not_in_linked_group(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        $this->actingAs($admin)
            ->put("/lorry_receipts/{$lr->id}", [
                'gr_date' => now()->toDateString(),
                'company_id' => $admin->company_id,
                'lrs' => [
                    ['id' => $lr->id, 'lr_number' => 'LR001', 'gr_number' => 'GR001'],
                ],
            ])
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_edit_single_lr_with_owner_transporter_type_loads_truck_owners(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        $owner = Transporter::factory()->forCompany($admin->company_id)->truckOwner()->create([
            'mobile_number' => '9111111111',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-OWNER-TYPE',
            'gr_number' => 'GR-OWNER-TYPE',
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
            'transporter_type' => 'owner',
            'transporter_id' => $owner->id,
            'truck_id' => null,
        ]);

        $this->actingAs($admin)->get("/lorry_receipts/{$lr->id}/edit")->assertOk();
    }

    public function test_edit_redirects_when_plan_cannot_write(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();
        $admin->company->subscription->update([
            'status' => 'active',
            'ends_on' => now()->subDays(3)->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('lorry_receipts.edit', $lr))
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_update_redirects_to_index_when_subscription_cannot_write(): void
    {
        $this->withoutMiddleware(EnsureActiveSubscription::class);

        $admin = $this->makeAdmin();
        $admin->company->subscription->update([
            'status' => 'expired',
            'ends_on' => now()->subDays(2)->toDateString(),
        ]);
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        $this->actingAs($admin)
            ->put("/lorry_receipts/{$lr->id}", $this->validPayload($admin))
            ->assertRedirectContains(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_destroy_redirects_when_plan_cannot_write(): void
    {
        $this->withoutMiddleware(EnsureActiveSubscription::class);

        $admin = $this->makeAdmin();
        $admin->company->subscription->update([
            'status' => 'expired',
            'ends_on' => now()->subDays(2)->toDateString(),
        ]);
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        $this->actingAs($admin)
            ->delete("/lorry_receipts/{$lr->id}", ['delete_remark' => 'cleanup'])
            ->assertRedirectContains(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_update_linked_put_redirects_when_invoice_generated(): void
    {
        $this->withoutMiddleware(EnsureActiveSubscription::class);

        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);
        $linkedId = 88001;

        $first = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-PUT-INV-1',
            'gr_number' => 'GR-PUT-INV-1',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
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
            'linked_lr_id' => $linkedId,
            'invoice_generated' => 1,
        ]);
        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-PUT-INV-2',
            'gr_number' => 'GR-PUT-INV-2',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Cement',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'delivery_status' => 'Pending',
            'linked_lr_id' => $linkedId,
        ]);

        $this->actingAs($admin)
            ->put("/lorry_receipts/{$first->id}", [
                'gr_date' => now()->toDateString(),
                'company_id' => $admin->company_id,
                'lrs' => [
                    [
                        'id' => $first->id,
                        'lr_number' => 'LR-PUT-INV-1',
                        'gr_number' => 'GR-PUT-INV-1',
                        'invoice_number' => null,
                        'customer_id' => $customer->id,
                        'consignor_id' => $consignor->id,
                        'consignee_id' => $consignee->id,
                        'source_city' => 'A',
                        'source_state' => 'B',
                        'destination_city' => 'C',
                        'destination_state' => 'D',
                        'lane' => 'Lane',
                        'good_type' => 'Steel',
                        'weight' => 1,
                        'fr_rate' => 100,
                        'amount' => 100,
                    ],
                ],
            ])
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_update_linked_put_redirects_when_one_lr_is_delivered(): void
    {
        $this->withoutMiddleware(EnsureActiveSubscription::class);

        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);
        $linkedId = 88002;

        $delivered = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-PUT-DEL-1',
            'gr_number' => 'GR-PUT-DEL-1',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
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
            'delivery_status' => 'Delivered',
            'linked_lr_id' => $linkedId,
        ]);
        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-PUT-DEL-2',
            'gr_number' => 'GR-PUT-DEL-2',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Cement',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'delivery_status' => 'Pending',
            'linked_lr_id' => $linkedId,
        ]);

        $this->actingAs($admin)
            ->put("/lorry_receipts/{$delivered->id}", [
                'gr_date' => now()->toDateString(),
                'company_id' => $admin->company_id,
                'lrs' => [
                    [
                        'id' => $delivered->id,
                        'lr_number' => 'LR-PUT-DEL-1',
                        'gr_number' => 'GR-PUT-DEL-1',
                        'invoice_number' => null,
                        'customer_id' => $customer->id,
                        'consignor_id' => $consignor->id,
                        'consignee_id' => $consignee->id,
                        'source_city' => 'A',
                        'source_state' => 'B',
                        'destination_city' => 'C',
                        'destination_state' => 'D',
                        'lane' => 'Lane',
                        'good_type' => 'Steel',
                        'weight' => 1,
                        'fr_rate' => 100,
                        'amount' => 100,
                    ],
                ],
            ])
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_update_linked_put_redirects_when_linked_group_missing_in_company_context(): void
    {
        $this->withoutMiddleware(EnsureActiveSubscription::class);

        $companyA = Company::create([
            'name' => 'Ctx A',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
        ]);
        $companyB = Company::create([
            'name' => 'Ctx B',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
        ]);
        foreach ([$companyA, $companyB] as $company) {
            CompanySubscription::create([
                'company_id' => $company->id,
                'status' => 'active',
                'starts_on' => now()->subMonth(),
                'ends_on' => now()->addYear(),
                'included_branches' => 2,
                'users_per_branch' => 3,
            ]);
        }

        $super = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $adminB = User::factory()->create(['user_role_id' => 3, 'company_id' => $companyB->id]);
        [$customer, $consignor, $consignee] = $this->seedConsignees($adminB);
        [, $truck] = $this->makeTruck($adminB);

        $lr = LorryReceipt::create([
            'company_id' => $companyB->id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-PUT-CTX',
            'gr_number' => 'GR-PUT-CTX',
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
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
            'linked_lr_id' => 88003,
        ]);

        $this->actingAs($super)
            ->withSession(['super_admin_company_id' => $companyA->id])
            ->put("/lorry_receipts/{$lr->id}", [
                'gr_date' => now()->toDateString(),
                'company_id' => $companyA->id,
                'lrs' => [
                    [
                        'id' => $lr->id,
                        'lr_number' => 'LR-PUT-CTX',
                        'gr_number' => 'GR-PUT-CTX',
                        'invoice_number' => null,
                        'customer_id' => $customer->id,
                        'consignor_id' => $consignor->id,
                        'consignee_id' => $consignee->id,
                        'source_city' => 'A',
                        'source_state' => 'B',
                        'destination_city' => 'C',
                        'destination_state' => 'D',
                        'lane' => 'Lane',
                        'good_type' => 'Steel',
                        'weight' => 1,
                        'fr_rate' => 100,
                        'amount' => 100,
                    ],
                ],
            ])
            ->assertRedirect(route('lorry_receipts.index'))
            ->assertSessionHas('error');
    }

    public function test_update_linked_syncs_freight_booking_when_transporter_changes(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [$transporter1, $truck1] = $this->makeTruck($admin);
        [$transporter2, $truck2] = $this->makeTruck($admin);
        $linkedId = 88004;

        $lr1 = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-LINK-SYNC-1',
            'gr_number' => 'GR-LINK-SYNC-1',
            'truck_id' => $truck1->id,
            'transporter_id' => $transporter1->id,
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'delivery_status' => 'Pending',
            'linked_lr_id' => $linkedId,
        ]);
        $lr2 = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-LINK-SYNC-2',
            'gr_number' => 'GR-LINK-SYNC-2',
            'truck_id' => $truck1->id,
            'transporter_id' => $transporter1->id,
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Cement',
            'weight' => 5,
            'fr_rate' => 100,
            'amount' => 500,
            'delivery_status' => 'Pending',
            'linked_lr_id' => $linkedId,
        ]);

        $fb1 = FreightBooking::create([
            'lr_id' => $lr1->id,
            'transporter_id' => $transporter1->id,
            'transporter_tds_percentage' => 2,
            'tr_fr_rate' => 100,
            'tr_amount' => 1000,
            'advance_amount' => 0,
            'balance_amount' => 980,
            'tds_amount' => 20,
            'company_id' => $admin->company_id,
            'voucher_number' => 'VB-LINK-SYNC-1',
        ]);
        $lr1->update(['freight_booking_id' => $fb1->id]);

        $itemPayload = fn (LorryReceipt $lr): array => [
            'id' => $lr->id,
            'lr_number' => $lr->lr_number,
            'gr_number' => $lr->gr_number,
            'invoice_number' => $lr->invoice_number,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => $lr->good_type,
            'weight' => $lr->weight,
            'fr_rate' => $lr->fr_rate,
            'amount' => $lr->amount,
        ];

        $response = $this->actingAs($admin)
            ->put("/lorry_receipts/{$lr1->id}", [
                'gr_date' => now()->toDateString(),
                'truck_id' => $truck2->id,
                'transporter_id' => $transporter2->id,
                'transporter_type' => 'agency',
                'company_id' => $admin->company_id,
                'lrs' => [$itemPayload($lr1), $itemPayload($lr2)],
            ]);

        $response->assertRedirectContains(route('lorry_receipts.index'));
        $response->assertSessionHas('success');

        $this->assertSame($transporter2->id, $lr1->fresh()->transporter_id);
    }

    public function test_update_delivered_lr_rejects_memo_when_other_lr_has_invoice(): void
    {
        $admin = $this->makeAdmin();
        [$customerA, $consignorA, $consigneeA] = $this->seedConsignees($admin);
        $customerB = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Customer B',
            'address' => 'Addr',
        ]);
        [, $truck] = $this->makeTruck($admin);

        $invoiced = LorryReceipt::create(array_merge($this->validPayload($admin), [
            'lr_number' => 'LR-MEMO-INV',
            'gr_number' => 'GR-MEMO-INV',
            'customer_id' => $customerA->id,
            'memo_id' => 'MEMO-INV-GEN',
            'invoice_generated' => 1,
            'delivery_status' => 'Delivered',
        ]));

        $delivered = LorryReceipt::create(array_merge($this->validPayload($admin), [
            'lr_number' => 'LR-MEMO-DEL',
            'gr_number' => 'GR-MEMO-DEL',
            'customer_id' => $customerB->id,
            'delivery_status' => 'Delivered',
            'memo_id' => null,
        ]));

        $this->actingAs($admin)
            ->from(route('lorry_receipts.edit', $delivered))
            ->put("/lorry_receipts/{$delivered->id}", [
                'memo_id' => $invoiced->memo_id,
                'customer_id' => $customerB->id,
            ])
            ->assertSessionHasErrors('memo_id');
    }

    public function test_update_auto_generates_gr_number_when_cleared(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/lorry_receipts', $this->validPayload($admin));
        $lr = LorryReceipt::where('lr_number', 'LR001')->firstOrFail();

        $payload = $this->validPayload($admin);
        $payload['gr_number'] = null;

        $this->actingAs($admin)->put("/lorry_receipts/{$lr->id}", $payload)
            ->assertRedirectContains(route('lorry_receipts.index'));

        $this->assertSame('1', $lr->fresh()->gr_number);
    }

    public function test_update_rejects_duplicate_memo_id_within_company(): void
    {
        $admin = $this->makeAdmin();
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);
        [, $truck] = $this->makeTruck($admin);

        LorryReceipt::create(array_merge($this->validPayload($admin), [
            'lr_number' => 'LR-MEMO-CO-1',
            'gr_number' => 'GR-MEMO-CO-1',
            'memo_id' => 'MEMO-CO-DUP',
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
        ]));

        $lr2 = LorryReceipt::create(array_merge($this->validPayload($admin), [
            'lr_number' => 'LR-MEMO-CO-2',
            'gr_number' => 'GR-MEMO-CO-2',
            'memo_id' => null,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
        ]));

        $update = $this->validPayload($admin);
        $update['lr_number'] = 'LR-MEMO-CO-2';
        $update['gr_number'] = 'GR-MEMO-CO-2';
        $update['memo_id'] = 'MEMO-CO-DUP';

        $this->actingAs($admin)
            ->from(route('lorry_receipts.edit', $lr2))
            ->put("/lorry_receipts/{$lr2->id}", $update)
            ->assertSessionHasErrors('memo_id');
    }

}
