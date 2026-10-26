<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ControlPlaneSubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNING_SECRET = 'super-secret-onboarding-token-12345';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('onboarding.signing_secret', self::SIGNING_SECRET);
        Config::set('subscription.admin_api_url', 'https://controlplane.truckbill.test');
        Config::set('subscription.signing_secret', 'admin-api-secret');
        Config::set('subscription.product', 'broker');
    }

    private function signedHeaders(string $body = '', ?int $customTimestamp = null, ?string $customSecret = null): array
    {
        $timestamp = (string) ($customTimestamp ?? time());
        $secret = $customSecret ?? self::SIGNING_SECRET;
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        return [
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function test_signature_verification_missing_headers_returns_401(): void
    {
        $response = $this->get('/api/admin/subscriptions', ['Accept' => 'application/json']);
        $response->assertStatus(401)
            ->assertJson(['message' => 'Missing signature headers.']);
    }

    public function test_signature_verification_non_digit_timestamp_returns_401(): void
    {
        $response = $this->get('/api/admin/subscriptions', [
            'X-Timestamp' => 'not-a-number',
            'X-Signature' => 'dummy',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid timestamp.']);
    }

    public function test_signature_verification_expired_timestamp_returns_401(): void
    {
        $expiredTime = time() - 301;
        $headers = $this->signedHeaders('', $expiredTime);

        $response = $this->get('/api/admin/subscriptions', $headers);
        $response->assertStatus(401)
            ->assertJson(['message' => 'Request timestamp expired.']);
    }

    public function test_signature_verification_invalid_signature_returns_401(): void
    {
        $headers = $this->signedHeaders('', time(), 'wrong-secret');

        $response = $this->get('/api/admin/subscriptions', $headers);
        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid signature.']);
    }

    public function test_signature_verification_when_secret_not_configured_returns_503(): void
    {
        Config::set('onboarding.signing_secret', null);

        $headers = $this->signedHeaders();
        $response = $this->get('/api/admin/subscriptions', $headers);
        $response->assertStatus(503)
            ->assertJson(['message' => 'Onboarding is not configured.']);
    }

    public function test_index_lists_companies_with_subscription_and_usage(): void
    {
        $c1 = Company::create(['name' => 'Alpha Transport', 'contact_person' => 'Ramesh Kumar']);
        CompanySubscription::create([
            'company_id' => $c1->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addMonths(11),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $c2 = Company::create(['name' => 'Beta Logistics', 'contact_person' => 'Suresh Patel']);

        $headers = $this->signedHeaders();
        $response = $this->get('/api/admin/subscriptions', $headers);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Alpha Transport', 'status' => 'active'])
            ->assertJsonFragment(['name' => 'Beta Logistics', 'status' => 'trial']);
    }

    public function test_search_filters_companies_by_name_and_contact_person(): void
    {
        Company::create(['name' => 'Chennai Express', 'contact_person' => 'Dinesh']);
        Company::create(['name' => 'Delhi Cargo', 'contact_person' => 'Vikas']);

        $headers = $this->signedHeaders();
        $response = $this->get('/api/admin/subscriptions/search?q=Delhi', $headers);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Delhi Cargo', $response->json('data.0.name'));
    }

    public function test_show_returns_company_subscription_details_payments_and_upcoming(): void
    {
        $company = Company::create(['name' => 'Star Freight']);
        $sub = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'renewal',
            'status' => 'completed',
            'subtotal' => 10000,
            'total_amount' => 11800,
            'promo_discount' => 0,
            'gst_amount' => 1800,
        ]);

        $headers = $this->signedHeaders();
        $response = $this->get("/api/admin/subscriptions/{$company->id}", $headers);

        $response->assertOk()
            ->assertJsonPath('company.id', $company->id)
            ->assertJsonPath('company.name', 'Star Freight')
            ->assertJsonPath('subscription.id', $sub->id)
            ->assertJsonCount(1, 'payments');
    }

    public function test_show_non_existent_company_returns_404(): void
    {
        $headers = $this->signedHeaders();
        $response = $this->get('/api/admin/subscriptions/99999', $headers);
        $response->assertNotFound();
    }

    public function test_quote_calculates_pricing_breakdown(): void
    {
        $company = Company::create(['name' => 'Quote Logistics']);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

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

        $payload = [
            'purchase_type' => 'renewal',
            'addon_branches' => 1,
            'addon_users' => 2,
        ];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $response = $this->withHeaders($headers)->postJson("/api/admin/subscriptions/{$company->id}/quote", $payload);

        $response->assertOk()
            ->assertJsonPath('purchase_type', 'renewal')
            ->assertJsonStructure(['total_amount', 'subtotal', 'gst_amount', 'line_items']);
    }

    public function test_extend_trial_updates_dates_and_creates_payment(): void
    {
        $company = Company::create(['name' => 'Trial Co']);
        $futureDate = now()->addDays(30)->toDateString();

        $payload = [
            'trial_ends_on' => $futureDate,
            'amount' => 1500.0,
            'reference' => 'CHQ-987654',
            'notes' => 'Customer requested 30 days trial extension',
        ];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $response = $this->withHeaders($headers)
            ->postJson("/api/admin/subscriptions/{$company->id}/extend-trial", $payload);

        $response->assertOk()
            ->assertJsonPath('subscription.status', 'trial');

        $this->assertEquals(1500.0, (float) $response->json('payment.total_amount'));
        $this->assertSame($futureDate, $company->fresh(['subscription'])->subscription->trial_ends_on->toDateString());

        $this->assertDatabaseHas('subscription_payments', [
            'company_id' => $company->id,
            'type' => 'trial_extension',
            'total_amount' => 1500,
        ]);

        $this->assertDatabaseHas('subscription_events', [
            'company_id' => $company->id,
            'event_type' => 'trial_extended',
        ]);
    }

    public function test_extend_trial_rejects_past_date_or_negative_amount(): void
    {
        $company = Company::create(['name' => 'Trial Co 2']);

        $payload = [
            'trial_ends_on' => now()->subDay()->toDateString(),
            'amount' => -100,
        ];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $response = $this->withHeaders($headers)
            ->postJson("/api/admin/subscriptions/{$company->id}/extend-trial", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trial_ends_on', 'amount']);
    }

    public function test_patch_subscription_updates_fields_and_creates_event_audit(): void
    {
        $company = Company::create(['name' => 'Patch Co']);
        $sub = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'trial',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addMonth(),
            'included_branches' => 2,
            'users_per_branch' => 3,
            'lr_credits_remaining' => 10,
        ]);

        $payload = [
            'status' => 'active',
            'included_branches' => 5,
            'lr_credits_remaining' => 100,
            'notes' => 'Upgraded by sales support',
        ];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $response = $this->withHeaders($headers)
            ->patchJson("/api/admin/subscriptions/{$company->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('subscription.included_branches', 5)
            ->assertJsonPath('subscription.lr_credits_remaining', 100);

        $sub->refresh();
        $this->assertSame('active', $sub->status);
        $this->assertSame(5, $sub->included_branches);
        $this->assertSame(100, $sub->lr_credits_remaining);

        $this->assertDatabaseHas('subscription_events', [
            'company_id' => $company->id,
            'event_type' => 'admin_override',
        ]);
    }

    public function test_patch_subscription_rejects_empty_changes_with_422(): void
    {
        $company = Company::create(['name' => 'No Change Co']);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => '2026-01-01',
            'ends_on' => '2027-01-01',
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        // Sending same status as current
        $payload = ['status' => 'active'];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $response = $this->withHeaders($headers)
            ->patchJson("/api/admin/subscriptions/{$company->id}", $payload);

        $response->assertStatus(422)
            ->assertJson(['message' => 'No changes to save.']);
    }

    public function test_index_filters_with_q_parameter(): void
    {
        Company::create(['name' => 'North Logistics', 'contact_person' => 'Amit']);
        Company::create(['name' => 'South Cargo', 'contact_person' => 'Vijay']);

        $headers = $this->signedHeaders();
        $response = $this->get('/api/admin/subscriptions?q=South', $headers);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('South Cargo', $response->json('data.0.name'));
    }

    public function test_record_offline_payment_returns_payment_payload(): void
    {
        $company = Company::create(['name' => 'Offline Pay Co']);
        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'addon',
            'status' => 'completed',
            'subtotal' => 1000,
            'promo_discount' => 0,
            'gst_amount' => 180,
            'total_amount' => 1180,
        ]);

        $mock = Mockery::mock(SubscriptionCheckoutService::class);
        $mock->shouldReceive('recordOfflinePurchase')
            ->once()
            ->andReturn($payment);
        $this->app->instance(SubscriptionCheckoutService::class, $mock);

        $payload = [
            'purchase_type' => 'addon',
            'addon_branches' => 1,
            'reference' => 'NEFT-001',
        ];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $response = $this->withHeaders($headers)
            ->postJson("/api/admin/subscriptions/{$company->id}/offline-payment", $payload);

        $response->assertOk()
            ->assertJsonPath('payment.id', $payment->id)
            ->assertJsonStructure(['subscription']);
    }

    public function test_record_offline_payment_returns_422_when_checkout_blocked(): void
    {
        $company = Company::create(['name' => 'Blocked Checkout Co']);

        $mock = Mockery::mock(SubscriptionCheckoutService::class);
        $mock->shouldReceive('recordOfflinePurchase')
            ->once()
            ->andThrow(new \InvalidArgumentException('Checkout is not available.'));
        $this->app->instance(SubscriptionCheckoutService::class, $mock);

        $payload = [
            'purchase_type' => 'renewal',
            'addon_branches' => 0,
            'addon_users' => 0,
        ];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $response = $this->withHeaders($headers)
            ->postJson("/api/admin/subscriptions/{$company->id}/offline-payment", $payload);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Checkout is not available.']);
    }

    public function test_extend_trial_returns_422_when_checkout_service_rejects_payload(): void
    {
        $company = Company::create(['name' => 'Trial Reject Co']);

        $checkoutMock = Mockery::mock(SubscriptionCheckoutService::class);
        $checkoutMock->shouldReceive('recordTrialExtension')
            ->once()
            ->andThrow(new \InvalidArgumentException('Trial extension not allowed.'));
        $this->app->instance(SubscriptionCheckoutService::class, $checkoutMock);

        $payload = [
            'trial_ends_on' => now()->addDays(14)->toDateString(),
            'amount' => 0,
        ];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $this->withHeaders($headers)
            ->postJson("/api/admin/subscriptions/{$company->id}/extend-trial", $payload)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Trial extension not allowed.');
    }

    public function test_patch_subscription_normalizes_stored_carbon_end_date(): void
    {
        $company = Company::create(['name' => 'Carbon Date Co']);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => '2026-01-01',
            'ends_on' => '2027-01-01',
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $payload = ['ends_on' => '2028-06-01'];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $this->withHeaders($headers)
            ->patchJson("/api/admin/subscriptions/{$company->id}", $payload)
            ->assertOk()
            ->assertJsonPath('subscription.ends_on', '2028-06-01');
    }

    public function test_patch_subscription_skips_unchanged_null_trial_end_date(): void
    {
        $company = Company::create(['name' => 'Null Trial Co']);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => '2026-01-01',
            'ends_on' => '2027-01-01',
            'trial_ends_on' => null,
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $payload = ['trial_ends_on' => null, 'notes' => 'No-op null trial'];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $this->withHeaders($headers)
            ->patchJson("/api/admin/subscriptions/{$company->id}", $payload)
            ->assertStatus(422)
            ->assertJsonPath('message', 'No changes to save.');
    }

    public function test_patch_creates_subscription_when_company_has_none(): void
    {
        $company = Company::create(['name' => 'No Sub Co']);

        $payload = ['included_branches' => 4];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $response = $this->withHeaders($headers)
            ->patchJson("/api/admin/subscriptions/{$company->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('subscription.included_branches', 4);

        $this->assertDatabaseHas('company_subscriptions', [
            'company_id' => $company->id,
            'included_branches' => 4,
        ]);
    }
}
