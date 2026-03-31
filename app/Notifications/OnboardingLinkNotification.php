<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OnboardingLinkNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $leadName,
        private readonly string $onboardingUrl
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your TruckBill Onboarding Link')
            ->greeting('Hi ' . $this->leadName . ',')
            ->line('Thank you for taking the time to connect with us and attend the demo of TruckBill.')
            ->line('To get started, please complete your account setup by using the onboarding link below:')
            ->action('Complete Onboarding', $this->onboardingUrl)
            ->line('This link will guide you through a quick setup where you can:')
            ->line('- Create your login credentials')
            ->line('- Enter your basic details')
            ->line('- Accept the Terms & Conditions')
            ->line('For security purposes, you will also need to verify your email using an OTP during the process.')
            ->line('Once completed, your account will be activated and you will be ready to start using TruckBill.')
            ->line('If you face any issues or need assistance at any step, feel free to reply to this email or contact us.')
            ->line('Looking forward to having you onboard.')
            ->salutation('Best regards,' . PHP_EOL . 'Team TruckBill' . PHP_EOL . 'www.truckbill.in');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
