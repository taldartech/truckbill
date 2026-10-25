<?php

namespace Tests\Unit\Services;

use App\Exceptions\SubscriptionCatalogUnavailableException;
use App\Services\AdminApiClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminApiClientTest extends TestCase
{
    private AdminApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('subscription.admin_api_url', 'https://admin.example.com');
        Config::set('subscription.signing_secret', 'test-signing-secret');
        Config::set('subscription.product', 'truckbill');

        $this->client = new AdminApiClient();
    }

    public function test_get_catalog_returns_payload_when_successful(): void
    {
        Http::fake([
            'https://admin.example.com/api/v1/products/truckbill/subscription-catalog' => Http::response([
                'catalog' => [
                    'plans' => [
                        ['id' => 'starter', 'name' => 'Starter'],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->client->getCatalog();

        $this->assertArrayHasKey('catalog', $result);
        $this->assertSame('starter', $result['catalog']['plans'][0]['id']);
    }

    public function test_get_catalog_throws_exception_when_catalog_empty_or_not_array(): void
    {
        Http::fake([
            'https://admin.example.com/api/v1/products/truckbill/subscription-catalog' => Http::response([
                'catalog' => null,
            ], 200),
        ]);

        $this->expectException(SubscriptionCatalogUnavailableException::class);
        $this->client->getCatalog();
    }

    public function test_validate_promo_returns_response_data(): void
    {
        Http::fake([
            'https://admin.example.com/api/v1/products/truckbill/promo-codes/validate' => Http::response([
                'valid' => true,
                'discount_percent' => 20,
            ], 200),
        ]);

        $result = $this->client->validatePromo('SAVE20');

        $this->assertTrue($result['valid']);
        $this->assertSame(20, $result['discount_percent']);
    }

    public function test_increment_promo_use_makes_post_request(): void
    {
        Http::fake([
            'https://admin.example.com/api/v1/products/truckbill/promo-codes/increment-use' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $this->client->incrementPromoUse('SAVE20');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://admin.example.com/api/v1/products/truckbill/promo-codes/increment-use'
                && $request->method() === 'POST'
                && $request['code'] === 'SAVE20';
        });
    }

    public function test_request_throws_exception_when_config_is_missing(): void
    {
        Config::set('subscription.admin_api_url', '');

        $this->expectException(SubscriptionCatalogUnavailableException::class);
        $this->client->validatePromo('CODE');
    }

    public function test_request_throws_exception_when_http_fails(): void
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $this->expectException(SubscriptionCatalogUnavailableException::class);
        $this->client->validatePromo('FAIL');
    }

    public function test_request_throws_exception_on_network_timeout(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $this->expectException(SubscriptionCatalogUnavailableException::class);
        $this->client->validatePromo('TIMEOUT');
    }
}
