<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceReminderLog;
use App\Services\Notifications\InvoiceNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendInvoiceDueReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:send-due-reminders {--days= : Comma separated day offsets (default: 3,1)} {--due-days=7 : Due date offset from generated_date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send invoice due reminders (3-day / 1-day) to customers';

    /**
     * Execute the console command.
     */
    public function __construct(
        private readonly InvoiceNotificationService $invoiceNotificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $daysOption = (string) ($this->option('days') ?? '');
        $days = $daysOption !== ''
            ? collect(explode(',', $daysOption))->map(fn ($v) => (int) trim($v))->filter()->values()->all()
            : [3, 1];

        $dueDays = (int) ($this->option('due-days') ?? 7);
        if ($dueDays <= 0) {
            $dueDays = 7;
        }

        $today = Carbon::today();
        $sentOn = $today->toDateString();

        $totalSent = 0;

        foreach ($days as $dueInDays) {
            $reminderKey = $dueInDays === 1 ? 'due_in_1_day' : 'due_in_3_days';

            // dueDate = generated_date + dueDays
            // eligible when dueDate is exactly today + dueInDays
            $targetDueDate = $today->copy()->addDays($dueInDays)->toDateString();
            $targetGeneratedDate = $today->copy()->addDays($dueInDays - $dueDays)->toDateString();

            $query = Invoice::query()
                ->whereIn('payment_status', ['pending', 'partial'])
                ->whereDate('generated_date', $targetGeneratedDate);

            $invoices = $query->get();

            foreach ($invoices as $invoice) {
                // Skip if already sent today for this invoice & reminderKey
                $alreadySent = InvoiceReminderLog::query()
                    ->where('invoice_id', $invoice->id)
                    ->where('reminder_key', $reminderKey)
                    ->whereDate('sent_on', $sentOn)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $invoice->loadMissing(['company', 'consignee', 'payments']);

                // Safety: if due amount already 0, skip
                $amountToCollect = (float) ($invoice->net_amount ?? $invoice->total_amount ?? 0);
                $paidAmount = (float) $invoice->payments->sum('amount');
                if (($amountToCollect - $paidAmount) <= 0.01) {
                    continue;
                }

                $this->invoiceNotificationService->notifyDueReminder($invoice, $dueInDays);

                InvoiceReminderLog::query()->create([
                    'invoice_id' => $invoice->id,
                    'company_id' => $invoice->company_id,
                    'reminder_key' => $reminderKey,
                    'sent_on' => $sentOn,
                ]);

                $totalSent++;
            }

            $this->line("Target due date {$targetDueDate}: processed ".count($invoices)." invoices for reminder {$reminderKey}");
        }

        $this->info("Invoice due reminders sent: {$totalSent}");

        return self::SUCCESS;
    }
}
