<?php

namespace App\Services\Notifications;

use App\Services\EmailTemplateMailSender;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;

class TransporterOnboardingNotificationService
{
    public function __construct(
        private readonly EmailTemplateMailSender $mailSender
    ) {}

    public function sendAccountCreated(string $email, string $name): void
    {
        $loginUrl = Route::has('login') ? route('login') : (string) config('app.url');

        try {
            $this->mailSender->sendByKey('TRANSPORTER_ACCOUNT_CREATED', $email, [
                'name' => $name,
                'welcomeLink' => $loginUrl,
                'transporterEmail' => $email,
                'dateTime' => now()->format('d M Y, h:i A'),
                'downloadUrl' => config('app.url'),
            ], [
                'triggered_by_type' => 'system',
                'triggered_by_id' => null,
                'source' => 'transporter_account_created',
            ]);
        } catch (InvalidArgumentException $exception) {
            // Template may be unavailable in some environments; skip notification silently.
        }
    }
}
