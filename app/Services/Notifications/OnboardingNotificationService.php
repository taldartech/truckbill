<?php

namespace App\Services\Notifications;

use App\Notifications\OnboardingLinkNotification;
use App\Notifications\OnboardingOtpNotification;
use App\Notifications\OnboardingWelcomeNotification;
use App\Services\EmailTemplateMailSender;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class OnboardingNotificationService
{
    public function __construct(
        private readonly EmailTemplateMailSender $mailSender
    ) {}

    public function sendLeadOnboardingLink(string $email, string $leadName, string $onboardingUrl): void
    {
        $sent = $this->sendByKey('LEAD_ONBOARDING_LINK', $email, [
            'name' => $leadName,
            'onboardingLink' => $onboardingUrl,
            'downloadUrl' => config('app.url'),
            'dateTime' => now()->format('d M Y, h:i A'),
        ]);

        if (! $sent) {
            Notification::route('mail', $email)->notify(new OnboardingLinkNotification($leadName, $onboardingUrl));
        }
    }

    public function sendLeadOnboardingOtp(string $email, string $otp, int $expiresInMinutes): void
    {
        $sent = $this->sendByKey('LEAD_ONBOARDING_OTP', $email, [
            'otp' => $otp,
            'otpExpiryMinutes' => (string) $expiresInMinutes,
            'downloadUrl' => config('app.url'),
            'dateTime' => now()->format('d M Y, h:i A'),
        ]);

        if (! $sent) {
            Notification::route('mail', $email)->notify(new OnboardingOtpNotification($otp, $expiresInMinutes));
        }
    }

    public function sendUserOnboardingWelcome(string $email, string $name): void
    {
        $loginUrl = Route::has('login') ? route('login') : (string) config('app.url');

        $sent = $this->sendByKey('USER_ONBOARDING_WELCOME', $email, [
            'name' => $name,
            'welcomeLink' => $loginUrl,
            'downloadUrl' => config('app.url'),
            'dateTime' => now()->format('d M Y, h:i A'),
        ]);

        if (! $sent) {
            Notification::route('mail', $email)->notify(new OnboardingWelcomeNotification($name));
        }
    }

    private function sendByKey(string $key, string $to, array $variables): bool
    {
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid recipient email.');
        }

        try {
            $this->mailSender->sendByKey($key, $to, $variables, [
                'triggered_by_type' => 'system',
                'triggered_by_id' => null,
                'source' => 'onboarding_flow_a',
            ]);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
