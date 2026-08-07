<?php

namespace Tests\Feature\Subscription;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExtendTrialTest extends TestCase
{
    use RefreshDatabase;

    private function signedPost(string $uri, array $payload = [])
    {
        config(['onboarding.signing_secret' => 'test-secret']);

        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'test-secret');

        return $this->call(
            'POST',
            $uri,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_TIMESTAMP' => $timestamp,
                'HTTP_X_SIGNATURE' => $signature,
            ],
            $body,
        );
    }

    public function test_extend_trial_updates_dates_and_records_payment(): void
    {
        $company = Company::create([
            'name' => 'Trial Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'trial',
            'starts_on' => Carbon::today()->subDays(5),
            'ends_on' => Carbon::today()->addDays(2),
            'trial_ends_on' => Carbon::today()->addDays(2),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $trialEndsOn = Carbon::today()->addDays(20)->toDateString();

        $response = $this->signedPost("/api/admin/subscriptions/{$company->id}/extend-trial", [
            'trial_ends_on' => $trialEndsOn,
            'amount' => 1500.5,
            'reference' => 'UPI-123',
            'notes' => 'Paid extension',
            'admin_user_id' => 9,
        ]);

        $response->assertOk()
            ->assertJsonPath('subscription.status', 'trial')
            ->assertJsonPath('payment.type', 'trial_extension')
            ->assertJsonPath('payment.status', 'completed')
            ->assertJsonPath('payment.total_amount', '1500.50');

        $company->refresh()->load('subscription');
        $this->assertSame('trial', $company->subscription->status);
        $this->assertSame($trialEndsOn, $company->subscription->ends_on->toDateString());
        $this->assertSame($trialEndsOn, $company->subscription->trial_ends_on->toDateString());

        $this->assertDatabaseHas('subscription_payments', [
            'company_id' => $company->id,
            'type' => 'trial_extension',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('subscription_events', [
            'company_id' => $company->id,
            'event_type' => 'trial_extended',
        ]);

        $payment = SubscriptionPayment::query()->where('company_id', $company->id)->first();
        $this->assertTrue((bool) data_get($payment->razorpay_response, 'trial_extension'));
        $this->assertSame('UPI-123', data_get($payment->razorpay_response, 'reference'));

        $event = SubscriptionEvent::query()->where('company_id', $company->id)->first();
        $this->assertSame($trialEndsOn, data_get($event->metadata, 'trial_ends_on'));
        $this->assertSame(1500.5, data_get($event->metadata, 'amount'));
    }

    public function test_extend_trial_requires_trial_end_and_amount(): void
    {
        $company = Company::create([
            'name' => 'Trial Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        $response = $this->signedPost("/api/admin/subscriptions/{$company->id}/extend-trial", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trial_ends_on', 'amount']);
    }

    public function test_extend_trial_rejects_past_end_date(): void
    {
        $company = Company::create([
            'name' => 'Trial Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        $response = $this->signedPost("/api/admin/subscriptions/{$company->id}/extend-trial", [
            'trial_ends_on' => Carbon::yesterday()->toDateString(),
            'amount' => 100,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trial_ends_on']);
    }
}
