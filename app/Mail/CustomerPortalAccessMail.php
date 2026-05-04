<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerPortalAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $consigneeName,
        public string $email,
        public ?string $plainPassword,
        public bool $isNewUser,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name').': Customer portal access',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-portal-access',
            with: [
                'userName' => $this->userName,
                'consigneeName' => $this->consigneeName,
                'email' => $this->email,
                'plainPassword' => $this->plainPassword,
                'isNewUser' => $this->isNewUser,
                'loginUrl' => route('login'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
