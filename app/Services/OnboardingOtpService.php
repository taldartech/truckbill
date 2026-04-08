<?php

namespace App\Services;

use App\Models\EmailOtp;
use App\Services\Notifications\OnboardingNotificationService;
use Illuminate\Validation\ValidationException;

class OnboardingOtpService
{
    public function __construct(
        private readonly OnboardingNotificationService $onboardingNotificationService
    ) {}

    public function send(string $email): void
    {
        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(10);

        EmailOtp::query()
            ->where('email', $email)
            ->whereNull('verified_at')
            ->update(['expires_at' => now()]);

        EmailOtp::create([
            'email' => $email,
            'otp_hash' => $this->hashOtp($email, $otp),
            'expires_at' => $expiresAt,
        ]);

        $this->onboardingNotificationService->sendLeadOnboardingOtp($email, $otp, 10);
    }

    public function verify(string $email, string $otp): void
    {
        $record = EmailOtp::query()
            ->where('email', $email)
            ->whereNull('verified_at')
            ->orderByDesc('id')
            ->first();

        if ($record === null || now()->greaterThan($record->expires_at)) {
            throw ValidationException::withMessages([
                'otp' => 'OTP expired or invalid.',
            ]);
        }

        if (! hash_equals($record->otp_hash, $this->hashOtp($email, $otp))) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid OTP.',
            ]);
        }

        $record->verified_at = now();
        $record->save();
    }

    public function isEmailVerified(string $email): bool
    {
        return EmailOtp::query()
            ->where('email', $email)
            ->whereNotNull('verified_at')
            ->where('expires_at', '>=', now())
            ->exists();
    }

    public function consumeVerified(string $email): void
    {
        EmailOtp::query()
            ->where('email', $email)
            ->whereNotNull('verified_at')
            ->where('expires_at', '>=', now())
            ->update(['expires_at' => now()]);
    }

    private function hashOtp(string $email, string $otp): string
    {
        return hash_hmac('sha256', $email.'|'.$otp, (string) config('app.key'));
    }
}
