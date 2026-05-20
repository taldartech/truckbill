<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Referral;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Notifications\OnboardingNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeadOnboardingService
{
    public function __construct(
        private readonly OnboardingTokenService $tokenService,
        private readonly OnboardingOtpService $otpService,
        private readonly OnboardingNotificationService $onboardingNotificationService,
        private readonly UserBlockLifecycleService $userBlockLifecycleService
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

        $existingBlockedUser = $this->resolveExistingBlockedUser($payload['email']);

        $result = DB::transaction(function () use ($payload, $lead, $tokenRecord, $existingBlockedUser): array {
            $user = $existingBlockedUser;

            if ($user === null) {
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
            } else {
                $user->update([
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    'password' => Hash::make($payload['password']),
                ]);

                $this->userBlockLifecycleService->unblockOwnedAccount($user, sendNotification: false);
            }

            $lead->update([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'status' => Lead::STATUS_ACTIVATED,
                'accepted_terms_at' => now(),
            ]);

            $this->tokenService->markUsed($tokenRecord);
            $this->otpService->consumeVerified($payload['email']);

            return [
                'user' => $user,
                'lead' => $lead,
            ];
        });

        try {
            $this->onboardingNotificationService->sendUserOnboardingWelcome(
                $result['user']->email,
                $result['user']->name
            );
        } catch (\Throwable $exception) {
            \Log::error('Welcome email notification failed after onboarding', [
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

    private function resolveExistingBlockedUser(string $email): ?User
    {
        $user = User::query()
            ->with('userRole')
            ->where('email', $email)
            ->first();

        if ($user === null) {
            return null;
        }

        if ($user->is_blocked && $user->userRole?->name === 'super_admin') {
            return $user;
        }

        throw ValidationException::withMessages([
            'email' => 'The email has already been taken.',
        ]);
    }
}
