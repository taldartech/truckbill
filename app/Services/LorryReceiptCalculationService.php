<?php

namespace App\Services;

use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Transporter;

class LorryReceiptCalculationService
{
    private const WEIGHT_TOLERANCE = 0.01;

    /**
     * LR create/store: fixed uses `amount` as gross actual; variable uses loose-weight rules.
     *
     * @return array{actual_amount: float, amount: float}
     */
    public function resolveForStore(
        bool $isFixedAmount,
        float $weight,
        float $frRate,
        float $amountField,
    ): array {
        if ($isFixedAmount) {
            $actual = $amountField;
        } else {
            $actual = $weight * $frRate;
        }

        return [
            'actual_amount' => $actual,
            'amount' => $actual,
        ];
    }

    /**
     * LR update: fixed keeps submitted `actual_amount`; variable uses same loose-weight rules as store.
     *
     * @return array{actual_amount: float, amount: float}
     */
    public function resolveForUpdate(
        bool $isFixedAmount,
        float $weight,
        float $unloadingWeight,
        float $frRate,
        float $actualAmountWhenFixed,
        float $shortageAmount,
        float $damageAmount,
    ): array {
        if ($isFixedAmount) {
            $actual = $actualAmountWhenFixed;
        } else {
            $actual = min($weight, $unloadingWeight) * $frRate;
        }

        return [
            'actual_amount' => $actual,
            'amount' => $actual - $shortageAmount - $damageAmount,
        ];
    }

    public function syncFreightBookingsForUpdatedLorryReceipt(
        LorryReceipt $lorryReceipt,
        array $validated,
        ?int $oldTransporterId,
        ?int $oldLinkedLrId,
        ?string $oldDeliveryStatus,
        float $oldWeight,
    ): void {
        $freightBookings = FreightBooking::where('lr_id', $lorryReceipt->id)->get();

        foreach ($freightBookings as $freightBooking) {
            $updateData = [];
            $updateData['good_type'] = $validated['good_type'];

            if (isset($validated['transporter_id']) && $validated['transporter_id'] != $oldTransporterId) {
                $transporter = Transporter::find($validated['transporter_id']);

                if ($transporter) {
                    $updateData['transporter_id'] = $transporter->id;
                    $updateData['transporter_name'] = $transporter->name;
                    $updateData['transporter_tds_percentage'] = $transporter->tds_percentage;

                    if ($freightBooking->canChangeTdsAmount()) {
                        $loadingWeight = $lorryReceipt->weight;
                        $tdsBaseAmount = round($freightBooking->tr_fr_rate * $loadingWeight, 2);
                        $updateData['tds_amount'] = round($tdsBaseAmount * ($transporter->tds_percentage / 100), 2);
                    }
                }
            }

            if (isset($validated['delivery_status']) && $validated['delivery_status'] != $oldDeliveryStatus) {
                if ($validated['delivery_status'] === 'Delivered') {
                    $updateData['is_delivered'] = 1;
                } elseif ($validated['delivery_status'] === 'Cancelled') {
                    $updateData['is_cancelled'] = 1;
                } elseif ($validated['delivery_status'] === 'Pending' || $validated['delivery_status'] === 'In-Progress') {
                    $updateData['is_delivered'] = 0;
                    $updateData['is_cancelled'] = 0;
                }
            }

            $weightChanged = isset($validated['weight']) && $validated['weight'] != $oldWeight;
            $unloadingWeightChanged = isset($validated['unloading_weight'])
                && ($lorryReceipt->unloading_weight != $validated['unloading_weight']);

            if ($weightChanged || $unloadingWeightChanged) {
                $lorryReceipt->refresh();
                $loadingWeight = $lorryReceipt->weight;
                $unloadingWeight = $lorryReceipt->unloading_weight ?? $loadingWeight;

                if (abs($loadingWeight - $unloadingWeight) > self::WEIGHT_TOLERANCE) {
                    $effectiveWeight = min($loadingWeight, $unloadingWeight);
                    $updateData['tr_amount'] = round($freightBooking->tr_fr_rate * $effectiveWeight, 2);
                }

                if ($weightChanged && $freightBooking->canChangeTdsAmount()) {
                    $tdsBaseAmount = round($freightBooking->tr_fr_rate * $loadingWeight, 2);
                    $tdsPercentage = $freightBooking->transporter_tds_percentage ?? 0;
                    $updateData['tds_amount'] = round($tdsBaseAmount * ($tdsPercentage / 100), 2);
                }
            }

            if (isset($updateData['tr_amount'])) {
                $tdsAmount = isset($updateData['tds_amount']) ? $updateData['tds_amount'] : ($freightBooking->tds_amount ?? 0);
                $updateData['balance_amount'] = $this->calculateFreightBookingBalance(
                    $freightBooking,
                    $updateData['tr_amount'],
                    $tdsAmount
                );
            }

            if (isset($updateData['tds_amount']) && ! isset($updateData['tr_amount'])) {
                $updateData['balance_amount'] = $this->calculateFreightBookingBalance(
                    $freightBooking,
                    $freightBooking->tr_amount,
                    $updateData['tds_amount']
                );
            }

            if (! empty($updateData)) {
                $freightBooking->update($updateData);
            }
        }
    }

    private function calculateFreightBookingBalance(
        FreightBooking $freightBooking,
        float $trAmount,
        float $tdsAmount,
    ): float {
        return $trAmount
            - $freightBooking->advance_amount
            - $tdsAmount
            - ($freightBooking->damage_amount ?? 0)
            - ($freightBooking->shortage_amount ?? 0)
            + ($freightBooking->detention ?? 0)
            + ($freightBooking->deviation ?? 0)
            - ($freightBooking->misc_expense ?? 0);
    }
}
