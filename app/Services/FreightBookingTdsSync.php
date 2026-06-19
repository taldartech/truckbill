<?php

namespace App\Services;

use App\Models\FreightBooking;
use App\Models\Transporter;

class FreightBookingTdsSync
{
    /**
     * Recalculate TDS amount on freight bookings for this transporter when TDS % changes.
     * Only updates bookings where canChangeTdsAmount() is true.
     */
    public function syncForTransporter(Transporter $transporter, float $newTdsPercentage): void
    {
        $freightBookings = FreightBooking::where('transporter_id', $transporter->id)->get();

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

            $freightBooking->tds_amount = $newTdsAmount;
            $freightBooking->transporter_tds_percentage = $newTdsPercentage;

            $freightBooking->balance_amount = $freightBooking->tr_amount
                - $freightBooking->advance_amount
                - $newTdsAmount
                - ($freightBooking->damage_amount ?? 0)
                - ($freightBooking->shortage_amount ?? 0)
                + ($freightBooking->detention ?? 0)
                + ($freightBooking->deviation ?? 0)
                - ($freightBooking->misc_expense ?? 0);

            if ($freightBooking->balance_amount < 0) {
                $freightBooking->balance_amount = 0;
            }

            $freightBooking->save();
        }
    }
}
