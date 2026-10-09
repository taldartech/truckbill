<?php

namespace App\Observers\Accounting;

use App\Models\AdvancePayment;
use App\Services\AccountingPostingService;
use Illuminate\Support\Facades\Log;

class AdvancePaymentObserver
{
    public function __construct(
        private readonly AccountingPostingService $postingService
    ) {}

    public function saved(AdvancePayment $advancePayment): void
    {
        try {
            $advancePayment->loadMissing(['company', 'transporter']);
            if ($advancePayment->company) {
                $this->postingService->postAdvancePayment($advancePayment);
            }
        } catch (\Throwable $e) {
            Log::error("Accounting sync error for advance payment #{$advancePayment->id}: {$e->getMessage()}");
        }
    }

    public function deleted(AdvancePayment $advancePayment): void
    {
        try {
            $this->postingService->deleteVoucherFor(AdvancePayment::class, $advancePayment->id, $advancePayment->company_id);
        } catch (\Throwable $e) {
            Log::error("Accounting delete error for advance payment #{$advancePayment->id}: {$e->getMessage()}");
        }
    }
}
