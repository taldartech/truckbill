<?php

namespace Tests\Feature\Reports;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\City;
use App\Models\Country;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use App\Models\Pump;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportViewsTest extends TestCase
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

    protected function makeUser(string $role = 'admin'): User
    {
        $company = Company::create([
            'name' => 'Company '.$role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
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

    /**
     * @return array{0: LorryReceipt, 1: FreightBooking}
     */
    protected function seedLr(User $user): array
    {
        $customer = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Customer',
        ]);

        $transporter = \App\Models\Transporter::factory()
            ->forCompany($user->company_id)
            ->create([
                'short_name' => 'FL',
                'mobile_number' => '9999999999',
                'declaration_given' => false,
                'tds_percentage' => 20,
                'total_trucks' => 0,
                'advance_amount' => 0,
            ]);

        $truck = \App\Models\Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB9999',
            'truck_type' => 'Flatbed',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $user->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-RPT-1',
            'memo_id' => 5001,
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1.234,
            'fr_rate' => 100,
            'amount' => 100,
            'gr_date' => now()->toDateString(),
        ]);

        $booking = FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 100,
            'advance_amount' => 0,
            'balance_amount' => 100,
            'tds_amount' => 0,
            'company_id' => $user->company_id,
            'voucher_number' => 9001,
        ]);

        $lr->freight_booking_id = $booking->id;
        $lr->save();

        return [$lr, $booking];
    }

    protected function dateParams(int $daysBack = 7): array
    {
        return [
            'start_date' => now()->subDays($daysBack)->toDateString(),
            'end_date' => now()->toDateString(),
        ];
    }

    protected function seedMemoCopyViewData(User $admin, LorryReceipt $lr): void
    {
        $country = Country::firstOrCreate(['name' => 'India', 'code' => 'IN']);
        $state = State::firstOrCreate(['name' => 'Gujarat'], ['country_id' => $country->id]);
        $city = City::firstOrCreate(
            ['name' => 'Ahmedabad', 'state_id' => $state->id],
            ['country_id' => $country->id]
        );

        $customer = Consignee::query()->findOrFail($lr->customer_id);
        $customer->update([
            'city_id' => $city->id,
            'state_id' => $state->id,
            'address' => 'Customer Address',
        ]);

        $admin->company->update([
            'city_id' => $city->id,
            'state_id' => $state->id,
            'address' => 'Company Address',
            'contact_number' => '9000000001',
        ]);
    }

    public function test_profitability_report_renders_with_filters(): void
    {
        $admin = $this->makeUser();
        [$lrProfit, $bookingProfit] = $this->seedLr($admin);
        $lrProfit->update(['amount' => 10000, 'delivery_status' => 'Delivered']);
        $bookingProfit->update(['tr_amount' => 6000]);

        $lrLoss = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $lrProfit->customer_id,
            'consignor_id' => $lrProfit->customer_id,
            'consignee_id' => $lrProfit->customer_id,
            'lr_number' => 'LR-LOSS-VIEW',
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'truck_id' => $lrProfit->truck_id,
            'transporter_id' => $lrProfit->transporter_id,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1.0,
            'fr_rate' => 100,
            'amount' => 5000,
            'gr_date' => now()->toDateString(),
        ]);

        $bookingLoss = FreightBooking::create([
            'lr_id' => $lrLoss->id,
            'transporter_id' => $lrProfit->transporter_id,
            'tr_fr_rate' => 100,
            'tr_amount' => 8000,
            'advance_amount' => 0,
            'balance_amount' => 8000,
            'tds_amount' => 0,
            'company_id' => $admin->company_id,
            'voucher_number' => 9003,
        ]);
        $lrLoss->freight_booking_id = $bookingLoss->id;
        $lrLoss->save();

        $base = $this->dateParams();

        $this->actingAs($admin)
            ->get(route('reports.profitability', $base))
            ->assertOk()
            ->assertViewIs('reports.profitability')
            ->assertViewHas('totalRevenue');

        $this->actingAs($admin)
            ->get(route('reports.profitability', $base + [
                'customer_id' => $lrProfit->customer_id,
                'transporter_id' => $lrProfit->transporter_id,
                'truck_id' => $lrProfit->truck_id,
                'trip_id' => $lrProfit->id,
                'status' => 'Delivered',
            ]))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('reports.profitability', $base + ['profit_loss' => 'profit']))
            ->assertOk()
            ->assertViewHas('totalProfit');

        $this->actingAs($admin)
            ->get(route('reports.profitability', $base + ['profit_loss' => 'loss']))
            ->assertOk();
    }

    public function test_cash_report_renders_with_payments_and_branch_filter(): void
    {
        $admin = $this->makeUser();
        [$lr, $booking] = $this->seedLr($admin);
        $branch = Branch::create([
            'company_id' => $admin->company_id,
            'branch_name' => 'Main',
        ]);
        $admin->update(['branch_id' => $branch->id]);

        $this->actingAs($admin);

        VoucherPayment::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_number' => 401,
            'payment_type' => 'advance',
            'date' => now()->toDateString(),
            'amount' => 1500,
            'payment_mode' => 'cash',
            'payment_status' => 'completed',
        ]);

        VoucherLinked::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_ids' => '401',
            'payment_type' => 'advance',
            'date' => now()->toDateString(),
            'amount' => 800,
            'payment_mode' => 'cash',
            'payment_status' => 'in-progress',
        ]);

        $params = $this->dateParams(1) + [
            'branch' => $branch->id,
        ];

        $response = $this->actingAs($admin)->get(route('reports.cash', $params));

        $response->assertOk()
            ->assertViewIs('reports.cash')
            ->assertViewHas('grandTotalAmount', fn ($total) => (float) $total > 0);

        $this->actingAs($admin)
            ->get(route('reports.cash', $this->dateParams(1) + ['payment_status' => 'in-progress']))
            ->assertOk()
            ->assertViewIs('reports.cash');
    }

    public function test_bank_report_renders_with_payment_status_filter(): void
    {
        $admin = $this->makeUser();
        [$lr, $booking] = $this->seedLr($admin);

        VoucherPayment::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-BANK-VIEW',
            'payment_type' => 'bank',
            'payment_mode' => 'bank transfer',
            'amount' => 700,
            'payment_status' => 'pending',
            'date' => now()->toDateString(),
        ]);

        VoucherLinked::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_ids' => 'V-BANK-VIEW',
            'payment_type' => 'bank',
            'payment_mode' => 'bank transfer',
            'amount' => 300,
            'payment_status' => 'pending',
            'date' => now()->toDateString(),
        ]);

        $params = $this->dateParams(1) + ['payment_status' => 'pending'];

        $this->actingAs($admin)
            ->get(route('reports.bank', $params))
            ->assertOk()
            ->assertViewIs('reports.bank')
            ->assertSee('Pending')
            ->assertViewHas('grandTotalAmount', fn ($total) => (float) $total === 1000.0);
    }

    public function test_shortage_damage_report_renders_with_booking_amounts(): void
    {
        $admin = $this->makeUser();
        [$lr, $booking] = $this->seedLr($admin);
        $booking->update([
            'shortage_amount' => 200,
            'damage_amount' => 100,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.shortage-damage', $this->dateParams()))
            ->assertOk()
            ->assertViewIs('reports.shortage-damage')
            ->assertViewHas('generated', true);
    }

    public function test_eway_bill_report_renders_with_freight_bookings(): void
    {
        $admin = $this->makeUser();
        [$lr, $booking] = $this->seedLr($admin);
        $booking->update(['is_delivered' => 0, 'is_cancelled' => 0]);
        \App\Models\EwayBill::create([
            'freight_booking_id' => $booking->id,
            'eway_bill_number' => 'EWB-RPT-1',
            'eway_bill_expiry' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('reports.eway-bill', [
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
            ]))
            ->assertOk()
            ->assertViewIs('reports.eway-bill')
            ->assertSee('EWB-RPT-1');
    }

    public function test_pending_memo_report_renders_status_branches(): void
    {
        $admin = $this->makeUser();
        [$lr] = $this->seedLr($admin);
        $lr->update(['memo_id' => null, 'delivery_status' => 'In-Progress']);

        $base = $this->dateParams() + ['customer_id' => $lr->customer_id];

        $this->actingAs($admin)
            ->get(route('reports.pending-memo', $base + ['status' => 'in-progress']))
            ->assertOk()
            ->assertViewIs('reports.pending-memo')
            ->assertSee('LR-RPT-1');

        $this->actingAs($admin)
            ->get(route('reports.pending-memo', $base + ['status' => 'both']))
            ->assertOk();
    }

    public function test_pending_invoice_report_renders_delivered_memo_without_invoice(): void
    {
        $admin = $this->makeUser();
        [$lr] = $this->seedLr($admin);
        $lr->update([
            'memo_id' => 7001,
            'invoice_generated' => 0,
            'delivery_status' => 'Delivered',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.pending-invoice', $this->dateParams() + ['customer_id' => $lr->customer_id]))
            ->assertOk()
            ->assertViewIs('reports.pending-invoice')
            ->assertSee('LR-RPT-1');
    }

    public function test_memo_copy_report_renders_for_customer_and_memo(): void
    {
        $admin = $this->makeUser();
        [$lr] = $this->seedLr($admin);
        $lr->update(['memo_id' => 8888]);
        $this->seedMemoCopyViewData($admin, $lr);

        $this->actingAs($admin)
            ->get(route('reports.memo-copy'))
            ->assertOk()
            ->assertViewIs('reports.memo-copy');

        $this->actingAs($admin)
            ->get(route('reports.memo-copy', [
                'customer_id' => $lr->customer_id,
                'memo_id' => 8888,
            ]))
            ->assertOk()
            ->assertViewHas('presetCustomer');
    }

    public function test_consignor_consignee_report_renders_with_party_filters_and_sort(): void
    {
        $admin = $this->makeUser();
        [$lr] = $this->seedLr($admin);

        $params = $this->dateParams() + [
            'consignor_id' => $lr->consignor_id,
            'consignee_id' => $lr->consignee_id,
            'sort_by' => 'lr_number',
            'sort_order' => 'asc',
        ];

        $this->actingAs($admin)
            ->get(route('reports.consignor-consignee', $params))
            ->assertOk()
            ->assertViewIs('reports.consignor-consignee')
            ->assertSee('LR-RPT-1');
    }

    public function test_party_bill_report_renders_index_and_customer_invoice_list(): void
    {
        $admin = $this->makeUser();
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Ahmedabad', 'state_id' => $state->id, 'country_id' => $country->id]);
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Party Bill View Customer',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-VIEW-1',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 100,
            'base_amount' => 100,
            'net_amount' => 100,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.party-bill'))
            ->assertOk()
            ->assertViewIs('reports.party-bill');

        $this->actingAs($admin)
            ->get(route('reports.party-bill', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertViewHas('invoices', function ($invoices) {
                return $invoices->isNotEmpty();
            });

        $invoice = Invoice::query()
            ->where('company_id', $admin->company_id)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('reports.party-bill', ['invoice_number' => $invoice->id]))
            ->assertOk()
            ->assertViewHas('customerId', (string) $customer->id)
            ->assertViewHas('invoice', fn ($loaded) => (int) $loaded->id === (int) $invoice->id);
    }

    public function test_lr_copy_export_streams_pdf_for_selected_lr(): void
    {
        $admin = $this->makeUser();
        [$lr] = $this->seedLr($admin);

        $this->actingAs($admin)
            ->get(route('reports.lr-copy-export', ['lr_id' => $lr->id]))
            ->assertOk();
    }

    public function test_voucher_receipt_export_and_mark_printed(): void
    {
        $admin = $this->makeUser();
        [$lr, $booking] = $this->seedLr($admin);

        $this->actingAs($admin)
            ->from(route('reports.transporter-report', $this->dateParams()))
            ->get(route('reports.voucher-receipt-export'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->get(route('reports.voucher-receipt-export', ['lr_id' => $lr->id]))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('reports.voucher-receipt-mark-printed'), [
                'freight_booking_id' => $booking->id,
            ])
            ->assertOk()
            ->assertJson([
                'voucher_printed' => true,
            ]);

        $booking->refresh();
        $this->assertTrue((bool) $booking->voucher_printed);
    }

    public function test_memo_copy_export_pdf_branch(): void
    {
        $admin = $this->makeUser();
        [$lr] = $this->seedLr($admin);
        $lr->update(['memo_id' => 7777]);
        $this->seedMemoCopyViewData($admin, $lr);

        $this->actingAs($admin)
            ->get(route('reports.memo-copy-export', ['memo_id' => 7777, 'type' => 'pdf']))
            ->assertOk();
    }

    public function test_transporter_filter_options_api_returns_options(): void
    {
        $admin = $this->makeUser();
        [$lr] = $this->seedLr($admin);

        $this->actingAs($admin)
            ->getJson('/api/reports/transporter-filter-options?'.http_build_query([
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date' => now()->toDateString(),
                'transporter_id' => $lr->transporter_id,
                'truck_id' => $lr->truck_id,
            ]))
            ->assertOk()
            ->assertJsonStructure([
                'transporters',
                'trucks',
                'references',
            ]);
    }

    public function test_dispatch_report_clamps_date_range_and_flashes_error(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('reports.dispatch', [
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk()
            ->assertSessionHas('error');

        $start = $response->viewData('startDate');
        $end = $response->viewData('endDate');
        $minStart = \Carbon\Carbon::parse($end)->subMonths(3)->toDateString();
        $this->assertGreaterThanOrEqual($minStart, $start);
    }

    public function test_transporter_filter_options_rejects_date_range_over_three_months(): void
    {
        $admin = $this->makeUser();
        $this->seedLr($admin);

        $this->actingAs($admin)
            ->getJson('/api/reports/transporter-filter-options?'.http_build_query([
                'start_date' => now()->subMonths(4)->toDateString(),
                'end_date' => now()->toDateString(),
            ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.start_date.0', 'Maximum allowed date range is 3 months.');
    }

    public function test_pump_report_merges_linked_and_unlinked_payments_with_status_filter(): void
    {
        $admin = $this->makeUser();
        [$lr, $booking] = $this->seedLr($admin);
        $pump = Pump::create([
            'user_id' => $admin->company->user_id,
            'pump_name' => 'Merged Pump',
            'contact_name' => 'Operator',
            'contact_number' => '9888777666',
            'remark' => 'Merged',
        ]);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-PUMP-U',
            'payment_type' => 'cash',
            'payment_mode' => 'fuel',
            'amount' => 300,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'pump_id' => $pump->id,
            'date' => now()->toDateString(),
        ]);

        VoucherLinked::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'payment_type' => 'cash',
            'payment_mode' => 'fuel',
            'amount' => 450,
            'payment_status' => 'pending',
            'pump_id' => $pump->id,
            'date' => now()->toDateString(),
            'voucher_ids' => json_encode([]),
        ]);

        $params = [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
            'pump_id' => $pump->id,
            'payment_status' => 'pending',
        ];

        $this->actingAs($admin)
            ->get(route('reports.pump', $params))
            ->assertOk()
            ->assertViewHas('grandTotalAmount', 750.0)
            ->assertSee('Merged Pump');
    }

    public function test_transporter_report_clears_invalid_filters_and_supports_reference_filter(): void
    {
        $admin = $this->makeUser();
        [$lr] = $this->seedLr($admin);
        $params = $this->dateParams() + [
            'transporter_id' => 999999,
            'truck_id' => 999999,
            'reference_by' => $lr->transporter_id,
        ];

        $this->actingAs($admin)
            ->get(route('reports.transporter-report', $params))
            ->assertOk()
            ->assertViewHas('transporterId', null)
            ->assertViewHas('truckId', null)
            ->assertSee('LR-RPT-1');
    }

    public function test_voucher_receipt_export_requires_freight_bookings_on_linked_lrs(): void
    {
        $admin = $this->makeUser();
        [$lr] = $this->seedLr($admin);
        $linkedGroup = 55001;
        $lr->update(['linked_lr_id' => $linkedGroup]);

        LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $lr->customer_id,
            'consignor_id' => $lr->customer_id,
            'consignee_id' => $lr->customer_id,
            'lr_number' => 'LR-LINKED-NO-FB',
            'linked_lr_id' => $linkedGroup,
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'truck_id' => $lr->truck_id,
            'transporter_id' => $lr->transporter_id,
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
        ]);

        $this->actingAs($admin)
            ->from(route('reports.transporter-report', $this->dateParams()))
            ->get(route('reports.voucher-receipt-export', ['lr_id' => $lr->id]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_mark_voucher_receipt_printed_rejects_other_company_booking(): void
    {
        $owner = $this->makeUser();
        [, $booking] = $this->seedLr($owner);
        $intruder = $this->makeUser();

        $this->actingAs($intruder)
            ->postJson(route('reports.voucher-receipt-mark-printed'), [
                'freight_booking_id' => $booking->id,
            ])
            ->assertForbidden();
    }

    public function test_report_date_range_normalizes_swapped_dates(): void
    {
        $admin = $this->makeUser();

        $this->actingAs($admin)
            ->get(route('reports.bank', [
                'start_date' => now()->toDateString(),
                'end_date' => now()->subDays(3)->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('startDate', fn ($start) => $start <= now()->toDateString());
    }

    public function test_lr_copy_export_requires_selected_lr(): void
    {
        $admin = $this->makeUser();

        $this->actingAs($admin)
            ->from(route('reports.lr-copy'))
            ->get(route('reports.lr-copy-export'))
            ->assertRedirect()
            ->assertSessionHas('error', 'Please select a Good Receipt (Bilty) to export.');
    }

    public function test_lr_copy_export_rejects_other_company_lr(): void
    {
        $owner = $this->makeUser();
        [$lr] = $this->seedLr($owner);
        $intruder = $this->makeUser();

        $this->actingAs($intruder)
            ->from(route('reports.lr-copy'))
            ->get(route('reports.lr-copy-export', ['lr_id' => $lr->id]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_memo_copy_export_requires_selected_memo(): void
    {
        $admin = $this->makeUser();

        $this->actingAs($admin)
            ->from(route('reports.memo-copy'))
            ->get(route('reports.memo-copy-export'))
            ->assertRedirect()
            ->assertSessionHas('error', 'Please select a Memo to export.');
    }

    public function test_memo_copy_export_rejects_unknown_memo(): void
    {
        $admin = $this->makeUser();

        $this->actingAs($admin)
            ->from(route('reports.memo-copy'))
            ->get(route('reports.memo-copy-export', [
                'customer_id' => 1,
                'memo_id' => 999999,
            ]))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_pending_memo_and_invoice_reports_render_without_date_range(): void
    {
        $admin = $this->makeUser();
        $this->seedLr($admin);

        $this->actingAs($admin)
            ->get(route('reports.pending-memo'))
            ->assertOk()
            ->assertViewIs('reports.pending-memo');

        $this->actingAs($admin)
            ->get(route('reports.pending-invoice'))
            ->assertOk()
            ->assertViewIs('reports.pending-invoice');
    }

    public function test_consignor_consignee_report_uses_default_sort_when_invalid(): void
    {
        $admin = $this->makeUser();
        [$lr] = $this->seedLr($admin);

        $this->actingAs($admin)
            ->get(route('reports.consignor-consignee', $this->dateParams() + [
                'consignor_id' => $lr->consignor_id,
                'sort_by' => 'not_a_column',
                'sort_order' => 'sideways',
            ]))
            ->assertOk();
    }

    public function test_mark_voucher_receipt_printed_updates_booking(): void
    {
        $admin = $this->makeUser();
        [, $booking] = $this->seedLr($admin);

        $this->actingAs($admin)
            ->postJson(route('reports.voucher-receipt-mark-printed'), [
                'freight_booking_id' => $booking->id,
            ])
            ->assertOk()
            ->assertJsonPath('voucher_printed', true);

        $this->assertTrue((bool) $booking->fresh()->voucher_printed);
        $this->assertNotNull($booking->fresh()->voucher_last_printed_at);
    }
}
