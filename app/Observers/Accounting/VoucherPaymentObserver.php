<?php

namespace App\Observers\Accounting;

use App\Models\VoucherPayment;
use App\Services\AccountingPostingService;
use Illuminate\Support\Facades\Log;

class VoucherPaymentObserver
{
    public function __construct(
        private readonly AccountingPostingService $postingService
    ) {}

    public function saved(VoucherPayment $voucherPayment): void
    {
        try {
            $voucherPayment->loadMissing(['company', 'freight_booking.transporter', 'freight_booking.truck', 'pump']);
            if ($voucherPayment->company) {
                $this->postingService->postVoucherPayment($voucherPayment);
            }
        } catch (\Throwable $e) {
            Log::error("Accounting sync error for voucher payment #{$voucherPayment->id}: {$e->getMessage()}");
        }
    }

    public function deleted(VoucherPayment $voucherPayment): void
    {
        try {
            $this->postingService->deleteVoucherFor(VoucherPayment::class, $voucherPayment->id, $voucherPayment->company_id);
        } catch (\Throwable $e) {
            Log::error("Accounting delete error for voucher payment #{$voucherPayment->id}: {$e->getMessage()}");
        }
    }
}
