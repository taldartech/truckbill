<?php

namespace App\Services;

use App\Models\AdvancePayment;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use Illuminate\Support\Facades\Log;

class FreightBookingCalculationService
{
    /**
     * @return array{
     *     tr_amount: float,
     *     advance_amount: float,
     *     detention: float,
     *     deviation: float,
     *     misc_expense: float,
     *     damage_amount: float,
     *     shortage_amount: float,
     *     negative_adjusted_amount: float,
     *     tds_amount: float,
     *     balance_amount: float
     * }
     */
    public function calculateForStore(
        bool $isFixedAmount,
        float $trFrRate,
        float $weight,
        float $trAmountInput,
        float $tdsPercentage,
    ): array {
        $advanceAmount = 0.0;
        $detention = 0.0;
        $deviation = 0.0;
        $miscExpense = 0.0;
        $damageAmount = 0.0;
        $shortageAmount = 0.0;
        $negativeAdjustedAmount = 0.0;

        if ($isFixedAmount) {
            $trAmount = $trAmountInput;
        } else {
            $trAmount = $trFrRate * $weight;
        }

        $tdsAmount = round($trAmount * ($tdsPercentage / 100), 2);
        $balanceAmount = $trAmount - $tdsAmount;

        return [
            'tr_amount' => $trAmount,
            'advance_amount' => $advanceAmount,
            'detention' => $detention,
            'deviation' => $deviation,
            'misc_expense' => $miscExpense,
            'damage_amount' => $damageAmount,
            'shortage_amount' => $shortageAmount,
            'negative_adjusted_amount' => $negativeAdjustedAmount,
            'tds_amount' => $tdsAmount,
            'balance_amount' => $balanceAmount,
        ];
    }

    /**
     * @return array{
     *     tr_amount: float,
     *     advance_amount: float,
     *     tds_amount: float,
     *     balance_amount: float,
     *     detention: float,
     *     deviation: float,
     *     misc_expense: float,
     *     damage_amount: float,
     *     shortage_amount: float
     * }
     */
    public function calculateForUpdate(
        bool $isFixedAmount,
        float $trFrRate,
        float $weight,
        float $unloadingWeight,
        float $trAmountInput,
        float $voucherPaymentsTotal,
        float $detention,
        float $deviation,
        float $miscExpense,
        float $damageAmount,
        float $shortageAmount,
        float $tdsPercentage,
        bool $canChangeTdsAmount,
        float $existingTdsAmount,
        float $negativeAdjustedAmount,
    ): array {
        if ($isFixedAmount) {
            $trAmount = $trAmountInput;
        } else {
            $trAmount = $trFrRate * min($weight, $unloadingWeight);
        }

        if ($canChangeTdsAmount) {
            if ($isFixedAmount) {
                $tdsAmount = round($trAmount * ($tdsPercentage / 100), 2);
            } else {
                $tdsAmount = round(($trFrRate * $weight) * ($tdsPercentage / 100), 2);
            }
        } else {
            $tdsAmount = round($existingTdsAmount, 2);
        }

        $advanceAmount = $voucherPaymentsTotal;
        $balanceAmount = $trAmount
            - $shortageAmount
            - $damageAmount
            - $miscExpense
            + $detention
            + $deviation
            - $tdsAmount
            - $advanceAmount
            + $negativeAdjustedAmount;

        return [
            'tr_amount' => $trAmount,
            'advance_amount' => $advanceAmount,
            'tds_amount' => $tdsAmount,
            'balance_amount' => $balanceAmount,
            'detention' => $detention,
            'deviation' => $deviation,
            'misc_expense' => $miscExpense,
            'damage_amount' => $damageAmount,
            'shortage_amount' => $shortageAmount,
        ];
    }

    /**
     * @return array{
     *     old_delivery_status: string|null,
     *     delivery_status: string
     * }
     */
    public function updateLorryReceiptForFreightBookingUpdate(
        LorryReceipt $lorryReceipt,
        array $validated,
        float $damageAmount,
        float $shortageAmount,
    ): array {
        $lorryReceipt->refresh();
        $oldDeliveryStatus = $lorryReceipt->delivery_status;

        $deliveryStatus = 'Pending';
        if (isset($validated['is_cancelled']) && $validated['is_cancelled'] === '1') {
            $deliveryStatus = 'Pending';
            $lorryReceipt->freight_booking_id = null;
        } elseif (isset($validated['is_delivered']) && $validated['is_delivered'] === '1') {
            $deliveryStatus = 'Delivered';
        } else {
            $deliveryStatus = 'In-Progress';
        }

        $lorryReceipt->damage_amount = $damageAmount;
        $lorryReceipt->shortage_amount = $shortageAmount;

        if (isset($validated['unloading_weight'])) {
            $lorryReceipt->unloading_weight = $validated['unloading_weight'];
        }

        $loadingWeight = (float) $lorryReceipt->weight;
        $unloadingWeight = (float) ($lorryReceipt->unloading_weight ?? $loadingWeight);
        if (!$lorryReceipt->is_fixed_amount) {
            $lorryReceipt->actual_amount = min($loadingWeight, $unloadingWeight) * $lorryReceipt->fr_rate;
        }

        $lorryReceipt->amount = $lorryReceipt->actual_amount - $damageAmount - $shortageAmount;
        $lorryReceipt->delivery_status = $deliveryStatus;
        $lorryReceipt->save();

        return [
            'old_delivery_status' => $oldDeliveryStatus,
            'delivery_status' => $deliveryStatus,
        ];
    }

    public function applyNegativeAdjustmentForSingleBooking(FreightBooking $freightBooking, ?string $lrNumber = null): void
    {
        $currentBalance = (float) ($freightBooking->balance_amount ?? 0);
        if ($currentBalance >= -0.01) {
            return;
        }

        $transporter = Transporter::find($freightBooking->transporter_id);
        if (! $transporter) {
            Log::warning('Skipped negative balance adjustment: transporter missing', [
                'freight_booking_id' => $freightBooking->id,
                'transporter_id' => $freightBooking->transporter_id,
            ]);

            return;
        }

        $alreadyAdjusted = round((float) ($freightBooking->negative_adjusted_amount ?? 0), 2);
        $requiredAdjustedTotal = round(abs($currentBalance), 2);
        $incrementalAdjustAmount = round($requiredAdjustedTotal - $alreadyAdjusted, 2);
        $resolvedLrNumber = $lrNumber ?? $freightBooking->lr?->lr_number ?? 'N/A';

        if (abs($incrementalAdjustAmount) <= 0.01) {
            $freightBooking->negative_adjusted_amount = $requiredAdjustedTotal;
            $freightBooking->balance_amount = 0;
            $freightBooking->save();

            return;
        }

        if ($incrementalAdjustAmount > 0) {
            $transporter->advance_amount = round((float) ($transporter->advance_amount ?? 0) + $incrementalAdjustAmount, 2);
            $transporter->save();

            AdvancePayment::create([
                'company_id' => $freightBooking->company_id,
                'transporter_id' => $transporter->id,
                'advance_amount' => $incrementalAdjustAmount,
                'payment_date' => now()->toDateString(),
                'remark' => "Negative balance adjustment: LR#{$resolvedLrNumber}",
            ]);
        } else {
            $deductionAmount = abs($incrementalAdjustAmount);
            $transporter->advance_amount = round((float) ($transporter->advance_amount ?? 0) - $deductionAmount, 2);
            $transporter->save();

            AdvancePayment::create([
                'company_id' => $freightBooking->company_id,
                'transporter_id' => $transporter->id,
                'advance_amount' => -$deductionAmount,
                'payment_date' => now()->toDateString(),
                'remark' => "Negative balance adjustment reversal: LR#{$resolvedLrNumber}",
            ]);
        }

        $freightBooking->negative_adjusted_amount = $requiredAdjustedTotal;
        $freightBooking->balance_amount = 0;
        $freightBooking->save();
    }

    public function applyNegativeAdjustmentForLinkedBookings(FreightBooking $freightBooking, ?string $lrNumber = null): void
    {
    }
}
