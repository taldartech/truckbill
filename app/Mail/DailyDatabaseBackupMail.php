<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyDatabaseBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $disk,
        public string $backupPath,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily Database Backup - '.now()->format('Y-m-d'),
        );
    }

    public function content(): Content
    {
        $attachmentName = basename($this->backupPath);

        return new Content(
            view: 'emails.database-backup',
            with: [
                'attachmentName' => $attachmentName,
                'generatedAt' => now()->format('d M Y, h:i A'),
                'appName' => config('app.name'),
            ],
        );
    }

    public function attachments(): array
    {
        $attachmentName = basename($this->backupPath);

        return [
            Attachment::fromStorageDisk($this->disk, $this->backupPath)
                ->as($attachmentName),
        ];
    }
}
