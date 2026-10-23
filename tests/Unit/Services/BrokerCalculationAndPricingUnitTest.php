<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\UserRole;
use App\Services\FreightBookingCalculationService;
use App\Services\InvoiceNumberService;
use App\Services\SubscriptionGuard;
use App\Services\SubscriptionUsageService;
use App\Services\TransporterTdsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BrokerCalculationAndPricingUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! UserRole::where('id', 3)->exists()) {
            UserRole::create(['id' => 1, 'name' => 'master_admin']);
            UserRole::create(['id' => 2, 'name' => 'super_admin']);
            UserRole::create(['id' => 3, 'name' => 'admin']);
            UserRole::create(['id' => 4, 'name' => 'moderator']);
        }
    }

    public function test_transporter_tds_calculator_declaration_given_returns_zero(): void
    {
        $calculator = new TransporterTdsCalculator();

        $tds = $calculator->calculate(true, 'ABCDE1234F', true);
        $this->assertSame(0.00, $tds);

        $tdsInactive = $calculator->calculate(true, 'ABCDE1234F', false);
        $this->assertSame(0.00, $tdsInactive);
    }

    public function test_transporter_tds_calculator_pan_inactive_returns_default_twenty_percent(): void
    {
        $calculator = new TransporterTdsCalculator();

        $tds = $calculator->calculate(false, 'ABCPE1234F', false);
        $this->assertSame(20.00, $tds);
    }

    public function test_transporter_tds_calculator_invalid_pan_length_returns_twenty_percent(): void
    {
        $calculator = new TransporterTdsCalculator();

        $this->assertSame(20.00, $calculator->calculate(false, '', true));
        $this->assertSame(20.00, $calculator->calculate(false, 'ABC', true));
    }

    public function test_transporter_tds_calculator_fourth_char_p_and_h_returns_one_percent(): void
    {
        $calculator = new TransporterTdsCalculator();

        $this->assertSame(1.00, $calculator->calculate(false, 'AAAP0000A', true));
        $this->assertSame(1.00, $calculator->calculate(false, 'aaap0000a', true)); // lowercase test
        $this->assertSame(1.00, $calculator->calculate(false, 'AAAH0000A', true));
    }

    public function test_transporter_tds_calculator_fourth_char_corporate_returns_two_percent(): void
    {
        $calculator = new TransporterTdsCalculator();

        $corporateCodes = ['C', 'F', 'A', 'B', 'J', 'L', 'T'];
        foreach ($corporateCodes as $code) {
            $pan = 'AAA' . $code . '1111A';
            $this->assertSame(2.00, $calculator->calculate(false, $pan, true), "Failed for PAN 4th char {$code}");
        }
    }

    public function test_transporter_tds_calculator_other_fourth_char_returns_twenty_percent(): void
    {
        $calculator = new TransporterTdsCalculator();

        $this->assertSame(20.00, $calculator->calculate(false, 'AAAG1111A', true));
        $this->assertSame(20.00, $calculator->calculate(false, 'AAAK1111A', true));
    }

    public function test_freight_booking_calculate_for_store_fixed_amount(): void
    {
        $service = new FreightBookingCalculationService();

        $result = $service->calculateForStore(
            isFixedAmount: true,
            trFrRate: 500.0,
            weight: 10.0,
            trAmountInput: 15000.0,
            tdsPercentage: 2.0
        );

        $this->assertSame(15000.0, $result['tr_amount']);
        $this->assertSame(300.0, $result['tds_amount']); // 2% of 15000
        $this->assertSame(14700.0, $result['balance_amount']);
        $this->assertSame(0.0, $result['advance_amount']);
    }

    public function test_freight_booking_calculate_for_store_per_tonne_with_fractional_tds_rounding(): void
    {
        $service = new FreightBookingCalculationService();

        // 1234.56 * 10.5 = 12962.88. 1% TDS = 129.6288 => ceil(129.6288) = 130
        $result = $service->calculateForStore(
            isFixedAmount: false,
            trFrRate: 1234.56,
            weight: 10.5,
            trAmountInput: 0.0,
            tdsPercentage: 1.0
        );

        $expectedTrAmount = 1234.56 * 10.5;
        $this->assertEqualsWithDelta($expectedTrAmount, $result['tr_amount'], 0.001);
        $this->assertSame(130.0, $result['tds_amount']);
        $this->assertEqualsWithDelta($expectedTrAmount - 130.0, $result['balance_amount'], 0.001);
    }

    public function test_freight_booking_calculate_for_update_incorporates_all_adjustments(): void
    {
        $service = new FreightBookingCalculationService();

        $result = $service->calculateForUpdate(
            isFixedAmount: false,
            trFrRate: 1000.0,
            weight: 12.0,
            unloadingWeight: 10.0, // min(12, 10) = 10 tonnes => tr_amount = 10000
            trAmountInput: 0.0,
            voucherPaymentsTotal: 2000.0,
            detention: 500.0,
            deviation: 200.0,
            miscExpense: 100.0,
            damageAmount: 300.0,
            shortageAmount: 400.0,
            tdsPercentage: 2.0,
            canChangeTdsAmount: true,
            existingTdsAmount: 0.0,
            negativeAdjustedAmount: 50.0,
            customAmountsTotal: 150.0
        );

        $this->assertSame(10000.0, $result['tr_amount']);
        // TDS on booking rate * original weight (1000 * 12 = 12000), 2% = 240
        $this->assertSame(240.0, $result['tds_amount']);
        $this->assertSame(2000.0, $result['advance_amount']);

        // Balance = 10000 - 400 (shortage) - 300 (damage) - 100 (misc) + 500 (detention) + 200 (deviation) + 150 (custom) - 240 (tds) - 2000 (advance) + 50 (neg adjusted)
        // = 10000 - 800 + 850 - 2240 + 50 = 7860
        $this->assertSame(7860.0, $result['balance_amount']);
    }

    public function test_freight_booking_calculate_for_update_preserves_existing_tds_when_locked(): void
    {
        $service = new FreightBookingCalculationService();

        $result = $service->calculateForUpdate(
            isFixedAmount: true,
            trFrRate: 0.0,
            weight: 0.0,
            unloadingWeight: 0.0,
            trAmountInput: 50000.0,
            voucherPaymentsTotal: 10000.0,
            detention: 0.0,
            deviation: 0.0,
            miscExpense: 0.0,
            damageAmount: 0.0,
            shortageAmount: 0.0,
            tdsPercentage: 2.0,
            canChangeTdsAmount: false,
            existingTdsAmount: 500.0, // preserved TDS
            negativeAdjustedAmount: 0.0,
            customAmountsTotal: 0.0
        );

        $this->assertSame(500.0, $result['tds_amount']);
        $this->assertSame(39500.0, $result['balance_amount']); // 50000 - 500 - 10000
    }

    public function test_lorry_receipt_update_status_transitions(): void
    {
        $service = new FreightBookingCalculationService();

        $company = Company::create(['name' => 'Transport Co']);
        $consignor = \App\Models\Consignee::create([
            'company_id' => $company->id,
            'name' => 'Consignor A',
            'address' => 'Addr',
        ]);
        $consignee = \App\Models\Consignee::create([
            'company_id' => $company->id,
            'name' => 'Consignee A',
            'address' => 'Addr',
        ]);
        $transporter = \App\Models\Transporter::factory()
            ->forCompany($company->id)
            ->create([
                'mobile_number' => '9876543210',
                'tds_percentage' => 1.0,
            ]);
        $truck = \App\Models\Truck::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'truck_number' => 'MH12AB1234',
            'truck_type' => 'Open',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $consignee->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'lr_number' => 'LR-TEST-1',
            'gr_number' => 'GR-TEST-1',
            'source_city' => 'Mumbai',
            'source_state' => 'MH',
            'destination_city' => 'Delhi',
            'destination_state' => 'DL',
            'lane' => 'Mumbai-Delhi',
            'weight' => 10,
            'no_of_units' => 1,
            'good_type' => 'Goods',
            'fr_rate' => 1000,
            'amount' => 10000,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
        ]);

        // Delivered transition
        $result = $service->updateLorryReceiptForFreightBookingUpdate(
            $lr,
            ['is_delivered' => '1', 'unloading_weight' => 18.5],
            damageAmount: 200.0,
            shortageAmount: 150.0
        );

        $this->assertSame('Delivered', $result['delivery_status']);
        $this->assertSame('Delivered', $lr->delivery_status);
        $this->assertSame(200.0, (float) $lr->damage_amount);
        $this->assertSame(150.0, (float) $lr->shortage_amount);
        $this->assertSame(18.5, (float) $lr->unloading_weight);

        // Cancelled transition
        $booking = \App\Models\FreightBooking::create([
            'lr_id' => $lr->id,
            'company_id' => $company->id,
            'tr_fr_rate' => 1000,
            'tr_amount' => 10000,
            'advance_amount' => 0,
            'balance_amount' => 10000,
            'tds_amount' => 0,
        ]);
        $lr->freight_booking_id = $booking->id;
        $lr->save();

        $cancelResult = $service->updateLorryReceiptForFreightBookingUpdate(
            $lr,
            ['is_cancelled' => '1'],
            damageAmount: 0.0,
            shortageAmount: 0.0
        );

        $this->assertSame('Pending', $cancelResult['delivery_status']);
        $this->assertNull($lr->freight_booking_id);
    }

    public function test_invoice_number_service_generates_sequential_numbers(): void
    {
        $service = new InvoiceNumberService();

        $company = Company::create([
            'name' => 'Fast Logistics Pvt Ltd',
            'short_name' => 'FLPL',
        ]);
        $customer = \App\Models\Consignee::create([
            'company_id' => $company->id,
            'name' => 'Customer Corp',
            'address' => 'Addr',
        ]);

        $year = now()->format('Y');
        $number1 = $service->generate($company, now()->toDateString());
        $this->assertSame("FLPL/{$year}/0001", $number1);

        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => $number1,
            'invoice_type' => 'regular',
            'generated_date' => now()->toDateString(),
        ]);

        $number2 = $service->generate($company, now()->toDateString());
        $this->assertSame("FLPL/{$year}/0002", $number2);
    }

    public function test_invoice_number_service_fallbacks_when_no_short_name(): void
    {
        $service = new InvoiceNumberService();

        $company = Company::create([
            'name' => 'Apex Freight',
            'short_name' => null,
        ]);

        $year = now()->format('Y');
        $number = $service->generate($company, now()->toDateString());
        // Company model automatically generates short_name AF from initials of Apex Freight
        $this->assertSame("AF/{$year}/0001", $number);

        $emptyCompany = new Company();
        $emptyCompany->id = 99999;
        $emptyCompany->name = null;
        $emptyCompany->short_name = null;
        $numberFallback = $service->generate($emptyCompany, now()->toDateString());
        $this->assertSame("COMP/{$year}/0001", $numberFallback);
    }

    public function test_subscription_guard_assert_has_lr_credits(): void
    {
        $usageService = new SubscriptionUsageService();
        $guard = new SubscriptionGuard($usageService);

        $company = Company::create(['name' => 'Credits Co']);
        $subscription = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
            'lr_credits_remaining' => 5,
        ]);

        // Has credits -> passes without exception
        $guard->assertHasLrCredits($company, 2);

        // Decrement credits
        $guard->decrementLrCredits($company, 5);
        $subscription->refresh();
        $this->assertSame(0, (int) $subscription->lr_credits_remaining);

        // Insufficient credits -> throws HttpException 422
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Insufficient LR credits.');
        $guard->assertHasLrCredits($company, 1);
    }

    public function test_subscription_guard_assert_can_create_branch(): void
    {
        $usageService = new SubscriptionUsageService();
        $guard = new SubscriptionGuard($usageService);

        $company = Company::create(['name' => 'Branch Co']);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 1,
            'addon_branches' => 0,
            'users_per_branch' => 2,
        ]);

        // 0 branches created, max is 1 -> succeeds
        $guard->assertCanCreateBranch($company);

        // Create 1 branch
        Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'HQ',
            'branch_address' => 'Mumbai',
        ]);

        // Now count is 1, max is 1 -> throws ValidationException
        $this->expectException(ValidationException::class);
        $guard->assertCanCreateBranch($company);
    }

    public function test_subscription_usage_service_metrics(): void
    {
        $service = new SubscriptionUsageService();

        $company = Company::create(['name' => 'Metrics Co']);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 2,
            'lr_credits_remaining' => 10,
        ]);

        $b1 = Branch::create(['company_id' => $company->id, 'branch_name' => 'B1', 'branch_address' => 'Delhi']);
        $b2 = Branch::create(['company_id' => $company->id, 'branch_name' => 'B2', 'branch_address' => 'Jaipur']);

        User::factory()->create(['company_id' => $company->id, 'branch_id' => $b1->id, 'user_role_id' => 3]);
        User::factory()->create(['company_id' => $company->id, 'branch_id' => $b1->id, 'user_role_id' => 3]);
        User::factory()->create(['company_id' => $company->id, 'branch_id' => $b1->id, 'user_role_id' => 3]); // overflow in b1: 3 - 2 = 1
        User::factory()->create(['company_id' => $company->id, 'branch_id' => $b2->id, 'user_role_id' => 3]);

        $usage = $service->usageForCompany($company);
        $this->assertSame(2, $usage['branches']);
        $this->assertSame(4, $usage['total_users']);
        $this->assertSame(3, $usage['max_users_in_any_branch']);
        $this->assertSame(10, $usage['lr_credits_remaining']);

        $overflow = $service->overflowUsersUsed($company, perBranch: 2);
        $this->assertSame(1, $overflow);

        $catalog = [
            'included_branches' => 2,
            'users_per_branch' => 2,
        ];
        $minAddons = $service->minimumRenewalAddons($company, $catalog);
        $this->assertSame(0, $minAddons['addon_branches']);
        $this->assertSame(1, $minAddons['addon_users']);
    }
}
