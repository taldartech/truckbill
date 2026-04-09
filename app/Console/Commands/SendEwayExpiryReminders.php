<?php

namespace App\Console\Commands;

use App\Models\EwayBillReminderLog;
use App\Models\FreightBooking;
use App\Services\Notifications\EwayBillNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendEwayExpiryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eway:send-expiry-reminders {--days=1 : Days before expiry to remind}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send E-Way Bill expiry reminders to office roles';

    /**
     * Execute the console command.
     */
    public function __construct(
        private readonly EwayBillNotificationService $ewayBillNotificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?? 1));
        $targetDate = Carbon::today()->addDays($days)->toDateString();
        $sentOn = Carbon::today()->toDateString();
        $reminderKey = 'expiry_in_'.$days.'_days';

        $bookings = FreightBooking::query()
            ->whereNotNull('eway_bill_number')
            ->whereNotNull('eway_bill_expiry')
            ->where(function ($query): void {
                $query->whereNull('is_cancelled')
                    ->orWhere('is_cancelled', 0)
                    ->orWhere('is_cancelled', '0');
            })
            ->where(function ($query): void {
                $query->whereNull('is_delivered')
                    ->orWhere('is_delivered', 0)
                    ->orWhere('is_delivered', '0');
            })
            ->whereDate('eway_bill_expiry', $targetDate)
            ->get();

        $sentCount = 0;

        foreach ($bookings as $booking) {
            $alreadySent = EwayBillReminderLog::query()
                ->where('freight_booking_id', $booking->id)
                ->where('reminder_key', $reminderKey)
                ->whereDate('sent_on', $sentOn)
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $booking->loadMissing(['company', 'lr', 'transporter.transporterMaster']);
            $this->ewayBillNotificationService->notifyExpiry($booking, $days);

            EwayBillReminderLog::query()->create([
                'freight_booking_id' => $booking->id,
                'company_id' => $booking->company_id,
                'reminder_key' => $reminderKey,
                'sent_on' => $sentOn,
            ]);

            $sentCount++;
        }

        $this->info("E-Way expiry reminders sent: {$sentCount}");

        return self::SUCCESS;
    }
}
