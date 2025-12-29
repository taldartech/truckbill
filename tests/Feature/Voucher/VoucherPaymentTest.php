<?php

namespace Tests\Feature\Voucher;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\PaymentMode;
use App\Models\PaymentType;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherPaymentTest extends TestCase
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

    protected function seedBaseData(User $user, int $voucherNumber = 101): array
    {
        $customer = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Customer',
        ]);
        $consignor = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Consignor',
        ]);
        $consignee = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Consignee',
        ]);

        $transporter = Transporter::create([
            'company_id' => $user->company_id,
            'name' => 'Fast Logistics',
            'pan_number' => 'ABCDE1234F',
            'mobile_number' => '9999999999',
            'is_owner' => 0,
            'pan_active' => true,
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

        $lr = LorryReceipt::create([
            'company_id' => $user->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-VP-001',
            'gr_number' => 'GR-VP-001',
            'invoice_number' => 'INV-VP-001',
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
            'no_of_units' => 1,
            'fr_rate' => 1000,
            'amount' => 10000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ]);

        $booking = FreightBooking::create([
            'lr_id' => $lr->id,
            'tr_fr_rate' => 1000,
            'tr_amount' => 10000,
            'advance_amount' => 0,
            'balance_amount' => 10000,
            'company_id' => $user->company_id,
            'voucher_number' => $voucherNumber,
            'pan_verified' => true,
        ]);

        $lr->freight_booking_id = $booking->id;
        $lr->save();

        // Seed payment types/modes
        $ptype = PaymentType::firstOrCreate(['name' => 'Cash']);
        $pmode = PaymentMode::firstOrCreate(['name' => 'Cash']);

        return [$booking, $ptype, $pmode];
    }

    public function test_admin_can_create_voucher_payment_and_updates_balance(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);

        $response = $this->actingAs($admin)->post('/voucher_payments', [
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-001',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 2000,
            'payment_status' => 'pending',
            'date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('lorry_receipts.index'));
        $this->assertDatabaseHas('voucher_payments', [
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-001',
            'company_id' => $admin->company_id,
            'amount' => 2000,
        ]);
        $this->assertDatabaseHas('freight_bookings', [
            'id' => $booking->id,
            'advance_amount' => 2000,
            'balance_amount' => 8000,
        ]);
    }

    public function test_admin_store_rejects_when_amount_exceeds_tr_amount(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);

        $response = $this->actingAs($admin)->from('/voucher_payments/create')->post('/voucher_payments', [
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-002',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 20000, // exceeds tr_amount (10000)
            'payment_status' => 'pending',
            'date' => now()->toDateString(),
        ]);

        $response->assertRedirect('/voucher_payments/create');
        // Controller validates before creating record and rolls back transaction on validation failure
        $this->assertEquals(0, VoucherPayment::where('voucher_number', 'V-002')->count());
        $booking->refresh();
        // Ensure booking amounts remain unchanged
        $this->assertEquals(0, $booking->advance_amount);
        $this->assertEquals(10000, $booking->balance_amount);
    }

    public function test_moderator_forbidden_to_store(): void
    {
        $moderator = $this->makeUser('moderator');
        [$booking, $ptype, $pmode] = $this->seedBaseData($moderator);

        $response = $this->actingAs($moderator)->post('/voucher_payments', [
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-003',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 500,
            'payment_status' => 'pending',
            'date' => now()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('voucher_payments', ['voucher_number' => 'V-003']);
    }

    public function test_admin_can_view_voucher_payment_show(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-004',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 500,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
        ]);

        $response = $this->actingAs($admin)->get('/voucher_payments/V-004');
        $response->assertOk();
    }

    public function test_moderator_can_view_readonly_voucher_screens_for_own_company(): void
    {
        $admin = $this->makeUser('admin');
        $mod = $this->makeUser('moderator');
        [$modBooking, $ptype, $pmode] = $this->seedBaseData($mod, 201);
        [$adminBooking] = $this->seedBaseData($admin, 301);

        VoucherPayment::create([
            'freight_booking_id' => $modBooking->id,
            'voucher_number' => 'V-MOD-1',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 500,
            'payment_status' => 'pending',
            'company_id' => $mod->company_id,
            'date' => now()->toDateString(),
        ]);

        VoucherPayment::create([
            'freight_booking_id' => $adminBooking->id,
            'voucher_number' => 'V-ADMIN-1',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 700,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($mod)->get('/voucher_payments')->assertOk()
            ->assertSee('V-MOD-1')
            ->assertDontSee('V-ADMIN-1');

        $this->actingAs($mod)->get('/voucher_payments/V-MOD-1')->assertOk();
        $this->actingAs($mod)->get('/daily-payments')->assertOk();
    }
}

