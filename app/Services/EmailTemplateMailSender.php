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
        $subject = $this->withSubjectBrandSuffix($subject);
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

    /**
     * Append " || {AppName}" when the subject is not already branded, so we avoid
     * duplicates like "Welcome to TruckBill || TruckBill".
     */
    private function withSubjectBrandSuffix(string $subject): string
    {
        $brand = trim((string) config('app.name', 'TruckBill'));
        if ($brand === '') {
            $brand = 'TruckBill';
        }

        $normalized = trim($subject);
        $suffix = ' || '.$brand;
        if ($normalized === '') {
            return $brand;
        }

        $lower = mb_strtolower($normalized);
        $brandLower = mb_strtolower($brand);
        $suffixLower = mb_strtolower($suffix);

        if (str_ends_with($lower, $suffixLower)) {
            return $normalized;
        }

        $withoutTrailingPunct = rtrim($normalized, " \t\n\r\0\x0B.!?");
        if (str_ends_with(mb_strtolower($withoutTrailingPunct), $brandLower)) {
            return $normalized;
        }

        return $normalized.$suffix;
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
