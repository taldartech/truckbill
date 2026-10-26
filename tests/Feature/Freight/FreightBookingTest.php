<?php

namespace Tests\Feature\Freight;

use App\Http\Controllers\FreightBookingController;
use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\FreightBookingCustomAmount;
use App\Models\LorryReceipt;
use App\Models\PaymentMode;
use App\Models\PaymentType;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use App\Services\FreightBookingCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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
            'name' => 'Company '.$role,
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

    /**
     * @return array<string, mixed>
     */
    private function freightBookingsDataTableRequestPayload(array $overrides = []): array
    {
        $columnDefs = [
            ['data' => 'checkbox', 'name' => 'checkbox', 'searchable' => false, 'orderable' => false],
            ['data' => 'gr_date', 'name' => 'gr_date'],
            ['data' => 'lr_number', 'name' => 'lr_number'],
            ['data' => 'customer', 'name' => 'customer', 'searchable' => true, 'orderable' => false],
            ['data' => 'transporter', 'name' => 'transporter', 'searchable' => true, 'orderable' => false],
            ['data' => 'truck_number', 'name' => 'truck_number', 'searchable' => true, 'orderable' => false],
            ['data' => 'route', 'name' => 'route', 'searchable' => true, 'orderable' => false],
            ['data' => 'weight', 'name' => 'weight'],
            ['data' => 'rate', 'name' => 'rate'],
            ['data' => 'total', 'name' => 'total'],
            ['data' => 'balance', 'name' => 'balance'],
            ['data' => 'actions', 'name' => 'actions', 'searchable' => false, 'orderable' => false],
        ];
        $columns = [];
        foreach ($columnDefs as $def) {
            $columns[] = [
                'data' => $def['data'],
                'name' => $def['name'],
                'searchable' => ($def['searchable'] ?? true) ? 'true' : 'false',
                'orderable' => ($def['orderable'] ?? true) ? 'true' : 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ];
        }

        return array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'search' => ['value' => '', 'regex' => 'false'],
            'order' => [['column' => 1, 'dir' => 'desc']],
            'columns' => $columns,
        ], $overrides);
    }

    public function test_datatable_list_search_with_joined_lr_does_not_error(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 'V-343233',
            'linked_lr_id' => 5,
        ]));

        $payload = $this->freightBookingsDataTableRequestPayload([
            'filter_search' => '343233',
        ]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data?' . http_build_query($payload));

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayNotHasKey('error', $json);
        $this->assertSame(1, (int) ($json['recordsFiltered'] ?? 0));
    }

    public function test_datatable_list_search_with_no_matches_returns_empty_results(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        FreightBooking::create($this->bookingPayload($admin, $lr));

        $payload = $this->freightBookingsDataTableRequestPayload([
            'filter_search' => 'ZZZ-NO-MATCH-TERM',
        ]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data?' . http_build_query($payload));

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayNotHasKey('error', $json);
        $this->assertSame(0, (int) ($json['recordsFiltered'] ?? -1));
        $this->assertSame(0, count($json['data'] ?? []));
    }

    public function test_super_admin_redirected_without_company_and_guest_redirected(): void
    {
        $super = $this->makeUser('super_admin');
        $this->actingAs($super)->get('/freight_bookings')->assertRedirect(route('company.index'));
        $this->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data')
            ->assertRedirect(route('company.index'));

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

    public function test_store_uses_document_calculations_and_defaults(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $response = $this->actingAs($admin)->post('/freight_bookings', array_merge(
            $this->bookingPayload($admin, $lr),
            [
                'advance_amount' => 2500,
                'balance_amount' => 1234,
                'tds_amount' => 999,
                'tr_amount' => 1,
            ]
        ));

        $response->assertRedirect(route('freight_bookings.index'));

        $booking = FreightBooking::query()->latest('id')->firstOrFail();

        $this->assertSame(10000.0, (float) $booking->tr_amount);
        $this->assertSame(0.0, (float) $booking->advance_amount);
        $this->assertSame(2000.0, (float) $booking->tds_amount);
        $this->assertSame(8000.0, (float) $booking->balance_amount);
        $this->assertSame(0.0, (float) ($booking->detention ?? 0));
        $this->assertSame(0.0, (float) ($booking->deviation ?? 0));
        $this->assertSame(0.0, (float) ($booking->misc_expense ?? 0));
        $this->assertSame(0.0, (float) ($booking->damage_amount ?? 0));
        $this->assertSame(0.0, (float) ($booking->shortage_amount ?? 0));
        $this->assertSame(0.0, (float) ($booking->negative_adjusted_amount ?? 0));
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

        VoucherPayment::create([
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

    public function test_update_does_not_change_tds_when_submission_date_passed(): void
    {
        Carbon::setTestNow('2025-02-05 12:00:00');

        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $lr->update(['gr_date' => '2025-01-15', 'delivery_status' => 'Delivered']);

        $initialBooking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'balance_amount' => 4800,
            'tds_amount' => 200,
            'transporter_tds_percentage' => 2,
            'voucher_number' => 5002,
            'is_delivered' => true,
        ]));

        $newTrAmount = 9000;
        $payload = array_merge($this->bookingPayload($admin, $lr), [
            'unloading_weight' => 9,
            'tr_amount' => $newTrAmount,
            'balance_amount' => $newTrAmount - 2000 - 200,
            'tds_amount' => 200,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'voucher_number' => 5002,
            'is_delivered' => true,
        ]);

        $this->actingAs($admin)->put("/freight_bookings/{$initialBooking->id}", $payload)
            ->assertRedirect(route('freight_bookings.index'));

        $initialBooking->refresh();
        $this->assertEquals(200.0, (float) $initialBooking->tds_amount);
        $this->assertEquals(9000.0, (float) $initialBooking->tr_amount);

        Carbon::setTestNow();
    }

    public function test_edit_page_shows_tds_lock_message_when_cannot_change_tds(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'balance_amount' => 0,
            'tds_amount' => 200,
            'voucher_number' => 5001,
        ]));
        $lr->update(['freight_booking_id' => $fb->id]);

        $response = $this->actingAs($admin)->get("/freight_bookings/{$fb->id}/edit");

        $response->assertOk()
            ->assertSee('TDS already submitted; amount cannot be changed.');
    }

    public function test_edit_page_shows_tds_auto_calculated_message_when_can_change_tds(): void
    {
        Carbon::setTestNow('2025-02-01 12:00:00');

        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $lr->update(['gr_date' => '2025-01-15']);

        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'balance_amount' => 500,
            'tds_amount' => 200,
            'voucher_number' => 5001,
        ]));
        $lr->update(['freight_booking_id' => $fb->id]);

        $response = $this->actingAs($admin)->get("/freight_bookings/{$fb->id}/edit");

        $response->assertOk()
            ->assertSee('Auto-calculated based on transporter TDS percentage')
            ->assertDontSee('TDS already submitted; amount cannot be changed.');

        Carbon::setTestNow();
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
        // amount = actual_amount - damage - shortage = 10000 - 70 - 10 = 9920
        $this->assertEquals(9920.0, (float) $lr->amount);
    }

    public function test_update_syncs_lorry_receipt_status_and_unlinks_when_booking_is_cancelled(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $booking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 5003,
            'transporter_id' => $lr->transporter_id,
            'transporter_name' => $lr->transporter?->name,
            'transporter_tds_percentage' => 20,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'balance_amount' => 8000,
        ]));
        $lr->update(['freight_booking_id' => $booking->id]);

        $payload = array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 5003,
            'damage_amount' => 60,
            'shortage_amount' => 40,
            'unloading_weight' => 8,
            'is_cancelled' => '1',
            'is_delivered' => '0',
        ]);

        $this->actingAs($admin)->put("/freight_bookings/{$booking->id}", $payload)
            ->assertRedirect(route('freight_bookings.index'));

        $lr->refresh();

        $this->assertNull($lr->freight_booking_id);
        $this->assertSame('Pending', $lr->delivery_status);
        $this->assertSame(60.0, (float) $lr->damage_amount);
        $this->assertSame(40.0, (float) $lr->shortage_amount);
        $this->assertSame(8.0, (float) $lr->unloading_weight);
        $this->assertSame(8000.0, (float) $lr->actual_amount);
        $this->assertSame(7900.0, (float) $lr->amount);
    }

    public function test_update_uses_document_formula_for_freight_booking_amounts(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $lr->update(['freight_booking_id' => null]);

        $booking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 7001,
            'transporter_id' => $lr->transporter_id,
            'transporter_name' => $lr->transporter?->name,
            'transporter_tds_percentage' => 20,
            'tds_amount' => 2000,
            'advance_amount' => 0,
            'balance_amount' => 8000,
        ]));
        $lr->update(['freight_booking_id' => $booking->id]);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-FR-1',
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 1000,
            'payment_status' => 'in-progress',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-FR-2',
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 500,
            'payment_status' => 'in-progress',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        $payload = array_merge($this->bookingPayload($admin, $lr), [
            'tr_fr_rate' => 1100,
            'tr_amount' => 99999,
            'advance_amount' => 99999,
            'balance_amount' => 99999,
            'tds_amount' => 99999,
            'detention' => 100,
            'deviation' => 40,
            'misc_expense' => 30,
            'damage_amount' => 20,
            'shortage_amount' => 50,
            'unloading_weight' => 8,
        ]);

        $this->actingAs($admin)->put("/freight_bookings/{$booking->id}", $payload)
            ->assertRedirect(route('freight_bookings.index'));

        $booking->refresh();

        $this->assertSame(8800.0, (float) $booking->tr_amount);
        $this->assertSame(1500.0, (float) $booking->advance_amount);
        $this->assertSame(2200.0, (float) $booking->tds_amount);
        $this->assertSame(5140.0, (float) $booking->balance_amount);
    }

    public function test_negative_balance_adjustment_reconciles_when_shortage_applied_after_delivery(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $service = app(FreightBookingCalculationService::class);

        $booking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 9001,
            'transporter_id' => $lr->transporter_id,
            'transporter_name' => $lr->transporter?->name,
            'transporter_tds_percentage' => 1,
            'tr_fr_rate' => 1000,
            'tr_amount' => 10000,
            'tds_amount' => 100,
            'advance_amount' => 10500,
            'balance_amount' => -600,
            'negative_adjusted_amount' => 0,
            'shortage_amount' => 0,
            'is_delivered' => 1,
        ]));

        $lr->update(['freight_booking_id' => $booking->id, 'unloading_weight' => 10]);

        $service->applyNegativeAdjustment($booking, $lr->lr_number);
        $booking->refresh();

        $this->assertSame(600.0, (float) $booking->negative_adjusted_amount);
        $this->assertSame(0.0, (float) $booking->balance_amount);

        $booking->shortage_amount = 5000;
        $booking->balance_amount = $service->calculateForUpdate(
            false,
            1000,
            10,
            10,
            10000,
            10500,
            0,
            0,
            0,
            0,
            5000,
            1,
            false,
            100,
            600,
        )['balance_amount'];
        $booking->save();

        $service->applyNegativeAdjustment($booking, $lr->lr_number);
        $booking->refresh();

        $this->assertSame(5600.0, (float) $booking->negative_adjusted_amount);
        $this->assertSame(0.0, (float) $booking->balance_amount);
        $this->assertEqualsWithDelta(
            0.0,
            $booking->balanceFromAdvance(),
            0.01,
            'Stored balance must match balanceFromAdvance() after shortage is applied.'
        );
    }

    public function test_linked_negative_balance_adjustment_skipped_when_group_net_is_zero(): void
    {
        $admin = $this->makeUser('admin');
        [$positiveBooking, $negativeBooking] = $this->createLinkedFreightBookings($admin, 500.0, -500.0);

        app(FreightBookingCalculationService::class)
            ->applyNegativeAdjustment($negativeBooking);

        $positiveBooking->refresh();
        $negativeBooking->refresh();
        $transporter = $positiveBooking->transporter()->firstOrFail();

        $this->assertSame(500.0, (float) $positiveBooking->balance_amount);
        $this->assertSame(0.0, (float) $positiveBooking->negative_adjusted_amount);
        $this->assertSame(-500.0, (float) $negativeBooking->balance_amount);
        $this->assertSame(0.0, (float) $negativeBooking->negative_adjusted_amount);
        $this->assertSame(0.0, (float) $transporter->advance_amount);
        $this->assertSame(0, AdvancePayment::count());
    }

    public function test_linked_negative_balance_adjustment_splits_proportionally_on_combined_deficit(): void
    {
        $admin = $this->makeUser('admin');
        [$positiveBooking, $negativeBooking] = $this->createLinkedFreightBookings($admin, 100.0, -150.0);

        app(FreightBookingCalculationService::class)
            ->applyNegativeAdjustment($negativeBooking);

        $positiveBooking->refresh();
        $negativeBooking->refresh();
        $transporter = $positiveBooking->transporter()->firstOrFail();

        $this->assertSame(100.0, (float) $positiveBooking->balance_amount);
        $this->assertSame(0.0, (float) $positiveBooking->negative_adjusted_amount);
        $this->assertSame(-100.0, (float) $negativeBooking->balance_amount);
        $this->assertSame(50.0, (float) $negativeBooking->negative_adjusted_amount);
        $this->assertSame(50.0, (float) $transporter->advance_amount);
        $this->assertSame(0.0, round(
            (float) $positiveBooking->balance_amount + (float) $negativeBooking->balance_amount,
            2
        ));
        $this->assertDatabaseHas('advance_payments', [
            'transporter_id' => $transporter->id,
            'advance_amount' => 50,
        ]);
    }

    public function test_individual_voucher_payment_rejected_for_linked_booking(): void
    {
        $admin = $this->makeUser('admin');
        [$positiveBooking] = $this->createLinkedFreightBookings($admin, 5000.0, 3000.0);
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $response = $this->actingAs($admin)->postJson(route('freight_bookings.voucher_payment'), [
            'payment_type_id' => $paymentType->id,
            'payment_mode_id' => $paymentMode->id,
            'payment_type' => 'Cash',
            'payment_mode' => 'cash',
            'booking_ids' => [$positiveBooking->id],
            'payment_amounts' => [1000],
            'total_amount' => 1000,
            'date' => now()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_voucher_payment_store_rejected_for_linked_booking(): void
    {
        $admin = $this->makeUser('admin');
        [$positiveBooking] = $this->createLinkedFreightBookings($admin, 5000.0, 3000.0);
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $response = $this->actingAs($admin)->from(route('voucher_payments.create'))->post(route('voucher_payments.store'), [
            'freight_booking_id' => $positiveBooking->id,
            'voucher_number' => (string) $positiveBooking->voucher_number,
            'payment_type_id' => $paymentType->id,
            'payment_mode_id' => $paymentMode->id,
            'payment_type' => 'Cash',
            'payment_mode' => 'cash',
            'amount' => 1000,
            'payment_status' => 'in-progress',
            'date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('voucher_payments.create'));
        $response->assertSessionHas('error');
        $this->assertSame(0, VoucherPayment::where('freight_booking_id', $positiveBooking->id)->count());
    }

    /**
     * @return array{0: FreightBooking, 1: FreightBooking}
     */
    private function createLinkedFreightBookings(User $admin, float $firstBalance, float $secondBalance): array
    {
        $lrOne = $this->makeLorryReceipt($admin);
        $lrTwo = $this->makeLorryReceipt($admin);
        $linkedId = 12345;

        $positiveBooking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lrOne), [
            'voucher_number' => 7101,
            'linked_lr_id' => $linkedId,
            'transporter_id' => $lrOne->transporter_id,
            'transporter_name' => $lrOne->transporter?->name,
            'transporter_tds_percentage' => 20,
            'tr_amount' => 10000,
            'tds_amount' => 2000,
            'advance_amount' => 10000 - 2000 - $firstBalance,
            'balance_amount' => $firstBalance,
            'negative_adjusted_amount' => 0,
            'is_cancelled' => 0,
        ]));

        $negativeBooking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lrTwo), [
            'voucher_number' => 7102,
            'linked_lr_id' => $linkedId,
            'transporter_id' => $lrOne->transporter_id,
            'transporter_name' => $lrOne->transporter?->name,
            'transporter_tds_percentage' => 20,
            'tr_amount' => 10000,
            'tds_amount' => 2000,
            'advance_amount' => 10000 - 2000 - $secondBalance,
            'balance_amount' => $secondBalance,
            'negative_adjusted_amount' => 0,
            'is_cancelled' => 0,
        ]));

        return [$positiveBooking, $negativeBooking];
    }

    public function test_show_page_displays_combined_payment_button_for_linked_bookings(): void
    {
        $admin = $this->makeUser('admin');
        $lr1 = $this->makeLorryReceipt($admin);
        $lr2 = $this->makeLorryReceipt($admin);

        $linkedId = 999;
        $fb1 = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr1), [
            'linked_lr_id' => $linkedId,
            'balance_amount' => 5000,
            'voucher_number' => 6001,
        ]));
        FreightBooking::create(array_merge($this->bookingPayload($admin, $lr2), [
            'linked_lr_id' => $linkedId,
            'balance_amount' => 3000,
            'voucher_number' => 6002,
        ]));
        $lr1->update(['freight_booking_id' => $fb1->id]);

        $response = $this->actingAs($admin)->get("/freight_bookings/{$fb1->id}");

        $response->assertOk()
            ->assertSee('Combined Payment')
            ->assertSee('combinedPaymentModal');
    }

    public function test_show_page_displays_voucher_payment_button_for_non_linked_booking(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'linked_lr_id' => null,
            'balance_amount' => 5000,
            'voucher_number' => 6003,
        ]));
        $lr->update(['freight_booking_id' => $fb->id]);

        $response = $this->actingAs($admin)->get("/freight_bookings/{$fb->id}");

        $response->assertOk()
            ->assertSee('Voucher Payment')
            ->assertSee('openVoucherPaymentModal');
    }

    public function test_show_page_displays_combined_payment_modal_for_linked_bookings(): void
    {
        $admin = $this->makeUser('admin');
        $lr1 = $this->makeLorryReceipt($admin);
        $lr2 = $this->makeLorryReceipt($admin);

        $linkedId = 998;
        $fb1 = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr1), [
            'linked_lr_id' => $linkedId,
            'balance_amount' => 4000,
            'shortage_amount' => 100,
            'damage_amount' => 50,
            'voucher_number' => 6004,
        ]));
        FreightBooking::create(array_merge($this->bookingPayload($admin, $lr2), [
            'linked_lr_id' => $linkedId,
            'balance_amount' => 2000,
            'voucher_number' => 6005,
        ]));
        $lr1->update(['freight_booking_id' => $fb1->id]);

        $response = $this->actingAs($admin)->get("/freight_bookings/{$fb1->id}");

        $response->assertOk()
            ->assertSee('combinedPaymentModal')
            ->assertSee('Combined Amount')
            ->assertSee('Shortage Amount')
            ->assertSee('Damage Amount')
            ->assertSee('Submit Combined Payment');
    }

    public function test_show_page_payment_history_shows_combined_badge_for_voucher_linked(): void
    {
        $admin = $this->makeUser('admin');
        $lr1 = $this->makeLorryReceipt($admin);
        $lr2 = $this->makeLorryReceipt($admin);

        $linkedId = 997;
        $fb1 = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr1), [
            'linked_lr_id' => $linkedId,
            'balance_amount' => 1000,
            'voucher_number' => 6006,
        ]));
        $fb2 = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr2), [
            'linked_lr_id' => $linkedId,
            'balance_amount' => 500,
            'voucher_number' => 6007,
        ]));
        $lr1->update(['freight_booking_id' => $fb1->id]);

        VoucherLinked::create([
            'freight_booking_id' => $fb1->id,
            'company_id' => $admin->company_id,
            'payment_type' => 'final',
            'payment_mode' => 'cash',
            'payment_type_id' => PaymentType::first()?->id,
            'payment_mode_id' => PaymentMode::first()?->id,
            'amount' => 1500,
            'date' => now()->toDateString(),
            'payment_status' => 'paid',
            'voucher_ids' => json_encode([]),
        ]);

        $response = $this->actingAs($admin)->get("/freight_bookings/{$fb1->id}");

        $response->assertOk()
            ->assertSee('Combined', false)
            ->assertSee('Payment History');
    }

    public function test_voucher_payment_includes_remaining_advance_balance_for_adjust_in_advance_mode(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $transporter = $lr->transporter;
        $transporter->update(['advance_amount' => 2500]);

        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'transporter_id' => $transporter->id,
            'advance_amount' => 0,
            'balance_amount' => 10000,
            'voucher_number' => 6010,
        ]));
        $lr->update(['freight_booking_id' => $fb->id]);

        $paymentType = PaymentType::firstOrCreate(['name' => 'final']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'adjust in advance']);

        $response = $this->actingAs($admin)->post(route('freight_bookings.voucher_payment'), [
            'payment_type_id' => $paymentType->id,
            'payment_mode_id' => $paymentMode->id,
            'payment_type' => 'final',
            'payment_mode' => 'adjust in advance',
            'booking_ids' => [$fb->id],
            'payment_amounts' => [1000],
            'total_amount' => 1000,
            'date' => now()->toDateString(),
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('adjust_in_advance', true)
            ->assertJsonStructure(['remaining_advance_balance', 'transporter_name']);

        $remaining = $response->json('remaining_advance_balance');
        $this->assertIsNumeric($remaining);
        $this->assertEquals(1500.0, (float) $remaining);

        $fb->refresh();
        $this->assertEquals(1000.0, (float) $fb->advance_amount);
        $this->assertEquals(9000.0, (float) $fb->balance_amount);
        $this->assertEquals(1500.0, (float) $transporter->fresh()->advance_amount);
    }

    /**
     * @return array{0: FreightBooking, 1: LorryReceipt}
     */
    private function createBookingForCustomAmountUpdate(User $admin): array
    {
        $lr = $this->makeLorryReceipt($admin);
        $lr->update(['freight_booking_id' => null]);

        $booking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 8201,
            'transporter_id' => $lr->transporter_id,
            'transporter_name' => $lr->transporter?->name,
            'transporter_tds_percentage' => 20,
            'tds_amount' => 2000,
            'advance_amount' => 0,
            'balance_amount' => 8000,
        ]));
        $lr->update(['freight_booking_id' => $booking->id]);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-CA-1',
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 1000,
            'payment_status' => 'in-progress',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-CA-2',
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 500,
            'payment_status' => 'in-progress',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        return [$booking, $lr];
    }

    /**
     * @return array<string, mixed>
     */
    private function customAmountUpdatePayload(User $admin, LorryReceipt $lr, array $customAmounts = [], array $overrides = []): array
    {
        return array_merge($this->bookingPayload($admin, $lr), [
            'tr_fr_rate' => 1100,
            'tr_amount' => 99999,
            'advance_amount' => 99999,
            'balance_amount' => 99999,
            'tds_amount' => 99999,
            'detention' => 0,
            'deviation' => 0,
            'misc_expense' => 0,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'unloading_weight' => 8,
            'custom_amounts' => $customAmounts,
        ], $overrides);
    }

    public function test_update_positive_custom_amount_increases_balance(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $lr] = $this->createBookingForCustomAmountUpdate($admin);

        $payload = $this->customAmountUpdatePayload($admin, $lr, [
            ['description' => 'Loading charge', 'amount' => 500],
        ]);

        $this->actingAs($admin)->put("/freight_bookings/{$booking->id}", $payload)
            ->assertRedirect(route('freight_bookings.index'));

        $booking->refresh();

        $this->assertSame(5600.0, (float) $booking->balance_amount);
        $this->assertDatabaseHas('freight_booking_custom_amounts', [
            'freight_booking_id' => $booking->id,
            'description' => 'Loading charge',
            'amount' => 500,
        ]);
    }

    public function test_update_negative_custom_amount_decreases_balance(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $lr] = $this->createBookingForCustomAmountUpdate($admin);

        $payload = $this->customAmountUpdatePayload($admin, $lr, [
            ['description' => 'Rate deduction', 'amount' => -200],
        ]);

        $this->actingAs($admin)->put("/freight_bookings/{$booking->id}", $payload)
            ->assertRedirect(route('freight_bookings.index'));

        $booking->refresh();

        $this->assertSame(4900.0, (float) $booking->balance_amount);
    }

    public function test_update_multiple_custom_amounts_sum_correctly(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $lr] = $this->createBookingForCustomAmountUpdate($admin);

        $payload = $this->customAmountUpdatePayload($admin, $lr, [
            ['description' => 'Loading charge', 'amount' => 300],
            ['description' => 'Unloading charge', 'amount' => 200],
        ]);

        $this->actingAs($admin)->put("/freight_bookings/{$booking->id}", $payload)
            ->assertRedirect(route('freight_bookings.index'));

        $booking->refresh();

        $this->assertSame(5600.0, (float) $booking->balance_amount);
        $this->assertCount(2, $booking->customAmounts);
    }

    public function test_update_removed_custom_amount_row_no_longer_affects_balance(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $lr] = $this->createBookingForCustomAmountUpdate($admin);

        $firstPayload = $this->customAmountUpdatePayload($admin, $lr, [
            ['description' => 'Loading charge', 'amount' => 500],
            ['description' => 'Unloading charge', 'amount' => 300],
        ]);

        $this->actingAs($admin)->put("/freight_bookings/{$booking->id}", $firstPayload)
            ->assertRedirect(route('freight_bookings.index'));

        $booking->refresh();
        $remainingRow = $booking->customAmounts->firstWhere('description', 'Loading charge');
        $this->assertNotNull($remainingRow);
        $this->assertSame(5900.0, (float) $booking->balance_amount);

        $secondPayload = $this->customAmountUpdatePayload($admin, $lr, [
            ['id' => $remainingRow->id, 'description' => 'Loading charge', 'amount' => 500],
        ]);

        $this->actingAs($admin)->put("/freight_bookings/{$booking->id}", $secondPayload)
            ->assertRedirect(route('freight_bookings.index'));

        $booking->refresh();

        $this->assertSame(5600.0, (float) $booking->balance_amount);
        $this->assertCount(1, $booking->customAmounts);
        $this->assertDatabaseMissing('freight_booking_custom_amounts', [
            'freight_booking_id' => $booking->id,
            'description' => 'Unloading charge',
        ]);
    }

    public function test_update_custom_amounts_are_additive_with_detention(): void
    {
        $admin = $this->makeUser('admin');
        [$booking, $lr] = $this->createBookingForCustomAmountUpdate($admin);

        $payload = $this->customAmountUpdatePayload($admin, $lr, [
            ['description' => 'Loading charge', 'amount' => 500],
        ], [
            'detention' => 100,
        ]);

        $this->actingAs($admin)->put("/freight_bookings/{$booking->id}", $payload)
            ->assertRedirect(route('freight_bookings.index'));

        $booking->refresh();

        $this->assertSame(5700.0, (float) $booking->balance_amount);
        $this->assertSame(100.0, (float) $booking->detention);
    }

    public function test_voucher_payment_recalc_respects_custom_amounts(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $lr->update(['freight_booking_id' => null]);

        $booking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 8301,
            'transporter_id' => $lr->transporter_id,
            'transporter_name' => $lr->transporter?->name,
            'transporter_tds_percentage' => 20,
            'tds_amount' => 2000,
            'advance_amount' => 0,
            'balance_amount' => 8000,
        ]));
        $lr->update(['freight_booking_id' => $booking->id]);

        FreightBookingCustomAmount::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'description' => 'Loading charge',
            'amount' => 1000,
        ]);

        $booking->update(['balance_amount' => 9000]);

        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $response = $this->actingAs($admin)->post('/voucher_payments', [
            'freight_booking_id' => $booking->id,
            'voucher_number' => (string) $booking->voucher_number,
            'payment_type_id' => $paymentType->id,
            'payment_mode_id' => $paymentMode->id,
            'payment_type' => 'Cash',
            'payment_mode' => 'cash',
            'amount' => 9000,
            'payment_status' => 'in-progress',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('lorry_receipts.index'));

        $booking->refresh();

        $this->assertSame(9000.0, (float) $booking->advance_amount);
        $this->assertSame(0.0, (float) $booking->balance_amount);
    }

    public function test_edit_and_show_pages_display_negative_adjusted_amount(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $booking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 8401,
            'transporter_id' => $lr->transporter_id,
            'transporter_name' => $lr->transporter?->name,
            'negative_adjusted_amount' => 250.50,
            'balance_amount' => 0,
        ]));
        $lr->update(['freight_booking_id' => $booking->id]);

        $this->actingAs($admin)
            ->get("/freight_bookings/{$booking->id}/edit")
            ->assertOk()
            ->assertSee('Negative Adjusted Amount')
            ->assertSee('250.50');

        $this->actingAs($admin)
            ->get("/freight_bookings/{$booking->id}")
            ->assertOk()
            ->assertSee('Negative Adjusted Amount')
            ->assertSee('250.50');
    }

    public function test_show_page_displays_custom_amounts_table(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $booking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 8501,
        ]));
        $lr->update(['freight_booking_id' => $booking->id]);

        FreightBookingCustomAmount::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'description' => 'Gate pass fee',
            'amount' => 150,
        ]);

        $this->actingAs($admin)
            ->get("/freight_bookings/{$booking->id}")
            ->assertOk()
            ->assertSee('Custom Amounts')
            ->assertSee('Gate pass fee')
            ->assertSee('Custom amounts total');
    }

    private function lockCompanySubscription(User $user): void
    {
        $user->company->subscription->update([
            'status' => 'expired',
            'ends_on' => now()->subDays(30)->toDateString(),
        ]);
    }

    private function graceReadOnlyCompanySubscription(User $user): void
    {
        $user->company->subscription->update([
            'status' => 'expired',
            'ends_on' => now()->subDays(2)->toDateString(),
        ]);
    }

    private function registerLorryReceiptLookupRoute(): void
    {
        Route::middleware(['web', 'auth'])
            ->get('/_test/freight-bookings/lr/{id}', [FreightBookingController::class, 'getLorryReceipt']);
    }

    /**
     * @return array<string, mixed>
     */
    private function voucherPaymentPayload(
        FreightBooking $booking,
        PaymentType $paymentType,
        PaymentMode $paymentMode,
        float $amount,
        array $overrides = [],
    ): array {
        return array_merge([
            'payment_type_id' => $paymentType->id,
            'payment_mode_id' => $paymentMode->id,
            'payment_type' => $paymentType->name,
            'payment_mode' => $paymentMode->name,
            'booking_ids' => [$booking->id],
            'payment_amounts' => [$amount],
            'total_amount' => $amount,
            'date' => now()->toDateString(),
        ], $overrides);
    }

    public function test_create_page_loads_for_admin(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('freight_bookings.create'))
            ->assertOk();
    }

    public function test_create_page_prefills_lr_when_lorry_receipt_id_given(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $this->actingAs($admin)
            ->get(route('freight_bookings.create', ['lorry_receipt_id' => $lr->id]))
            ->assertOk()
            ->assertSee($lr->lr_number);
    }

    public function test_create_page_redirects_when_subscription_cannot_write(): void
    {
        $admin = $this->makeUser('admin');
        $this->graceReadOnlyCompanySubscription($admin);

        $this->actingAs($admin)
            ->get(route('freight_bookings.create'))
            ->assertRedirect(route('freight_bookings.index'))
            ->assertSessionHas('error');
    }

    public function test_create_page_redirects_when_lr_credits_exhausted(): void
    {
        $admin = $this->makeUser('admin');
        $admin->company->subscription->update(['lr_credits_remaining' => 0]);

        $this->actingAs($admin)
            ->get(route('freight_bookings.create'))
            ->assertRedirect(route('freight_bookings.index'))
            ->assertSessionHas('error');
    }

    public function test_confirm_renders_review_page_with_valid_payload(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $this->actingAs($admin)
            ->post(route('freight_bookings.confirm'), $this->bookingPayload($admin, $lr))
            ->assertOk()
            ->assertSee('Confirm Good Hire')
            ->assertSee($lr->lr_number);
    }

    public function test_store_without_confirmed_flag_redirects_to_create(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $payload = $this->bookingPayload($admin, $lr);
        unset($payload['confirmed']);

        $this->actingAs($admin)
            ->post(route('freight_bookings.store'), $payload)
            ->assertRedirect(route('freight_bookings.create'))
            ->assertSessionHas('error');
    }

    public function test_store_rejects_mismatched_company_id(): void
    {
        $admin = $this->makeUser('admin');
        $other = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $this->actingAs($admin)
            ->from(route('freight_bookings.create'))
            ->post(route('freight_bookings.store'), array_merge($this->bookingPayload($admin, $lr), [
                'company_id' => $other->company_id,
            ]))
            ->assertRedirect(route('freight_bookings.create'))
            ->assertSessionHasErrors('company_id');
    }

    public function test_store_save_and_view_redirects_to_show_page(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $response = $this->actingAs($admin)->post(route('freight_bookings.store'), array_merge(
            $this->bookingPayload($admin, $lr),
            ['action' => 'save_and_view']
        ));

        $booking = FreightBooking::query()->where('lr_id', $lr->id)->firstOrFail();
        $response->assertRedirect(route('freight_bookings.show', $booking->id));
    }

    public function test_store_marks_lr_in_progress_when_not_delivered_or_cancelled(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $this->actingAs($admin)->post(route('freight_bookings.store'), $this->bookingPayload($admin, $lr));

        $this->assertSame('In-Progress', $lr->fresh()->delivery_status);
    }

    public function test_destroy_deletes_freight_booking(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $booking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 8601,
        ]));

        $this->actingAs($admin)
            ->delete(route('freight_bookings.destroy', $booking))
            ->assertRedirect(route('freight_bookings.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('freight_bookings', ['id' => $booking->id]);
    }

    public function test_destroy_redirects_when_subscription_cannot_write(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $booking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 8602,
        ]));
        $this->lockCompanySubscription($admin);

        $this->actingAs($admin)
            ->delete(route('freight_bookings.destroy', $booking))
            ->assertRedirect(route('dashboard'));

        $this->assertNotSoftDeleted('freight_bookings', ['id' => $booking->id]);
    }

    public function test_get_transporter_api_returns_transporter_json(): void
    {
        $admin = $this->makeUser('admin');
        [, $truck] = $this->makeTransporterAndTruck($admin);
        $transporter = $truck->transporter;

        $this->actingAs($admin)
            ->getJson("/api/transporters/{$transporter->id}")
            ->assertOk()
            ->assertJsonPath('id', $transporter->id);
    }

    public function test_get_transporter_api_returns_404_for_unknown_id(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->getJson('/api/transporters/999999')
            ->assertNotFound()
            ->assertJsonPath('error', 'Transporter not found');
    }

    public function test_get_truck_api_returns_truck_with_transporter(): void
    {
        $admin = $this->makeUser('admin');
        [, $truck] = $this->makeTransporterAndTruck($admin);

        $this->actingAs($admin)
            ->getJson("/api/trucks/{$truck->id}")
            ->assertOk()
            ->assertJsonPath('id', $truck->id)
            ->assertJsonPath('truck_number', $truck->truck_number);
    }

    public function test_get_truck_api_returns_404_for_unknown_id(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->getJson('/api/trucks/999999')
            ->assertNotFound()
            ->assertJsonPath('error', 'Truck not found');
    }

    public function test_get_lorry_receipt_returns_json_for_valid_lr(): void
    {
        $this->registerLorryReceiptLookupRoute();
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $this->actingAs($admin)
            ->getJson("/_test/freight-bookings/lr/{$lr->id}")
            ->assertOk()
            ->assertJsonPath('id', $lr->id)
            ->assertJsonPath('lr_number', $lr->lr_number);
    }

    public function test_get_lorry_receipt_returns_404_when_missing(): void
    {
        $this->registerLorryReceiptLookupRoute();
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->getJson('/_test/freight-bookings/lr/999999')
            ->assertNotFound()
            ->assertJsonPath('error', 'LR not found');
    }

    public function test_datatable_pending_memo_filter_returns_lr_without_memo(): void
    {
        $admin = $this->makeUser('admin');
        $lrWithMemo = $this->makeLorryReceipt($admin);
        $lrWithMemo->update(['memo_id' => 'MEMO-1']);
        FreightBooking::create(array_merge($this->bookingPayload($admin, $lrWithMemo), [
            'voucher_number' => 8701,
            'is_cancelled' => 0,
        ]));

        $lrPending = $this->makeLorryReceipt($admin);
        FreightBooking::create(array_merge($this->bookingPayload($admin, $lrPending), [
            'voucher_number' => 8702,
            'is_cancelled' => 0,
        ]));

        $payload = $this->freightBookingsDataTableRequestPayload([
            'pending_memo' => '1',
            'filter_search' => '8702',
        ]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data?'.http_build_query($payload));

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayNotHasKey('error', $json);
        $this->assertSame(1, (int) ($json['recordsFiltered'] ?? 0));
        $this->assertStringContainsString($lrPending->lr_number, json_encode($json['data'] ?? []));
    }

    public function test_datatable_marks_cancelled_and_delivered_row_classes(): void
    {
        $admin = $this->makeUser('admin');
        $lrCancelled = $this->makeLorryReceipt($admin);
        FreightBooking::create(array_merge($this->bookingPayload($admin, $lrCancelled), [
            'voucher_number' => 8703,
            'is_cancelled' => 1,
        ]));

        $lrDelivered = $this->makeLorryReceipt($admin);
        FreightBooking::create(array_merge($this->bookingPayload($admin, $lrDelivered), [
            'voucher_number' => 8704,
            'is_delivered' => 1,
        ]));

        $payload = $this->freightBookingsDataTableRequestPayload([
            'filter_search' => '8703',
        ]);

        $cancelledResponse = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data?'.http_build_query($payload));
        $cancelledResponse->assertOk();
        $cancelledJson = $cancelledResponse->json();
        $this->assertStringContainsString('table-danger', json_encode($cancelledJson['data'] ?? []));

        $deliveredPayload = $this->freightBookingsDataTableRequestPayload([
            'filter_search' => '8704',
        ]);
        $deliveredResponse = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data?'.http_build_query($deliveredPayload));
        $deliveredResponse->assertOk();
        $deliveredJson = $deliveredResponse->json();
        $this->assertStringContainsString('table-success', json_encode($deliveredJson['data'] ?? []));
    }

    public function test_bulk_memo_id_assigns_memo_and_marks_delivered(): void
    {
        $admin = $this->makeUser('admin');
        $lr1 = $this->makeLorryReceipt($admin);
        [, $truck2] = $this->makeTransporterAndTruck($admin);
        $lr2 = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $lr1->customer_id,
            'consignor_id' => $lr1->consignor_id,
            'consignee_id' => $lr1->consignee_id,
            'lr_number' => 'LR-FB-002',
            'gr_number' => 'GR-FB-002',
            'invoice_number' => 'INV-FB-002',
            'memo_id' => null,
            'truck_id' => $truck2->id,
            'transporter_id' => $truck2->transporter_id,
            'reference_by' => $truck2->transporter_id,
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
        $fb1 = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr1), [
            'voucher_number' => 8801,
        ]));
        $fb2 = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr2), [
            'voucher_number' => 8802,
        ]));

        $this->actingAs($admin)
            ->from(route('freight_bookings.index'))
            ->post(route('freight_bookings.bulk_memo_id'), [
                'memo_id' => 'MEMO-BULK-1',
                'lr_ids' => [$lr1->id, $lr2->id],
                'freight_booking_ids' => [$fb1->id, $fb2->id],
            ])
            ->assertRedirect(route('freight_bookings.index'))
            ->assertSessionHas('success');

        $this->assertSame('MEMO-BULK-1', $lr1->fresh()->memo_id);
        $this->assertSame('Delivered', $lr1->fresh()->delivery_status);
        $this->assertTrue((bool) $fb1->fresh()->is_delivered);
    }

    public function test_bulk_memo_id_warns_when_lrs_belong_to_different_customers(): void
    {
        $admin = $this->makeUser('admin');
        $lr1 = $this->makeLorryReceipt($admin);
        $lr2 = $this->makeLorryReceipt($admin);
        $otherCustomer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Other Customer',
            'address' => 'Addr',
        ]);
        $lr2->update(['customer_id' => $otherCustomer->id]);

        $fb1 = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr1), [
            'voucher_number' => 8803,
        ]));
        $fb2 = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr2), [
            'voucher_number' => 8804,
        ]));

        $this->actingAs($admin)
            ->from(route('freight_bookings.index'))
            ->post(route('freight_bookings.bulk_memo_id'), [
                'memo_id' => 'MEMO-BULK-2',
                'lr_ids' => [$lr1->id, $lr2->id],
                'freight_booking_ids' => [$fb1->id, $fb2->id],
            ])
            ->assertRedirect(route('freight_bookings.index'))
            ->assertSessionHas('warning');
    }

    public function test_bulk_memo_id_warns_when_memo_belongs_to_another_customer(): void
    {
        $admin = $this->makeUser('admin');
        $lrExisting = $this->makeLorryReceipt($admin);
        $otherCustomer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Memo Conflict Customer',
            'address' => 'Addr',
        ]);
        $lrExisting->update([
            'memo_id' => 'MEMO-CONFLICT',
            'customer_id' => $otherCustomer->id,
        ]);

        $lrTarget = $this->makeLorryReceipt($admin);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lrTarget), [
            'voucher_number' => 8805,
        ]));

        $this->actingAs($admin)
            ->from(route('freight_bookings.index'))
            ->post(route('freight_bookings.bulk_memo_id'), [
                'memo_id' => 'MEMO-CONFLICT',
                'lr_ids' => [$lrTarget->id],
                'freight_booking_ids' => [$fb->id],
            ])
            ->assertRedirect(route('freight_bookings.index'))
            ->assertSessionHas('warning');
    }

    public function test_voucher_payment_cash_updates_booking_balance(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'balance_amount' => 10000,
            'advance_amount' => 0,
            'tds_amount' => 0,
            'voucher_number' => 8901,
        ]));
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.voucher_payment'), $this->voucherPaymentPayload($fb, $paymentType, $paymentMode, 2000))
            ->assertOk()
            ->assertJsonPath('success', true);

        $fb->refresh();
        $this->assertSame(8000.0, (float) $fb->balance_amount);
        $this->assertSame(2000.0, (float) $fb->advance_amount);
    }

    public function test_voucher_payment_rejects_amount_exceeding_balance(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'balance_amount' => 500,
            'voucher_number' => 8902,
        ]));
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.voucher_payment'), $this->voucherPaymentPayload($fb, $paymentType, $paymentMode, 1000))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_voucher_payment_warns_on_duplicate_same_day_payment(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'balance_amount' => 5000,
            'voucher_number' => 8903,
        ]));
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);
        $payload = $this->voucherPaymentPayload($fb, $paymentType, $paymentMode, 1000);

        $this->actingAs($admin)->postJson(route('freight_bookings.voucher_payment'), $payload)->assertOk();

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.voucher_payment'), $payload)
            ->assertStatus(422)
            ->assertJsonPath('warning', true);
    }

    public function test_voucher_payment_allows_duplicate_when_confirmed(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'balance_amount' => 5000,
            'voucher_number' => 8904,
        ]));
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);
        $payload = $this->voucherPaymentPayload($fb, $paymentType, $paymentMode, 1000);

        $this->actingAs($admin)->postJson(route('freight_bookings.voucher_payment'), $payload)->assertOk();

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.voucher_payment'), array_merge($payload, [
                'confirm_duplicate' => '1',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(2, VoucherPayment::where('freight_booking_id', $fb->id)->count());
    }

    public function test_voucher_payment_rejects_adjust_in_advance_when_transporter_advance_insufficient(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $transporter = $lr->transporter;
        $transporter->update(['advance_amount' => 100]);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'transporter_id' => $transporter->id,
            'balance_amount' => 5000,
            'voucher_number' => 8905,
        ]));
        $paymentType = PaymentType::firstOrCreate(['name' => 'final']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'adjust in advance']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.voucher_payment'), $this->voucherPaymentPayload($fb, $paymentType, $paymentMode, 500))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_store_combined_payment_processes_linked_group(): void
    {
        $admin = $this->makeUser('admin');
        [$fb1, $fb2] = $this->createLinkedFreightBookings($admin, 5000.0, 3000.0);
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.store_combined_payment'), [
                'combined_amount' => 8000,
                'payment_type' => $paymentType->id,
                'payment_mode' => $paymentMode->id,
                'booking_ids' => [$fb1->id, $fb2->id],
                'payment_amounts' => [5000, 3000],
                'payment_date' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('voucher_linkeds', [
            'freight_booking_id' => $fb1->id,
            'amount' => 8000,
            'company_id' => $admin->company_id,
        ]);
        $this->assertSame(2, VoucherPayment::query()->whereIn('freight_booking_id', [$fb1->id, $fb2->id])->count());
        $fb1->refresh();
        $this->assertLessThan(5000.0, (float) $fb1->balance_amount);
    }

    public function test_store_combined_payment_rejects_partial_linked_group(): void
    {
        $admin = $this->makeUser('admin');
        [$fb1] = $this->createLinkedFreightBookings($admin, 5000.0, 3000.0);
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.store_combined_payment'), [
                'combined_amount' => 5000,
                'payment_type' => $paymentType->id,
                'payment_mode' => $paymentMode->id,
                'booking_ids' => [$fb1->id],
                'payment_amounts' => [5000],
                'payment_date' => now()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_store_combined_payment_rejects_non_positive_allocation_sum(): void
    {
        $admin = $this->makeUser('admin');
        [$fb1, $fb2] = $this->createLinkedFreightBookings($admin, -500.0, -300.0);
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.store_combined_payment'), [
                'combined_amount' => 800,
                'payment_type' => $paymentType->id,
                'payment_mode' => $paymentMode->id,
                'booking_ids' => [$fb1->id, $fb2->id],
                'payment_amounts' => [-500, -300],
                'payment_date' => now()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_update_rejects_cancelled_freight_booking(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $booking = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 9002,
            'is_cancelled' => 1,
        ]));
        $lr->update(['freight_booking_id' => $booking->id]);

        $this->actingAs($admin)
            ->from(route('freight_bookings.edit', $booking))
            ->put(route('freight_bookings.update', $booking), $this->bookingPayload($admin, $lr))
            ->assertRedirect(route('freight_bookings.edit', $booking))
            ->assertSessionHasErrors('general');
    }

    public function test_show_returns_403_for_other_company_booking(): void
    {
        $admin = $this->makeUser('admin');
        $other = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($other);
        $booking = FreightBooking::create(array_merge($this->bookingPayload($other, $lr), [
            'voucher_number' => 9003,
        ]));

        $this->actingAs($admin)
            ->get("/freight_bookings/{$booking->id}")
            ->assertRedirect(route('freight_bookings.index'))
            ->assertSessionHas('error');
    }

    public function test_edit_redirects_when_freight_booking_not_found(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get('/freight_bookings/999999/edit')
            ->assertRedirect(route('freight_bookings.index'))
            ->assertSessionHas('error');
    }

    public function test_index_renders_filters_and_caps_date_range(): void
    {
        $admin = $this->makeUser('admin');
        [$customer, , ] = $this->seedConsignees($admin);
        [$transporter, $truck] = $this->makeTransporterAndTruck($admin);

        $start = now()->toDateString();
        $end = now()->addMonths(6)->toDateString();

        $this->actingAs($admin)->get('/freight_bookings?'.http_build_query([
            'start_date' => $start,
            'end_date' => $end,
            'customer_id' => $customer->id,
            'transporter_id' => $transporter->id,
            'truck_id' => $truck->id,
        ]))
            ->assertOk()
            ->assertViewHas('selectedCustomer', fn ($c) => (int) $c->id === (int) $customer->id)
            ->assertViewHas('selectedTransporter', fn ($t) => (int) $t->id === (int) $transporter->id)
            ->assertViewHas('selectedTruck', fn ($t) => (int) $t->id === (int) $truck->id)
            ->assertViewHas('endDate', fn ($capped) => $capped <= now()->addMonths(3)->toDateString());
    }

    public function test_store_save_and_pay_redirects_to_voucher_payment_create(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $response = $this->actingAs($admin)->post(route('freight_bookings.store'), array_merge(
            $this->bookingPayload($admin, $lr),
            ['action' => 'save_and_pay']
        ));

        $booking = FreightBooking::query()->where('lr_id', $lr->id)->firstOrFail();
        $response->assertRedirect(route('voucher_payments.create', ['freight_booking_id' => $booking->id]));
    }

    public function test_super_admin_index_uses_session_company_context(): void
    {
        $companyOwner = $this->makeUser('admin');
        $super = User::factory()->create([
            'user_role_id' => UserRole::where('name', 'super_admin')->value('id'),
            'company_id' => null,
        ]);
        $companyOwner->company->update(['user_id' => $super->id]);

        [$customer, , ] = $this->seedConsignees($companyOwner);

        $this->actingAs($super)
            ->withSession(['super_admin_company_id' => $companyOwner->company_id])
            ->get('/freight_bookings?'.http_build_query(['customer_id' => $customer->id]))
            ->assertOk()
            ->assertViewHas('selectedCustomer', fn ($c) => (int) $c->id === (int) $customer->id);
    }

    public function test_datatable_caps_end_date_and_applies_entity_filters(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 77001,
            'transporter_id' => $lr->transporter_id,
            'is_cancelled' => 0,
        ]));

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data?'.http_build_query($this->freightBookingsDataTableRequestPayload([
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addYears(2)->toDateString(),
                'filter_search' => '77001',
            ])))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data?'.http_build_query($this->freightBookingsDataTableRequestPayload([
                'customer_id' => $lr->customer_id,
                'transporter_id' => $lr->transporter_id,
                'truck_id' => $lr->truck_id,
                'truck_owner_id' => $lr->transporter_id,
                'filter_search' => '77001',
            ])))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);
    }

    public function test_store_failure_surfaces_general_error_when_persist_throws(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        FreightBooking::creating(static function (): void {
            throw new \RuntimeException('Simulated persistence failure');
        });

        $response = $this->actingAs($admin)->from(route('freight_bookings.create'))->post(
            route('freight_bookings.store'),
            $this->bookingPayload($admin, $lr)
        );

        $response->assertRedirect(route('freight_bookings.create'));
        $response->assertSessionHasErrors('general');
        $this->assertDatabaseMissing('freight_bookings', ['lr_id' => $lr->id]);
    }

    public function test_confirm_rejects_mismatched_company_id(): void
    {
        $admin = $this->makeUser('admin');
        $other = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);

        $this->actingAs($admin)
            ->from(route('freight_bookings.create'))
            ->post(route('freight_bookings.confirm'), array_merge($this->bookingPayload($admin, $lr), [
                'company_id' => $other->company_id,
            ]))
            ->assertRedirect(route('freight_bookings.create'))
            ->assertSessionHasErrors('company_id');
    }

    public function test_datatable_checkbox_disabled_when_memo_and_zero_balance(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $lr->update(['memo_id' => 'MEMO-ZB']);
        FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 88001,
            'balance_amount' => 0,
            'advance_amount' => 8000,
        ]));

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data?'.http_build_query($this->freightBookingsDataTableRequestPayload([
                'filter_search' => '88001',
            ])));

        $response->assertOk();
        $row = $response->json('data.0');
        $this->assertNotNull($row);
        $this->assertStringContainsString('bi-dash-circle', $row['checkbox'] ?? '');
        $this->assertStringNotContainsString('row-checkbox', $row['checkbox'] ?? '');
    }

    public function test_datatable_renders_linked_lr_badge_and_owner_transporter_badge(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $owner = Transporter::factory()->forCompany($admin->company_id)->truckOwner()->create([
            'mobile_number' => '9888888888',
            'short_name' => 'OWN-1',
        ]);
        $lr->update([
            'transporter_id' => $owner->id,
            'reference_by' => $owner->id,
        ]);
        $lr->truck?->update(['transporter_id' => $owner->id]);

        FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 88002,
            'linked_lr_id' => 4242,
            'transporter_id' => $owner->id,
            'transporter_name' => $owner->name,
        ]));

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/freight_bookings/data?'.http_build_query($this->freightBookingsDataTableRequestPayload([
                'filter_search' => '88002',
            ])));

        $response->assertOk();
        $row = $response->json('data.0');
        $this->assertStringContainsString('[4242]', $row['lr_number'] ?? '');
        $this->assertStringContainsString('bi-truck', $row['transporter'] ?? '');
    }

    public function test_voucher_payment_combined_flow_rejects_mismatched_linked_groups(): void
    {
        $admin = $this->makeUser('admin');
        [$fb1] = $this->createLinkedFreightBookings($admin, 5000.0, 3000.0);
        $lrOther = $this->makeLorryReceipt($admin);
        $fb2 = FreightBooking::create(array_merge($this->bookingPayload($admin, $lrOther), [
            'voucher_number' => 88099,
            'linked_lr_id' => 88888,
            'balance_amount' => 2000,
            'advance_amount' => 8000,
        ]));
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.voucher_payment'), array_merge(
                $this->voucherPaymentPayload($fb1, $paymentType, $paymentMode, 1000, [
                    'booking_ids' => [$fb1->id, $fb2->id],
                    'payment_amounts' => [1000, 0],
                    'total_amount' => 1000,
                    'combined_payment_flow' => true,
                ])
            ))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_voucher_payment_rejects_negative_amount_on_positive_balance(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'balance_amount' => 5000,
            'voucher_number' => 88003,
        ]));
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.voucher_payment'), $this->voucherPaymentPayload($fb, $paymentType, $paymentMode, -100))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_voucher_payment_rejects_non_zero_payment_when_balance_is_zero(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'balance_amount' => 0,
            'advance_amount' => 10000,
            'voucher_number' => 88004,
        ]));
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.voucher_payment'), $this->voucherPaymentPayload($fb, $paymentType, $paymentMode, 100))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_voucher_payment_fuel_mode_requires_pump_id(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        $fb = FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'balance_amount' => 5000,
            'voucher_number' => 88005,
        ]));
        $paymentType = PaymentType::firstOrCreate(['name' => 'final']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'fuel(paid to pump)']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.voucher_payment'), $this->voucherPaymentPayload($fb, $paymentType, $paymentMode, 500))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.pump_id.0', 'Please select Pump for fuel payment.');
    }

    public function test_store_combined_payment_bank_transfer_sets_pending_on_voucher_linked(): void
    {
        $admin = $this->makeUser('admin');
        [$fb1, $fb2] = $this->createLinkedFreightBookings($admin, 5000.0, 3000.0);
        $paymentType = PaymentType::firstOrCreate(['name' => 'final']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'bank transfer']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.store_combined_payment'), [
                'combined_amount' => 8000,
                'payment_type' => $paymentType->id,
                'payment_mode' => $paymentMode->id,
                'booking_ids' => [$fb1->id, $fb2->id],
                'payment_amounts' => [5000, 3000],
                'payment_date' => now()->toDateString(),
                'reference_number' => 'UTR-123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('voucher_linkeds', [
            'company_id' => $admin->company_id,
            'payment_mode' => 'bank transfer',
            'payment_status' => 'pending',
            'reference_transaction_id' => 'UTR-123',
        ]);
    }

    public function test_store_combined_payment_accepts_signed_splits_for_linked_offsets(): void
    {
        $admin = $this->makeUser('admin');
        [$positiveBooking, $negativeBooking] = $this->createLinkedFreightBookings($admin, 1586.55, -586.05);
        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $this->actingAs($admin)
            ->postJson(route('freight_bookings.store_combined_payment'), [
                'combined_amount' => 1000.5,
                'payment_type' => $paymentType->id,
                'payment_mode' => $paymentMode->id,
                'booking_ids' => [$positiveBooking->id, $negativeBooking->id],
                'payment_amounts' => [1586.55, -586.05],
                'payment_date' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(2, VoucherPayment::query()->whereIn('freight_booking_id', [$positiveBooking->id, $negativeBooking->id])->count());
    }
}
