<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $name
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to TruckBill')
            ->greeting('Hi ' . $this->name . ',')
            ->line('Welcome to TruckBill!')
            ->line('Your account has been successfully set up and is now ready to use.')
            ->line('You can log in using your credentials here:')
            ->action('Log In to TruckBill', config('app.url'))
            ->line('With TruckBill, you can start managing your transport operations seamlessly - from billing and LR management to GST and TDS compliance.')
            ->line('If you need any help getting started or would like a quick walkthrough, feel free to reach out - we are here to support you.')
            ->line('Wishing you a smooth and efficient experience with TruckBill.')
            ->salutation('Best regards,' . PHP_EOL . 'Team TruckBill' . PHP_EOL . 'www.truckbill.in');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
