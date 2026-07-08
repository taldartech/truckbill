<?php

namespace App\Services;

use App\Models\FreightBooking;
use App\Models\Transporter;

class FreightBookingTdsSync
{
    public function __construct(
        private readonly FreightBookingCalculationService $freightBookingCalculationService,
    ) {}

    /**
     * Recalculate TDS amount on freight bookings for this transporter when TDS % changes.
     * Only updates bookings where canChangeTdsAmount() is true.
     */
    public function syncForTransporter(Transporter $transporter, float $newTdsPercentage): void
    {
        $freightBookings = FreightBooking::with('customAmounts')
            ->where('transporter_id', $transporter->id)
            ->get();

        $processedLinkedIds = [];

        foreach ($freightBookings as $freightBooking) {
            if (! $freightBooking->canChangeTdsAmount()) {
                continue;
            }

            $loadingWeight = $freightBooking->lr ? $freightBooking->lr->weight : 0;
            if ($freightBooking->is_fixed_amount) {
                $tdsBaseAmount = $freightBooking->tr_amount;
            } else {
                if ($loadingWeight <= 0) {
                    continue;
                }
                $tdsBaseAmount = round($freightBooking->tr_fr_rate * $loadingWeight, 2);
            }

            $newTdsAmount = roundUpTdsAmount($tdsBaseAmount * ($newTdsPercentage / 100));

            if ($newTdsAmount > $freightBooking->balance_amount) {
                continue;
            }

            $this->freightBookingCalculationService->recalculateFromTdsChange(
                $freightBooking,
                $newTdsAmount,
                $newTdsPercentage
            );

            if ($freightBooking->isLinked()) {
                $linkedLrId = (int) $freightBooking->linked_lr_id;
                if (! isset($processedLinkedIds[$linkedLrId])) {
                    $processedLinkedIds[$linkedLrId] = true;
                    $this->freightBookingCalculationService->applyNegativeAdjustment($freightBooking);
                }
            } else {
                $this->freightBookingCalculationService->applyNegativeAdjustment($freightBooking);
            }
        }
    }
}
