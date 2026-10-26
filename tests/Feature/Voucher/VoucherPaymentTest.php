<?php

namespace Tests\Feature\Voucher;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\PaymentMode;
use App\Models\PaymentType;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
            'name' => 'Company '.$role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $this->ensureActiveSubscription($company);

        $roleId = UserRole::where('name', $role)->value('id');

        return User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);
    }

    protected function ensureActiveSubscription(Company $company): CompanySubscription
    {
        return CompanySubscription::query()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'status' => 'active',
                'starts_on' => now()->subMonth()->toDateString(),
                'ends_on' => now()->addYear()->toDateString(),
                'included_branches' => 5,
                'users_per_branch' => 5,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createVoucherPayment(
        User $user,
        FreightBooking $booking,
        PaymentType $paymentType,
        PaymentMode $paymentMode,
        array $overrides = []
    ): VoucherPayment {
        return VoucherPayment::create(array_merge([
            'freight_booking_id' => $booking->id,
            'voucher_number' => (string) $booking->voucher_number,
            'payment_type_id' => $paymentType->id,
            'payment_mode_id' => $paymentMode->id,
            'payment_type' => $paymentType->name,
            'payment_mode' => $paymentMode->name,
            'amount' => 500,
            'payment_status' => 'in-progress',
            'company_id' => $user->company_id,
            'date' => now()->toDateString(),
        ], $overrides));
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
            'transporter_id' => $transporter->id,
            'pan_verified' => true,
        ]);

        $lr->freight_booking_id = $booking->id;
        $lr->save();

        $booking->update(['lr_id' => $lr->id]);

        // Seed payment types/modes
        $ptype = PaymentType::firstOrCreate(['name' => 'Cash']);
        $pmode = PaymentMode::firstOrCreate(['name' => 'cash']);

        return [$booking, $ptype, $pmode, $lr];
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

    public function test_voucher_index_links_retain_query_string(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-QS-001',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 500,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get('/voucher_payments?date='.now()->toDateString());
        $response->assertOk()
            ->assertSee('V-QS-001')
            ->assertSee('voucher_payments/V-QS-001?date='.now()->toDateString(), false);
    }

    public function test_voucher_show_page_has_back_and_back_to_list_buttons(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-SHOW-001',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 500,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get('/voucher_payments/V-SHOW-001');
        $response->assertOk()
            ->assertSee('Back')
            ->assertSee('Back to List');
    }

    public function test_voucher_show_back_button_preserves_query_string(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-QS-SHOW',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 500,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        $query = 'date='.now()->toDateString().'&transporter=1';
        $response = $this->actingAs($admin)->get('/voucher_payments/V-QS-SHOW?'.$query);
        $response->assertOk();
        $this->assertStringContainsString('?date=', $response->getContent());
        $this->assertStringContainsString('transporter=1', $response->getContent());
    }

    public function test_admin_update_recalculates_freight_booking_advance_from_voucher_payments_sum(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);

        $payment = VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => (string) $booking->voucher_number,
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => 'Cash',
            'payment_mode' => 'cash',
            'amount' => 1000,
            'payment_status' => 'in-progress',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        $booking->update(['advance_amount' => 0, 'balance_amount' => 10000]);

        $response = $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $pmode->id,
                'payment_type' => 'Cash',
                'payment_mode' => 'cash',
                'amount' => 2500,
                'reference_transaction_id' => null,
                'remark' => null,
                'payment_status' => 'in-progress',
                'company_id' => $admin->company_id,
                'pump_id' => null,
                'date' => now()->toDateString(),
            ]
        );

        $response->assertRedirect(route('voucher_payments.index'));
        $booking->refresh();
        $this->assertEquals(2500.0, (float) $booking->advance_amount);
        $this->assertEquals(7500.0, (float) $booking->balance_amount);
    }

    public function test_admin_can_view_create_form_for_freight_booking(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, , , $lr] = $this->seedBaseData($admin);

        $response = $this->actingAs($admin)->get(route('voucher_payments.create', [
            'freight_booking_id' => $booking->id,
            'lorry_receipt_id' => $lr->id,
        ]));

        $response->assertOk()
            ->assertViewIs('voucher_payments.create')
            ->assertViewHas('freight_booking_id', $booking->id);
        $this->assertEquals(10000.0, (float) $response->viewData('balanceAmount'));
    }

    public function test_create_redirects_when_subscription_is_read_only_in_grace(): void
    {
        Cache::put('subscription.settings', [
            'grace_period_days' => 15,
            'gst_rate_percent' => 18.0,
        ], now()->addDay());

        $admin = $this->makeUser('admin');
        [$booking, , , $lr] = $this->seedBaseData($admin);
        $admin->company->subscription->update([
            'status' => 'active',
            'ends_on' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get(route('voucher_payments.create', [
            'freight_booking_id' => $booking->id,
            'lorry_receipt_id' => $lr->id,
        ]));

        $response->assertRedirect(route('freight_bookings.index'));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_view_edit_form(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode, $lr] = $this->seedBaseData($admin);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode);

        $bufferLevel = ob_get_level();
        $response = $this->actingAs($admin)->get(route('voucher_payments.edit', $payment).'?'.http_build_query([
            'lorry_receipt_id' => $lr->id,
            'freight_booking_id' => $booking->id,
        ]));
        while (ob_get_level() > $bufferLevel) {
            ob_end_clean();
        }

        $response->assertOk();
        $response->assertViewIs('voucher_payments.edit');
        $response->assertViewHas('voucher_payment', fn ($vp) => $vp->is($payment));
    }

    public function test_admin_cannot_edit_voucher_payment_from_another_company(): void
    {
        $admin = $this->makeUser('admin');
        $other = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode);

        $this->actingAs($other)->get(route('voucher_payments.edit', $payment))->assertForbidden();
    }

    public function test_admin_can_destroy_pending_payment_and_sync_booking(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, ['amount' => 1500]);
        $booking->update(['advance_amount' => 1500, 'balance_amount' => 8500]);

        $response = $this->actingAs($admin)->delete(route('voucher_payments.destroy', $payment));

        $response->assertRedirect(route('voucher_payments.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('voucher_payments', ['id' => $payment->id]);
        $booking->refresh();
        $this->assertEquals(0.0, (float) $booking->advance_amount);
        $this->assertEquals(10000.0, (float) $booking->balance_amount);
    }

    public function test_admin_cannot_destroy_paid_payment(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->from(route('voucher_payments.index'))
            ->delete(route('voucher_payments.destroy', $payment));

        $response->assertRedirect(route('voucher_payments.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('voucher_payments', ['id' => $payment->id, 'deleted_at' => null]);
    }

    public function test_admin_cannot_update_paid_payment(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $pmode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => $pmode->name,
                'amount' => 600,
                'payment_status' => 'paid',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        );

        $response->assertRedirect(route('voucher_payments.edit', $payment));
        $response->assertSessionHas('error');
    }

    public function test_admin_update_rejects_mismatched_company_id(): void
    {
        $admin = $this->makeUser('admin');
        $other = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode);

        $response = $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $pmode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => $pmode->name,
                'amount' => 600,
                'payment_status' => 'in-progress',
                'company_id' => $other->company_id,
                'date' => now()->toDateString(),
            ]
        );

        $response->assertRedirect(route('voucher_payments.edit', $payment));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_update_payment_status_to_paid(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode);

        $response = $this->actingAs($admin)->from('/daily-payments')->put(route('voucher_payments.update_status'), [
            'payment_id' => $payment->id,
            'status' => 'paid',
        ]);

        $response->assertRedirect('/daily-payments');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('voucher_payments', [
            'id' => $payment->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_moderator_cannot_update_payment_status(): void
    {
        $moderator = $this->makeUser('moderator');
        [$booking, $ptype, $pmode] = $this->seedBaseData($moderator);
        $payment = $this->createVoucherPayment($moderator, $booking, $ptype, $pmode);

        $response = $this->actingAs($moderator)->from('/daily-payments')->put(route('voucher_payments.update_status'), [
            'payment_id' => $payment->id,
            'status' => 'paid',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_bulk_payment_creates_voucher_payments(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, , $lr] = $this->seedBaseData($admin, 501);

        $response = $this->actingAs($admin)->post(route('voucher_payments.bulkPayment'), [
            'payment_type' => $ptype->name,
            'payment_mode' => 'cash',
            'lorry_receipts' => [$lr->id],
            'freight_bookings' => [$lr->id => $booking->id],
            'amounts' => [$lr->id => 1200],
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('voucher_payments.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('voucher_payments', [
            'freight_booking_id' => $booking->id,
            'amount' => 1200,
            'payment_status' => 'in-progress',
            'company_id' => $admin->company_id,
        ]);
        $booking->refresh();
        $this->assertEquals(1200.0, (float) $booking->advance_amount);
        $this->assertEquals(8800.0, (float) $booking->balance_amount);
    }

    public function test_admin_bulk_update_status_updates_pending_bank_payments(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype] = $this->seedBaseData($admin, 601);
        $bankMode = PaymentMode::query()->where('name', 'bank transfer')->firstOrFail();

        $first = $this->createVoucherPayment($admin, $booking, $ptype, $bankMode, [
            'payment_status' => 'pending',
            'payment_mode' => 'bank transfer',
        ]);
        $second = $this->createVoucherPayment($admin, $booking, $ptype, $bankMode, [
            'payment_status' => 'pending',
            'payment_mode' => 'bank transfer',
            'amount' => 300,
        ]);

        $response = $this->actingAs($admin)->from('/daily-payments?status=pending')->post(
            route('voucher_payments.bulkUpdateStatus'),
            [
                'payment_ids' => [$first->id, $second->id],
                'new_status' => 'in-progress',
            ]
        );

        $response->assertRedirect('/daily-payments?status=in-progress');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('voucher_payments', ['id' => $first->id, 'payment_status' => 'in-progress']);
        $this->assertDatabaseHas('voucher_payments', ['id' => $second->id, 'payment_status' => 'in-progress']);
    }

    public function test_bulk_update_status_rejects_mixed_current_statuses(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 701);
        $bankMode = PaymentMode::query()->where('name', 'bank transfer')->firstOrFail();

        $pending = $this->createVoucherPayment($admin, $booking, $ptype, $bankMode, [
            'payment_status' => 'pending',
            'payment_mode' => 'bank transfer',
        ]);
        $inProgress = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'payment_status' => 'in-progress',
        ]);

        $response = $this->actingAs($admin)->from('/daily-payments')->post(
            route('voucher_payments.bulkUpdateStatus'),
            [
                'payment_ids' => [$pending->id, $inProgress->id],
                'new_status' => 'paid',
            ]
        );

        $response->assertRedirect('/daily-payments');
        $response->assertSessionHas('error');
    }

    public function test_daily_payments_supports_filters_and_lists_company_payments(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode, $lr] = $this->seedBaseData($admin, 801);
        $branch = Branch::create([
            'company_id' => $admin->company_id,
            'branch_name' => 'North',
        ]);
        $admin->update(['branch_id' => $branch->id]);

        $this->actingAs($admin);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'amount' => 900,
        ]);

        $date = now()->toDateString();

        $filtered = $this->actingAs($admin)->get('/daily-payments?'.http_build_query([
            'date' => $date,
            'truck_no' => 'GJ01',
            'transporter' => $lr->transporter_id,
            'status' => 'in-progress',
            'payment_mode' => 'cash',
            'branch' => $branch->id,
        ]));

        $filtered->assertOk()
            ->assertViewIs('voucher_payments.daily')
            ->assertViewHas('payments', function ($payments) use ($payment): bool {
                return $payments->contains('id', $payment->id);
            });

        $this->actingAs($admin)->get('/daily-payments?date='.$date)
            ->assertOk()
            ->assertViewHas('payments', function ($payments) use ($payment): bool {
                return $payments->contains('id', $payment->id);
            });
    }

    public function test_voucher_index_filters_by_status_voucher_number_and_truck(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode, $lr] = $this->seedBaseData($admin, 901);
        $bankMode = PaymentMode::query()->where('name', 'bank transfer')->firstOrFail();
        $this->createVoucherPayment($admin, $booking, $ptype, $bankMode, [
            'voucher_number' => 'V-FILTER-901',
            'payment_status' => 'pending',
            'payment_mode' => 'bank transfer',
        ]);

        $assertVoucherListed = function ($response): void {
            $response->assertOk()
                ->assertViewHas('payments', function ($payments): bool {
                    return $payments->has('V-FILTER-901');
                });
        };

        $assertVoucherListed($this->actingAs($admin)->get('/voucher_payments?payment_status=pending&voucher_number=FILTER-901&truck_no=GJ01'));
        $assertVoucherListed($this->actingAs($admin)->get('/voucher_payments?transporter='.$lr->transporter_id));
        $assertVoucherListed($this->actingAs($admin)->get('/voucher_payments?lr_no=LR-VP-001'));
        $assertVoucherListed($this->actingAs($admin)->get('/voucher_payments?freight_booking_no='.$booking->voucher_number));
    }

    public function test_store_warns_on_duplicate_payment_without_confirmation(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);

        $payload = [
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-DUP',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 750,
            'payment_status' => 'in-progress',
            'date' => now()->toDateString(),
        ];

        $this->actingAs($admin)->post('/voucher_payments', $payload)->assertRedirect(route('lorry_receipts.index'));

        $response = $this->actingAs($admin)->from('/voucher_payments/create')->post('/voucher_payments', $payload);

        $response->assertRedirect('/voucher_payments/create');
        $response->assertSessionHas('warning');
        $response->assertSessionHas('duplicate_payment_id');
        $this->assertEquals(1, VoucherPayment::where('voucher_number', 'V-DUP')->count());
    }

    public function test_moderator_forbidden_to_destroy_voucher_payment(): void
    {
        $moderator = $this->makeUser('moderator');
        [$booking, $ptype, $pmode] = $this->seedBaseData($moderator);
        $payment = $this->createVoucherPayment($moderator, $booking, $ptype, $pmode);

        $this->actingAs($moderator)->delete(route('voucher_payments.destroy', $payment))->assertForbidden();
    }

    public function test_admin_can_destroy_linked_voucher_payment_group(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);

        $linked = VoucherLinked::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'payment_type' => 'final',
            'payment_mode' => 'cash',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'amount' => 800,
            'date' => now()->toDateString(),
            'payment_status' => 'in-progress',
            'voucher_ids' => json_encode([]),
        ]);

        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'amount' => 800,
            'linked_voucher_id' => $linked->id,
            'payment_status' => 'in-progress',
        ]);
        $booking->update(['advance_amount' => 800, 'balance_amount' => 9200]);

        $this->actingAs($admin)->delete(route('voucher_payments.destroy', $payment))
            ->assertRedirect(route('voucher_payments.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('voucher_payments', ['id' => $payment->id]);
        $this->assertSoftDeleted('voucher_linkeds', ['id' => $linked->id]);
        $booking->refresh();
        $this->assertEquals(0.0, (float) $booking->advance_amount);
    }

    public function test_admin_update_switches_cash_payment_to_bank_transfer_status(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $bankMode = PaymentMode::firstOrCreate(['name' => 'bank transfer']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'payment_status' => 'in-progress',
        ]);

        $response = $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $bankMode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => 'bank transfer',
                'amount' => 500,
                'payment_status' => 'pending',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        );

        $response->assertRedirect(route('voucher_payments.index'));
        $payment->refresh();
        $this->assertSame('bank transfer', $payment->payment_mode);
        $this->assertSame('pending', $payment->payment_status);
    }

    public function test_admin_update_adjust_in_advance_payment_updates_transporter_advance(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode, $lr] = $this->seedBaseData($admin);
        $booking->transporter->update(['advance_amount' => 3000]);
        $adjustMode = PaymentMode::firstOrCreate(['name' => 'adjust in advance']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'amount' => 1000,
            'payment_status' => 'in-progress',
        ]);
        $booking->update(['advance_amount' => 1000, 'balance_amount' => 9000]);

        $response = $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $adjustMode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => 'adjust in advance',
                'amount' => 1200,
                'payment_status' => 'in-progress',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        );

        $response->assertRedirect(route('voucher_payments.index'));
        $this->assertEquals(1800.0, (float) $booking->transporter->fresh()->advance_amount);
    }

    public function test_admin_update_to_fuel_payment_mode_sets_pending_status(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $fuelMode = PaymentMode::firstOrCreate(['name' => 'fuel']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'payment_status' => 'in-progress',
        ]);

        $response = $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $fuelMode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => 'fuel',
                'amount' => 500,
                'payment_status' => 'pending',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        );

        $response->assertRedirect(route('voucher_payments.index'));
        $payment->refresh();
        $this->assertSame('fuel', $payment->payment_mode);
        $this->assertSame('pending', $payment->payment_status);
    }

    public function test_bulk_payment_surfaces_error_when_create_throws(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, , $lr] = $this->seedBaseData($admin, 801);

        VoucherPayment::creating(static function (): void {
            throw new \RuntimeException('Simulated bulk payment failure');
        });

        $response = $this->actingAs($admin)->from(route('voucher_payments.index'))->post(
            route('voucher_payments.bulkPayment'),
            [
                'payment_type' => $ptype->name,
                'payment_mode' => 'cash',
                'lorry_receipts' => [$lr->id],
                'freight_bookings' => [$lr->id => $booking->id],
                'amounts' => [$lr->id => 500],
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        );

        $response->assertRedirect(route('voucher_payments.index'));
        $response->assertSessionHas('error');
    }

    public function test_voucher_index_and_daily_payments_expose_city_filter_selection(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode, $lr] = $this->seedBaseData($admin);
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $sourceCity = City::create(['name' => 'Ahmedabad', 'state_id' => $state->id, 'country_id' => $country->id]);
        $destCity = City::create(['name' => 'Mumbai', 'state_id' => $state->id, 'country_id' => $country->id]);
        $lr->consignor->update(['city_id' => $sourceCity->id]);
        $lr->consignee->update(['city_id' => $destCity->id]);
        $this->createVoucherPayment($admin, $booking, $ptype, $pmode);

        $this->actingAs($admin)->get(route('voucher_payments.index', [
            'source' => $sourceCity->id,
            'destination' => $destCity->id,
        ]))
            ->assertOk()
            ->assertViewHas('selectedVoucherPaymentSourceCity', fn ($city) => (int) $city['id'] === (int) $sourceCity->id)
            ->assertViewHas('selectedVoucherPaymentDestinationCity', fn ($city) => (int) $city['id'] === (int) $destCity->id);

        $this->actingAs($admin)->get(route('voucher_payments.daily', [
            'date' => now()->toDateString(),
            'source' => $sourceCity->id,
            'destination' => $destCity->id,
        ]))
            ->assertOk()
            ->assertViewHas('selectedVoucherPaymentSourceCity', fn ($city) => (int) $city['id'] === (int) $sourceCity->id);
    }

    public function test_admin_update_auto_marks_bank_payment_paid_with_reference_transaction(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $bankMode = PaymentMode::firstOrCreate(['name' => 'bank transfer']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'payment_mode' => 'bank transfer',
            'payment_mode_id' => $bankMode->id,
            'payment_status' => 'in-progress',
        ]);

        $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $bankMode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => 'bank transfer',
                'amount' => 500,
                'payment_status' => 'in-progress',
                'reference_transaction_id' => 'NEFT-999',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )->assertRedirect(route('voucher_payments.index'));

        $this->assertSame('paid', $payment->fresh()->payment_status);
    }

    public function test_admin_update_rejects_reverting_paid_payment_status(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'payment_status' => 'paid',
        ]);

        $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $pmode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => $pmode->name,
                'amount' => 500,
                'payment_status' => 'in-progress',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )
            ->assertRedirect(route('voucher_payments.edit', $payment))
            ->assertSessionHas('error');

        $this->assertSame('paid', $payment->fresh()->payment_status);
    }

    public function test_admin_update_rejects_pending_status_for_cash_payment_mode(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'payment_status' => 'in-progress',
        ]);

        $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $pmode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => $pmode->name,
                'amount' => 500,
                'payment_status' => 'pending',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )
            ->assertRedirect(route('voucher_payments.edit', $payment))
            ->assertSessionHas('error');
    }

    public function test_admin_update_switching_to_adjust_in_advance_requires_transporter_on_booking(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $booking->update(['transporter_id' => null]);
        $adjustMode = PaymentMode::firstOrCreate(['name' => 'adjust in advance']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode);

        $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $adjustMode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => 'adjust in advance',
                'amount' => 500,
                'payment_status' => 'in-progress',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )
            ->assertRedirect(route('voucher_payments.edit', $payment))
            ->assertSessionHas('error', 'Adjust in advance requires a transporter on the freight booking.');
    }

    public function test_admin_update_switching_cash_to_fuel_sets_default_pending_status(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $fuelMode = PaymentMode::firstOrCreate(['name' => 'fuel']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'payment_status' => 'in-progress',
        ]);

        $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $fuelMode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => 'fuel',
                'amount' => 500,
                'payment_status' => 'in-progress',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )->assertRedirect(route('voucher_payments.index'));

        $payment->refresh();
        $this->assertSame('fuel', $payment->payment_mode);
        $this->assertSame('pending', $payment->payment_status);
    }
}
