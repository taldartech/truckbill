<?php

namespace App\Services\Notifications;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Models\VoucherPayment;
use App\Services\EmailTemplateMailSender;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VoucherPaymentNotificationService
{
    public function __construct(
        private readonly EmailTemplateMailSender $mailSender
    ) {}

    /**
     * Matrix (all three voucher events): SuperAdmin, Admin, Transporter — via template defaults.
     */
    public function notifyForCurrentStatus(VoucherPayment $voucherPayment, ?User $triggeredBy = null): void
    {
        $templateKey = $this->templateKeyForPaymentStatus($voucherPayment->payment_status);
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

        $voucherPayment->loadMissing([
            'company',
            'freight_booking.lr',
            'freight_booking.transporter.transporterMaster',
        ]);

        $company = $voucherPayment->company;
        if ($company === null) {
            return;
        }

        $recipients = $this->resolveRecipients($voucherPayment, $template, $company);
        if ($recipients->isEmpty()) {
            return;
        }

        $fb = $voucherPayment->freight_booking;
        $lr = $fb?->lr;
        $transporter = $fb?->transporter;

        $baseVariables = [
            'companyName' => $company->name ?? '',
            'voucherNumber' => $voucherPayment->voucher_number !== null ? (string) $voucherPayment->voucher_number : '',
            'lrNumber' => $lr?->lr_number ?? '',
            'amount' => number_format((float) $voucherPayment->amount, 2),
            'paymentMode' => (string) ($voucherPayment->payment_mode ?? ''),
            'paymentType' => (string) ($voucherPayment->payment_type ?? ''),
            'transporterName' => $transporter?->name ?? '',
            'remark' => (string) ($voucherPayment->remark ?? ''),
            'dateTime' => now()->format('d M Y, h:i A'),
            'paymentDate' => $voucherPayment->date
                ? Carbon::parse((string) $voucherPayment->date)->format('d M Y')
                : '',
            'paymentStatus' => (string) ($voucherPayment->payment_status ?? ''),
            'downloadUrl' => config('app.url'),
        ];

        $meta = [
            'company_id' => $voucherPayment->company_id,
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

    private function templateKeyForPaymentStatus(?string $status): ?string
    {
        return match ($status) {
            'pending' => 'VOUCHER_PAYMENT_PENDING',
            'in-progress' => 'VOUCHER_PAYMENT_IN_PROGRESS',
            'paid' => 'VOUCHER_PAYMENT_PAID',
            default => null,
        };
    }

    private function dispatchSourceForTemplateKey(string $templateKey): string
    {
        return match ($templateKey) {
            'VOUCHER_PAYMENT_PENDING' => 'voucher_payment_pending',
            'VOUCHER_PAYMENT_IN_PROGRESS' => 'voucher_payment_in_progress',
            'VOUCHER_PAYMENT_PAID' => 'voucher_payment_paid',
            default => 'voucher_payment',
        };
    }

    /**
     * @return Collection<int, array{email: string, name: string}>
     */
    private function resolveRecipients(VoucherPayment $voucherPayment, EmailTemplate $template, Company $company): Collection
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
            $transporterEmail = $voucherPayment->freight_booking?->transporter?->transporterMaster?->email;
            if (is_string($transporterEmail) && filter_var($transporterEmail, FILTER_VALIDATE_EMAIL) !== false) {
                $transporter = $voucherPayment->freight_booking?->transporter;
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
