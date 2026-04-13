<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserBlockedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $blockedByName,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name').': Your account is currently deactivated',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-blocked',
            with: [
                'userName' => $this->userName,
                'blockedByName' => $this->blockedByName,
                'reason' => $this->reason,
                'blockedAt' => now()->format('d M Y, h:i A'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
