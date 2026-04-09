<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyReminderLog;
use App\Services\Notifications\CompanyLifecycleNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendCompanyPlanLifecycleReminders extends Command
{
    protected $signature = 'company:send-plan-lifecycle-reminders {--expiring-days=30,15,10 : Comma separated expiring day offsets}';

    protected $description = 'Send company plan expiring and expired reminders';

    public function __construct(
        private readonly CompanyLifecycleNotificationService $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = Carbon::today();
        $sentOn = $today->toDateString();

        $expiringDays = collect(explode(',', (string) $this->option('expiring-days')))
            ->map(fn ($value): int => (int) trim($value))
            ->filter(fn (int $value): bool => $value > 0)
            ->values()
            ->all();

        if ($expiringDays === []) {
            $expiringDays = [30, 15, 10];
        }

        $sentCount = 0;

        foreach ($expiringDays as $daysLeft) {
            $targetDate = $today->copy()->addDays($daysLeft)->toDateString();

            $companies = Company::query()
                ->whereDate('plan_end_date', $targetDate)
                ->get();

            foreach ($companies as $company) {
                $reminderKey = 'plan_expiring_'.$daysLeft.'d';
                if ($this->alreadySent($company->id, $reminderKey, $sentOn)) {
                    continue;
                }

                $this->notificationService->notifyPlanExpiringSoon($company, $daysLeft);
                $this->storeLog($company->id, $reminderKey, $sentOn);
                $sentCount++;
            }
        }

        $expiredCompanies = Company::query()
            ->whereDate('plan_end_date', '<', $today->toDateString())
            ->get();

        foreach ($expiredCompanies as $company) {
            $reminderKey = 'plan_expired';
            if ($this->alreadySent($company->id, $reminderKey, $sentOn)) {
                continue;
            }

            $this->notificationService->notifyPlanExpired($company);
            $this->storeLog($company->id, $reminderKey, $sentOn);
            $sentCount++;
        }

        $this->info("Company lifecycle reminders sent: {$sentCount}");

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
