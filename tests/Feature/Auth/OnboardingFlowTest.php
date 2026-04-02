<?php

namespace Tests\Feature\Auth;

use App\Models\Lead;
use App\Models\OnboardingToken;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\OnboardingOtpNotification;
use App\Services\OnboardingTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        UserRole::firstOrCreate(['id' => 1], ['name' => 'master_admin']);
        UserRole::firstOrCreate(['id' => 2], ['name' => 'super_admin']);
    }

    public function test_website_can_create_lead(): void
    {
        $response = $this->postJson('/api/public/leads', [
            'name' => 'Website Lead',
            'mobile' => '9876543210',
            'email' => 'lead@example.com',
            'source' => 'website',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('leads', [
            'mobile' => '9876543210',
            'email' => 'lead@example.com',
            'source' => 'website',
            'status' => Lead::STATUS_LEAD_CREATED,
        ]);
    }

    public function test_website_allows_duplicate_mobile_and_email_for_leads(): void
    {
        $payload = [
            'name' => 'Dup Lead',
            'mobile' => '9123456789',
            'email' => 'same@example.com',
            'source' => 'website',
        ];

        $this->postJson('/api/public/leads', $payload)->assertOk();
        $this->postJson('/api/public/leads', $payload)->assertOk();

        $this->assertSame(2, Lead::query()->where('mobile', '9123456789')->count());
    }

    public function test_master_admin_can_generate_hashed_onboarding_token(): void
    {
        $this->seedRoles();

        $masterAdmin = User::factory()->create(['user_role_id' => 1]);
        $lead = Lead::create([
            'name' => 'Lead One',
            'mobile' => '9999999999',
            'email' => 'leadone@example.com',
            'status' => Lead::STATUS_DEMO_COMPLETED,
            'source' => 'manual',
        ]);

        $response = $this->actingAs($masterAdmin)->postJson(route('leads.generate-link', $lead));
        $response->assertOk()->assertJsonStructure(['url']);

        $plainToken = basename((string) $response->json('url'));
        $tokenService = app(OnboardingTokenService::class);

        $this->assertDatabaseHas('onboarding_tokens', [
            'lead_id' => $lead->id,
            'token_hash' => $tokenService->hashToken($plainToken),
        ]);
    }

    public function test_onboarding_requires_email_verification_before_completion(): void
    {
        $lead = Lead::create([
            'name' => 'Lead Two',
            'mobile' => '9888888888',
            'email' => 'leadtwo@example.com',
            'status' => Lead::STATUS_DEMO_COMPLETED,
            'source' => 'manual',
        ]);

        $tokenService = app(OnboardingTokenService::class);
        $generated = $tokenService->generateForLead($lead, User::factory()->create()->id);

        $response = $this->postJson('/api/onboard', [
            'token' => $generated['plain_token'],
            'name' => 'Lead Two',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'accepted_terms' => true,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_complete_onboarding_creates_user_activates_lead_and_consumes_token(): void
    {
        Notification::fake();
        $this->seedRoles();

        $referrer = User::factory()->create([
            'user_role_id' => 2,
            'referral_code' => 'REFCODE1',
        ]);

        $lead = Lead::create([
            'name' => 'Lead Three',
            'mobile' => '9777777777',
            'email' => 'leadthree@example.com',
            'status' => Lead::STATUS_DEMO_COMPLETED,
            'source' => 'website',
        ]);

        $tokenService = app(OnboardingTokenService::class);
        $generated = $tokenService->generateForLead($lead, $referrer->id);

        $this->postJson('/api/onboarding/send-otp', [
            'token' => $generated['plain_token'],
            'email' => 'activated@example.com',
        ])->assertOk();

        Notification::assertSentOnDemand(OnboardingOtpNotification::class);

        $otpRecord = \App\Models\EmailOtp::where('email', 'activated@example.com')->latest('id')->first();
        $this->assertNotNull($otpRecord);

        // We cannot read plain OTP from hash, so verify path by directly setting verified marker.
        $otpRecord->update(['verified_at' => now()]);

        $response = $this->postJson('/api/onboard', [
            'token' => $generated['plain_token'],
            'name' => 'Activated User',
            'email' => 'activated@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'referral_code' => 'REFCODE1',
            'accepted_terms' => true,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['email' => 'activated@example.com']);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => Lead::STATUS_ACTIVATED,
        ]);
        $this->assertDatabaseHas('referrals', [
            'referrer_user_id' => $referrer->id,
            'referral_code' => 'REFCODE1',
        ]);
        $this->assertDatabaseHas('onboarding_tokens', [
            'lead_id' => $lead->id,
        ]);

        $this->assertNotNull(OnboardingToken::where('lead_id', $lead->id)->first()?->used_at);
    }
}
