<?php

namespace App\Services;

use App\Jobs\SendTemplateEmailJob;
use App\Models\EmailDispatchLog;
use App\Models\EmailTemplate;
use InvalidArgumentException;

class EmailTemplateMailSender
{
    public function __construct(
        private readonly EmailTemplateRenderer $renderer
    ) {}

    public function sendTemplate(EmailTemplate $template, array|string $to, array $variables = [], array $meta = []): void
    {
        if (! $template->is_active) {
            throw new InvalidArgumentException('Email template is inactive.');
        }

        $allowedVariables = $template->allowed_variables ?? [];
        $filteredVariables = [];
        foreach ($allowedVariables as $key) {
            if (array_key_exists($key, $variables)) {
                $filteredVariables[$key] = $variables[$key];
            }
        }

        $subject = $this->renderer->render($template->subject, $filteredVariables);
        $renderedBody = $this->renderer->render($template->body_html, $filteredVariables);
        $recipients = is_array($to) ? $to : [$to];

        foreach ($recipients as $recipient) {
            if (! is_string($recipient) || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }

            $resolvedCompanyId = array_key_exists('company_id', $meta)
                ? $meta['company_id']
                : $template->company_id;

            $log = EmailDispatchLog::query()->create([
                'email_template_id' => $template->id,
                'company_id' => $resolvedCompanyId,
                'event_key' => $template->key,
                'recipient_email' => $recipient,
                'subject_snapshot' => $subject,
                'body_snapshot' => $renderedBody,
                'status' => 'queued',
                'triggered_by_type' => (string) ($meta['triggered_by_type'] ?? 'system'),
                'triggered_by_id' => $meta['triggered_by_id'] ?? null,
                'source' => (string) ($meta['source'] ?? 'system'),
                'queued_at' => now(),
            ]);

            SendTemplateEmailJob::dispatch($log->id, $recipient, $subject, $renderedBody);
        }
    }

    public function sendByKey(string $templateKey, array|string $to, array $variables = [], array $meta = []): void
    {
        $template = EmailTemplate::query()
            ->where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            throw new InvalidArgumentException('Email template not found for key: '.$templateKey);
        }

        $this->sendTemplate($template, $to, $variables, $meta);
    }
}
