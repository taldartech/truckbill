<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\LorryReceipt;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FixInvoiceLrIdsCompanyMismatch extends Command
{
    protected $signature = 'invoices:fix-lr-ids-company-mismatch
                            {--apply : Apply changes to the database (default is dry run)}
                            {--list : List affected invoices only (no changes)}
                            {--company-id= : Process only invoices for this company id}';

    protected $description = 'Remove lr_ids from invoices when the LR belongs to another company; reset invoice_generated when no invoice exists for that LR in its company';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listAffectedInvoices();
        }

        $dryRun = ! $this->option('apply');

        $this->info('========================================');
        $this->info('Fix Invoice LR IDs Company Mismatch');
        $this->info('========================================');
        $this->info('Mode: '.($dryRun ? 'DRY RUN (no changes will be made)' : 'LIVE (changes will be saved)'));
        $this->newLine();

        if (! $dryRun && $this->input->isInteractive()) {
            if (! $this->confirm('This will update invoices and lorry_receipts. Continue?')) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $invoicesChecked = 0;
        $invoicesFixed = 0;
        $lrIdsRemoved = 0;
        $lrsReset = 0;
        $errors = 0;

        foreach ($this->invoiceQuery()->cursor() as $invoice) {
            $invoicesChecked++;

            try {
                $result = $this->processInvoice($invoice, $dryRun);

                if ($result === null) {
                    continue;
                }

                $invoicesFixed++;
                $lrIdsRemoved += $result['removed_count'];
                $lrsReset += $result['reset_count'];

                $this->line(sprintf(
                    'Invoice #%d (%s, company %d): removed %d lr_id(s), reset invoice_generated on %d LR(s)',
                    $invoice->id,
                    $invoice->invoice_number ?? 'N/A',
                    $invoice->company_id,
                    $result['removed_count'],
                    $result['reset_count']
                ));

                foreach ($result['details'] as $detail) {
                    $this->line(sprintf(
                        '  LR #%d (%s): LR company %d %s',
                        $detail['lr_id'],
                        $detail['lr_number'],
                        $detail['lr_company_id'],
                        $detail['action']
                    ));
                }
            } catch (Throwable $exception) {
                $errors++;
                Log::error('Failed fixing invoice lr_ids company mismatch', [
                    'invoice_id' => $invoice->id,
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);
                $this->error("Invoice #{$invoice->id}: {$exception->getMessage()}");
            }
            $this->newLine();
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('Summary');
        $this->info('========================================');
        $this->info("Invoices checked: {$invoicesChecked}");
        $this->info("Invoices updated: {$invoicesFixed}");
        $this->info("LR IDs removed from invoices: {$lrIdsRemoved}");
        $this->info("LRs marked invoice not generated: {$lrsReset}");
        $this->info("Errors: {$errors}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN complete. Re-run with --apply to persist changes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    public function listAffectedInvoices(): int
    {
        $this->info('Invoices with lr_ids that do not belong to the invoice company');
        $this->newLine();

        $rows = [];
        $detailLines = [];

        foreach ($this->invoiceQuery()->cursor() as $invoice) {
            $result = $this->analyzeInvoice($invoice);

            if ($result === null) {
                continue;
            }

            $rows[] = [
                $invoice->id,
                $invoice->invoice_number ?? '—',
                $invoice->company_id,
                $result['removed_count'],
                $result['reset_count'],
            ];

            foreach ($result['details'] as $detail) {
                $detailLines[] = [
                    $invoice->id,
                    $detail['lr_id'],
                    $detail['lr_number'],
                    $detail['lr_company_id'],
                    $detail['action'],
                ];
            }
        }

        if ($rows === []) {
            $this->info('No affected invoices found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Invoice ID', 'Invoice #', 'Invoice company', 'Bad lr_ids', 'Would reset LRs'],
            $rows
        );

        $this->newLine();
        $this->info('Mismatched LR details:');
        $this->table(
            ['Invoice ID', 'LR ID', 'LR #', 'LR company', 'Note'],
            $detailLines
        );

        $this->newLine();
        $this->info('Total affected invoices: '.count($rows));

        return self::SUCCESS;
    }

    private function invoiceQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Invoice::query()
            ->whereNotNull('lr_ids')
            ->orderBy('id');

        if ($this->option('company-id')) {
            $query->where('company_id', (int) $this->option('company-id'));
        }

        return $query;
    }

    /**
     * @return array{
     *     removed_count: int,
     *     reset_count: int,
     *     details: list<array{lr_id: int, lr_number: string, lr_company_id: int, action: string}>
     * }|null
     */
    private function analyzeInvoice(Invoice $invoice): ?array
    {
        return $this->processInvoice($invoice, true);
    }

    /**
     * @return array{
     *     removed_count: int,
     *     reset_count: int,
     *     details: list<array{lr_id: int, lr_number: string, lr_company_id: int, action: string}>
     * }|null
     */
    private function processInvoice(Invoice $invoice, bool $dryRun): ?array
    {
        $lrIds = $this->normalizeLrIds($invoice->lr_ids);

        if ($lrIds->isEmpty()) {
            return null;
        }

        $lrs = LorryReceipt::query()
            ->whereIn('id', $lrIds)
            ->get()
            ->keyBy('id');

        $idsToRemove = [];
        $lrsToReset = [];
        $details = [];

        foreach ($lrIds as $lrId) {
            $lr = $lrs->get($lrId);

            if (! $lr) {
                $idsToRemove[] = $lrId;
                $details[] = [
                    'lr_id' => $lrId,
                    'lr_number' => 'missing',
                    'lr_company_id' => 0,
                    'action' => 'removed orphan lr_id from invoice',
                ];

                continue;
            }

            if ((int) $lr->company_id === (int) $invoice->company_id) {
                continue;
            }

            $idsToRemove[] = $lrId;

            $existsInLrCompany = $this->lrExistsOnCompanyInvoice(
                $lrId,
                (int) $lr->company_id,
                (int) $invoice->id
            );

            if ($existsInLrCompany) {
                $details[] = [
                    'lr_id' => $lrId,
                    'lr_number' => (string) $lr->lr_number,
                    'lr_company_id' => (int) $lr->company_id,
                    'action' => 'removed from invoice only (linked on company invoice)',
                ];
            } else {
                $lrsToReset[] = $lr;
                $details[] = [
                    'lr_id' => $lrId,
                    'lr_number' => (string) $lr->lr_number,
                    'lr_company_id' => (int) $lr->company_id,
                    'action' => 'removed from invoice and set invoice_generated = 0',
                ];
            }
        }

        if ($idsToRemove === []) {
            return null;
        }

        $newLrIds = $lrIds
            ->diff($idsToRemove)
            ->values()
            ->all();

        if (! $dryRun) {
            DB::transaction(function () use ($invoice, $newLrIds, $lrsToReset): void {
                $invoice->lr_ids = $newLrIds;
                $invoice->save();

                foreach ($lrsToReset as $lr) {
                    $lr->invoice_generated = 0;
                    $lr->save();
                }
            });
        }

        return [
            'removed_count' => count($idsToRemove),
            'reset_count' => count($lrsToReset),
            'details' => $details,
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function normalizeLrIds(mixed $lrIds): Collection
    {
        return collect(is_array($lrIds) ? $lrIds : [])
            ->filter(static fn ($id) => $id !== null && $id !== '')
            ->map(static fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function lrExistsOnCompanyInvoice(int $lrId, int $companyId, int $excludeInvoiceId): bool
    {
        return Invoice::query()
            ->where('company_id', $companyId)
            ->where('id', '!=', $excludeInvoiceId)
            ->where(function ($query) use ($lrId): void {
                $query->orWhereJsonContains('lr_ids', $lrId)
                    ->orWhereJsonContains('lr_ids', (string) $lrId);
            })
            ->exists();
    }
}
