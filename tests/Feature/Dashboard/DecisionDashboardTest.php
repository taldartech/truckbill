<?php

namespace Tests\Feature\Dashboard;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\EwayBill;
use App\Models\FreightBooking;
use App\Models\FreightBookingCustomAmount;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Dashboard\BusinessInsightsService;
use App\Services\Dashboard\DashboardPeriod;
use App\Services\Dashboard\PlatformInsightsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DecisionDashboardTest extends TestCase
{
    use RefreshDatabase;

    private const TODAY = '2026-09-20 10:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $compiledPath = sys_get_temp_dir().'/truckbill-views-'.getmypid();
        if (! is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }
        config(['view.compiled' => $compiledPath]);

        Carbon::setTestNow(Carbon::parse(self::TODAY));

        foreach (['master_admin', 'super_admin', 'admin', 'moderator', 'accountant'] as $index => $role) {
            UserRole::create(['id' => $index + 1, 'name' => $role]);
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_period_compares_with_the_same_days_of_the_previous_period(): void
    {
        $now = Carbon::parse(self::TODAY);

        $month = DashboardPeriod::fromKey('month', $now);
        $this->assertSame('2026-09-01', $month->start->toDateString());
        $this->assertSame('2026-09-20', $month->end->toDateString());
        $this->assertSame('2026-08-01', $month->previousStart->toDateString());
        $this->assertSame('2026-08-20', $month->previousEnd->toDateString());
        $this->assertSame(20, $month->days());

        $quarter = DashboardPeriod::fromKey('quarter', $now);
        $this->assertSame('2026-07-01', $quarter->start->toDateString());
        $this->assertSame('2026-04-01', $quarter->previousStart->toDateString());
        $this->assertSame('2026-06-21', $quarter->previousEnd->toDateString());

        $week = DashboardPeriod::fromKey('7d', $now);
        $this->assertSame('2026-09-14', $week->start->toDateString());
        $this->assertSame('2026-09-07', $week->previousStart->toDateString());
        $this->assertSame('2026-09-13', $week->previousEnd->toDateString());

        $this->assertSame('month', DashboardPeriod::fromKey('bogus', $now)->key);
        $this->assertSame('month', DashboardPeriod::fromKey(null, $now)->key);
    }

    public function test_format_rupees_compact(): void
    {
        $this->assertSame('₹2.50 Cr', formatRupeesCompact(25000000));
        $this->assertSame('₹1.25 L', formatRupeesCompact(125000));
        $this->assertSame('₹8.0 K', formatRupeesCompact(8000));
        $this->assertSame('₹950', formatRupeesCompact(950));
        $this->assertSame('-₹1.25 L', formatRupeesCompact(-125000));
    }

    public function test_receivables_use_due_date_or_default_credit_period(): void
    {
        $company = $this->makeCompany();
        $this->seedBusiness($company);

        $receivables = $this->insightsFor($company)['receivables'];

        $this->assertEquals(10000, $receivables['outstanding']);
        $this->assertEquals(8000, $receivables['overdue']);
        $this->assertSame(2, $receivables['overdue_count']);
        $this->assertSame(2, $receivables['overdue_customers']);
        $this->assertEquals(2000, $receivables['due_soon']);
        $this->assertSame(3, $receivables['open_count']);
        $this->assertEquals(8000, $receivables['buckets']['1_30']);
        $this->assertEquals(2000, $receivables['buckets']['not_due']);
        $this->assertEquals(0, $receivables['buckets']['90_plus']);

        $overdueByNumber = collect($receivables['overdue_invoices'])->keyBy('number');
        $this->assertSame(20, $overdueByNumber['INV-DEFAULT']['days_overdue']);
        $this->assertFalse($overdueByNumber['INV-DEFAULT']['has_due_date']);
        $this->assertSame(2, $overdueByNumber['INV-DUE']['days_overdue']);
        $this->assertTrue($overdueByNumber['INV-DUE']['has_due_date']);

        $this->assertEquals(4000, $receivables['collected']);
        $this->assertEquals(1000, $receivables['collected_previous']);
        $this->assertEquals(300.0, $receivables['collected_change']);
    }

    public function test_margin_uses_first_active_hire_and_skips_cancelled_trips(): void
    {
        $company = $this->makeCompany();
        $this->seedBusiness($company);

        $insights = $this->insightsFor($company);
        $margin = $insights['margin'];

        $this->assertSame(3, $margin['trips']);
        $this->assertEquals(23000, $margin['revenue']);
        $this->assertEquals(19000, $margin['cost']);
        $this->assertEquals(4000, $margin['profit']);
        $this->assertSame(1, $margin['loss_trips']);
        $this->assertEquals(500, $margin['loss_amount']);
        $this->assertSame('LR-LOSS', $margin['worst_trips'][0]['lr_number']);

        $this->assertEquals(20000, $margin['previous']['revenue']);
        $this->assertEquals(5000, $margin['previous']['profit']);
        $this->assertEquals(15.0, $insights['growth']['revenue_change']);

        $trend = $insights['trend'];
        $this->assertSame('Sep 2026', end($trend['labels']));
        $this->assertEquals(23000, end($trend['revenue']));
        $this->assertEquals(19000, end($trend['cost']));
        $this->assertEquals(20000, $trend['revenue'][4]);
        $this->assertEquals(15000, $trend['cost'][4]);
    }

    public function test_unbilled_payables_and_exceptions(): void
    {
        $company = $this->makeCompany();
        $this->seedBusiness($company);

        $insights = $this->insightsFor($company);

        $unbilled = $insights['unbilled'];
        $this->assertSame(1, $unbilled['ready_count']);
        $this->assertEquals(10000, $unbilled['ready_value']);
        $this->assertSame(1, $unbilled['pod_pending_count']);
        $this->assertEquals(4000, $unbilled['pod_pending_value']);
        $this->assertSame(2, $unbilled['in_transit_count']);
        $this->assertEquals(16000, $unbilled['in_transit_value']);

        $payables = $insights['payables'];
        $this->assertEquals(9000, $payables['total']);
        $this->assertEquals(3000, $payables['due_now']);
        $this->assertSame(1, $payables['due_now_trips']);
        $this->assertEquals(6000, $payables['running']);

        $exceptions = $insights['exceptions'];
        $this->assertSame(1, $exceptions['eway_expired']);
        $this->assertSame(1, $exceptions['long_running']);
        $this->assertSame('LR-RUNNING', $exceptions['long_running_trips'][0]['lr_number']);
        $this->assertSame(1, $exceptions['without_hire']);
        $this->assertSame(1, $exceptions['cancelled']);
    }

    public function test_action_items_depend_on_audience(): void
    {
        $company = $this->makeCompany();
        $this->seedBusiness($company);
        $service = app(BusinessInsightsService::class);
        $insights = $this->insightsFor($company);

        $owner = collect($service->actionItems($insights, BusinessInsightsService::AUDIENCE_OWNER));
        $finance = collect($service->actionItems($insights, BusinessInsightsService::AUDIENCE_FINANCE));
        $operations = collect($service->actionItems($insights, BusinessInsightsService::AUDIENCE_OPERATIONS));
        $portfolio = collect($service->actionItems($insights, BusinessInsightsService::AUDIENCE_PORTFOLIO));

        $this->assertStringContainsString('expired e-way bill', $owner->first()['title']);
        $this->assertSame($owner->sortBy('priority')->values()->all(), $owner->values()->all());

        $ownerCollect = $owner->first(fn (array $item): bool => str_starts_with($item['title'], 'Collect ₹'));
        $this->assertSame('Collect ₹8.0 K overdue from 2 customers', $ownerCollect['title']);
        $this->assertSame(route('invoices.index'), $ownerCollect['url']);

        $financeCollect = $finance->first(fn (array $item): bool => str_starts_with($item['title'], 'Collect ₹'));
        $this->assertSame(route('accounting.debtors-aging'), $financeCollect['url']);
        $this->assertNull($finance->first(fn (array $item): bool => str_contains($item['title'], 'ran at a loss')));

        $this->assertNull($operations->first(fn (array $item): bool => str_starts_with($item['title'], 'Collect ₹')));
        $this->assertNotNull($operations->first(fn (array $item): bool => str_contains($item['title'], 'ran at a loss')));

        $this->assertTrue($portfolio->every(fn (array $item): bool => $item['url'] === null && $item['cta'] === ''));
    }

    public function test_admin_dashboard_shows_decisions(): void
    {
        $company = $this->makeCompany();
        $this->seedBusiness($company);
        $admin = $this->makeUser('admin', $company);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('dashboardView', 'admin');
        $response->assertViewHas('dashboardPeriod', fn (DashboardPeriod $period): bool => $period->key === 'month');
        $response->assertViewHas('insights', fn (array $insights): bool => $insights['receivables']['overdue'] == 8000);
        $response->assertSee('Collect ₹8.0 K overdue from 2 customers');
    }

    public function test_dashboard_period_can_be_changed(): void
    {
        $company = $this->makeCompany();
        $admin = $this->makeUser('admin', $company);

        $this->actingAs($admin)->get(route('dashboard', ['period' => 'quarter']))
            ->assertOk()
            ->assertViewHas('dashboardPeriod', fn (DashboardPeriod $period): bool => $period->key === 'quarter');

        $this->actingAs($admin)->get(route('dashboard', ['period' => 'invalid']))
            ->assertOk()
            ->assertViewHas('dashboardPeriod', fn (DashboardPeriod $period): bool => $period->key === 'month');
    }

    public function test_empty_period_explains_dates_and_offers_last_30_days(): void
    {
        $company = $this->makeCompany();
        $this->seedBusiness($company);
        $admin = $this->makeUser('admin', $company);

        $response = $this->actingAs($admin)->get(route('dashboard', ['period' => '7d']));

        $response->assertOk();
        $response->assertViewHas('insights', fn (array $insights): bool => $insights['margin']['trips'] === 0
            && $insights['margin']['last_trip_date']->toDateString() === '2026-09-10');
        $response->assertSee('No trips with a lorry hire dated 14 Sep – 20 Sep 2026 (last 7 days).');
        $response->assertSee('Last trip was on 10 Sep 2026.');
        $response->assertSee(route('dashboard', ['period' => '30d']), false);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('No trips with a lorry hire dated');
    }

    public function test_moderator_dashboard_hides_collection_actions(): void
    {
        $company = $this->makeCompany();
        $this->seedBusiness($company);
        $moderator = $this->makeUser('moderator', $company);

        $response = $this->actingAs($moderator)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('dashboardView', 'moderator');
        $response->assertDontSee('Collect ₹8.0 K overdue');
        $response->assertSee('ran at a loss');
    }

    public function test_accountant_dashboard_links_to_accounting_pages(): void
    {
        $company = $this->makeCompany();
        $this->seedBusiness($company);
        $accountant = $this->makeUser('accountant', $company);

        $response = $this->actingAs($accountant)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('dashboardView', 'accountant');
        $response->assertSee(route('accounting.debtors-aging'), false);
        $response->assertSee('INV-DEFAULT');
        $response->assertDontSee('href="'.route('invoices.create').'"', false);
    }

    public function test_super_admin_dashboard_scores_owned_companies(): void
    {
        $owner = $this->makeUser('super_admin');
        $busy = $this->makeCompany(['name' => 'Busy Logistics', 'user_id' => $owner->id]);
        $this->makeCompany(['name' => 'Quiet Carriers', 'user_id' => $owner->id]);
        $this->makeCompany(['name' => 'Someone Else Co']);
        $this->seedBusiness($busy);

        $response = $this->actingAs($owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('dashboardView', 'super_admin');
        $response->assertSee('Enter Company Panel');
        $response->assertDontSee('Someone Else Co');
        $response->assertViewHas('scorecards', function (array $scorecards): bool {
            $busy = collect($scorecards)->firstWhere('name', 'Busy Logistics');

            return count($scorecards) === 2
                && $scorecards[0]['name'] === 'Busy Logistics'
                && $busy['overdue'] == 8000
                && collect($busy['flags'])->contains('label', 'Over half of dues overdue');
        });
    }

    public function test_master_admin_dashboard_flags_expired_plans(): void
    {
        $master = $this->makeUser('master_admin');
        $expired = $this->makeCompany(['name' => 'Lapsed Freight']);
        $expired->subscription->update(['status' => 'active', 'ends_on' => '2026-09-10']);
        $this->makeCompany(['name' => 'Healthy Freight']);

        $response = $this->actingAs($master)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('dashboardView', 'master_admin');
        $response->assertViewHas('platform', function (array $platform): bool {
            return $platform['companies_total'] === 2
                && collect($platform['expired'])->pluck('name')->all() === ['Lapsed Freight'];
        });
        $response->assertSee('Lapsed Freight');

        $actions = app(PlatformInsightsService::class)->build()['actions'];
        $this->assertNotEmpty($actions);
    }

    /**
     * @return array<string, mixed>
     */
    private function insightsFor(Company $company): array
    {
        return app(BusinessInsightsService::class)->build([$company->id], DashboardPeriod::fromKey('month'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCompany(array $overrides = []): Company
    {
        $company = Company::create(array_merge([
            'name' => 'Company '.uniqid(),
            'user_id' => null,
            'is_blocked' => false,
        ], $overrides));

        $company->subscription->update([
            'status' => 'active',
            'starts_on' => '2026-01-01',
            'ends_on' => '2027-01-01',
            'included_lr_credits' => 500,
            'lr_credits_remaining' => 400,
        ]);

        return $company->fresh('subscription');
    }

    private function makeUser(string $role, ?Company $company = null): User
    {
        return User::factory()->create([
            'user_role_id' => UserRole::where('name', $role)->value('id'),
            'company_id' => $company?->id,
        ]);
    }

    /**
     * Current month (to 20 Sep): one profitable trip ready to bill, one loss-making trip awaiting POD,
     * one trip whose only hire was cancelled, and one trip still running since 1 Sep.
     * Previous month: one delivered, invoiced trip.
     */
    private function seedBusiness(Company $company): void
    {
        $acme = Consignee::create(['company_id' => $company->id, 'name' => 'Acme Steel']);
        $bharat = Consignee::create(['company_id' => $company->id, 'name' => 'Bharat Cement']);

        $readyToBill = $this->makeLr($company, $acme, 'LR-READY', '2026-09-05', 10000, [
            'delivery_status' => 'Delivered',
            'memo_id' => 'M-1',
        ]);
        $readyHire = $this->makeHire($company, $readyToBill, 8000, ['balance_amount' => 3000, 'is_delivered' => true]);
        FreightBookingCustomAmount::create([
            'freight_booking_id' => $readyHire->id,
            'company_id' => $company->id,
            'description' => 'Loading',
            'amount' => 500,
        ]);

        $loss = $this->makeLr($company, $bharat, 'LR-LOSS', '2026-09-10', 5000, [
            'delivery_status' => 'Delivered',
            'actual_amount' => 4000,
        ]);
        $this->makeHire($company, $loss, 4500, ['is_delivered' => true]);

        $cancelled = $this->makeLr($company, $acme, 'LR-CANCELLED-HIRE', '2026-09-12', 7000);
        $this->makeHire($company, $cancelled, 1000, ['is_cancelled' => true, 'balance_amount' => 1000]);

        $running = $this->makeLr($company, $acme, 'LR-RUNNING', '2026-09-01', 9000, ['delivery_status' => 'In-Progress']);
        $runningHire = $this->makeHire($company, $running, 6000, ['balance_amount' => 6000]);
        EwayBill::create([
            'freight_booking_id' => $runningHire->id,
            'eway_bill_number' => '123456789012',
            'eway_bill_expiry' => '2026-09-19',
        ]);

        $previous = $this->makeLr($company, $acme, 'LR-AUGUST', '2026-08-10', 20000, [
            'delivery_status' => 'Delivered',
            'memo_id' => 'M-0',
            'invoice_generated' => 1,
        ]);
        $this->makeHire($company, $previous, 15000, ['is_delivered' => true]);

        $defaultDue = $this->makeInvoice($company, $acme, 'INV-DEFAULT', '2026-08-01', 10000);
        InvoicePayment::create([
            'invoice_id' => $defaultDue->id,
            'mode' => 'Cash',
            'amount' => 4000,
            'tds_amount' => 1000,
            'payment_date' => '2026-09-05',
        ]);

        $this->makeInvoice($company, $bharat, 'INV-DUE', '2026-09-15', 3000, '2026-09-18');
        $this->makeInvoice($company, $acme, 'INV-NOT-DUE', '2026-09-10', 2000, '2026-09-25');

        $paid = $this->makeInvoice($company, $acme, 'INV-PAID', '2026-08-05', 1000);
        InvoicePayment::create([
            'invoice_id' => $paid->id,
            'mode' => 'Cash',
            'amount' => 1000,
            'payment_date' => '2026-08-15',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeLr(Company $company, Consignee $customer, string $number, string $grDate, float $amount, array $overrides = []): LorryReceipt
    {
        return LorryReceipt::create(array_merge([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => $number,
            'gr_date' => $grDate,
            'delivery_status' => 'Pending',
            'invoice_generated' => 0,
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => $amount,
            'source_city' => 'Raipur',
            'source_state' => 'CG',
            'destination_city' => 'Nagpur',
            'destination_state' => 'MH',
            'lane' => 'Lane 1',
            'good_type' => 'Steel',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeHire(Company $company, LorryReceipt $lr, float $hireAmount, array $overrides = []): FreightBooking
    {
        return FreightBooking::create(array_merge([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_name' => 'Fast Movers',
            'tr_fr_rate' => 100,
            'tr_amount' => $hireAmount,
            'advance_amount' => 0,
            'balance_amount' => 0,
            'tds_amount' => 0,
            'is_delivered' => false,
            'is_cancelled' => false,
        ], $overrides));
    }

    private function makeInvoice(Company $company, Consignee $customer, string $number, string $date, float $amount, ?string $dueDate = null): Invoice
    {
        return Invoice::create([
            'manual_invoice_number' => $number,
            'invoice_type' => 'Reverse',
            'generated_date' => $date,
            'due_date' => $dueDate,
            'customer_id' => $customer->id,
            'total_amount' => $amount,
            'base_amount' => $amount,
            'net_amount' => $amount,
            'payment_status' => 'pending',
            'company_id' => $company->id,
            'memo_ids' => [],
            'lr_ids' => [],
        ]);
    }
}
