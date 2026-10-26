<?php

namespace Tests\Feature\Api;

use App\Models\OnboardingProvisioningLog;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OnboardingApiTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNING_SECRET = 'super-secret-onboarding-token-12345';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('onboarding.signing_secret', self::SIGNING_SECRET);
        UserRole::firstOrCreate(['name' => 'super_admin']);
    }

    /**
     * @return array<string, string>
     */
    private function signedHeaders(string $body = '', ?int $customTimestamp = null): array
    {
        $timestamp = (string) ($customTimestamp ?? time());
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, self::SIGNING_SECRET);

        return [
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    public function test_create_user_returns_201_for_new_provisioning(): void
    {
        $payload = [
            'idempotency_key' => 'api-create-1',
            'name' => 'New Super',
            'email' => 'newsuper@onboarding.test',
            'password' => 'secret1234',
            'role' => 'super_admin',
        ];
        $raw = json_encode($payload);

        $response = $this->withHeaders($this->signedHeaders($raw))
            ->postJson('/api/onboarding/create-user', $payload);

        $response->assertCreated()
            ->assertJsonPath('status', 'created')
            ->assertJsonPath('email', 'newsuper@onboarding.test');

        $this->assertDatabaseHas('users', ['email' => 'newsuper@onboarding.test']);
    }

    public function test_create_user_returns_200_when_idempotency_key_replays(): void
    {
        $payload = [
            'idempotency_key' => 'api-create-replay',
            'name' => 'Replay Super',
            'email' => 'replay@onboarding.test',
            'password' => 'secret1234',
            'role' => 'super_admin',
        ];
        $raw = json_encode($payload);
        $headers = $this->signedHeaders($raw);

        $this->withHeaders($headers)->postJson('/api/onboarding/create-user', $payload)->assertCreated();

        $replay = $this->withHeaders($headers)->postJson('/api/onboarding/create-user', $payload);

        $replay->assertOk()
            ->assertJsonPath('status', 'already_provisioned')
            ->assertJsonPath('email', 'replay@onboarding.test');
    }

    public function test_create_user_validation_requires_signed_payload_fields(): void
    {
        $payload = ['email' => 'not-an-email'];
        $raw = json_encode($payload);

        $response = $this->withHeaders($this->signedHeaders($raw))
            ->postJson('/api/onboarding/create-user', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['idempotency_key', 'name', 'email', 'password', 'role']);
    }

    public function test_update_user_changes_super_admin_profile(): void
    {
        $role = UserRole::where('name', 'super_admin')->first();
        $user = User::factory()->create([
            'email' => 'update-me@onboarding.test',
            'user_role_id' => $role->id,
            'password' => Hash::make('oldpassword'),
        ]);

        $payload = [
            'email' => 'update-me@onboarding.test',
            'name' => 'Updated Name',
            'password' => 'newpassword1',
        ];
        $raw = json_encode($payload);

        $response = $this->withHeaders($this->signedHeaders($raw))
            ->postJson('/api/onboarding/update-user', $payload);

        $response->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('email', 'update-me@onboarding.test');

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertTrue(Hash::check('newpassword1', $user->password));
    }

    public function test_status_returns_exists_false_for_unknown_email(): void
    {
        $query = http_build_query(['email' => 'missing@onboarding.test']);
        $headers = $this->signedHeaders('');

        $response = $this->withHeaders($headers)->get('/api/onboarding/status?'.$query);

        $response->assertOk()
            ->assertJsonPath('exists', false)
            ->assertJsonPath('email', 'missing@onboarding.test');
    }

    public function test_status_returns_profile_for_existing_super_admin(): void
    {
        $role = UserRole::where('name', 'super_admin')->first();
        User::factory()->create([
            'email' => 'known@onboarding.test',
            'user_role_id' => $role->id,
            'email_verified_at' => now(),
            'policy_accepted_at' => now(),
        ]);

        OnboardingProvisioningLog::create([
            'idempotency_key' => 'status-log',
            'action' => 'create_user',
            'request_payload' => [],
            'response_payload' => [],
        ]);

        $query = http_build_query(['email' => 'known@onboarding.test']);
        $response = $this->withHeaders($this->signedHeaders(''))
            ->get('/api/onboarding/status?'.$query);

        $response->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('email', 'known@onboarding.test')
            ->assertJsonPath('status', 'active');
    }

    public function test_unsigned_request_is_rejected(): void
    {
        $this->postJson('/api/onboarding/create-user', [])
            ->assertUnauthorized()
            ->assertJson(['message' => 'Missing signature headers.']);
    }
}
