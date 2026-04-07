<?php

namespace App\Services\Notifications;

use App\Models\EmailTemplate;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Services\EmailTemplateMailSender;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LorryReceiptNotificationService
{
    public function __construct(
        private readonly EmailTemplateMailSender $mailSender
    ) {}

    public function notifyStatusChange(LorryReceipt $lorryReceipt, string $status, ?User $triggeredBy = null): void
    {
        $eventKey = $this->eventKeyFromStatus($status);
        if ($eventKey === null) {
            return;
        }

        $template = EmailTemplate::query()
            ->where('key', $eventKey)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return;
        }

        $lorryReceipt->loadMissing([
            'company',
            'truck',
            'customer',
            'consignee',
            'transporter.transporterMaster',
        ]);

        $recipients = $this->resolveRecipients($lorryReceipt, $eventKey, $template);
        if ($recipients->isEmpty()) {
            return;
        }

        $baseVariables = [
            'companyName' => $lorryReceipt->company?->name ?? '',
            'lrNumber' => $lorryReceipt->lr_number ?? '',
            'truckNumber' => $lorryReceipt->truck?->truck_number ?? '',
            'date' => $lorryReceipt->gr_date ? Carbon::parse((string) $lorryReceipt->gr_date)->format('d M Y') : '',
            'dateTime' => $lorryReceipt->gr_date ? Carbon::parse((string) $lorryReceipt->gr_date)->format('d M Y, h:i A') : '',
            'lrCreateDate' => $lorryReceipt->created_at ? $lorryReceipt->created_at->format('d M Y, h:i A') : '',
            'status' => $status,
            'downloadUrl' => config('app.url'),
        ];

        $meta = [
            'company_id' => $lorryReceipt->company_id,
            'triggered_by_type' => $triggeredBy !== null ? 'user' : 'system',
            'triggered_by_id' => $triggeredBy?->id,
            'source' => 'lr_status_change',
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
    private function resolveRecipients(LorryReceipt $lorryReceipt, string $eventKey, EmailTemplate $template): Collection
    {
        $matrix = [
            'LR_STATUS_PENDING' => [
                'allow_internal_roles' => false,
                'transporter' => false,
                'customer' => false,
            ],
            'LR_STATUS_IN_PROGRESS' => [
                'allow_internal_roles' => true,
                'transporter' => true,
                'customer' => true,
            ],
            'LR_STATUS_DELIVERED' => [
                'allow_internal_roles' => true,
                'transporter' => true,
                'customer' => true,
            ],
        ];

        $rule = $matrix[$eventKey] ?? null;
        if ($rule === null) {
            return collect();
        }

        /** @var array<string, array{email: string, name: string}> $byKey */
        $byKey = [];

        $templateRoles = collect($template->allowed_roles ?? [])
            ->filter(static fn ($r): bool => is_string($r) && $r !== '')
            ->values()
            ->all();

        if ($rule['allow_internal_roles'] && $templateRoles !== []) {
            $users = User::query()
                ->with('userRole')
                ->where('company_id', $lorryReceipt->company_id)
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
        }

        if ($template->send_to_transporter && $rule['transporter']) {
            $transporterEmail = $lorryReceipt->transporter?->transporterMaster?->email;
            if (is_string($transporterEmail) && filter_var($transporterEmail, FILTER_VALIDATE_EMAIL) !== false) {
                $transporterName = $lorryReceipt->transporter->name
                    ?? $lorryReceipt->transporter->transporterMaster?->name
                    ?? $lorryReceipt->transporter->contact_person_name
                    ?? 'User';
                $key = $this->normalizeEmailKey($transporterEmail);
                $byKey[$key] = [
                    'email' => $transporterEmail,
                    'name' => $transporterName,
                ];
            }
        }

        if ($template->send_to_customer && $rule['customer']) {
            $customerEmail = $lorryReceipt->customer?->email;
            if (is_string($customerEmail) && filter_var($customerEmail, FILTER_VALIDATE_EMAIL) !== false) {
                $customerName = $lorryReceipt->customer?->name
                    ?? $lorryReceipt->consignee?->name
                    ?? 'User';
                $key = $this->normalizeEmailKey($customerEmail);
                $byKey[$key] = [
                    'email' => $customerEmail,
                    'name' => $customerName,
                ];
            }
        }

        return collect($byKey)->values();
    }

    private function normalizeEmailKey(string $email): string
    {
        return strtolower(trim($email));
    }

    private function eventKeyFromStatus(string $status): ?string
    {
        return match ($status) {
            'Pending' => 'LR_STATUS_PENDING',
            'In-Progress' => 'LR_STATUS_IN_PROGRESS',
            'Delivered' => 'LR_STATUS_DELIVERED',
            default => null,
        };
    }
}
