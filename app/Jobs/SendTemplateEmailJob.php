<?php

namespace App\Jobs;

use App\Models\EmailDispatchLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTemplateEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $emailDispatchLogId,
        private readonly string $recipient,
        private readonly string $subject,
        private readonly string $renderedBody
    ) {}

    public function handle(): void
    {
        $log = EmailDispatchLog::query()->find($this->emailDispatchLogId);
        if ($log === null) {
            return;
        }

        try {
            Mail::send('emails.dynamic_content', [
                'bodyHtml' => $this->renderedBody,
                'emailSubject' => $this->subject,
            ], function ($message): void {
                $message->to($this->recipient)->subject($this->subject);
            });

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $throwable) {
            $log->update([
                'status' => 'failed',
                'error_message' => $throwable->getMessage(),
            ]);

            throw $throwable;
        }
    }
}
