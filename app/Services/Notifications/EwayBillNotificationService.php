<?php

namespace App\Services\Notifications;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\FreightBooking;
use App\Models\User;
use App\Services\EmailTemplateMailSender;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EwayBillNotificationService
{
    private const TEMPLATE_KEY = 'EWAY_EXPIRY_1D';

    public function __construct(
        private readonly EmailTemplateMailSender $mailSender
    ) {}

    public function notifyExpiry(FreightBooking $freightBooking, int $expiryInDays, ?User $triggeredBy = null): void
    {
        $template = EmailTemplate::query()
            ->where('key', self::TEMPLATE_KEY)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return;
        }

        $freightBooking->loadMissing(['company', 'lr', 'transporter.transporterMaster']);

        $company = $freightBooking->company;
        if ($company === null) {
            return;
        }

        $recipients = $this->resolveRecipients($freightBooking, $template, $company);
        if ($recipients->isEmpty()) {
            return;
        }

        $expiryDate = $freightBooking->eway_bill_expiry
            ? Carbon::parse((string) $freightBooking->eway_bill_expiry)->format('d M Y')
            : '';

        $baseVariables = [
            'companyName' => $company->name ?? '',
            'lrNumber' => (string) ($freightBooking->lr?->lr_number ?? ''),
            'truckNumber' => (string) ($freightBooking->lr?->truck?->truck_number ?? ''),
            'ewayBillNumber' => (string) ($freightBooking->eway_bill_number ?? ''),
            'ewayBillExpiryDate' => $expiryDate,
            'expiryInDays' => (string) $expiryInDays,
            'dateTime' => now()->format('d M Y, h:i A'),
            'downloadUrl' => config('app.url'),
        ];

        $meta = [
            'company_id' => $freightBooking->company_id,
            'triggered_by_type' => $triggeredBy !== null ? 'user' : 'system',
            'triggered_by_id' => $triggeredBy?->id,
            'source' => 'eway_expiry_1d',
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
    private function resolveRecipients(FreightBooking $freightBooking, EmailTemplate $template, Company $company): Collection
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
            $key = strtolower(trim($email));
            $byKey[$key] = [
                'email' => $email,
                'name' => $user->name ?? 'User',
            ];
        }

        return collect($byKey)->values();
    }
}
