<?php

namespace Tests\Feature\Freight;

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
            'filter_search' => '',
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
            ->call('GET', '/freight_bookings/data', $payload);

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayNotHasKey('error', $json);
        $this->assertSame(1, (int) ($json['recordsFiltered'] ?? 0));
    }

    public function test_datatable_list_search_with_no_matches_returns_empty_results(): void
    {
        $admin = $this->makeUser('admin');
        $lr = $this->makeLorryReceipt($admin);
        FreightBooking::create(array_merge($this->bookingPayload($admin, $lr), [
            'voucher_number' => 'V-NO-MATCH',
        ]));

        $payload = $this->freightBookingsDataTableRequestPayload([
            'filter_search' => 'ZZZ-NO-MATCH-TERM',
        ]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->call('GET', '/freight_bookings/data', $payload);

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayNotHasKey('error', $json);
        $this->assertSame(0, (int) ($json['recordsFiltered'] ?? -1));
        $this->assertSame(0, count($json['data'] ?? []));
    }

    public function test_datatable_transporter_filter_matches_id_not_digit_in_name(): void
    {
        $admin = $this->makeUser('admin');
        [$customer, $consignor, $consignee] = $this->seedConsignees($admin);

        $selectedTransporter = Transporter::factory()
            ->forCompany($admin->company_id)
            ->create([
                'mobile_number' => '9000000001',
                'declaration_given' => false,
                'tds_percentage' => 1,
                'total_trucks' => 0,
                'advance_amount' => 0,
            ]);
        $selectedTruck = Truck::create([
            'transporter_id' => $selectedTransporter->id,
            'truck_number' => 'RJ16GB0056',
            'truck_type' => 'Trailer',
        ]);

        $otherTransporter = Transporter::factory()
            ->forCompany($admin->company_id)
            ->create([
                'mobile_number' => '9000000002',
                'declaration_given' => false,
                'tds_percentage' => 1,
                'total_trucks' => 0,
                'advance_amount' => 0,
            ]);
        $otherTruck = Truck::create([
            'transporter_id' => $otherTransporter->id,
            'truck_number' => 'RJ27GD4891',
            'truck_type' => 'Trailer',
        ]);

        $selectedLr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-FILTER-SELECTED',
            'gr_number' => 'GR-FILTER-SELECTED',
            'invoice_number' => 'INV-FILTER-SELECTED',
            'truck_id' => $selectedTruck->id,
            'transporter_id' => $selectedTransporter->id,
            'reference_by' => $selectedTransporter->id,
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

        $otherLr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-FILTER-OTHER',
            'gr_number' => 'GR-FILTER-OTHER',
            'invoice_number' => 'INV-FILTER-OTHER',
            'truck_id' => $otherTruck->id,
            'transporter_id' => $otherTransporter->id,
            'reference_by' => $otherTransporter->id,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
            'source_city' => 'Jaipur',
            'source_state' => 'Rajasthan',
            'destination_city' => 'Udaipur',
            'destination_state' => 'Rajasthan',
            'unloading_station' => 'Station B',
            'lane' => 'Lane 2',
            'good_type' => 'Cement',
            'weight' => 12,
            'no_of_units' => 1,
            'fr_rate' => 1100,
            'amount' => 13200,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'owner',
        ]);

        $selectedBooking = FreightBooking::create(array_merge($this->bookingPayload($admin, $selectedLr), [
            'voucher_number' => 'V-FILTER-SELECTED',
            'transporter_id' => $selectedTransporter->id,
            'transporter_name' => 'MAHADEV TRANSPORT COMPANY KAILASH',
        ]));

        // Name intentionally contains the selected transporter id digits (e.g. "...RJ27...")
        // so a buggy LIKE '%{id}%' on transporter_name would falsely include this row.
        FreightBooking::create(array_merge($this->bookingPayload($admin, $otherLr), [
            'voucher_number' => 'V-FILTER-OTHER',
            'transporter_id' => $otherTransporter->id,
            'transporter_name' => 'VIKRAM NATH CHOUHAN RJ'.$selectedTransporter->id.'GD4891',
        ]));

        $payload = $this->freightBookingsDataTableRequestPayload([
            'transporter_id' => $selectedTransporter->id,
        ]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->call('GET', '/freight_bookings/data', $payload);

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayNotHasKey('error', $json, json_encode($json));
        $rowIds = collect($json['data'] ?? [])->map(fn ($row) => (string) ($row['DT_RowId'] ?? ''))->all();
        $this->assertSame(
            [(string) $selectedBooking->id],
            $rowIds,
            'Expected only selected transporter booking. Got: '.json_encode([
                'selected_transporter_id' => $selectedTransporter->id,
                'other_transporter_id' => $otherTransporter->id,
                'recordsFiltered' => $json['recordsFiltered'] ?? null,
                'rows' => $json['data'] ?? null,
            ])
        );
    }

    public function test_datatable_customer_and_truck_filters_are_exact(): void
    {
        $admin = $this->makeUser('admin');
        [$customerA, $consignor, $consignee] = $this->seedConsignees($admin);
        $customerB = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Other Customer',
            'address' => 'Addr',
        ]);
        [$transporter, $truckA] = $this->makeTransporterAndTruck($admin);
        $truckB = Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ09ZZ9999',
            'truck_type' => 'Flatbed',
        ]);

        $lrA = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customerA->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-CUST-A',
            'gr_number' => 'GR-CUST-A',
            'invoice_number' => 'INV-CUST-A',
            'truck_id' => $truckA->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
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
        $lrB = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customerB->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-CUST-B',
            'gr_number' => 'GR-CUST-B',
            'invoice_number' => 'INV-CUST-B',
            'truck_id' => $truckB->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
            'source_city' => 'Surat',
            'source_state' => 'Gujarat',
            'destination_city' => 'Pune',
            'destination_state' => 'Maharashtra',
            'unloading_station' => 'Station B',
            'lane' => 'Lane 2',
            'good_type' => 'Cement',
            'weight' => 8,
            'no_of_units' => 1,
            'fr_rate' => 900,
            'amount' => 7200,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ]);

        $bookingA = FreightBooking::create(array_merge($this->bookingPayload($admin, $lrA), [
            'voucher_number' => 'V-CUST-A',
            'transporter_id' => $transporter->id,
            'transporter_name' => 'Agency A',
        ]));
        FreightBooking::create(array_merge($this->bookingPayload($admin, $lrB), [
            'voucher_number' => 'V-CUST-B',
            'transporter_id' => $transporter->id,
            'transporter_name' => 'Agency A',
        ]));

        $customerPayload = $this->freightBookingsDataTableRequestPayload([
            'customer_id' => $customerA->id,
        ]);
        $customerResponse = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->call('GET', '/freight_bookings/data', $customerPayload);
        $customerResponse->assertOk();
        $customerJson = $customerResponse->json();
        $this->assertSame(1, (int) ($customerJson['recordsFiltered'] ?? -1));
        $this->assertSame((string) $bookingA->id, (string) ($customerJson['data'][0]['DT_RowId'] ?? ''));

        $truckPayload = $this->freightBookingsDataTableRequestPayload([
            'truck_id' => $truckA->id,
        ]);
        $truckResponse = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->call('GET', '/freight_bookings/data', $truckPayload);
        $truckResponse->assertOk();
        $truckJson = $truckResponse->json();
        $this->assertSame(1, (int) ($truckJson['recordsFiltered'] ?? -1));
        $this->assertSame((string) $bookingA->id, (string) ($truckJson['data'][0]['DT_RowId'] ?? ''));
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
            ->assertSee('Custom amounts total')
            ->assertSee('Custom Amounts Total:');
    }
}
