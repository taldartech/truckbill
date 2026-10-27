<?php

namespace App\Observers\Accounting;

use App\Models\FreightBooking;
use App\Services\AccountingPostingService;
use Illuminate\Support\Facades\Log;

class FreightBookingObserver
{
    public function __construct(
        private readonly AccountingPostingService $postingService
    ) {}

    public function saved(FreightBooking $booking): void
    {
        try {
            $booking->loadMissing(['company', 'transporter', 'truck', 'lr', 'customAmounts']);
            if ($booking->company) {
                $this->postingService->postFreightBooking($booking);
            }
        } catch (\Throwable $e) {
            Log::error("Accounting sync error for freight booking #{$booking->id}: {$e->getMessage()}");
        }
    }

    public function deleted(FreightBooking $booking): void
    {
        try {
            $this->postingService->deleteVoucherFor(FreightBooking::class, $booking->id, $booking->company_id);
        } catch (\Throwable $e) {
            Log::error("Accounting delete error for freight booking #{$booking->id}: {$e->getMessage()}");
        }
    }
}
