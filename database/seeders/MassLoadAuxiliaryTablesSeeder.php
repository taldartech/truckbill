<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Adds LOAD_TEST_AUX_ROW_COUNT (default 100000) rows to remaining operational tables.
 *
 * Skips lookup / system (per product rules): users, user_roles, countries, states, cities,
 * payment_types, payment_modes, truck_types, migrations, cache*, sessions, jobs*, failed_jobs,
 * password_reset_tokens, pulse_*, referrals (needs many distinct user pairs), companies (needs user owner).
 *
 * Run after {@see MassLoadAllMainTablesSeeder} so invoices, freight_bookings, leads, ledgers exist.
 *
 * Env:
 * - LOAD_TEST_AUX_ROW_COUNT (default 100000)
 * - LOAD_TEST_BATCH_SIZE (default 2000, max 2000)
 * - LOAD_TEST_COMPANY_ID
 */
class MassLoadAuxiliaryTablesSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $target = max((int) env('LOAD_TEST_AUX_ROW_COUNT', (int) env('LOAD_TEST_ROW_COUNT', 100_000)), 1);
        $requestedBatch = max((int) env('LOAD_TEST_BATCH_SIZE', 2000), 1);
        $batch = min($requestedBatch, 2000);

        $companyId = (int) env('LOAD_TEST_COMPANY_ID', 0);
        if ($companyId <= 0) {
            $companyId = (int) Company::query()->value('id');
        }
        if ($companyId <= 0) {
            throw new RuntimeException('No company. Set LOAD_TEST_COMPANY_ID or seed companies.');
        }

        $userId = (int) (DB::table('users')->orderBy('id')->value('id') ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('At least one user is required (for FKs). Seed users first.');
        }

        $runToken = now()->format('YmdHis');
        $now = now();
        $nowStr = $now->toDateTimeString();
        $dateStr = $now->toDateString();

        if ($requestedBatch !== $batch && $this->command) {
            $this->command->warn("Batch size capped to {$batch}.");
        }

        $this->seedGoodTypesAndPumps($target, $batch, $userId, $runToken, $nowStr);
        $this->seedPromoCodes($target, $batch, $runToken, $nowStr, $dateStr);
        $this->seedEmailTemplates($target, $batch, $companyId, $runToken, $nowStr);
        $this->seedEmailDispatchLogs($target, $batch, $companyId, $runToken, $nowStr);
        $this->seedCompanyReminderLogs($target, $batch, $companyId, $runToken, $dateStr, $nowStr);
        $this->seedAuditLogsWithChanges($target, $batch, $companyId, $userId, $runToken, $now);
        $this->seedInvoiceReminderLogs($target, $companyId, $runToken, $dateStr, $nowStr);
        $this->seedEwayBillReminderLogs($target, $companyId, $runToken, $dateStr, $nowStr);
        $this->seedCustomInvoiceDetails($target, $userId, $nowStr);
        $this->seedUpcomingPlans($target, $companyId, $dateStr, $nowStr);

        if ($this->command) {
            $this->command->info("Auxiliary mass load finished (target {$target} per table where FK data exists).");
        }
    }

    private function seedGoodTypesAndPumps(int $target, int $batch, int $userId, string $runToken, string $nowStr): void
    {
        $inserted = 0;
        while ($inserted < $target) {
            $chunk = min($batch, $target - $inserted);
            $base = $inserted + 1;
            $gt = [];
            $pu = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $base + $i;
                $gt[] = [
                    'user_id' => $userId,
                    'name' => "Mass Good {$runToken} {$seq}",
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
                $pu[] = [
                    'user_id' => $userId,
                    'pump_name' => "Mass Pump {$runToken} {$seq}",
                    'contact_name' => 'Contact',
                    'remark' => null,
                    'contact_number' => substr(hash('sha256', "pump|{$runToken}|{$seq}"), 0, 15),
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
            }
            DB::table('good_types')->insert($gt);
            DB::table('pumps')->insert($pu);
            $inserted += $chunk;
        }
    }

    private function seedPromoCodes(int $target, int $batch, string $runToken, string $nowStr, string $dateStr): void
    {
        $inserted = 0;
        while ($inserted < $target) {
            $chunk = min($batch, $target - $inserted);
            $base = $inserted + 1;
            $rows = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $base + $i;
                $rows[] = [
                    'code' => 'PC'.substr(hash('sha256', "{$runToken}|{$seq}"), 0, 20),
                    'discount_type' => 'percentage',
                    'discount_value' => 5.0,
                    'start_date' => $dateStr,
                    'end_date' => $dateStr,
                    'max_uses' => 1000,
                    'is_active' => true,
                    'deleted_at' => null,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
            }
            DB::table('promo_codes')->insert($rows);
            $inserted += $chunk;
        }
    }

    private function seedEmailTemplates(int $target, int $batch, int $companyId, string $runToken, string $nowStr): void
    {
        $inserted = 0;
        while ($inserted < $target) {
            $chunk = min($batch, $target - $inserted);
            $base = $inserted + 1;
            $rows = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $base + $i;
                $key = 'mass_tpl_'.$runToken.'_'.$seq;
                $rows[] = [
                    'key' => $key,
                    'name' => 'Mass Template '.$seq,
                    'company_id' => $companyId,
                    'subject' => 'Subject '.$seq,
                    'body_html' => '<p>Mass body '.$seq.'</p>',
                    'allowed_variables' => json_encode([]),
                    'allowed_roles' => json_encode([]),
                    'send_to_transporter' => false,
                    'send_to_customer' => false,
                    'is_active' => true,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
            }
            DB::table('email_templates')->insert($rows);
            $inserted += $chunk;
        }
    }

    private function seedEmailDispatchLogs(int $target, int $batch, int $companyId, string $runToken, string $nowStr): void
    {
        $inserted = 0;
        while ($inserted < $target) {
            $chunk = min($batch, $target - $inserted);
            $base = $inserted + 1;
            $rows = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $base + $i;
                $rows[] = [
                    'email_template_id' => null,
                    'company_id' => $companyId,
                    'event_key' => 'mass_event',
                    'recipient_email' => "mass_edl_{$runToken}_{$seq}@example.test",
                    'subject_snapshot' => 'Snap '.$seq,
                    'body_snapshot' => 'Body',
                    'status' => 'queued',
                    'triggered_by_type' => 'system',
                    'triggered_by_id' => null,
                    'source' => 'mass_seed',
                    'error_message' => null,
                    'queued_at' => $nowStr,
                    'sent_at' => null,
                    'delivered_at' => null,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
            }
            DB::table('email_dispatch_logs')->insert($rows);
            $inserted += $chunk;
        }
    }

    private function seedCompanyReminderLogs(int $target, int $batch, int $companyId, string $runToken, string $dateStr, string $nowStr): void
    {
        $inserted = 0;
        while ($inserted < $target) {
            $chunk = min($batch, $target - $inserted);
            $base = $inserted + 1;
            $rows = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $base + $i;
                $rows[] = [
                    'company_id' => $companyId,
                    'reminder_key' => 'mass_cr_'.$runToken.'_'.$seq,
                    'sent_on' => $dateStr,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ];
            }
            try {
                DB::table('company_reminder_logs')->insert($rows);
            } catch (\Throwable $e) {
                if ($this->command) {
                    $this->command->warn('company_reminder_logs: '.$e->getMessage());
                }
            }
            $inserted += $chunk;
        }
    }

    private function seedAuditLogsWithChanges(int $target, int $batch, int $companyId, int $userId, string $runToken, \Illuminate\Support\Carbon $now): void
    {
        $inserted = 0;
        while ($inserted < $target) {
            $chunk = min($batch, $target - $inserted);
            $base = $inserted + 1;
            $logRows = [];
            for ($i = 0; $i < $chunk; $i++) {
                $seq = $base + $i;
                $summary = "mass_audit_{$runToken}_{$seq}";
                $logRows[] = [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'action' => 'created',
                    'subject_type' => 'App\\Models\\LorryReceipt',
                    'subject_id' => $seq,
                    'subject_label' => 'LR',
                    'subject_type_name' => 'LorryReceipt',
                    'summary' => $summary,
                    'occurred_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('audit_logs')->insert($logRows);

            $summaries = [];
            for ($i = 0; $i < $chunk; $i++) {
                $summaries[] = "mass_audit_{$runToken}_".($base + $i);
            }

            $ids = DB::table('audit_logs')
                ->whereIn('summary', $summaries)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $changeRows = [];
            foreach ($ids as $auditLogId) {
                $changeRows[] = [
                    'audit_log_id' => $auditLogId,
                    'attribute_name' => 'status',
                    'field_label' => 'Status',
                    'old_value' => 'a',
                    'new_value' => 'b',
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($changeRows !== []) {
                DB::table('audit_log_changes')->insert($changeRows);
            }

            $inserted += $chunk;
        }
    }

    private function seedInvoiceReminderLogs(int $target, int $companyId, string $runToken, string $dateStr, string $nowStr): void
    {
        $invoiceIds = DB::table('invoices')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->limit($target)
            ->pluck('id')
            ->all();

        if (count($invoiceIds) === 0) {
            if ($this->command) {
                $this->command->warn('invoice_reminder_logs: no invoices; skip.');
            }

            return;
        }

        $rows = [];
        foreach ($invoiceIds as $i => $invId) {
            $rows[] = [
                'invoice_id' => (int) $invId,
                'company_id' => $companyId,
                'reminder_key' => 'irm_'.$runToken.'_'.$invId,
                'sent_on' => $dateStr,
                'created_at' => $nowStr,
                'updated_at' => $nowStr,
            ];
        }
        try {
            DB::table('invoice_reminder_logs')->insert($rows);
        } catch (\Throwable $e) {
            if ($this->command) {
                $this->command->warn('invoice_reminder_logs: '.$e->getMessage());
            }
        }
    }

    private function seedEwayBillReminderLogs(int $target, int $companyId, string $runToken, string $dateStr, string $nowStr): void
    {
        $fbIds = DB::table('freight_bookings')
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->limit($target)
            ->pluck('id')
            ->all();

        if (count($fbIds) === 0) {
            if ($this->command) {
                $this->command->warn('eway_bill_reminder_logs: no freight_bookings; skip.');
            }

            return;
        }

        $rows = [];
        foreach ($fbIds as $fbId) {
            $rows[] = [
                'freight_booking_id' => (int) $fbId,
                'company_id' => $companyId,
                'reminder_key' => 'ewb_'.$runToken.'_'.$fbId,
                'sent_on' => $dateStr,
                'created_at' => $nowStr,
                'updated_at' => $nowStr,
            ];
        }
        try {
            DB::table('eway_bill_reminder_logs')->insert($rows);
        } catch (\Throwable $e) {
            if ($this->command) {
                $this->command->warn('eway_bill_reminder_logs: '.$e->getMessage());
            }
        }
    }

    private function seedCustomInvoiceDetails(int $target, int $userId, string $nowStr): void
    {
        $sql = <<<'SQL'
INSERT INTO custom_invoice_details (
  invoice_id, lr_id, portal_rate, diff, damage_qty, damage_rate, shortage_qty, amount, remark, sort_order,
  created_at, updated_at, created_by, updated_by
)
SELECT i.id, MIN(lr.id), 0, 0, 0, 0, 0, 0, 'mass', 0, ?, ?, ?, ?
FROM invoices i
INNER JOIN lorry_receipts lr
  ON lr.company_id = i.company_id AND lr.customer_id = i.customer_id AND lr.deleted_at IS NULL
WHERE i.deleted_at IS NULL
GROUP BY i.id
LIMIT ?
SQL;
        try {
            DB::statement($sql, [$nowStr, $nowStr, $userId, $userId, $target]);
        } catch (\Throwable $e) {
            if ($this->command) {
                $this->command->warn('custom_invoice_details: '.$e->getMessage());
            }
        }
    }

    private function seedUpcomingPlans(int $target, int $companyId, string $dateStr, string $nowStr): void
    {
        $ledgerIds = DB::table('payment_ledgers')
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->limit($target)
            ->pluck('id')
            ->all();

        if (count($ledgerIds) === 0) {
            if ($this->command) {
                $this->command->warn('upcoming_plans: no payment_ledgers; skip.');
            }

            return;
        }

        $rows = [];
        foreach ($ledgerIds as $lid) {
            $rows[] = [
                'company_id' => $companyId,
                'payment_ledger_id' => (int) $lid,
                'plan_start_date' => $dateStr,
                'plan_end_date' => $dateStr,
                'truck_count' => 1,
                'tenure' => 1,
                'status' => 'pending',
                'created_at' => $nowStr,
                'updated_at' => $nowStr,
            ];
        }
        DB::table('upcoming_plans')->insert($rows);
    }
}
