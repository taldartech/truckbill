<?php

namespace App\Services\Notifications;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\PaymentLedger;
use App\Models\User;
use App\Services\EmailTemplateMailSender;
use Illuminate\Support\Collection;

class CompanyLifecycleNotificationService
{
    public function __construct(
        private readonly EmailTemplateMailSender $mailSender
    ) {}

    public function notifyCompanyRegistered(Company $company, ?User $triggeredBy = null): void
    {
        $template = $this->activeTemplate('COMPANY_REGISTERED');
        if ($template === null) {
            return;
        }

        $owner = $company->user()->with('userRole')->first();
        if (! ($owner instanceof User)) {
            return;
        }

        $email = $owner->email;
        if (! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        $variables = [
            'name' => $owner->name ?? 'User',
            'companyName' => (string) ($company->name ?? ''),
            'planEndDate' => $company->plan_end_date ? (string) $company->plan_end_date : '',
            'dateTime' => now()->format('d M Y, h:i A'),
            'downloadUrl' => config('app.url'),
        ];

        $meta = [
            'company_id' => $company->id,
            'triggered_by_type' => $triggeredBy !== null ? 'user' : 'system',
            'triggered_by_id' => $triggeredBy?->id,
            'source' => 'company_registered',
        ];

        $this->mailSender->sendTemplate($template, $email, $variables, $meta);
    }

    public function notifySubscriptionRenewed(Company $company, PaymentLedger $payment, ?User $triggeredBy = null): void
    {
        $template = $this->activeTemplate('COMPANY_SUBSCRIPTION_RENEWED');
        if ($template === null) {
            return;
        }

        $company->loadMissing('user.userRole');
        $recipients = $this->resolveRecipientsForTemplate($company, $template, ['super_admin']);
        if ($recipients->isEmpty()) {
            return;
        }

        $baseVariables = [
            'companyName' => (string) ($company->name ?? ''),
            'amount' => number_format((float) ($payment->total_amount ?? 0), 2),
            'planEndDate' => $company->plan_end_date ? (string) $company->plan_end_date : '',
            'tenure' => (string) ($payment->tenure ?? ''),
            'dateTime' => now()->format('d M Y, h:i A'),
            'downloadUrl' => config('app.url'),
        ];

        $meta = [
            'company_id' => $company->id,
            'triggered_by_type' => $triggeredBy !== null ? 'user' : 'system',
            'triggered_by_id' => $triggeredBy?->id,
            'source' => 'company_subscription_renewed',
        ];

        foreach ($recipients as $recipient) {
            $variables = array_merge($baseVariables, ['name' => $recipient['name']]);
            $this->mailSender->sendTemplate($template, $recipient['email'], $variables, $meta);
        }
    }

    public function notifyPlanExpiringSoon(Company $company, int $daysLeft, ?User $triggeredBy = null): void
    {
        $template = $this->activeTemplate('COMPANY_PLAN_EXPIRING_SOON');
        if ($template === null) {
            return;
        }

        $recipients = $this->resolveRecipientsForTemplate($company, $template, ['super_admin', 'admin', 'accountant']);
        if ($recipients->isEmpty()) {
            return;
        }

        $baseVariables = [
            'companyName' => (string) ($company->name ?? ''),
            'planEndDate' => $company->plan_end_date ? (string) $company->plan_end_date : '',
            'daysLeft' => (string) $daysLeft,
            'dateTime' => now()->format('d M Y, h:i A'),
            'downloadUrl' => config('app.url'),
        ];

        $meta = [
            'company_id' => $company->id,
            'triggered_by_type' => $triggeredBy !== null ? 'user' : 'system',
            'triggered_by_id' => $triggeredBy?->id,
            'source' => 'company_plan_expiring_soon',
        ];

        foreach ($recipients as $recipient) {
            $variables = array_merge($baseVariables, ['name' => $recipient['name']]);
            $this->mailSender->sendTemplate($template, $recipient['email'], $variables, $meta);
        }
    }

    public function notifyPlanExpired(Company $company, ?User $triggeredBy = null): void
    {
        $template = $this->activeTemplate('COMPANY_PLAN_EXPIRED');
        if ($template === null) {
            return;
        }

        $recipients = $this->resolveRecipientsForTemplate($company, $template, ['super_admin', 'admin', 'accountant']);
        if ($recipients->isEmpty()) {
            return;
        }

        $baseVariables = [
            'companyName' => (string) ($company->name ?? ''),
            'planEndDate' => $company->plan_end_date ? (string) $company->plan_end_date : '',
            'dateTime' => now()->format('d M Y, h:i A'),
            'downloadUrl' => config('app.url'),
        ];

        $meta = [
            'company_id' => $company->id,
            'triggered_by_type' => $triggeredBy !== null ? 'user' : 'system',
            'triggered_by_id' => $triggeredBy?->id,
            'source' => 'company_plan_expired',
        ];

        foreach ($recipients as $recipient) {
            $variables = array_merge($baseVariables, ['name' => $recipient['name']]);
            $this->mailSender->sendTemplate($template, $recipient['email'], $variables, $meta);
        }
    }

    public function notifyCreditLow(Company $company, int $threshold, int $lrCredits, ?User $triggeredBy = null): void
    {
        $template = $this->activeTemplate('COMPANY_CREDIT_LOW');
        if ($template === null) {
            return;
        }

        $recipients = $this->resolveRecipientsForTemplate($company, $template, ['super_admin', 'admin']);
        if ($recipients->isEmpty()) {
            return;
        }

        $baseVariables = [
            'companyName' => (string) ($company->name ?? ''),
            'creditThreshold' => (string) $threshold,
            'lrCredits' => (string) $lrCredits,
            'dateTime' => now()->format('d M Y, h:i A'),
            'downloadUrl' => config('app.url'),
        ];

        $meta = [
            'company_id' => $company->id,
            'triggered_by_type' => $triggeredBy !== null ? 'user' : 'system',
            'triggered_by_id' => $triggeredBy?->id,
            'source' => 'company_credit_low',
        ];

        foreach ($recipients as $recipient) {
            $variables = array_merge($baseVariables, ['name' => $recipient['name']]);
            $this->mailSender->sendTemplate($template, $recipient['email'], $variables, $meta);
        }
    }

    private function activeTemplate(string $key): ?EmailTemplate
    {
        return EmailTemplate::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @param  array<int, string>  $fallbackRoles
     * @return Collection<int, array{email: string, name: string}>
     */
    private function resolveRecipientsForTemplate(Company $company, EmailTemplate $template, array $fallbackRoles): Collection
    {
        $templateRoles = collect($template->allowed_roles ?? [])
            ->filter(static fn ($r): bool => is_string($r) && $r !== '')
            ->values()
            ->all();

        if ($templateRoles === []) {
            $templateRoles = $fallbackRoles;
        }

        /** @var array<string, array{email: string, name: string}> $byKey */
        $byKey = [];

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

            $key = strtolower(trim($email));
            $byKey[$key] = [
                'email' => $email,
                'name' => $user->name ?? 'User',
            ];
        }

        return collect($byKey)->values();
    }
}
