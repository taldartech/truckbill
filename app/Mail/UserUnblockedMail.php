<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserUnblockedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $unblockedByName,
        public string $reason,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name').': Your account has been unlocked',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-unblocked',
            with: [
                'userName' => $this->userName,
                'unblockedByName' => $this->unblockedByName,
                'reason' => $this->reason,
                'unblockedAt' => now()->format('d M Y, h:i A'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
