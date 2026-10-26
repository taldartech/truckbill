<?php

namespace Tests\Feature\Subscription;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\UserRole;
use App\Services\SubscriptionCheckoutService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class SubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);

        Config::set('subscription.admin_api_url', 'https://controlplane.truckbill.test');
        Config::set('subscription.signing_secret', 'admin-api-secret');
        Config::set('subscription.product', 'broker');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @return array{super: User, company: Company} */
    protected function superAdminWithCompany(array $subscriptionOverrides = []): array
    {
        $super = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $company = Company::create([
            'name' => 'Checkout Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'user_id' => $super->id,
            'is_blocked' => false,
        ]);

        CompanySubscription::create(array_merge([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => Carbon::today()->subMonth(),
            'ends_on' => Carbon::today()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
            'lr_credits_remaining' => 50,
        ], $subscriptionOverrides));

        return ['super' => $super, 'company' => $company];
    }

    protected function fakeCatalog(): void
    {
        Http::fake([
            '*/subscription-catalog' => Http::response([
                'catalog' => [
                    'base_price' => 5000,
                    'addon_branch_price' => 1000,
                    'addon_user_price' => 500,
                    'included_branches' => 2,
                    'users_per_branch' => 3,
                    'included_lr_credits' => 100,
                    'included_trucks' => 10,
                ],
                'gst_rate_percent' => 18.0,
            ]),
        ]);
    }

    public function test_super_admin_can_view_manage_and_checkout_pages(): void
    {
        $this->fakeCatalog();
        ['super' => $super, 'company' => $company] = $this->superAdminWithCompany();

        SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'renewal',
            'status' => 'completed',
            'subtotal' => 5000,
            'total_amount' => 5900,
            'promo_discount' => 0,
            'gst_amount' => 900,
        ]);

        $this->actingAs($super)->get(route('subscription.show', ['company_id' => $company->id]))
            ->assertOk()
            ->assertViewIs('subscription.show')
            ->assertSee('Checkout Co', false);

        $this->actingAs($super)->get(route('subscription.checkout.form', [
            'company_id' => $company->id,
            'purchase_type' => 'renewal',
        ]))
            ->assertOk()
            ->assertViewIs('subscription.checkout');

        $this->actingAs($super)->get(route('subscription.history', ['company_id' => $company->id]))
            ->assertOk()
            ->assertViewIs('subscription.history');
    }

    public function test_show_forbidden_for_company_not_owned_by_super_admin(): void
    {
        $owner = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $other = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $company = Company::create([
            'name' => 'Foreign Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'user_id' => $owner->id,
        ]);

        $this->actingAs($other)->get(route('subscription.show', ['company_id' => $company->id]))
            ->assertForbidden();
    }

    public function test_checkout_form_redirects_addon_when_subscription_expired(): void
    {
        $this->fakeCatalog();
        ['super' => $super, 'company' => $company] = $this->superAdminWithCompany([
            'status' => 'expired',
            'ends_on' => Carbon::today()->subMonth(),
        ]);

        $this->actingAs($super)->get(route('subscription.checkout.form', [
            'company_id' => $company->id,
            'purchase_type' => 'addon',
        ]))
            ->assertRedirect(route('subscription.show', ['company_id' => $company->id]))
            ->assertSessionHas('error');
    }

    public function test_checkout_form_redirects_when_catalog_unavailable(): void
    {
        Http::fake(['*/subscription-catalog' => Http::response([], 500)]);
        ['super' => $super, 'company' => $company] = $this->superAdminWithCompany();

        $this->actingAs($super)->get(route('subscription.checkout.form', [
            'company_id' => $company->id,
        ]))
            ->assertRedirect(route('subscription.show', ['company_id' => $company->id]))
            ->assertSessionHas('error');
    }

    public function test_show_page_renders_when_catalog_unavailable(): void
    {
        Http::fake(['*/subscription-catalog' => Http::response([], 500)]);
        ['super' => $super, 'company' => $company] = $this->superAdminWithCompany();

        $this->actingAs($super)->get(route('subscription.show', ['company_id' => $company->id]))
            ->assertOk()
            ->assertViewIs('subscription.show')
            ->assertViewHas('catalog', null);
    }

    public function test_quote_endpoint_returns_pricing_json(): void
    {
        $this->fakeCatalog();
        ['super' => $super, 'company' => $company] = $this->superAdminWithCompany();

        $response = $this->actingAs($super)->postJson(route('subscription.quote'), [
            'company_id' => $company->id,
            'purchase_type' => 'renewal',
            'addon_branches' => 0,
            'addon_users' => 0,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['total_amount', 'subtotal', 'gst_amount', 'can_checkout']);
    }

    public function test_quote_returns_503_when_catalog_unavailable(): void
    {
        Http::fake(['*/subscription-catalog' => Http::response([], 500)]);
        ['super' => $super, 'company' => $company] = $this->superAdminWithCompany();

        $this->actingAs($super)->postJson(route('subscription.quote'), [
            'company_id' => $company->id,
            'purchase_type' => 'renewal',
        ])->assertStatus(503)->assertJsonPath('can_checkout', false);
    }

    public function test_validate_promo_returns_success_when_code_is_valid(): void
    {
        Http::fake([
            '*/promo-codes/validate' => Http::response([
                'valid' => true,
                'promo' => ['code' => 'SAVE10', 'discount_type' => 'percentage', 'discount_value' => 10],
            ]),
        ]);

        ['super' => $super] = $this->superAdminWithCompany();

        $this->actingAs($super)->postJson(route('subscription.promo.validate'), ['code' => 'SAVE10'])
            ->assertOk()
            ->assertJsonPath('valid', true);
    }

    public function test_validate_promo_returns_422_when_code_is_invalid(): void
    {
        Http::fake([
            '*/promo-codes/validate' => Http::response(['valid' => false, 'message' => 'Invalid code']),
        ]);

        ['super' => $super] = $this->superAdminWithCompany();

        $this->actingAs($super)->postJson(route('subscription.promo.validate'), ['code' => 'BAD'])
            ->assertStatus(422)
            ->assertJsonPath('valid', false);
    }

    public function test_checkout_and_callback_endpoints_delegate_to_checkout_service(): void
    {
        $this->fakeCatalog();
        ['super' => $super, 'company' => $company] = $this->superAdminWithCompany();

        $checkoutMock = Mockery::mock(SubscriptionCheckoutService::class);
        $checkoutMock->shouldReceive('applyUpcomingForCompany')->andReturn(false);
        $checkoutMock->shouldReceive('createCheckoutOrder')->once()->andReturn([
            'payment_id' => 99,
            'razorpay_order_id' => 'order_test_1',
            'razorpay_key' => 'rzp_test',
            'amount' => 5900.0,
        ]);
        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'renewal',
            'status' => 'completed',
            'subtotal' => 5000,
            'total_amount' => 5900,
            'promo_discount' => 0,
            'gst_amount' => 900,
            'razorpay_order_id' => 'order_test_1',
        ]);
        $checkoutMock->shouldReceive('handleCallback')->once()->andReturn($payment);
        $this->app->instance(SubscriptionCheckoutService::class, $checkoutMock);

        $this->actingAs($super)->postJson(route('subscription.checkout'), [
            'company_id' => $company->id,
            'purchase_type' => 'renewal',
        ])->assertOk()->assertJsonPath('razorpay_order_id', 'order_test_1');

        $this->actingAs($super)->postJson(route('subscription.callback'), [
            'razorpay_order_id' => 'order_test_1',
            'razorpay_payment_id' => 'pay_test_1',
            'razorpay_signature' => 'sig_test_1',
        ])->assertOk()->assertJsonPath('success', true);
    }
}
