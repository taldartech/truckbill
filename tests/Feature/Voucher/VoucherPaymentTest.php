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
use App\Http\Middleware\EnsureActiveSubscription;
use App\Services\AccountingPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
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

    protected function setSubscriptionReadOnly(User $user): void
    {
        Cache::put('subscription.settings', [
            'grace_period_days' => 15,
            'gst_rate_percent' => 18.0,
        ], now()->addDay());

        $user->company->subscription->update([
            'status' => 'active',
            'ends_on' => now()->subDay()->toDateString(),
        ]);
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

    public function test_admin_update_switching_from_adjust_in_advance_to_cash_reverses_advance(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $booking->transporter->update(['advance_amount' => 5000]);
        $adjustMode = PaymentMode::firstOrCreate(['name' => 'adjust in advance']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $adjustMode, [
            'payment_mode' => 'adjust in advance',
            'payment_mode_id' => $adjustMode->id,
            'amount' => 800,
            'payment_status' => 'in-progress',
        ]);
        $booking->transporter->update(['advance_amount' => 4200]);

        $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $pmode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => $pmode->name,
                'amount' => 800,
                'payment_status' => 'in-progress',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )->assertRedirect(route('voucher_payments.index'));

        $this->assertEquals(5000.0, (float) $booking->transporter->fresh()->advance_amount);
    }

    public function test_admin_update_switching_cash_to_bank_when_not_in_progress_uses_default_status(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $bankMode = PaymentMode::firstOrCreate(['name' => 'bank transfer']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'payment_status' => 'pending',
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
                'payment_status' => 'pending',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )->assertRedirect(route('voucher_payments.index'));

        $payment->refresh();
        $this->assertSame('bank transfer', $payment->payment_mode);
        $this->assertSame('pending', $payment->payment_status);
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

    public function test_admin_update_switching_bank_pending_to_cash_sets_in_progress_status(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin);
        $bankMode = PaymentMode::firstOrCreate(['name' => 'bank transfer']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $bankMode, [
            'payment_mode' => 'bank transfer',
            'payment_mode_id' => $bankMode->id,
            'payment_status' => 'pending',
        ]);

        $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $pmode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => 'cash',
                'amount' => 500,
                'payment_status' => 'in-progress',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )->assertRedirect(route('voucher_payments.index'));

        $payment->refresh();
        $this->assertSame('cash', $payment->payment_mode);
        $this->assertSame('in-progress', $payment->payment_status);
    }

    public function test_daily_payments_supports_lr_no_filter_and_linked_lorry_receipt_map(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode, $lr] = $this->seedBaseData($admin, 811);
        $linkedGroupId = 88001;
        $booking->update(['linked_lr_id' => $linkedGroupId]);
        $lr->update(['linked_lr_id' => $linkedGroupId]);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $lr->customer_id,
            'consignor_id' => $lr->consignor_id,
            'consignee_id' => $lr->consignee_id,
            'lr_number' => 'LR-LINKED-SIBLING',
            'linked_lr_id' => $linkedGroupId,
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
            'gr_date' => now()->toDateString(),
            'truck_id' => $lr->truck_id,
            'transporter_id' => $lr->transporter_id,
        ]);

        $linked = VoucherLinked::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'payment_type' => $ptype->name,
            'payment_mode' => $pmode->name,
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'amount' => 400,
            'date' => now()->toDateString(),
            'payment_status' => 'in-progress',
            'voucher_ids' => json_encode([]),
        ]);

        $this->actingAs($admin)->get('/daily-payments?'.http_build_query([
            'date' => now()->toDateString(),
            'lr_no' => 'LR-VP',
        ]))
            ->assertOk()
            ->assertViewHas('linkedLorryReceiptsByLinkedId', fn ($map) => $map->has($linkedGroupId));

        $this->actingAs($admin)->get('/daily-payments?'.http_build_query([
            'date' => now()->toDateString(),
        ]))
            ->assertOk()
            ->assertViewHas('payments1', fn ($payments) => $payments->contains('id', $linked->id));
    }

    public function test_create_redirects_when_freight_booking_is_linked(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, , , $lr] = $this->seedBaseData($admin, 812);
        $booking->update(['linked_lr_id' => 99001]);

        $this->actingAs($admin)->get(route('voucher_payments.create', [
            'freight_booking_id' => $booking->id,
            'lorry_receipt_id' => $lr->id,
        ]))
            ->assertRedirect(route('freight_bookings.index'))
            ->assertSessionHas('error');
    }

    public function test_store_rejects_when_amount_exceeds_balance_amount(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 813);
        $booking->update(['advance_amount' => 9500, 'balance_amount' => 500]);

        $this->actingAs($admin)->from('/voucher_payments/create')->post('/voucher_payments', [
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-BAL',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => $ptype->name,
            'payment_mode' => $pmode->name,
            'amount' => 600,
            'payment_status' => 'in-progress',
            'date' => now()->toDateString(),
        ])
            ->assertRedirect('/voucher_payments/create')
            ->assertSessionHas('error');
    }

    public function test_store_continues_when_accounting_post_fails(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 814);

        $this->mock(AccountingPostingService::class, function ($mock): void {
            $mock->shouldReceive('postVoucherPayment')->andThrow(new \RuntimeException('ledger offline'));
        });

        $this->actingAs($admin)->post('/voucher_payments', [
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-ACCT',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => $ptype->name,
            'payment_mode' => $pmode->name,
            'amount' => 100,
            'payment_status' => 'in-progress',
            'date' => now()->toDateString(),
        ])->assertRedirect(route('lorry_receipts.index'));

        $this->assertDatabaseHas('voucher_payments', ['voucher_number' => 'V-ACCT']);
    }

    public function test_store_surfaces_generic_exception(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 815);

        VoucherPayment::creating(static function (): void {
            throw new \RuntimeException('Simulated store failure');
        });

        $this->actingAs($admin)->from('/voucher_payments/create')->post('/voucher_payments', [
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-ERR',
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => $ptype->name,
            'payment_mode' => $pmode->name,
            'amount' => 100,
            'payment_status' => 'in-progress',
            'date' => now()->toDateString(),
        ])
            ->assertRedirect('/voucher_payments/create')
            ->assertSessionHas('error');
    }

    public function test_show_returns_not_found_and_forbidden_for_other_company(): void
    {
        $owner = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($owner, 816);
        $this->createVoucherPayment($owner, $booking, $ptype, $pmode, ['voucher_number' => 'V-ISO-816']);

        $intruder = $this->makeUser('admin');
        $this->actingAs($intruder)->get(route('voucher_payments.show', 'V-ISO-816'))->assertForbidden();
        $this->actingAs($intruder)->get(route('voucher_payments.show', 'V-MISSING-999'))->assertNotFound();
    }

    public function test_edit_and_destroy_redirect_when_subscription_is_read_only(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 817);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode);
        $this->setSubscriptionReadOnly($admin);
        $this->withoutMiddleware(EnsureActiveSubscription::class);

        $this->actingAs($admin)->get(route('voucher_payments.edit', $payment))
            ->assertRedirect(route('voucher_payments.index'))
            ->assertSessionHas('error');

        $this->actingAs($admin)->delete(route('voucher_payments.destroy', $payment))
            ->assertRedirect(route('voucher_payments.index'))
            ->assertSessionHas('error');
    }

    public function test_update_redirects_when_subscription_is_read_only(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 818);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode);
        $this->setSubscriptionReadOnly($admin);
        $this->withoutMiddleware(EnsureActiveSubscription::class);

        $this->actingAs($admin)->put(route('voucher_payments.update', $payment), [
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
        ])
            ->assertRedirect(route('voucher_payments.index'))
            ->assertSessionHas('error');
    }

    public function test_update_rejects_changing_bank_mode_while_in_progress(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype] = $this->seedBaseData($admin, 819);
        $bankMode = PaymentMode::firstOrCreate(['name' => 'bank transfer']);
        $fuelMode = PaymentMode::firstOrCreate(['name' => 'fuel']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $bankMode, [
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
                'payment_mode_id' => $fuelMode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => 'fuel',
                'amount' => 500,
                'payment_status' => 'in-progress',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )
            ->assertRedirect(route('voucher_payments.edit', $payment))
            ->assertSessionHas('error');
    }

    public function test_update_rejects_total_payments_exceeding_effective_payable(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 820);
        $this->createVoucherPayment($admin, $booking, $ptype, $pmode, ['amount' => 9000]);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, ['amount' => 500]);

        $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $pmode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => $pmode->name,
                'amount' => 1500,
                'payment_status' => 'in-progress',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )
            ->assertRedirect(route('voucher_payments.edit', $payment))
            ->assertSessionHas('error');
    }

    public function test_update_reverse_adjust_fails_when_original_booking_has_no_transporter(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype] = $this->seedBaseData($admin, 821);
        $adjustMode = PaymentMode::firstOrCreate(['name' => 'adjust in advance']);
        $cashMode = PaymentMode::firstOrCreate(['name' => 'cash']);
        $booking->update(['transporter_id' => null]);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $adjustMode, [
            'payment_mode' => 'adjust in advance',
            'payment_mode_id' => $adjustMode->id,
            'amount' => 300,
        ]);

        $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(
            route('voucher_payments.update', $payment),
            [
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type_id' => $ptype->id,
                'payment_mode_id' => $cashMode->id,
                'payment_type' => $ptype->name,
                'payment_mode' => 'cash',
                'amount' => 300,
                'payment_status' => 'in-progress',
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )
            ->assertRedirect(route('voucher_payments.edit', $payment))
            ->assertSessionHas('error', 'Cannot reverse adjust in advance: original freight booking has no transporter.');
    }

    public function test_update_adjust_in_advance_fails_when_transporter_advance_is_insufficient(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 822);
        $adjustMode = PaymentMode::firstOrCreate(['name' => 'adjust in advance']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, ['amount' => 200]);
        $booking->transporter->update(['advance_amount' => 100]);

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
            ->assertSessionHas('error');
    }

    public function test_update_adjusts_linked_voucher_total_when_payment_is_grouped(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 823);
        $linked = VoucherLinked::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'payment_type' => $ptype->name,
            'payment_mode' => $pmode->name,
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'amount' => 500,
            'date' => now()->toDateString(),
            'payment_status' => 'in-progress',
            'voucher_ids' => json_encode([]),
        ]);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'amount' => 500,
            'linked_voucher_id' => $linked->id,
        ]);

        $this->actingAs($admin)->put(route('voucher_payments.update', $payment), [
            'freight_booking_id' => $booking->id,
            'voucher_number' => (string) $booking->voucher_number,
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => $ptype->name,
            'payment_mode' => $pmode->name,
            'amount' => 650,
            'payment_status' => 'in-progress',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ])->assertRedirect(route('voucher_payments.index'));

        $this->assertEquals(650.0, (float) $linked->fresh()->amount);
    }

    public function test_update_surfaces_exception_when_save_fails(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 824);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode);

        VoucherPayment::updating(static function (): void {
            throw new \RuntimeException('Simulated update failure');
        });

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
    }

    public function test_bulk_payment_rejects_invalid_company_and_payment_mode(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, , $lr] = $this->seedBaseData($admin, 825);
        $other = $this->makeUser('admin');

        $this->actingAs($admin)->from(route('voucher_payments.index'))->post(
            route('voucher_payments.bulkPayment'),
            [
                'payment_type' => $ptype->name,
                'payment_mode' => 'cash',
                'lorry_receipts' => [$lr->id],
                'freight_bookings' => [$lr->id => $booking->id],
                'amounts' => [$lr->id => 100],
                'company_id' => $other->company_id,
                'date' => now()->toDateString(),
            ]
        )->assertSessionHas('error');

        $this->actingAs($admin)->from(route('voucher_payments.index'))->post(
            route('voucher_payments.bulkPayment'),
            [
                'payment_type' => $ptype->name,
                'payment_mode' => 'not-a-real-mode',
                'lorry_receipts' => [$lr->id],
                'freight_bookings' => [$lr->id => $booking->id],
                'amounts' => [$lr->id => 100],
                'company_id' => $admin->company_id,
                'date' => now()->toDateString(),
            ]
        )->assertSessionHas('error');
    }

    public function test_bulk_payment_skips_missing_booking_and_linked_freight_booking(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, , $lr] = $this->seedBaseData($admin, 826);
        $booking->update(['linked_lr_id' => 44001]);

        $this->actingAs($admin)->post(route('voucher_payments.bulkPayment'), [
            'payment_type' => $ptype->name,
            'payment_mode' => 'cash',
            'lorry_receipts' => [$lr->id, 999999, 777777],
            'freight_bookings' => [$lr->id => $booking->id, 999999 => 888888],
            'amounts' => [$lr->id => 100, 999999 => 200, 777777 => 0],
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ])
            ->assertRedirect(route('voucher_payments.index'))
            ->assertSessionHas('success');

        $this->assertEquals(0, VoucherPayment::where('freight_booking_id', $booking->id)->count());
    }

    public function test_bulk_update_status_updates_linked_voucher_group(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 827);
        $child = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, ['payment_status' => 'in-progress']);
        $linked = VoucherLinked::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'payment_type' => $ptype->name,
            'payment_mode' => $pmode->name,
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'amount' => 500,
            'date' => now()->toDateString(),
            'payment_status' => 'in-progress',
            'voucher_ids' => json_encode([$child->id]),
        ]);

        $this->actingAs($admin)->from('/daily-payments?status=in-progress')->post(
            route('voucher_payments.bulkUpdateStatus'),
            [
                'multiple_payment_ids' => [$linked->id],
                'new_status' => 'paid',
            ]
        )
            ->assertRedirect('/daily-payments?status=paid')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('voucher_payments', ['id' => $child->id, 'payment_status' => 'paid']);
        $this->assertDatabaseHas('voucher_linkeds', ['id' => $linked->id, 'payment_status' => 'paid']);
    }

    public function test_bulk_update_status_rejects_paid_selection_and_forbidden_for_moderator(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 828);
        $paid = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, ['payment_status' => 'paid']);

        $this->actingAs($admin)->from('/daily-payments')->post(
            route('voucher_payments.bulkUpdateStatus'),
            ['payment_ids' => [$paid->id], 'new_status' => 'in-progress']
        )->assertSessionHas('error');

        $moderator = $this->makeUser('moderator');
        [$modBooking, $modPtype, $modPmode] = $this->seedBaseData($moderator, 829);
        $payment = $this->createVoucherPayment($moderator, $modBooking, $modPtype, $modPmode);

        $this->actingAs($moderator)->from('/daily-payments')->post(
            route('voucher_payments.bulkUpdateStatus'),
            ['payment_ids' => [$payment->id], 'new_status' => 'paid']
        )->assertForbidden();
    }

    public function test_destroy_surfaces_error_when_delete_fails(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 830);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode);

        VoucherPayment::deleting(static function (): void {
            throw new \RuntimeException('Simulated delete failure');
        });

        $this->actingAs($admin)->from(route('voucher_payments.index'))
            ->delete(route('voucher_payments.destroy', $payment))
            ->assertRedirect(route('voucher_payments.index'))
            ->assertSessionHas('error');
    }

    public function test_update_status_honors_redirect_and_rejects_invalid_transitions(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 831);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, ['payment_status' => 'in-progress']);

        $this->actingAs($admin)->put(route('voucher_payments.update_status'), [
            'payment_id' => $payment->id,
            'status' => 'paid',
            'redirect' => '/daily-payments?status=paid',
        ])
            ->assertRedirect('/daily-payments?status=paid')
            ->assertSessionHas('success');

        $paid = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, ['payment_status' => 'paid']);
        $this->actingAs($admin)->from('/daily-payments')->put(route('voucher_payments.update_status'), [
            'payment_id' => $paid->id,
            'status' => 'in-progress',
        ])->assertSessionHas('error');

        $bankMode = PaymentMode::firstOrCreate(['name' => 'bank transfer']);
        $pendingBank = $this->createVoucherPayment($admin, $booking, $ptype, $bankMode, [
            'payment_mode' => 'bank transfer',
            'payment_mode_id' => $bankMode->id,
            'payment_status' => 'in-progress',
        ]);
        $this->actingAs($admin)->from('/daily-payments')->put(route('voucher_payments.update_status'), [
            'payment_id' => $pendingBank->id,
            'status' => 'pending',
        ])->assertSessionHas('error');
    }

    public function test_store_uses_created_at_when_date_omitted(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 832);
        $createdAt = now()->subDays(2)->toDateTimeString();

        $this->actingAs($admin)->post('/voucher_payments', [
            'freight_booking_id' => $booking->id,
            'voucher_number' => (string) $booking->voucher_number,
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => $ptype->name,
            'payment_mode' => $pmode->name,
            'amount' => 100,
            'payment_status' => 'in-progress',
            'created_at' => $createdAt,
        ])
            ->assertRedirect(route('lorry_receipts.index'));

        $payment = VoucherPayment::where('freight_booking_id', $booking->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame($createdAt, (string) $payment->date);
    }

    public function test_store_rejects_payment_amount_above_balance(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 833);
        $booking->update(['balance_amount' => 500]);

        $this->actingAs($admin)->from(route('voucher_payments.create'))->post(route('voucher_payments.store'), [
            'freight_booking_id' => $booking->id,
            'voucher_number' => (string) $booking->voucher_number,
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $pmode->id,
            'payment_type' => $ptype->name,
            'payment_mode' => $pmode->name,
            'amount' => 900,
            'payment_status' => 'in-progress',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ])
            ->assertRedirect(route('voucher_payments.create'))
            ->assertSessionHas('error');
    }

    public function test_update_sets_default_status_when_payment_mode_changes_to_bank(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 835);
        $bankMode = PaymentMode::firstOrCreate(['name' => 'bank transfer']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, [
            'payment_status' => 'in-progress',
        ]);

        $this->actingAs($admin)->put(route('voucher_payments.update', $payment), [
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
        ])
            ->assertRedirect(route('voucher_payments.index'));

        $this->assertSame('pending', $payment->fresh()->payment_status);
    }

    public function test_update_rejects_invalid_payment_status_transition_for_bank_mode(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $cashMode] = $this->seedBaseData($admin, 836);
        $chequeMode = PaymentMode::firstOrCreate(['name' => 'cheque']);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $cashMode, [
            'payment_status' => 'paid',
        ]);

        $this->actingAs($admin)->from(route('voucher_payments.edit', $payment))->put(route('voucher_payments.update', $payment), [
            'freight_booking_id' => $booking->id,
            'voucher_number' => (string) $booking->voucher_number,
            'payment_type_id' => $ptype->id,
            'payment_mode_id' => $chequeMode->id,
            'payment_type' => $ptype->name,
            'payment_mode' => 'cheque',
            'amount' => 500,
            'payment_status' => 'in-progress',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ])
            ->assertRedirect(route('voucher_payments.edit', $payment))
            ->assertSessionHas('error');
    }

    public function test_bulk_payment_logs_and_skips_when_advance_would_exceed_payable(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, , $lr] = $this->seedBaseData($admin, 837);
        $booking->update(['balance_amount' => 100, 'advance_amount' => 9900, 'tr_amount' => 10000]);

        $this->actingAs($admin)->post(route('voucher_payments.bulkPayment'), [
            'payment_type' => $ptype->name,
            'payment_mode' => 'cash',
            'lorry_receipts' => [$lr->id],
            'freight_bookings' => [$lr->id => $booking->id],
            'amounts' => [$lr->id => 500],
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ])
            ->assertRedirect(route('voucher_payments.index'))
            ->assertSessionHas('success');

        $this->assertEquals(0, VoucherPayment::where('freight_booking_id', $booking->id)->count());
    }

    public function test_update_status_surfaces_exception_when_save_fails(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 839);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, ['payment_status' => 'in-progress']);

        VoucherPayment::updating(static function (): void {
            throw new \RuntimeException('Simulated status update failure');
        });

        $this->actingAs($admin)->from('/daily-payments')->put(route('voucher_payments.update_status'), [
            'payment_id' => $payment->id,
            'status' => 'paid',
        ])
            ->assertRedirect('/daily-payments')
            ->assertSessionHas('error');
    }

    public function test_bulk_update_status_surfaces_exception_when_save_fails(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $ptype, $pmode] = $this->seedBaseData($admin, 840);
        $payment = $this->createVoucherPayment($admin, $booking, $ptype, $pmode, ['payment_status' => 'in-progress']);

        VoucherPayment::updating(static function (): void {
            throw new \RuntimeException('Simulated bulk status failure');
        });

        $this->actingAs($admin)->from('/daily-payments')->post(route('voucher_payments.bulkUpdateStatus'), [
            'payment_ids' => [$payment->id],
            'new_status' => 'paid',
        ])
            ->assertRedirect('/daily-payments')
            ->assertSessionHas('error');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
