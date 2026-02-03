<?php

namespace Tests\Feature\Freight;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreightBookingTest extends TestCase
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

    protected function seedConsignees(User $user): array
    {
        $customer = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Customer',
            'address' => 'Addr',
        ]);
        $consignor = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Consignor',
            'address' => 'Addr',
        ]);
        $consignee = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Consignee',
            'address' => 'Addr',
        ]);

        return [$customer, $consignor, $consignee];
    }

    protected function makeTransporterAndTruck(User $user): array
    {
        $transporter = Transporter::factory()
            ->forCompany($user->company_id)
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

    protected function makeLorryReceipt(User $user): LorryReceipt
    {
        [$customer, $consignor, $consignee] = $this->seedConsignees($user);
        [, $truck] = $this->makeTransporterAndTruck($user);

        return LorryReceipt::create([
            'company_id' => $user->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-FB-001',
            'gr_number' => 'GR-FB-001',
            'invoice_number' => 'INV-FB-001',
            'memo_id' => null,
            'truck_id' => $truck->id,
            'transporter_id' => $truck->transporter_id,
            'reference_by' => $truck->transporter_id,
            'gr_date' => now()->toDateString(),
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
            'amount' => 10000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ]);
    }

    protected function bookingPayload(User $user, LorryReceipt $lr): array
    {
        return [
            'lr_id' => $lr->id,
            'tr_fr_rate' => 1000,
            'tr_amount' => 10000,
            'advance_amount' => 2000,
            'balance_amount' => 8000,
            'tds_amount' => 0,
            'eway_bill_number' => 'EB123',
            'eway_bill_expiry' => now()->addDays(5)->toDateString(),
            'good_type' => 'Cement',
            'pan_verified' => true,
            'remark' => 'Test',
            'is_delivered' => false,
            'is_cancelled' => false,
            'company_id' => $user->company_id,
            'confirmed' => true,
        ];
    }

    public function test_admin_and_moderator_can_access_index_and_data(): void
    {
        $admin = $this->makeUser('admin');
        $moderator = $this->makeUser('moderator');

        $this->actingAs($admin)->get('/freight_bookings')->assertOk();
        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data')
            ->assertOk();

        $this->actingAs($moderator)->get('/freight_bookings')->assertOk();
        $this->actingAs($moderator)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data')
            ->assertOk();
    }

    public function test_super_admin_forbidden_and_guest_redirected(): void
    {
        $super = $this->makeUser('super_admin');
        $this->actingAs($super)->get('/freight_bookings')->assertForbidden();
        $this->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data')
            ->assertForbidden();

        $this->post(route('logout'));
        $this->get('/freight_bookings')->assertRedirect(route('login'));
    }

    public function test_store_creates_booking_and_links_lr(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $response = $this->actingAs($admin)->post('/freight_bookings', $this->bookingPayload($admin, $lr));

        $response->assertRedirect(route('freight_bookings.index'));
        $this->assertDatabaseHas('freight_bookings', [
            'lr_id' => $lr->id,
            'company_id' => $admin->company_id,
        ]);
        $this->assertNotNull($lr->fresh()->freight_booking_id);
    }

    public function test_store_prevents_duplicate_booking_for_same_lr(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $this->actingAs($admin)->post('/freight_bookings', $this->bookingPayload($admin, $lr));

        $response = $this->actingAs($admin)->from('/freight_bookings')->post('/freight_bookings', $this->bookingPayload($admin, $lr));

        $response->assertRedirect(route('freight_bookings.index'));
        $this->assertEquals(1, FreightBooking::count());
    }

    public function test_moderator_can_create_booking(): void
    {
        $moderator = $this->makeUser('moderator');
        $lr = $this->makeLorryReceipt($moderator);

        $response = $this->actingAs($moderator)->post('/freight_bookings', $this->bookingPayload($moderator, $lr));

        $response->assertRedirect(route('freight_bookings.index'));
        $this->assertDatabaseHas('freight_bookings', [
            'lr_id' => $lr->id,
            'company_id' => $moderator->company_id,
        ]);
    }

    public function test_payment_history_endpoint_returns_ok(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 10,
        ]));

        $response = $this->actingAs($admin)->post('/freight_bookings/payment-history', [
            'freight_booking_ids' => [$fb->id],
        ]);

        $response->assertOk();
    }

    public function test_payment_history_includes_pump_details(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 99,
        ]));

        $pump = Pump::create([
            'user_id' => $admin->id,
            'pump_name' => 'HP Pump',
            'contact_name' => 'Operator',
            'contact_number' => '9999999999',
            'remark' => 'Test pump',
        ]);

        \App\Models\VoucherPayment::create([
            'freight_booking_id' => $fb->id,
            'voucher_number' => 'V-HP-1',
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 500,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'pump_id' => $pump->id,
            'date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->post('/freight_bookings/payment-history', [
            'freight_booking_ids' => [$fb->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('payments.0.pump.pump_name', 'HP Pump');
    }

    public function test_update_recalculates_lr_amount_with_damage_and_shortage(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        // Create initial booking with existing damage/shortage
        $initialBooking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'damage_amount' => 50,
            'shortage_amount' => 20,
            'balance_amount' => 7930, // tr_amount 10000 - advance 2000 - damage 50 - shortage 20
            'voucher_number' => 5001,
        ]));

        // Update with new damage/shortage values
        $payload = array_merge($this->bookingPayload($admin, $lr), [
            'damage_amount' => 70,
            'shortage_amount' => 10,
            'balance_amount' => 7920, // tr_amount 10000 - advance 2000 - new damage/shortage 80
            'voucher_number' => 5001,
        ]);

        $this->actingAs($admin)->put("/freight_bookings/{$initialBooking->id}", $payload)
            ->assertRedirect(route('freight_bookings.index'));

        $lr->refresh();

        $this->assertEquals(70.0, (float) $lr->damage_amount);
        $this->assertEquals(10.0, (float) $lr->shortage_amount);
        // original amount (10000 + 50 + 20) - (70 + 10) = 9990
        $this->assertEquals(9990.0, (float) $lr->amount);
    }
}

