<?php

namespace App\Services\Notifications;

use App\Models\EmailTemplate;
use App\Models\FreightBooking;
use App\Models\User;
use App\Services\EmailTemplateMailSender;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FreightBookingNotificationService
{
    private const FR_CANCELLED_KEY = 'FR_STATUS_CANCELLED';

    public function __construct(
        private readonly EmailTemplateMailSender $mailSender
    ) {}

    public function notifyCancelled(FreightBooking $freightBooking, ?User $triggeredBy = null): void
    {
        $template = EmailTemplate::query()
            ->where('key', self::FR_CANCELLED_KEY)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return;
        }

        $freightBooking->loadMissing([
            'company',
            'lr.truck',
            'transporter.transporterMaster',
        ]);

        $recipients = $this->resolveCancelledRecipients($freightBooking, $template);
        if ($recipients->isEmpty()) {
            return;
        }

        $lr = $freightBooking->lr;

        $baseVariables = [
            'companyName' => $freightBooking->company?->name ?? '',
            'lrNumber' => $lr?->lr_number ?? '',
            'truckNumber' => $lr?->truck?->truck_number ?? '',
            'voucherNumber' => $freightBooking->voucher_number !== null ? (string) $freightBooking->voucher_number : '',
            'date' => $lr?->gr_date ? Carbon::parse((string) $lr->gr_date)->format('d M Y') : '',
            'dateTime' => now()->format('d M Y, h:i A'),
            'status' => 'Cancelled',
            'downloadUrl' => config('app.url'),
        ];

        $meta = [
            'company_id' => $freightBooking->company_id,
            'triggered_by_type' => $triggeredBy !== null ? 'user' : 'system',
            'triggered_by_id' => $triggeredBy?->id,
            'source' => 'freight_booking_cancelled',
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
    private function resolveCancelledRecipients(FreightBooking $freightBooking, EmailTemplate $template): Collection
    {
        /** @var array<string, array{email: string, name: string}> $byKey */
        $byKey = [];

        $templateRoles = collect($template->allowed_roles ?? [])
            ->filter(static fn ($r): bool => is_string($r) && $r !== '')
            ->values()
            ->all();

        if ($templateRoles === []) {
            $templateRoles = ['admin', 'moderator', 'accountant'];
        }

        $users = User::query()
            ->with('userRole')
            ->where('company_id', $freightBooking->company_id)
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
            $transporterEmail = $freightBooking->transporter?->transporterMaster?->email;
            if (is_string($transporterEmail) && filter_var($transporterEmail, FILTER_VALIDATE_EMAIL) !== false) {
                $transporter = $freightBooking->transporter;
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
