<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Referral;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\OnboardingWelcomeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeadOnboardingService
{
    public function __construct(
        private readonly OnboardingTokenService $tokenService,
        private readonly OnboardingOtpService $otpService
    ) {}

    public function complete(array $payload): array
    {
        $tokenRecord = $this->tokenService->findActiveByPlainToken($payload['token']);

        if ($tokenRecord === null || $tokenRecord->lead === null) {
            throw ValidationException::withMessages(['token' => 'Invalid or used onboarding token.']);
        }

        $lead = $tokenRecord->lead;

        if ($lead->status === Lead::STATUS_REJECTED) {
            throw ValidationException::withMessages(['lead' => 'This lead has been rejected.']);
        }

        if ($lead->status === Lead::STATUS_ACTIVATED) {
            throw ValidationException::withMessages(['lead' => 'This lead is already activated.']);
        }

        if (! $this->otpService->isEmailVerified($payload['email'])) {
            throw ValidationException::withMessages(['email' => 'Email OTP verification is required.']);
        }

        $result = DB::transaction(function () use ($payload, $lead, $tokenRecord): array {
            $role = UserRole::firstOrCreate(['name' => 'super_admin']);

            $user = User::create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => Hash::make($payload['password']),
                'user_role_id' => $role->id,
                'company_id' => null,
                'branch_id' => null,
                'referral_code' => $this->generateUniqueReferralCode(),
            ]);

            if (! empty($payload['referral_code'])) {
                $referrer = User::query()->where('referral_code', $payload['referral_code'])->first();
                if ($referrer !== null && $referrer->id !== $user->id) {
                    Referral::create([
                        'referrer_user_id' => $referrer->id,
                        'referred_user_id' => $user->id,
                        'referral_code' => $payload['referral_code'],
                        'reward_status' => 'pending',
                    ]);
                }
            }

            $lead->update([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'status' => Lead::STATUS_ACTIVATED,
                'accepted_terms_at' => now(),
            ]);

            $this->tokenService->markUsed($tokenRecord);
            $this->otpService->consumeVerified($payload['email']);

            Log::info('Lead onboarding completed', [
                'lead_id' => $lead->id,
                'user_id' => $user->id,
            ]);

            return [
                'user' => $user,
                'lead' => $lead,
            ];
        });

        try {
            $result['user']->notify(new OnboardingWelcomeNotification($result['user']->name));
        } catch (\Throwable $exception) {
            Log::error('Welcome email notification failed after onboarding', [
                'user_id' => $result['user']->id,
                'lead_id' => $result['lead']->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return $result;
    }

    private function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
