<?php

namespace App\Services\Notifications;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\User;
use App\Services\EmailTemplateMailSender;
use Illuminate\Support\Collection;

class InvoiceNotificationService
{
    public function __construct(
        private readonly EmailTemplateMailSender $mailSender
    ) {}

    public function notifyForCurrentStatus(Invoice $invoice, ?User $triggeredBy = null, array $extraVariables = []): void
    {
        $templateKey = $this->templateKeyForStatus($invoice->payment_status);
        if ($templateKey === null) {
            return;
        }

        $template = EmailTemplate::query()
            ->where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return;
        }

        $invoice->loadMissing(['company', 'consignee', 'payments']);

        $company = $invoice->company;
        if ($company === null) {
            return;
        }

        $recipients = $this->resolveRecipients($invoice, $template, $company);
        if ($recipients->isEmpty()) {
            return;
        }

        $paidAmount = (float) $invoice->paid_amount;
        $amountToCollect = (float) ($invoice->net_amount ?? $invoice->total_amount ?? 0);
        $remainingAmount = (float) $invoice->remaining_amount;

        $baseVariables = array_merge([
            'companyName' => $company->name ?? '',
            'invoiceNumber' => (string) ($invoice->invoice_number ?? ''),
            'manualInvoiceNumber' => (string) ($invoice->manual_invoice_number ?? ''),
            'customerName' => (string) ($invoice->consignee?->name ?? 'Customer'),
            'amount' => number_format($amountToCollect, 2),
            'paidAmount' => number_format($paidAmount, 2),
            'dueAmount' => number_format($remainingAmount, 2),
            'paymentAmount' => number_format((float) ($extraVariables['paymentAmount'] ?? 0), 2),
            'paymentDate' => array_key_exists('paymentDate', $extraVariables)
                ? (string) $extraVariables['paymentDate']
                : '',
            'dateTime' => now()->format('d M Y, h:i A'),
            'paymentStatus' => (string) ($invoice->payment_status ?? ''),
            'downloadUrl' => config('app.url'),
        ], $extraVariables);

        $meta = [
            'company_id' => $invoice->company_id,
            'triggered_by_type' => $triggeredBy !== null ? 'user' : 'system',
            'triggered_by_id' => $triggeredBy?->id,
            'source' => $this->dispatchSourceForTemplateKey($templateKey),
        ];

        foreach ($recipients as $recipient) {
            $variables = array_merge($baseVariables, [
                'name' => $recipient['name'],
            ]);

            $this->mailSender->sendTemplate($template, $recipient['email'], $variables, $meta);
        }
    }

    public function notifyDueReminder(Invoice $invoice, int $dueInDays, ?User $triggeredBy = null): void
    {
        $template = EmailTemplate::query()
            ->where('key', 'INVOICE_DUE_REMINDER')
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return;
        }

        $invoice->loadMissing(['company', 'consignee', 'payments']);

        $company = $invoice->company;
        if ($company === null) {
            return;
        }

        $recipients = $this->resolveRecipients($invoice, $template, $company);
        if ($recipients->isEmpty()) {
            return;
        }

        $paidAmount = (float) $invoice->paid_amount;
        $amountToCollect = (float) ($invoice->net_amount ?? $invoice->total_amount ?? 0);
        $remainingAmount = (float) $invoice->remaining_amount;

        $baseVariables = [
            'companyName' => $company->name ?? '',
            'invoiceNumber' => (string) ($invoice->invoice_number ?? ''),
            'manualInvoiceNumber' => (string) ($invoice->manual_invoice_number ?? ''),
            'customerName' => (string) ($invoice->consignee?->name ?? 'Customer'),
            'amount' => number_format($amountToCollect, 2),
            'paidAmount' => number_format($paidAmount, 2),
            'dueAmount' => number_format($remainingAmount, 2),
            'dateTime' => now()->format('d M Y, h:i A'),
            'paymentStatus' => (string) ($invoice->payment_status ?? ''),
            'downloadUrl' => config('app.url'),
            'dueInDays' => (string) $dueInDays,
        ];

        $meta = [
            'company_id' => $invoice->company_id,
            'triggered_by_type' => $triggeredBy !== null ? 'user' : 'system',
            'triggered_by_id' => $triggeredBy?->id,
            'source' => $dueInDays === 1 ? 'invoice_due_reminder_1d' : 'invoice_due_reminder_3d',
        ];

        foreach ($recipients as $recipient) {
            $variables = array_merge($baseVariables, [
                'name' => $recipient['name'],
            ]);

            $this->mailSender->sendTemplate($template, $recipient['email'], $variables, $meta);
        }
    }

    private function templateKeyForStatus(?string $status): ?string
    {
        return match ($status) {
            'pending' => 'INVOICE_PAYMENT_PENDING',
            'partial' => 'INVOICE_PAYMENT_PARTIAL',
            'paid' => 'INVOICE_PAYMENT_PAID',
            default => null,
        };
    }

    private function dispatchSourceForTemplateKey(string $templateKey): string
    {
        return match ($templateKey) {
            'INVOICE_PAYMENT_PENDING' => 'invoice_payment_pending',
            'INVOICE_PAYMENT_PARTIAL' => 'invoice_payment_partial',
            'INVOICE_PAYMENT_PAID' => 'invoice_payment_paid',
            default => 'invoice_payment',
        };
    }

    /**
     * @return Collection<int, array{email: string, name: string}>
     */
    private function resolveRecipients(Invoice $invoice, EmailTemplate $template, Company $company): Collection
    {
        /** @var array<string, array{email: string, name: string}> $byKey */
        $byKey = [];

        $templateRoles = collect($template->allowed_roles ?? [])
            ->filter(static fn ($r): bool => is_string($r) && $r !== '')
            ->values()
            ->all();

        if ($templateRoles === []) {
            $templateRoles = ['super_admin', 'admin', 'moderator'];
        }

        $users = User::query()
            ->with('userRole')
            ->where(function ($query) use ($company): void {
                $query->where('company_id', $company->id);
                if ($company->user_id !== null) {
                    $query->orWhere('id', $company->user_id);
                }
            })
            ->get()
            ->filter(static fn (User $user): bool => in_array($user->userRole?->name, $templateRoles, true));

        foreach ($users as $user) {
            $email = $user->email;
            if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }

            $key = $this->normalizeEmailKey($email);
            $byKey[$key] = [
                'email' => $email,
                'name' => $user->name ?? 'User',
            ];
        }

        if ($template->send_to_customer) {
            $customerEmail = $invoice->consignee?->email;
            if (is_string($customerEmail) && filter_var($customerEmail, FILTER_VALIDATE_EMAIL) !== false) {
                $key = $this->normalizeEmailKey($customerEmail);
                $byKey[$key] = [
                    'email' => $customerEmail,
                    'name' => $invoice->consignee?->name ?? 'Customer',
                ];
            }
        }

        return collect($byKey)->values();
    }

    private function normalizeEmailKey(string $email): string
    {
        return strtolower(trim($email));
    }
}
