<?php

namespace App\Services\Notifications;

use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailTemplateMailSender;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdvancePaymentNotificationService
{
    private const TEMPLATE_KEY = 'ADVANCE_PAYMENT_PAID';

    public function __construct(
        private readonly EmailTemplateMailSender $mailSender
    ) {}

    /**
     * Notify when advance is disbursed to transporter (positive amount). Matrix: SuperAdmin, Admin, Transporter.
     */
    public function notifyAdvancePaid(AdvancePayment $advancePayment, ?User $triggeredBy = null): void
    {
        if ((float) $advancePayment->advance_amount <= 0) {
            return;
        }

        $template = EmailTemplate::query()
            ->where('key', self::TEMPLATE_KEY)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return;
        }

        $advancePayment->loadMissing([
            'company',
            'transporter.transporterMaster',
        ]);

        $company = $advancePayment->company;
        if ($company === null) {
            return;
        }

        $recipients = $this->resolveRecipients($advancePayment, $template, $company);
        if ($recipients->isEmpty()) {
            return;
        }

        $amountFormatted = number_format((float) $advancePayment->advance_amount, 2);
        $paymentDateFormatted = $advancePayment->payment_date
            ? Carbon::parse((string) $advancePayment->payment_date)->format('d M Y')
            : '';

        $baseVariables = [
            'companyName' => $company->name ?? '',
            'amount' => $amountFormatted,
            'paymentDate' => $paymentDateFormatted,
            'transporterName' => $advancePayment->transporter?->name ?? '',
            'remark' => (string) ($advancePayment->remark ?? ''),
            'dateTime' => now()->format('d M Y, h:i A'),
            'downloadUrl' => config('app.url'),
        ];

        $meta = [
            'company_id' => $advancePayment->company_id,
            'triggered_by_type' => $triggeredBy !== null ? 'user' : 'system',
            'triggered_by_id' => $triggeredBy?->id,
            'source' => 'advance_payment_paid',
        ];

        foreach ($recipients as $recipient) {
            $variables = array_merge($baseVariables, [
                'name' => $recipient['name'],
            ]);

            $this->mailSender->sendTemplate($template, $recipient['email'], $variables, $meta);
        }
    }

    /**
     * @return Collection<int, array{email: string, name: string}>
     */
    private function resolveRecipients(AdvancePayment $advancePayment, EmailTemplate $template, Company $company): Collection
    {
        /** @var array<string, array{email: string, name: string}> $byKey */
        $byKey = [];

        $templateRoles = collect($template->allowed_roles ?? [])
            ->filter(static fn ($r): bool => is_string($r) && $r !== '')
            ->values()
            ->all();

        if ($templateRoles === []) {
            $templateRoles = ['super_admin', 'admin'];
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

        if ($template->send_to_transporter) {
            $transporterEmail = $advancePayment->transporter?->transporterMaster?->email;
            if (is_string($transporterEmail) && filter_var($transporterEmail, FILTER_VALIDATE_EMAIL) !== false) {
                $transporter = $advancePayment->transporter;
                $transporterName = $transporter?->name
                    ?? $transporter?->transporterMaster?->name
                    ?? $transporter?->contact_person_name
                    ?? 'User';
                $key = $this->normalizeEmailKey($transporterEmail);
                $byKey[$key] = [
                    'email' => $transporterEmail,
                    'name' => $transporterName,
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
