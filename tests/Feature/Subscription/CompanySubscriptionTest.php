<?php

namespace Tests\Feature\Subscription;

use App\Enums\SubscriptionAccessMode;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\AdminApiClient;
use App\Services\SubscriptionAccessService;
use App\Services\SubscriptionCheckoutService;
use App\Services\SubscriptionPricingService;
use App\Services\SubscriptionUsageService;
use App\Support\SubscriptionExpiredResponse;
use App\Support\SubscriptionProration;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CompanySubscriptionTest extends TestCase
{
    use RefreshDatabase;
    // ==========================================
    // CompanySubscription Model Tests
    // ==========================================

    public function test_active_trial_subscription_within_ends_on(): void
    {
        $subscription = new CompanySubscription([
            'status' => 'trial',
            'starts_on' => Carbon::today(),
            'ends_on' => Carbon::today()->addDays(10),
            'included_branches' => 2,
            'users_per_branch' => 3,
            'addon_users' => 0,
        ]);

        $this->assertTrue($subscription->isActive());
        $this->assertSame(6, $subscription->maxCompanyUsers());
    }

    public function test_expired_subscription_is_not_active(): void
    {
        $subscription = new CompanySubscription([
            'status' => 'expired',
            'starts_on' => Carbon::today()->subYear(),
            'ends_on' => Carbon::today()->subDay(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $this->assertFalse($subscription->isActive());
    }

    public function test_display_status_shows_expired_when_end_date_passed(): void
    {
        $subscription = new CompanySubscription([
            'status' => 'active',
            'starts_on' => Carbon::today()->subYear(),
            'ends_on' => Carbon::today()->subDay(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $this->assertFalse($subscription->isActive());
        $this->assertSame('expired', $subscription->displayStatus());
        $this->assertSame('expired', $subscription->display_status);
    }

    // ==========================================
    // SubscriptionProration Support Tests
    // ==========================================

    public function test_remaining_days_includes_today_and_end_date(): void
    {
        Carbon::setTestNow('2026-01-01');

        $this->assertSame(170, SubscriptionProration::remainingDaysInclusive('2026-06-19'));
        $this->assertSame(1, SubscriptionProration::remainingDaysInclusive('2026-01-01'));

        $carbonEnd = Carbon::parse('2026-06-19');
        $this->assertSame(170, SubscriptionProration::remainingDaysInclusive($carbonEnd));

        $asOf = Carbon::parse('2026-06-01');
        $this->assertSame(19, SubscriptionProration::remainingDaysInclusive('2026-06-19', $asOf));

        Carbon::setTestNow();
    }

    public function test_remaining_days_returns_zero_when_end_date_is_in_past(): void
    {
        Carbon::setTestNow('2026-01-05');

        $this->assertSame(0, SubscriptionProration::remainingDaysInclusive('2026-01-04'));
        $this->assertSame(0, SubscriptionProration::remainingDaysInclusive('2025-12-31'));

        $pastDate = Carbon::parse('2025-06-01');
        $this->assertSame(0, SubscriptionProration::remainingDaysInclusive($pastDate));

        $asOf = Carbon::parse('2026-06-10');
        $this->assertSame(0, SubscriptionProration::remainingDaysInclusive('2026-06-09', $asOf));

        Carbon::setTestNow();
    }

    public function test_prorates_annual_amount_by_remaining_days(): void
    {
        $annual = 36500.0;
        $expected = round(($annual / 365) * 170, 2);

        $this->assertSame($expected, SubscriptionProration::prorateAnnualAmount($annual, 170));
    }

    public function test_prorated_amount_never_exceeds_annual_price(): void
    {
        $annual = 1000.0;

        $this->assertSame($annual, SubscriptionProration::prorateAnnualAmount($annual, 365));
        $this->assertSame($annual, SubscriptionProration::prorateAnnualAmount($annual, 400));
    }

    public function test_prorate_annual_amount_returns_zero_for_zero_or_negative_inputs(): void
    {
        $this->assertSame(0.0, SubscriptionProration::prorateAnnualAmount(0.0, 100));
        $this->assertSame(0.0, SubscriptionProration::prorateAnnualAmount(-500.0, 100));

        $this->assertSame(0.0, SubscriptionProration::prorateAnnualAmount(1000.0, 0));
        $this->assertSame(0.0, SubscriptionProration::prorateAnnualAmount(1000.0, -10));

        $this->assertSame(0.0, SubscriptionProration::prorateAnnualAmount(0.0, 0));
        $this->assertSame(0.0, SubscriptionProration::prorateAnnualAmount(-100.0, -5));
    }

    // ==========================================
    // SubscriptionExpiredResponse Support Tests
    // ==========================================

    public function test_for_grace_restricted_action_returns_redirect_to_dashboard(): void
    {
        $request = Request::create('/some-action', 'POST');
        $response = SubscriptionExpiredResponse::forGraceRestrictedAction($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('dashboard'), $response->getTargetUrl());

        $company = Mockery::mock(Company::class);
        $responseWithCompany = SubscriptionExpiredResponse::forGraceRestrictedAction($request, $company);
        $this->assertInstanceOf(RedirectResponse::class, $responseWithCompany);
        $this->assertSame(route('dashboard'), $responseWithCompany->getTargetUrl());
    }

    public function test_for_locked_returns_redirect_to_dashboard(): void
    {
        $request = Request::create('/some-action', 'GET');
        $response = SubscriptionExpiredResponse::forLocked($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('dashboard'), $response->getTargetUrl());
    }

    public function test_redirect_for_user_calls_for_locked(): void
    {
        $request = Request::create('/profile', 'GET');
        $response = SubscriptionExpiredResponse::redirectForUser($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(route('dashboard'), $response->getTargetUrl());
    }

    public function test_abort_unless_active_throws_http_response_exception_with_redirect(): void
    {
        $request = Request::create('/some-resource', 'POST');

        try {
            SubscriptionExpiredResponse::abortUnlessActive($request);
            $this->fail('Expected HttpResponseException was not thrown.');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertSame(route('dashboard'), $response->getTargetUrl());
        }
    }

    public function test_abort_unless_can_mutate_throws_for_null_grace_and_locked(): void
    {
        $request = Request::create('/some-mutate-route', 'POST');

        // 1. null company (defaults to locked)
        try {
            SubscriptionExpiredResponse::abortUnlessCanMutate($request, null);
            $this->fail('Expected HttpResponseException when company is null.');
        } catch (HttpResponseException $e) {
            $this->assertSame(route('dashboard'), $e->getResponse()->getTargetUrl());
        }

        // 2. company in grace mode
        $graceCompany = Mockery::mock(Company::class);
        $graceCompany->shouldReceive('subscriptionAccessPayload')
            ->once()
            ->andReturn(['access_mode' => 'grace']);

        try {
            SubscriptionExpiredResponse::abortUnlessCanMutate($request, $graceCompany);
            $this->fail('Expected HttpResponseException for grace mode.');
        } catch (HttpResponseException $e) {
            $this->assertSame(route('dashboard'), $e->getResponse()->getTargetUrl());
        }

        // 3. company in locked mode
        $lockedCompany = Mockery::mock(Company::class);
        $lockedCompany->shouldReceive('subscriptionAccessPayload')
            ->once()
            ->andReturn(['access_mode' => 'locked']);

        try {
            SubscriptionExpiredResponse::abortUnlessCanMutate($request, $lockedCompany);
            $this->fail('Expected HttpResponseException for locked mode.');
        } catch (HttpResponseException $e) {
            $this->assertSame(route('dashboard'), $e->getResponse()->getTargetUrl());
        }
    }

    // ==========================================
    // SubscriptionAccessMode Enum & Service Tests
    // ==========================================

    public function test_subscription_access_mode_enum_cases_values_and_from(): void
    {
        $this->assertSame('full', SubscriptionAccessMode::Full->value);
        $this->assertSame('grace', SubscriptionAccessMode::GraceReadOnly->value);
        $this->assertSame('locked', SubscriptionAccessMode::Locked->value);

        $this->assertSame(SubscriptionAccessMode::Full, SubscriptionAccessMode::from('full'));
        $this->assertSame(SubscriptionAccessMode::GraceReadOnly, SubscriptionAccessMode::from('grace'));
        $this->assertSame(SubscriptionAccessMode::Locked, SubscriptionAccessMode::from('locked'));

        $this->assertNull(SubscriptionAccessMode::tryFrom('unknown'));
        $this->assertNull(SubscriptionAccessMode::tryFrom(''));
        $this->assertCount(3, SubscriptionAccessMode::cases());
    }

    public function test_subscription_access_mode_enum_names_and_json_serialization(): void
    {
        $this->assertSame('Full', SubscriptionAccessMode::Full->name);
        $this->assertSame('GraceReadOnly', SubscriptionAccessMode::GraceReadOnly->name);
        $this->assertSame('Locked', SubscriptionAccessMode::Locked->name);

        $this->assertSame('"full"', json_encode(SubscriptionAccessMode::Full));
        $this->assertSame('"grace"', json_encode(SubscriptionAccessMode::GraceReadOnly));
        $this->assertSame('"locked"', json_encode(SubscriptionAccessMode::Locked));

        $this->assertSame([
            SubscriptionAccessMode::Full,
            SubscriptionAccessMode::GraceReadOnly,
            SubscriptionAccessMode::Locked,
        ], SubscriptionAccessMode::cases());
    }

    public function test_subscription_access_service_resolves_locked_when_no_subscription(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $checkoutService = Mockery::mock(SubscriptionCheckoutService::class);
        $checkoutService->shouldReceive('applyUpcomingForCompany')->with(1)->andReturn(false);

        $company = Mockery::mock(Company::class)->makePartial();
        $company->id = 1;
        $company->shouldReceive('load')->with('subscription')->andReturnSelf();
        $company->subscription = null;

        $service = new SubscriptionAccessService($adminApi, $checkoutService);
        $mode = $service->resolveForCompany($company);

        $this->assertSame(SubscriptionAccessMode::Locked, $mode);

        $payload = $service->accessPayloadForCompany($company);
        $this->assertSame('locked', $payload['access_mode']);
        $this->assertFalse($payload['can_write']);
        $this->assertFalse($payload['can_export']);
        $this->assertFalse($payload['can_print']);
        $this->assertFalse($payload['is_in_grace_period']);
        $this->assertNull($payload['grace_ends_at']);
    }

    public function test_subscription_access_service_resolves_full_when_subscription_active(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $checkoutService = Mockery::mock(SubscriptionCheckoutService::class);
        $checkoutService->shouldReceive('applyUpcomingForCompany')->with(2)->andReturn(false);

        $subscription = new CompanySubscription([
            'status' => 'active',
            'starts_on' => Carbon::today()->subMonth(),
            'ends_on' => Carbon::today()->addMonth(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $company = Mockery::mock(Company::class)->makePartial();
        $company->id = 2;
        $company->shouldReceive('load')->with('subscription')->andReturnSelf();
        $company->subscription = $subscription;

        $service = new SubscriptionAccessService($adminApi, $checkoutService);
        $mode = $service->resolveForCompany($company);

        $this->assertSame(SubscriptionAccessMode::Full, $mode);

        $payload = $service->accessPayloadForCompany($company);
        $this->assertSame('full', $payload['access_mode']);
        $this->assertTrue($payload['can_write']);
        $this->assertTrue($payload['can_export']);
        $this->assertTrue($payload['can_print']);
        $this->assertFalse($payload['is_in_grace_period']);
        $this->assertNull($payload['grace_ends_at']);
    }

    public function test_subscription_access_service_resolves_grace_read_only_when_within_grace_period(): void
    {
        Cache::forget('subscription.settings');
        $adminApi = Mockery::mock(AdminApiClient::class);
        $adminApi->shouldReceive('getCatalog')->andReturn(['settings' => ['grace_period_days' => 15]]);
        $checkoutService = Mockery::mock(SubscriptionCheckoutService::class);
        $checkoutService->shouldReceive('applyUpcomingForCompany')->with(3)->andReturn(false);

        $subscription = new CompanySubscription([
            'status' => 'expired',
            'starts_on' => Carbon::today()->subYear(),
            'ends_on' => Carbon::today()->subDays(2),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $company = Mockery::mock(Company::class)->makePartial();
        $company->id = 3;
        $company->shouldReceive('load')->with('subscription')->andReturnSelf();
        $company->subscription = $subscription;

        $service = new SubscriptionAccessService($adminApi, $checkoutService);
        $mode = $service->resolveForCompany($company);

        $this->assertSame(SubscriptionAccessMode::GraceReadOnly, $mode);

        $payload = $service->accessPayloadForCompany($company);
        $this->assertSame('grace', $payload['access_mode']);
        $this->assertFalse($payload['can_write']);
        $this->assertFalse($payload['can_export']);
        $this->assertFalse($payload['can_print']);
        $this->assertTrue($payload['is_in_grace_period']);
        $this->assertSame(Carbon::today()->subDays(2)->addDays(15)->toDateString(), $payload['grace_ends_at']);
    }

    public function test_subscription_access_service_resolves_locked_when_past_grace_period(): void
    {
        Cache::forget('subscription.settings');
        $adminApi = Mockery::mock(AdminApiClient::class);
        $adminApi->shouldReceive('getCatalog')->andReturn(['settings' => ['grace_period_days' => 15]]);
        $checkoutService = Mockery::mock(SubscriptionCheckoutService::class);
        $checkoutService->shouldReceive('applyUpcomingForCompany')->with(4)->andReturn(false);

        $subscription = new CompanySubscription([
            'status' => 'expired',
            'starts_on' => Carbon::today()->subYear(),
            'ends_on' => Carbon::today()->subDays(20),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $company = Mockery::mock(Company::class)->makePartial();
        $company->id = 4;
        $company->shouldReceive('load')->with('subscription')->andReturnSelf();
        $company->subscription = $subscription;

        $service = new SubscriptionAccessService($adminApi, $checkoutService);
        $mode = $service->resolveForCompany($company);

        $this->assertSame(SubscriptionAccessMode::Locked, $mode);

        $payload = $service->accessPayloadForCompany($company);
        $this->assertSame('locked', $payload['access_mode']);
        $this->assertFalse($payload['can_write']);
        $this->assertFalse($payload['can_export']);
        $this->assertFalse($payload['can_print']);
        $this->assertFalse($payload['is_in_grace_period']);
    }

    // ==========================================
    // SubscriptionCheckoutService Lifecycle Tests
    // ==========================================

    public function test_subscription_checkout_service_renewal_term_following(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $pricing = Mockery::mock(\App\Services\SubscriptionPricingService::class);
        $usage = Mockery::mock(\App\Services\SubscriptionUsageService::class);

        $checkout = new SubscriptionCheckoutService($adminApi, $pricing, $usage);

        $term = $checkout->renewalTermFollowing('2026-05-15');
        $this->assertSame('2026-05-16', $term['starts_on']);
        $this->assertSame('2027-05-15', $term['ends_on']);
    }

    public function test_subscription_checkout_service_record_trial_extension_validations_and_fulfillment(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $pricing = Mockery::mock(\App\Services\SubscriptionPricingService::class);
        $usage = Mockery::mock(\App\Services\SubscriptionUsageService::class);
        $usage->shouldReceive('usageForCompany')->andReturn(['branches' => 1]);

        $checkout = new SubscriptionCheckoutService($adminApi, $pricing, $usage);

        $company = Company::create(['name' => 'Trial Extension Co']);

        // 1. Past trial end date throws exception
        try {
            $checkout->recordTrialExtension($company, [
                'trial_ends_on' => now()->subDay()->toDateString(),
                'amount' => 500,
            ]);
            $this->fail('Expected exception for past trial end date.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Trial end date must be today or a future date.', $e->getMessage());
        }

        // 2. Negative amount throws exception
        try {
            $checkout->recordTrialExtension($company, [
                'trial_ends_on' => now()->addDays(15)->toDateString(),
                'amount' => -100,
            ]);
            $this->fail('Expected exception for negative amount.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Amount charged must be zero or greater.', $e->getMessage());
        }

        // 3. Valid trial extension creates payment, event, and subscription
        $futureDate = now()->addDays(30)->toDateString();
        $result = $checkout->recordTrialExtension($company, [
            'trial_ends_on' => $futureDate,
            'amount' => 1500.50,
            'reference' => 'REF-TRIAL-001',
            'notes' => 'Extended by support',
        ]);

        $this->assertSame('trial', $result['subscription']->status);
        $this->assertSame($futureDate, $result['subscription']->ends_on->toDateString());
        $this->assertSame($futureDate, $result['subscription']->trial_ends_on->toDateString());
        $this->assertEquals(1500.50, $result['payment']->total_amount);
        $this->assertSame('completed', $result['payment']->status);
        $this->assertSame('trial_extension', $result['payment']->type);

        $this->assertDatabaseHas('subscription_events', [
            'company_id' => $company->id,
            'event_type' => 'trial_extended',
            'payment_id' => $result['payment']->id,
        ]);
    }

    public function test_subscription_checkout_service_apply_upcoming_and_due_subscriptions(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $pricing = Mockery::mock(\App\Services\SubscriptionPricingService::class);
        $usage = Mockery::mock(\App\Services\SubscriptionUsageService::class);

        $checkout = new SubscriptionCheckoutService($adminApi, $pricing, $usage);

        // 1. Company with no subscription returns false
        $this->assertFalse($checkout->applyUpcomingForCompany(999999));

        // 2. Subscription with future end date returns false
        $company = Company::create(['name' => 'Upcoming Test Co']);
        $sub = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonths(6)->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);
        $this->assertFalse($checkout->applyUpcomingForCompany($company->id));

        // 3. Subscription ended in past, but no upcoming subscription -> marks status as expired and returns false
        $sub->update([
            'status' => 'active',
            'ends_on' => now()->subDay()->toDateString(),
        ]);
        $this->assertFalse($checkout->applyUpcomingForCompany($company->id));
        $this->assertSame('expired', $sub->fresh()->status);

        // 4. Pending upcoming subscription exists -> applies and marks active
        $pay1 = \App\Models\SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'renewal',
            'status' => 'completed',
            'subtotal' => 5000,
            'promo_discount' => 0,
            'gst_amount' => 0,
            'total_amount' => 5000,
        ]);

        $upcoming = \App\Models\UpcomingSubscription::forceCreate([
            'company_id' => $company->id,
            'payment_id' => $pay1->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'status' => 'pending',
            'included_branches' => 5,
            'users_per_branch' => 10,
            'included_lr_credits' => 100,
            'included_trucks' => 0,
            'addon_branches' => 1,
            'addon_users' => 2,
            'addon_trucks' => 0,
            'addon_lr_credits' => 50,
        ]);

        $this->assertTrue($checkout->applyUpcomingForCompany($company->id));
        $freshSub = $sub->fresh();
        $this->assertSame('active', $freshSub->status);
        $this->assertSame(5, $freshSub->included_branches);
        $this->assertSame(10, $freshSub->users_per_branch);
        $this->assertSame(150, $freshSub->lr_credits_remaining);
        $this->assertSame('applied', $upcoming->fresh()->status);

        // 5. applyDueUpcomingSubscriptions applies across eligible companies
        $company2 = Company::create(['name' => 'Upcoming Test Co 2']);
        $sub2 = CompanySubscription::create([
            'company_id' => $company2->id,
            'status' => 'active',
            'starts_on' => now()->subYear()->toDateString(),
            'ends_on' => now()->subDay()->toDateString(),
            'included_branches' => 1,
            'users_per_branch' => 1,
        ]);

        $pay2 = \App\Models\SubscriptionPayment::create([
            'company_id' => $company2->id,
            'type' => 'renewal',
            'status' => 'completed',
            'subtotal' => 5000,
            'promo_discount' => 0,
            'gst_amount' => 0,
            'total_amount' => 5000,
        ]);

        \App\Models\UpcomingSubscription::forceCreate([
            'company_id' => $company2->id,
            'payment_id' => $pay2->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'status' => 'pending',
            'included_branches' => 3,
            'users_per_branch' => 4,
            'included_lr_credits' => 50,
            'included_trucks' => 0,
            'addon_branches' => 0,
            'addon_users' => 0,
            'addon_trucks' => 0,
            'addon_lr_credits' => 0,
        ]);

        $appliedCount = $checkout->applyDueUpcomingSubscriptions();
        $this->assertGreaterThanOrEqual(1, $appliedCount);
        $this->assertSame('active', $sub2->fresh()->status);
        $this->assertSame(3, $sub2->fresh()->included_branches);
    }

    public function test_subscription_checkout_service_create_checkout_order_validation_failure(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $pricing = Mockery::mock(SubscriptionPricingService::class);
        $usage = Mockery::mock(SubscriptionUsageService::class);

        $company = Company::create(['name' => 'Order Val Co']);
        $pricing->shouldReceive('quote')->once()->andReturn([
            'can_checkout' => false,
            'blocking_errors' => ['Cannot checkout because plan expired.'],
        ]);

        $checkout = new SubscriptionCheckoutService($adminApi, $pricing, $usage);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot checkout because plan expired.');

        $checkout->createCheckoutOrder($company, ['purchase_type' => 'renewal'], 1);
    }

    public function test_subscription_checkout_service_create_checkout_order_success(): void
    {
        config([
            'subscription.razorpay_key' => 'rzp_test_key_abc',
            'subscription.razorpay_secret' => 'rzp_test_secret_xyz',
            'subscription.product' => 'broker',
        ]);

        $transport = new class implements \WpOrg\Requests\Transport {
            public function request($url, $headers = [], $data = [], $options = [])
            {
                return "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n".json_encode([
                    'id' => 'order_rzp_mock_123',
                    'entity' => 'order',
                    'amount' => 1062000,
                    'currency' => 'INR',
                    'status' => 'created',
                ]);
            }

            public function request_multiple($requests, $options)
            {
                return [];
            }

            public static function test($capabilities = [])
            {
                return true;
            }
        };

        $ref = new \ReflectionClass(\WpOrg\Requests\Requests::class);
        $prop = $ref->getProperty('transport');
        $prevTransport = $prop->getValue();
        $prop->setValue(null, [
            'a:1:{s:3:"ssl";b:1;}' => $transport,
            'a:1:{s:3:"ssl";b:0;}' => $transport,
        ]);

        $adminApi = Mockery::mock(AdminApiClient::class);
        $pricing = Mockery::mock(SubscriptionPricingService::class);
        $usage = Mockery::mock(SubscriptionUsageService::class);

        $company = Company::create(['name' => 'Order Success Co']);
        $user = User::factory()->create();

        $pricing->shouldReceive('quote')->once()->andReturn([
            'can_checkout' => true,
            'blocking_errors' => [],
            'quantities' => ['addon_branches' => 1],
            'subtotal' => 10000,
            'promo_discount' => 1000,
            'gst_amount' => 1620,
            'total_amount' => 10620,
            'catalog_snapshot' => ['included_branches' => 2],
            'current_usage' => ['branches' => 1],
            'purchase_type' => 'renewal',
            'promo_snapshot' => ['code' => 'SAVE10'],
        ]);

        $checkout = new SubscriptionCheckoutService($adminApi, $pricing, $usage);

        $result = $checkout->createCheckoutOrder($company, [
            'purchase_type' => 'renewal',
            'promo_code' => 'SAVE10',
        ], $user->id);

        $this->assertSame('order_rzp_mock_123', $result['razorpay_order_id']);
        $this->assertSame('rzp_test_key_abc', $result['razorpay_key']);
        $this->assertEquals(10620.0, $result['amount']);
        $this->assertSame(1062000, $result['razorpay_amount_paise']);
        $this->assertStringContainsString('TruckBill Broker renewal', $result['description']);

        $payment = SubscriptionPayment::find($result['payment_id']);
        $this->assertNotNull($payment);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('order_rzp_mock_123', $payment->razorpay_order_id);
        $this->assertSame('SAVE10', $payment->promo_code);

        $event = \App\Models\SubscriptionEvent::where('company_id', $company->id)
            ->where('event_type', 'checkout_created')
            ->first();
        $this->assertNotNull($event);
        $this->assertSame('order_rzp_mock_123', $event->metadata['order_id']);

        $prop->setValue(null, $prevTransport);
    }

    public function test_subscription_checkout_service_record_offline_purchase_with_validations_and_fulfillment(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $pricing = Mockery::mock(SubscriptionPricingService::class);
        $usage = Mockery::mock(SubscriptionUsageService::class);

        $company = Company::create(['name' => 'Offline Co']);
        $user = User::factory()->create();

        // 1. Validation failure when can_checkout is false and force is false
        $pricing->shouldReceive('quote')->with($company, Mockery::type('array'))->andReturn([
            'can_checkout' => false,
            'blocking_errors' => ['Offline capacity exceeded.'],
        ]);

        $checkout = new SubscriptionCheckoutService($adminApi, $pricing, $usage);

        try {
            $checkout->recordOfflinePurchase($company, [
                'purchase_type' => 'renewal',
                'force' => false,
            ], $user->id);
            $this->fail('Expected exception for unforced invalid offline purchase.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Offline capacity exceeded.', $e->getMessage());
        }

        // 2. Successful offline purchase with force = true (or can_checkout = true) - renewal
        $quoteSuccess = [
            'purchase_type' => 'renewal',
            'can_checkout' => false,
            'blocking_errors' => ['Blocked'],
            'quantities' => ['addon_branches' => 2, 'addon_users' => 3, 'addon_trucks' => 0, 'addon_lr_credits' => 100],
            'subtotal' => 15000,
            'promo_discount' => 0,
            'gst_amount' => 2700,
            'total_amount' => 17700,
            'catalog_snapshot' => [
                'included_branches' => 3,
                'users_per_branch' => 5,
                'included_lr_credits' => 500,
            ],
            'current_usage' => ['branches' => 2, 'total_users' => 5],
        ];

        $pricing2 = Mockery::mock(SubscriptionPricingService::class);
        $pricing2->shouldReceive('quote')->andReturn($quoteSuccess);

        $checkout2 = new SubscriptionCheckoutService($adminApi, $pricing2, $usage);

        // Delete existing subscription so fulfillCompletedPayment executes the fallback CompanySubscription::create branch
        $company->subscription()->delete();
        $company->unsetRelation('subscription');

        $payment = $checkout2->recordOfflinePurchase($company, [
            'purchase_type' => 'renewal',
            'force' => true,
            'reference' => 'CHQ-OFFLINE-01',
            'notes' => 'Cash received by accounts',
            'admin_user_id' => $user->id,
        ], $user->id);

        $this->assertSame('completed', $payment->status);
        $this->assertEquals(17700, $payment->total_amount);
        $this->assertSame('CHQ-OFFLINE-01', $payment->razorpay_response['reference']);
        $this->assertTrue($payment->razorpay_response['offline']);

        // Check subscription updated
        $sub = $company->fresh()->subscription;
        $this->assertSame('active', $sub->status);
        $this->assertSame(3, $sub->included_branches);
        $this->assertSame(2, $sub->addon_branches);
        $this->assertSame(3, $sub->addon_users);
        $this->assertSame(600, $sub->lr_credits_remaining); // 500 + 100

        // 3. Successful offline purchase - addon type
        $quoteAddon = [
            'purchase_type' => 'addon',
            'can_checkout' => true,
            'blocking_errors' => [],
            'quantities' => ['addon_branches' => 1, 'addon_users' => 2, 'addon_lr_credits' => 50],
            'subtotal' => 5000,
            'promo_discount' => 0,
            'gst_amount' => 900,
            'total_amount' => 5900,
            'catalog_snapshot' => [],
            'current_usage' => [],
        ];

        $pricing3 = Mockery::mock(SubscriptionPricingService::class);
        $pricing3->shouldReceive('quote')->andReturn($quoteAddon);

        $checkout3 = new SubscriptionCheckoutService($adminApi, $pricing3, $usage);

        $addonPayment = $checkout3->recordOfflinePurchase($company, [
            'purchase_type' => 'addon',
            'addon_branches' => 1,
            'addon_users' => 2,
            'addon_lr_credits' => 50,
        ], $user->id);

        $this->assertSame('completed', $addonPayment->status);
        $subAfterAddon = $company->fresh()->subscription;
        $this->assertSame(3, $subAfterAddon->addon_branches); // 2 + 1
        $this->assertSame(5, $subAfterAddon->addon_users);    // 3 + 2
        $this->assertSame(650, $subAfterAddon->lr_credits_remaining); // 600 + 50
    }

    public function test_subscription_checkout_service_fulfill_completed_payment_active_ends_on_branch(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $pricing = Mockery::mock(SubscriptionPricingService::class);
        $usage = Mockery::mock(SubscriptionUsageService::class);

        $checkout = new SubscriptionCheckoutService($adminApi, $pricing, $usage);

        $company = Company::create(['name' => 'Fulfill Active Branch Co']);
        $user = User::factory()->create();

        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'renewal',
            'status' => 'completed',
            'subtotal' => 10000,
            'gst_amount' => 1800,
            'total_amount' => 11800,
            'catalog_snapshot' => [
                'included_branches' => 2,
                'users_per_branch' => 5,
                'included_lr_credits' => 100,
            ],
            'quantities' => [
                'addon_branches' => 0,
                'addon_users' => 0,
                'addon_lr_credits' => 0,
            ],
        ]);

        $company->subscription()->delete();
        $company->unsetRelation('subscription');

        $dynSub = new class extends CompanySubscription {
            protected $table = 'company_subscriptions';
            private int $calls = 0;

            public function isActive(): bool
            {
                return ++$this->calls > 1;
            }
        };
        $dynSub->company_id = $company->id;
        $dynSub->status = 'active';
        $dynSub->starts_on = now()->toDateString();
        $dynSub->ends_on = now()->addDays(30)->toDateString();
        $dynSub->included_branches = 2;
        $dynSub->users_per_branch = 5;
        $dynSub->included_lr_credits = 100;
        $dynSub->lr_credits_remaining = 100;
        $dynSub->save();

        $company->setRelation('subscription', $dynSub);
        $payment->setRelation('company', $company);

        $refMethod = new \ReflectionMethod(SubscriptionCheckoutService::class, 'fulfillCompletedPayment');
        $fulfilled = $refMethod->invoke($checkout, $payment, $user->id, 'payment_completed');

        $this->assertInstanceOf(SubscriptionPayment::class, $fulfilled);
        $this->assertSame('completed', $fulfilled->status);
    }

    public function test_subscription_checkout_service_handle_callback_completed_and_verified_signature(): void
    {
        config([
            'subscription.razorpay_key' => 'rzp_test_key_abc',
            'subscription.razorpay_secret' => 'rzp_test_secret_xyz',
        ]);

        $adminApi = Mockery::mock(AdminApiClient::class);
        $adminApi->shouldReceive('incrementPromoUse')->with('DISCOUNT10')->once();

        $pricing = Mockery::mock(SubscriptionPricingService::class);
        $usage = Mockery::mock(SubscriptionUsageService::class);

        $company = Company::create(['name' => 'Callback Co']);
        $company->subscription->update([
            'status' => 'expired',
            'ends_on' => now()->subDay()->toDateString(),
        ]);
        $user = User::factory()->create();

        $checkout = new SubscriptionCheckoutService($adminApi, $pricing, $usage);

        // 1. If payment is already completed, return immediately
        $alreadyCompleted = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'renewal',
            'status' => 'completed',
            'razorpay_order_id' => 'order_already_done',
            'subtotal' => 5000,
            'total_amount' => 5900,
        ]);

        $returned = $checkout->handleCallback(['razorpay_order_id' => 'order_already_done'], $user->id);
        $this->assertSame($alreadyCompleted->id, $returned->id);

        // 2. Pending payment with valid signature completes and fulfills
        $orderId = 'order_valid_123';
        $paymentId = 'pay_valid_456';
        $signature = hash_hmac('sha256', $orderId.'|'.$paymentId, 'rzp_test_secret_xyz');

        $pendingPayment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'renewal',
            'status' => 'pending',
            'razorpay_order_id' => $orderId,
            'promo_code' => 'DISCOUNT10',
            'subtotal' => 10000,
            'total_amount' => 11800,
            'catalog_snapshot' => [
                'included_branches' => 2,
                'users_per_branch' => 5,
                'included_lr_credits' => 100,
            ],
            'quantities' => ['addon_branches' => 0, 'addon_users' => 0, 'addon_lr_credits' => 0],
        ]);

        $callbackResult = $checkout->handleCallback([
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ], $user->id);

        $this->assertSame('completed', $callbackResult->status);
        $this->assertSame($paymentId, $callbackResult->razorpay_payment_id);
        $this->assertSame($signature, $callbackResult->razorpay_signature);
        $this->assertSame('active', $company->fresh()->subscription->status);
    }

    public function test_subscription_checkout_service_private_helper_methods_via_reflection(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $pricing = Mockery::mock(SubscriptionPricingService::class);
        $usage = Mockery::mock(SubscriptionUsageService::class);

        $checkout = new SubscriptionCheckoutService($adminApi, $pricing, $usage);
        $refClass = new \ReflectionClass($checkout);

        // 1. productDisplayName() across different configs
        $prodMethod = $refClass->getMethod('productDisplayName');
        $prodMethod->setAccessible(true);

        config(['subscription.product' => 'broker']);
        $this->assertSame('TruckBill Broker', $prodMethod->invoke($checkout));

        config(['subscription.product' => 'ownfleet']);
        $this->assertSame('TruckBill Ownfleet', $prodMethod->invoke($checkout));

        config(['subscription.product' => 'parchun']);
        $this->assertSame('TruckBill Parchun', $prodMethod->invoke($checkout));

        config(['subscription.product' => 'custom']);
        $this->assertSame('TruckBill', $prodMethod->invoke($checkout));

        // 2. formatRupee()
        $rupeeMethod = $refClass->getMethod('formatRupee');
        $rupeeMethod->setAccessible(true);
        $this->assertSame('INR 1250.50', $rupeeMethod->invoke($checkout, 1250.5));
        $this->assertSame('INR 0.00', $rupeeMethod->invoke($checkout, 0));

        // 3. normalizeIndianMobile()
        $mobileMethod = $refClass->getMethod('normalizeIndianMobile');
        $mobileMethod->setAccessible(true);
        $this->assertNull($mobileMethod->invoke($checkout, null));
        $this->assertNull($mobileMethod->invoke($checkout, '   '));
        $this->assertNull($mobileMethod->invoke($checkout, 'no-digits-here'));
        $this->assertSame('9876543210', $mobileMethod->invoke($checkout, '9876543210'));
        $this->assertSame('9876543210', $mobileMethod->invoke($checkout, '+91 98765 43210'));
        $this->assertSame('9876543210', $mobileMethod->invoke($checkout, '09876543210'));

        // 4. promoCodeFromQuote()
        $promoMethod = $refClass->getMethod('promoCodeFromQuote');
        $promoMethod->setAccessible(true);
        $this->assertNull($promoMethod->invoke($checkout, []));
        $this->assertNull($promoMethod->invoke($checkout, ['promo_snapshot' => null]));
        $this->assertNull($promoMethod->invoke($checkout, ['promo_snapshot' => ['code' => '']]));
        $this->assertSame('SAVE50', $promoMethod->invoke($checkout, ['promo_snapshot' => ['code' => 'SAVE50']]));

        // 5. razorpayDescription()
        $descMethod = $refClass->getMethod('razorpayDescription');
        $descMethod->setAccessible(true);
        config(['subscription.product' => 'broker']);
        $this->assertSame('TruckBill Broker renewal', $descMethod->invoke($checkout, ['purchase_type' => 'renewal']));
        $this->assertSame('TruckBill Broker subscription', $descMethod->invoke($checkout, []));

        // 6. formatPlanDetails()
        $planMethod = $refClass->getMethod('formatPlanDetails');
        $planMethod->setAccessible(true);

        $quoteWithAll = [
            'catalog_snapshot' => [
                'included_branches' => 2,
                'users_per_branch' => 5,
                'included_lr_credits' => 100,
                'included_trucks' => 5,
            ],
            'quantities' => [
                'addon_branches' => 1,
                'addon_users' => 2,
                'addon_lr_credits' => 50,
                'addon_trucks' => 2,
            ],
            'proration' => ['remaining_days' => 120],
        ];
        $planDetails = $planMethod->invoke($checkout, $quoteWithAll);
        $this->assertStringContainsString('Branches 2+1', $planDetails);
        $this->assertStringContainsString('Users/branch 5+2 users', $planDetails);
        $this->assertStringContainsString('LR credits 100+50', $planDetails);
        $this->assertStringContainsString('Trucks 5+2', $planDetails);
        $this->assertStringContainsString('Prorated 120 days', $planDetails);

        // 7. formatChargesBreakdown()
        $breakdownMethod = $refClass->getMethod('formatChargesBreakdown');
        $breakdownMethod->setAccessible(true);

        $quoteBreakdown = [
            'line_items' => [
                ['label' => 'Annual base plan', 'amount' => 10000],
                ['label' => 'Branch add-on', 'amount' => 2000],
                ['label' => 'Promo discount', 'amount' => -1000],
                'invalid-item-skipped',
            ],
            'promo_snapshot' => ['code' => 'SUPER10'],
            'promo_discount' => 1000,
            'gst_rate_percent' => 18,
            'gst_amount' => 1980,
            'total_amount' => 12980,
        ];
        $breakdown = $breakdownMethod->invoke($checkout, $quoteBreakdown);
        $this->assertStringContainsString('Annual base plan: INR 10000.00', $breakdown);
        $this->assertStringContainsString('Promo discount (SUPER10): INR -1000.00', $breakdown);
        $this->assertStringContainsString('GST (18%): INR 1980.00', $breakdown);
        $this->assertStringContainsString('Total: INR 12980.00', $breakdown);

        // formatChargesBreakdown without promo line item in line_items but with promo_discount
        $quoteBreakdownNoLine = [
            'line_items' => [
                ['label' => 'Annual base plan', 'amount' => 10000],
            ],
            'promo_snapshot' => ['code' => 'AUTO10'],
            'promo_discount' => 500,
            'gst_rate_percent' => null,
            'gst_amount' => 1710,
            'total_amount' => 11210,
        ];
        $breakdown2 = $breakdownMethod->invoke($checkout, $quoteBreakdownNoLine);
        $this->assertStringContainsString('Promo discount (AUTO10): -INR 500.00', $breakdown2);
        $this->assertStringContainsString('GST: INR 1710.00', $breakdown2);

        // 8. razorpayCheckoutMeta()
        $metaMethod = $refClass->getMethod('razorpayCheckoutMeta');
        $metaMethod->setAccessible(true);

        $user = User::factory()->create(['email' => 'actor@example.com']);
        $company = Company::create([
            'name' => 'Meta Test Co',
            'contact_person' => 'Ramesh Kumar',
            'contact_number' => '+91 9876543210',
            'gst_number' => '07AAAAA0000A1Z5',
            'pan_number' => 'AAAAA0000A',
            'address' => '123 Industrial Area, Sector 5',
        ]);

        $meta = $metaMethod->invoke($checkout, $company, $user->id, [
            'purchase_type' => 'renewal',
            'promo_discount' => 500,
            'promo_snapshot' => ['code' => 'META50'],
            'catalog_snapshot' => ['included_branches' => 2],
            'quantities' => [],
            'gst_rate_percent' => 18,
            'gst_amount' => 900,
            'total_amount' => 5900,
            'line_items' => [],
        ]);

        $this->assertIsArray($meta['notes']);
        $this->assertIsArray($meta['prefill']);
        $this->assertSame('Meta Test Co', $meta['notes']['company_name']);
        $this->assertSame('META50', $meta['notes']['promo_code']);
        $this->assertSame('INR 500.00', $meta['notes']['promo_discount']);
        $this->assertSame('Ramesh Kumar', $meta['prefill']['name']);
        $this->assertSame('actor@example.com', $meta['prefill']['email']);
        $this->assertSame('9876543210', $meta['prefill']['contact']);
    }

    public function test_subscription_checkout_service_fulfill_completed_payment_for_active_subscription_and_promo_exception(): void
    {
        config([
            'subscription.razorpay_key' => 'rzp_test_key_abc',
            'subscription.razorpay_secret' => 'rzp_test_secret_xyz',
        ]);

        if (\Illuminate\Support\Facades\Schema::hasColumn('upcoming_subscriptions', 'starts_on')) {
            \Illuminate\Support\Facades\Schema::table('upcoming_subscriptions', function (\Illuminate\Database\Schema\Blueprint $t) {
                $t->dropColumn(['starts_on', 'ends_on']);
            });
        }

        $adminApi = Mockery::mock(AdminApiClient::class);
        $adminApi->shouldReceive('incrementPromoUse')
            ->with('FAILPROMO')
            ->once()
            ->andThrow(new \RuntimeException('Admin API network down'));

        $pricing = Mockery::mock(SubscriptionPricingService::class);
        $usage = Mockery::mock(SubscriptionUsageService::class);

        $company = Company::create(['name' => 'Active Sub Co']);
        // Ensure company's subscription is currently active
        $company->subscription->update([
            'status' => 'active',
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addMonths(6)->toDateString(),
        ]);
        $user = User::factory()->create();

        $checkout = new SubscriptionCheckoutService($adminApi, $pricing, $usage);

        $orderId = 'order_active_promo_err';
        $paymentId = 'pay_active_promo_err';
        $signature = hash_hmac('sha256', $orderId.'|'.$paymentId, 'rzp_test_secret_xyz');

        $pendingPayment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'renewal',
            'status' => 'pending',
            'razorpay_order_id' => $orderId,
            'promo_code' => 'FAILPROMO',
            'subtotal' => 20000,
            'total_amount' => 21240,
            'catalog_snapshot' => [
                'included_branches' => 3,
                'users_per_branch' => 5,
                'included_lr_credits' => 300,
            ],
            'quantities' => [
                'addon_branches' => 2,
                'addon_users' => 4,
                'addon_trucks' => 0,
                'addon_lr_credits' => 150,
            ],
        ]);

        $callbackResult = $checkout->handleCallback([
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
        ], $user->id);

        $this->assertSame('completed', $callbackResult->status);

        // Verify UpcomingSubscription was created instead of overwriting active subscription
        $upcoming = \App\Models\UpcomingSubscription::where('company_id', $company->id)->first();
        $this->assertNotNull($upcoming);
        $this->assertSame('pending', $upcoming->status);
        $this->assertSame(3, $upcoming->included_branches);
        $this->assertSame(5, $upcoming->users_per_branch);
        $this->assertSame(300, $upcoming->included_lr_credits);
        $this->assertSame(2, $upcoming->addon_branches);
        $this->assertSame(4, $upcoming->addon_users);
        $this->assertSame(150, $upcoming->addon_lr_credits);
        $this->assertSame(['code' => 'FAILPROMO'], $upcoming->promo_snapshot);

        // Verify original subscription remains untouched in active state
        $sub = $company->fresh()->subscription;
        $this->assertSame('active', $sub->status);

        // Verify subscription event
        $event = \App\Models\SubscriptionEvent::where('company_id', $company->id)
            ->where('event_type', 'payment_completed')
            ->first();
        $this->assertNotNull($event);
        $this->assertSame('renewal', $event->metadata['type']);
    }

    // ==========================================
    // SubscriptionPricingService Tests
    // ==========================================

    public function test_subscription_pricing_service_minimum_renewal_addons(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $usage = Mockery::mock(SubscriptionUsageService::class);
        $company = Company::create(['name' => 'Min Renewal Co']);
        $catalog = ['included_branches' => 2];

        $usage->shouldReceive('minimumRenewalAddons')
            ->once()
            ->with($company, $catalog)
            ->andReturn(['addon_branches' => 3, 'addon_users' => 5]);

        $service = new SubscriptionPricingService($adminApi, $usage);
        $result = $service->minimumRenewalAddons($company, $catalog);

        $this->assertSame(['addon_branches' => 3, 'addon_users' => 5], $result);
    }

    public function test_subscription_pricing_service_create_trial_subscription_with_catalog_and_fallback(): void
    {
        $usage = Mockery::mock(SubscriptionUsageService::class);

        // 1. With successful catalog payload from Admin API
        $adminApi = Mockery::mock(AdminApiClient::class);
        $adminApi->shouldReceive('getCatalog')
            ->once()
            ->andReturn([
                'catalog' => [
                    'included_branches' => 3,
                    'users_per_branch' => 8,
                    'included_lr_credits' => 250,
                ],
            ]);

        $company1 = Company::create(['name' => 'Trial Co 1']);
        $service1 = new SubscriptionPricingService($adminApi, $usage);
        $sub1 = $service1->createTrialSubscription($company1);

        $this->assertSame('trial', $sub1->status);
        $this->assertSame(3, $sub1->included_branches);
        $this->assertSame(8, $sub1->users_per_branch);
        $this->assertSame(250, $sub1->included_lr_credits);
        $this->assertSame(250, $sub1->lr_credits_remaining);
        $this->assertSame(0, $sub1->included_trucks);
        $this->assertSame(now()->toDateString(), $sub1->starts_on->toDateString());
        $this->assertSame(now()->addDays(15)->toDateString(), $sub1->ends_on->toDateString());
        $this->assertSame(now()->addDays(15)->toDateString(), $sub1->trial_ends_on->toDateString());

        // 2. Fallback when Admin API throws Throwable
        $failingAdminApi = Mockery::mock(AdminApiClient::class);
        $failingAdminApi->shouldReceive('getCatalog')
            ->once()
            ->andThrow(new \RuntimeException('Admin API down'));

        $company2 = Company::create(['name' => 'Trial Co 2']);
        $service2 = new SubscriptionPricingService($failingAdminApi, $usage);
        $sub2 = $service2->createTrialSubscription($company2);

        $this->assertSame('trial', $sub2->status);
        $this->assertSame(1, $sub2->included_branches);
        $this->assertSame(5, $sub2->users_per_branch);
        $this->assertSame(100, $sub2->included_lr_credits);
        $this->assertSame(100, $sub2->lr_credits_remaining);
    }

    public function test_subscription_pricing_service_quote_renewal_clean_and_with_addons(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $adminApi->shouldReceive('getCatalog')->andReturn([
            'catalog' => [
                'base_price' => 10000,
                'addon_branch_price' => 2000,
                'addon_user_price' => 500,
                'included_branches' => 2,
                'users_per_branch' => 5,
            ],
            'gst_rate_percent' => 18,
        ]);

        $usage = Mockery::mock(SubscriptionUsageService::class);
        $usage->shouldReceive('usageForCompany')->andReturn(['branches' => 2, 'total_users' => 6]);
        $usage->shouldReceive('minimumRenewalAddons')->andReturn(['addon_branches' => 0, 'addon_users' => 0]);
        $usage->shouldReceive('overflowUsersUsed')->andReturn(0);

        $company = Company::create(['name' => 'Renewal Co']);
        $service = new SubscriptionPricingService($adminApi, $usage);

        $quote = $service->quote($company, [
            'purchase_type' => 'renewal',
            'addon_branches' => 1,
            'addon_users' => 2,
            'addon_trucks' => 0,
            'addon_lr_credits' => 50,
        ]);

        $this->assertSame('renewal', $quote['purchase_type']);
        $this->assertTrue($quote['can_checkout']);
        $this->assertEmpty($quote['blocking_errors']);
        $this->assertNull($quote['proration']);

        // Check line items: base plan (10000) + branch addon (2000) + user addon (1000) + lr credit addon (0)
        $this->assertCount(4, $quote['line_items']);
        $this->assertSame('Annual base plan', $quote['line_items'][0]['label']);
        $this->assertEquals(10000.0, $quote['line_items'][0]['amount']);
        $this->assertSame('Branch add-ons (1)', $quote['line_items'][1]['label']);
        $this->assertEquals(2000.0, $quote['line_items'][1]['amount']);
        $this->assertSame('User add-ons (2)', $quote['line_items'][2]['label']);
        $this->assertEquals(1000.0, $quote['line_items'][2]['amount']);
        $this->assertSame('LR credit add-ons (50)', $quote['line_items'][3]['label']);
        $this->assertEquals(0.0, $quote['line_items'][3]['amount']);

        // Math checks: Subtotal = 13000, Taxable = 13000, GST = 2340 (18%), Total = 15340
        $this->assertEquals(13000.0, $quote['subtotal']);
        $this->assertEquals(0.0, $quote['promo_discount']);
        $this->assertEquals(2340.0, $quote['gst_amount']);
        $this->assertEquals(15340.0, $quote['total_amount']);
    }

    public function test_subscription_pricing_service_quote_renewal_capacity_errors(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $adminApi->shouldReceive('getCatalog')->andReturn([
            'catalog' => [
                'base_price' => 10000,
                'addon_branch_price' => 2000,
                'addon_user_price' => 500,
                'included_branches' => 2,
                'users_per_branch' => 3,
            ],
            'gst_rate_percent' => 18,
        ]);

        $usage = Mockery::mock(SubscriptionUsageService::class);
        // Usage: 4 branches (included is 2), 15 users (slots would be 2*3 = 6), overflow users = 3
        $usage->shouldReceive('usageForCompany')->andReturn(['branches' => 4, 'total_users' => 15]);
        $usage->shouldReceive('minimumRenewalAddons')->andReturn(['addon_branches' => 2, 'addon_users' => 9]);
        $usage->shouldReceive('overflowUsersUsed')->with(Mockery::type(Company::class), 3)->andReturn(3);

        $company = Company::create(['name' => 'Capacity Error Co']);
        $service = new SubscriptionPricingService($adminApi, $usage);

        // Input with 0 addons triggers all 3 capacity error branches
        $quote = $service->quote($company, [
            'purchase_type' => 'renewal',
            'addon_branches' => 0,
            'addon_users' => 0,
        ]);

        $this->assertFalse($quote['can_checkout']);
        $this->assertCount(3, $quote['blocking_errors']);
        $this->assertStringContainsString('Add at least 2 branch add-on(s)', $quote['blocking_errors'][0]);
        $this->assertStringContainsString('Add more user add-ons, or remove users before renewing.', $quote['blocking_errors'][1]);
        $this->assertStringContainsString('Add at least 3 user add-on(s) for branches over the per-branch limit', $quote['blocking_errors'][2]);
    }

    public function test_subscription_pricing_service_quote_addon_active_subscription_prorated(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $adminApi->shouldReceive('getCatalog')->andReturn([
            'catalog' => [
                'base_price' => 10000,
                'addon_branch_price' => 3650, // 3650 / 365 = 10 per day
                'addon_user_price' => 730,    // 730 / 365 = 2 per day
                'included_branches' => 2,
                'users_per_branch' => 5,
            ],
            'gst_rate_percent' => 18,
        ]);

        $usage = Mockery::mock(SubscriptionUsageService::class);
        $usage->shouldReceive('usageForCompany')->andReturn(['branches' => 1, 'total_users' => 2]);
        $usage->shouldReceive('minimumRenewalAddons')->andReturn(['addon_branches' => 0, 'addon_users' => 0]);

        $company = Company::create(['name' => 'Prorated Addon Co']);
        $sub = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDays(65)->toDateString(),
            'ends_on' => now()->addDays(100)->toDateString(),
            'included_branches' => 2,
            'users_per_branch' => 5,
        ]);

        $service = new SubscriptionPricingService($adminApi, $usage);

        $quote = $service->quote($company, [
            'purchase_type' => 'addon',
            'addon_branches' => 2,
            'addon_users' => 1,
            'addon_lr_credits' => 100,
        ]);

        $this->assertSame('addon', $quote['purchase_type']);
        $this->assertTrue($quote['can_checkout']);
        $this->assertNotNull($quote['proration']);
        $this->assertSame($sub->remainingDaysInclusive(), $quote['proration']['remaining_days']);
        $this->assertSame(SubscriptionProration::ANNUAL_DAYS, $quote['proration']['annual_days']);

        // Check line items: labels include prorated note
        $this->assertStringContainsString('Branch add-ons (2,', $quote['line_items'][0]['label']);
        $this->assertStringContainsString('days prorated)', $quote['line_items'][0]['label']);
        $this->assertStringContainsString('User add-ons (1,', $quote['line_items'][1]['label']);
        $this->assertStringContainsString('LR credit add-ons (100)', $quote['line_items'][2]['label']);
        $this->assertEquals(0.0, $quote['line_items'][2]['amount']);
    }

    public function test_subscription_pricing_service_quote_addon_blocked_when_subscription_inactive(): void
    {
        $adminApi = Mockery::mock(AdminApiClient::class);
        $adminApi->shouldReceive('getCatalog')->andReturn([
            'catalog' => [
                'base_price' => 10000,
                'addon_branch_price' => 2000,
                'addon_user_price' => 500,
                'included_branches' => 2,
                'users_per_branch' => 5,
            ],
            'gst_rate_percent' => 18,
        ]);

        $usage = Mockery::mock(SubscriptionUsageService::class);
        $usage->shouldReceive('usageForCompany')->andReturn(['branches' => 1, 'total_users' => 2]);
        $usage->shouldReceive('minimumRenewalAddons')->andReturn(['addon_branches' => 0, 'addon_users' => 0]);

        // Case 1: Company with expired subscription
        $company1 = Company::create(['name' => 'Expired Addon Co']);
        $company1->subscription->update([
            'status' => 'expired',
            'starts_on' => now()->subYear()->toDateString(),
            'ends_on' => now()->subDay()->toDateString(),
        ]);

        $service = new SubscriptionPricingService($adminApi, $usage);

        $quote1 = $service->quote($company1, [
            'purchase_type' => 'addon',
            'addon_branches' => 1,
        ]);

        $this->assertFalse($quote1['can_checkout']);
        $this->assertContains('Add-ons are only available for active subscriptions.', $quote1['blocking_errors']);

        // Case 2: Company with no subscription
        $company2 = Company::create(['name' => 'No Sub Addon Co']);
        $company2->subscription()->delete();
        $company2->unsetRelation('subscription');

        $quote2 = $service->quote($company2, [
            'purchase_type' => 'addon',
            'addon_users' => 1,
        ]);

        $this->assertFalse($quote2['can_checkout']);
        $this->assertContains('Add-ons are only available for active subscriptions.', $quote2['blocking_errors']);
    }

    public function test_subscription_pricing_service_quote_promos_percentage_fixed_and_invalid(): void
    {
        $catalogPayload = [
            'catalog' => [
                'base_price' => 10000,
                'addon_branch_price' => 2000,
                'addon_user_price' => 500,
                'included_branches' => 5,
                'users_per_branch' => 5,
            ],
            'gst_rate_percent' => 18,
        ];

        $usage = Mockery::mock(SubscriptionUsageService::class);
        $usage->shouldReceive('usageForCompany')->andReturn(['branches' => 1, 'total_users' => 1]);
        $usage->shouldReceive('minimumRenewalAddons')->andReturn(['addon_branches' => 0, 'addon_users' => 0]);
        $usage->shouldReceive('overflowUsersUsed')->andReturn(0);

        $company = Company::create(['name' => 'Promo Co']);

        // 1. Percentage promo code
        $adminApiPct = Mockery::mock(AdminApiClient::class);
        $adminApiPct->shouldReceive('getCatalog')->andReturn($catalogPayload);
        $adminApiPct->shouldReceive('validatePromo')->with('PCT25')->once()->andReturn([
            'valid' => true,
            'promo' => [
                'code' => 'PCT25',
                'discount_type' => 'percentage',
                'discount_value' => 25,
            ],
        ]);

        $servicePct = new SubscriptionPricingService($adminApiPct, $usage);
        $quotePct = $servicePct->quote($company, [
            'purchase_type' => 'renewal',
            'promo_code' => 'PCT25',
            'promo_validated' => true,
        ]);

        // Base 10000, 25% discount = 2500, Taxable = 7500, GST 18% = 1350, Total = 8850
        $this->assertEquals(2500.0, $quotePct['promo_discount']);
        $this->assertEquals(1350.0, $quotePct['gst_amount']);
        $this->assertEquals(8850.0, $quotePct['total_amount']);
        $this->assertSame('PCT25', $quotePct['promo_snapshot']['code']);

        // 2. Fixed amount promo code
        $adminApiFixed = Mockery::mock(AdminApiClient::class);
        $adminApiFixed->shouldReceive('getCatalog')->andReturn($catalogPayload);
        $adminApiFixed->shouldReceive('validatePromo')->with('FLAT1000')->once()->andReturn([
            'valid' => true,
            'promo' => [
                'code' => 'FLAT1000',
                'discount_type' => 'fixed',
                'discount_value' => 1000,
            ],
        ]);

        $serviceFixed = new SubscriptionPricingService($adminApiFixed, $usage);
        $quoteFixed = $serviceFixed->quote($company, [
            'purchase_type' => 'renewal',
            'promo_code' => 'FLAT1000',
            'promo_validated' => true,
        ]);

        // Base 10000, 1000 discount, Taxable = 9000, GST 18% = 1620, Total = 10620
        $this->assertEquals(1000.0, $quoteFixed['promo_discount']);
        $this->assertEquals(1620.0, $quoteFixed['gst_amount']);
        $this->assertEquals(10620.0, $quoteFixed['total_amount']);

        // 3. Invalid promo code
        $adminApiInvalid = Mockery::mock(AdminApiClient::class);
        $adminApiInvalid->shouldReceive('getCatalog')->andReturn($catalogPayload);
        $adminApiInvalid->shouldReceive('validatePromo')->with('INVALID')->once()->andReturn([
            'valid' => false,
        ]);

        $serviceInvalid = new SubscriptionPricingService($adminApiInvalid, $usage);
        $quoteInvalid = $serviceInvalid->quote($company, [
            'purchase_type' => 'renewal',
            'promo_code' => 'INVALID',
            'promo_validated' => true,
        ]);

        $this->assertEquals(0.0, $quoteInvalid['promo_discount']);
        $this->assertNull($quoteInvalid['promo_snapshot']);

        // 4. Promo code present but promo_validated is false
        $adminApiUnval = Mockery::mock(AdminApiClient::class);
        $adminApiUnval->shouldReceive('getCatalog')->andReturn($catalogPayload);
        $adminApiUnval->shouldNotReceive('validatePromo');

        $serviceUnval = new SubscriptionPricingService($adminApiUnval, $usage);
        $quoteUnval = $serviceUnval->quote($company, [
            'purchase_type' => 'renewal',
            'promo_code' => 'UNVALIDATED',
            'promo_validated' => false,
        ]);

        $this->assertEquals(0.0, $quoteUnval['promo_discount']);
    }
}

