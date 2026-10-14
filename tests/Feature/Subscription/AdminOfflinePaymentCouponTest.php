<?php

namespace Tests\Feature\Subscription;

use App\Models\Company;
use App\Models\SubscriptionPayment;
use App\Services\AdminApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AdminOfflinePaymentCouponTest extends TestCase
{
    use RefreshDatabase;

    private function signedPost(string $uri, array $payload = [])
    {
        config(['onboarding.signing_secret' => 'test-secret']);

        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_TIMESTAMP' => $timestamp,
            'HTTP_X_SIGNATURE' => $signature,
        ], $body);
    }

    private function catalogPayload(): array
    {
        return [
            'catalog' => [
                'base_price' => 10000,
                'included_branches' => 2,
                'users_per_branch' => 3,
                'included_lr_credits' => 100,
                'addon_branch_price' => 2000,
                'addon_user_price' => 500,
            ],
            'gst_rate_percent' => 18,
        ];
    }

    private function company(): Company
    {
        return Company::create([
            'name' => 'Offline Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);
    }

    public function test_offline_payment_applies_valid_coupon(): void
    {
        $this->mock(AdminApiClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getCatalog')->andReturn($this->catalogPayload());
            $mock->shouldReceive('validatePromo')->with('SAVE10')->andReturn([
                'valid' => true,
                'promo' => ['code' => 'SAVE10', 'discount_type' => 'percentage', 'discount_value' => 10],
            ]);
            $mock->shouldReceive('incrementPromoUse')->once()->with('SAVE10');
        });

        $company = $this->company();

        $response = $this->signedPost("/api/admin/subscriptions/{$company->id}/offline-payment", [
            'purchase_type' => 'renewal',
            'promo_code' => 'SAVE10',
            'reference' => 'UPI-9',
        ]);

        $response->assertOk()->assertJsonPath('payment.promo_code', 'SAVE10');

        $payment = SubscriptionPayment::query()->where('company_id', $company->id)->firstOrFail();
        $this->assertSame('SAVE10', $payment->promo_code);
        $this->assertEquals(1000.0, (float) $payment->promo_discount);
        $this->assertEquals(10620.0, (float) $payment->total_amount);
    }

    public function test_offline_payment_rejects_invalid_coupon(): void
    {
        $this->mock(AdminApiClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getCatalog')->andReturn($this->catalogPayload());
            $mock->shouldReceive('validatePromo')->andReturn(['valid' => false, 'message' => 'Promo code has expired.']);
            $mock->shouldNotReceive('incrementPromoUse');
        });

        $company = $this->company();

        $response = $this->signedPost("/api/admin/subscriptions/{$company->id}/offline-payment", [
            'purchase_type' => 'renewal',
            'promo_code' => 'OLD',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Promo code is invalid or no longer applicable.');

        $this->assertDatabaseMissing('subscription_payments', ['company_id' => $company->id]);
    }

    public function test_quote_includes_coupon_discount(): void
    {
        $this->mock(AdminApiClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getCatalog')->andReturn($this->catalogPayload());
            $mock->shouldReceive('validatePromo')->with('SAVE10')->andReturn([
                'valid' => true,
                'promo' => ['code' => 'SAVE10', 'discount_type' => 'percentage', 'discount_value' => 10],
            ]);
        });

        $company = $this->company();

        $this->signedPost("/api/admin/subscriptions/{$company->id}/quote", [
            'purchase_type' => 'renewal',
            'promo_code' => 'SAVE10',
        ])
            ->assertOk()
            ->assertJsonPath('promo_discount', 1000)
            ->assertJsonPath('promo_snapshot.code', 'SAVE10');
    }
}
