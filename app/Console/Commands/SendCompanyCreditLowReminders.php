<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyReminderLog;
use App\Services\Notifications\CompanyLifecycleNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendCompanyCreditLowReminders extends Command
{
    protected $signature = 'company:send-credit-low-reminders {--thresholds=100,50,25 : Comma separated LR credit thresholds}';

    protected $description = 'Send low company credit reminders';

    public function __construct(
        private readonly CompanyLifecycleNotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today();
        $sentOn = $today->toDateString();

        $thresholds = collect(explode(',', (string) $this->option('thresholds')))
            ->map(fn ($value): int => (int) trim($value))
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if ($thresholds === []) {
            $thresholds = [100, 50, 25];
        }

        $sentCount = 0;

        foreach ($thresholds as $threshold) {
            $companies = Company::query()
                ->whereNotNull('lr_credits')
                ->where('lr_credits', '<', $threshold)
                ->get();

            foreach ($companies as $company) {
                $reminderKey = 'credit_low_lt_'.$threshold;
                if ($this->alreadySent($company->id, $reminderKey, $sentOn)) {
                    continue;
                }

                $this->notificationService->notifyCreditLow($company, $threshold, (int) ($company->lr_credits ?? 0));
                $this->storeLog($company->id, $reminderKey, $sentOn);
                $sentCount++;
            }
        }

        $this->info("Company credit low reminders sent: {$sentCount}");

        return self::SUCCESS;
    }

    private function alreadySent(int $companyId, string $reminderKey, string $sentOn): bool
    {
        return CompanyReminderLog::query()
            ->where('company_id', $companyId)
            ->where('reminder_key', $reminderKey)
            ->whereDate('sent_on', $sentOn)
            ->exists();
    }

    private function storeLog(int $companyId, string $reminderKey, string $sentOn): void
    {
        CompanyReminderLog::query()->create([
            'company_id' => $companyId,
            'reminder_key' => $reminderKey,
            'sent_on' => $sentOn,
        ]);
    }
}
