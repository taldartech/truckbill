<?php

namespace App\Observers\Accounting;

use App\Models\InvoicePayment;
use App\Services\AccountingPostingService;
use Illuminate\Support\Facades\Log;

class InvoicePaymentObserver
{
    public function __construct(
        private readonly AccountingPostingService $postingService
    ) {}

    public function saved(InvoicePayment $payment): void
    {
        try {
            $payment->loadMissing(['invoice.company', 'invoice.consignee']);
            if ($payment->invoice?->company) {
                $this->postingService->postInvoicePayment($payment);
            }
        } catch (\Throwable $e) {
            Log::error("Accounting sync error for payment #{$payment->id}: {$e->getMessage()}");
        }
    }

    public function deleted(InvoicePayment $payment): void
    {
        try {
            $this->postingService->deleteVoucherFor(InvoicePayment::class, $payment->id);
        } catch (\Throwable $e) {
            Log::error("Accounting delete error for payment #{$payment->id}: {$e->getMessage()}");
        }
    }
}
