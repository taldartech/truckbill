<?php

namespace App\Observers\Accounting;

use App\Models\Invoice;
use App\Services\AccountingPostingService;
use Illuminate\Support\Facades\Log;

class InvoiceObserver
{
    public function __construct(
        private readonly AccountingPostingService $postingService
    ) {}

    public function saved(Invoice $invoice): void
    {
        if (! $invoice->company_id) {
            return;
        }

        try {
            $invoice->loadMissing(['company', 'consignee', 'payments']);
            if ($invoice->company) {
                $this->postingService->postInvoice($invoice);
            }
        } catch (\Throwable $e) {
            Log::error("Accounting sync error for invoice #{$invoice->id}: {$e->getMessage()}");
        }
    }

    public function deleted(Invoice $invoice): void
    {
        try {
            $this->postingService->deleteVoucherFor(Invoice::class, $invoice->id, $invoice->company_id);
        } catch (\Throwable $e) {
            Log::error("Accounting delete error for invoice #{$invoice->id}: {$e->getMessage()}");
        }
    }
}
