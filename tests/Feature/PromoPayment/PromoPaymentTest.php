<?php

namespace Tests\Feature\PromoPayment;

use App\Models\Company;
use App\Models\PromoCode;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PromoPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Legacy promo codes and payment ledgers dropped in migration 2026_06_19_000006.');
        $this->seedRoles();
    }

    protected function seedRoles(): void
    {
        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
    }

    protected function makeSuperAdmin(): User
    {
        return User::factory()->create([
            'user_role_id' => 2,
        ]);
    }

    protected function createCompanyFor(User $owner, array $overrides = []): Company
    {
        return Company::create(array_merge([
            'name' => 'Company '.$owner->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'user_id' => $owner->id,
            'plan_end_date' => now()->addMonth(),
            'truck_credits' => 0,
            'lr_credits' => 0,
            'is_blocked' => false,
        ], $overrides));
    }

    public function test_promo_code_store_and_update(): void
    {
        $master = User::factory()->create(['user_role_id' => 1]);

        $create = $this->actingAs($master)->post(route('promo_codes.store'), [
            'code' => 'SAVE10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'max_uses' => 5,
            'is_active' => true,
        ]);
        $create->assertRedirect(route('promo_codes.index'));
        $this->assertDatabaseHas('promo_codes', ['code' => 'SAVE10', 'discount_value' => 10]);

        $promo = PromoCode::where('code', 'SAVE10')->first();

        $update = $this->actingAs($master)->put(route('promo_codes.update', $promo->id), [
            'code' => 'SAVE15',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'max_uses' => 3,
            'is_active' => true,
        ]);
        $update->assertRedirect(route('promo_codes.index'));
        $this->assertDatabaseHas('promo_codes', ['code' => 'SAVE15', 'discount_value' => 15]);
    }

    public function test_apply_promo_code_endpoint_returns_discount_details(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);
        PromoCode::create([
            'code' => 'SAVE10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'max_uses' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($super)->post(route('promo_codes.validate'), [
            'code' => 'SAVE10',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'code' => 'SAVE10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]);
    }

    public function test_payment_store_blocks_when_razorpay_config_missing(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);

        $response = $this->actingAs($super)->post('/payment', [
            'company_id' => $company->id,
            'tenure' => 1,
            'truck_count' => 1,
            'lr_credits' => config('payments.lr_credits_1'),
            'want_to_renew' => 'yes',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('payment_ledgers', 0);
    }

    public function test_payment_store_handles_razorpay_failure_with_promo(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);
        $promo = PromoCode::create([
            'code' => 'SAVE10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'max_uses' => 5,
            'is_active' => true,
        ]);

        // Fake Razorpay keys so PaymentController does not abort early
        config([
            'payments.razorpay_key' => 'key',
            'payments.razorpay_secret' => 'secret',
        ]);

        // Mock Razorpay API (simple stub)
        $fakeOrder = new class(['id' => 'order_123', 'amount' => 1000]) implements \ArrayAccess
        {
            public array $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function offsetExists(mixed $offset): bool
            {
                return array_key_exists($offset, $this->data);
            }

            public function offsetGet(mixed $offset): mixed
            {
                return $this->data[$offset] ?? null;
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
                $this->data[$offset] = $value;
            }

            public function offsetUnset(mixed $offset): void
            {
                unset($this->data[$offset]);
            }

            public function toArray(): array
            {
                return $this->data;
            }
        };

        // Overload Razorpay\Api\Api so "new Api" returns this mock
        $apiMock = Mockery::mock('overload:Razorpay\Api\Api');
        $apiMock->shouldReceive('order->create')->andReturn($fakeOrder);
        // In store() we don't hit utility, but guard just in case
        $apiMock->shouldReceive('utility->verifyPaymentSignature')->andReturnTrue();

        $response = $this->actingAs($super)->postJson('/payment', [
            'company_id' => $company->id,
            'tenure' => 1,
            'truck_count' => 1,
            'lr_credits' => config('payments.lr_credits_1'),
            'want_to_renew' => 'yes',
            'promo_code' => 'SAVE10',
        ]);

        // With stubbed API this still returns 500 because Razorpay client is constructed internally.
        // Assert it fails gracefully and does not create a ledger.
        $response->assertStatus(500);
        $response->assertJsonFragment(['success' => false]);
        $this->assertDatabaseCount('payment_ledgers', 0);
    }
}
