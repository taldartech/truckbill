<?php

namespace App\Services;

use App\Models\AdvancePayment;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\VoucherPayment;
use Illuminate\Support\Collection;
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

        $tdsAmount = roundUpTdsAmount($trAmount * ($tdsPercentage / 100));
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
        float $customAmountsTotal = 0.0,
    ): array {
        if ($isFixedAmount) {
            $trAmount = $trAmountInput;
        } else {
            $trAmount = $trFrRate * min($weight, $unloadingWeight);
        }

        if ($canChangeTdsAmount) {
            if ($isFixedAmount) {
                $tdsAmount = roundUpTdsAmount($trAmount * ($tdsPercentage / 100));
            } else {
                $tdsAmount = roundUpTdsAmount(($trFrRate * $weight) * ($tdsPercentage / 100));
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
            + $customAmountsTotal
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
        if (! $lorryReceipt->is_fixed_amount) {
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

    public function assertIndividualPaymentAllowed(FreightBooking $freightBooking, bool $isCombinedFlow): void
    {
        if ($isCombinedFlow) {
            return;
        }

        if ($freightBooking->isLinked()) {
            throw new \DomainException('Linked freight bookings must be paid via combined voucher payment.');
        }
    }

    /**
     * @return Collection<int, FreightBooking>
     */
    public function resolveLinkedGroup(FreightBooking $booking): Collection
    {
        if (! $booking->isLinked()) {
            return collect([$booking]);
        }

        return FreightBooking::query()
            ->with('customAmounts')
            ->linkedGroup((int) $booking->linked_lr_id, (int) $booking->company_id)
            ->where('is_cancelled', false)
            ->orderBy('id')
            ->get();
    }

    public function recalculateFromVoucherPayments(FreightBooking $freightBooking): void
    {
        $freightBooking->loadMissing('customAmounts');

        $advance = (float) VoucherPayment::query()
            ->where('freight_booking_id', $freightBooking->id)
            ->whereNull('deleted_at')
            ->sum('amount');
        $freightBooking->advance_amount = $advance;

        $freightBooking->balance_amount = $freightBooking->balanceFromAdvance();
        $freightBooking->save();
    }

    public function recalculateFromLorryReceipt(
        FreightBooking $freightBooking,
        LorryReceipt $lorryReceipt,
        array $validated,
    ): void {
        $freightBooking->loadMissing('customAmounts');
        $updateData = ['good_type' => $validated['good_type']];

        if ($freightBooking->canChangeTdsAmount()) {
            $transporter = Transporter::find($validated['transporter_id']);
            $updateData['transporter_id'] = $transporter->id;
            $updateData['transporter_name'] = $transporter->name;
            $updateData['transporter_tds_percentage'] = $transporter->tds_percentage;
        } else {
            $updateData['transporter_id'] = $freightBooking->transporter_id;
            $updateData['transporter_name'] = $freightBooking->transporter->name;
            $updateData['transporter_tds_percentage'] = $freightBooking->transporter_tds_percentage;
        }

        $weight = (float) ($validated['weight'] ?? $lorryReceipt->weight ?? 0);
        $unloadingWeight = (float) ($validated['unloading_weight'] ?? $lorryReceipt->unloading_weight ?? $weight);

        if ($freightBooking->is_fixed_amount) {
            $updateData['tr_amount'] = $freightBooking->tr_amount;
        } else {
            $updateData['tr_amount'] = $freightBooking->tr_fr_rate * min($weight, $unloadingWeight);
        }

        if ($freightBooking->canChangeTdsAmount()) {
            if ($freightBooking->is_fixed_amount) {
                $updateData['tds_amount'] = roundUpTdsAmount(
                    $updateData['tr_amount'] * ($updateData['transporter_tds_percentage'] / 100)
                );
            } else {
                $updateData['tds_amount'] = roundUpTdsAmount(
                    $freightBooking->tr_fr_rate * $weight * ($updateData['transporter_tds_percentage'] / 100)
                );
            }
        } else {
            $updateData['tds_amount'] = $freightBooking->tds_amount;
        }

        $freightBooking->tr_amount = $updateData['tr_amount'];
        $freightBooking->tds_amount = $updateData['tds_amount'];
        $updateData['balance_amount'] = $freightBooking->balanceFromAdvance();

        $freightBooking->update($updateData);
    }

    public function recalculateFromTdsChange(
        FreightBooking $freightBooking,
        float $newTdsAmount,
        float $newTdsPercentage,
    ): void {
        $freightBooking->loadMissing('customAmounts');
        $freightBooking->tds_amount = $newTdsAmount;
        $freightBooking->transporter_tds_percentage = $newTdsPercentage;
        $freightBooking->balance_amount = $freightBooking->balanceFromAdvance();
        $freightBooking->save();
    }

    public function applyNegativeAdjustment(FreightBooking $freightBooking, ?string $lrNumber = null): void
    {
        if ($freightBooking->isLinked()) {
            $this->applyNegativeAdjustmentForLinkedGroup(
                $this->resolveLinkedGroup($freightBooking),
                $lrNumber
            );

            return;
        }

        $this->applyNegativeAdjustmentForSingleBooking($freightBooking, $lrNumber);
    }

    /**
     * @param  iterable<int, FreightBooking>  $bookings
     */
    public function applyNegativeAdjustmentsForBookings(iterable $bookings): void
    {
        $processedLinkedIds = [];

        foreach ($bookings as $booking) {
            if ($booking->isLinked()) {
                $linkedLrId = (int) $booking->linked_lr_id;
                if (isset($processedLinkedIds[$linkedLrId])) {
                    continue;
                }
                $processedLinkedIds[$linkedLrId] = true;
                $this->applyNegativeAdjustmentForLinkedGroup(
                    $this->resolveLinkedGroup($booking)
                );

                continue;
            }

            $this->applyNegativeAdjustmentForSingleBooking($booking);
        }
    }

    /**
     * @param  Collection<int, FreightBooking>  $bookings
     */
    public function applyNegativeAdjustmentForLinkedGroup(Collection $bookings, ?string $lrNumber = null): void
    {
        if ($bookings->isEmpty()) {
            return;
        }

        $bookings->each(fn (FreightBooking $booking) => $booking->loadMissing('customAmounts'));

        $totalBalance = round($bookings->sum(fn (FreightBooking $b) => (float) ($b->balance_amount ?? 0)), 2);
        if ($totalBalance >= -0.01) {
            return;
        }

        $increment = round(-$totalBalance, 2);

        if (abs($increment) <= 0.01) {
            foreach ($bookings as $booking) {
                $booking->balance_amount = $booking->balanceFromAdvance();
                $booking->save();
            }

            return;
        }

        $transporter = Transporter::find($bookings->first()->transporter_id);
        if (! $transporter) {
            Log::warning('Skipped linked negative balance adjustment: transporter missing', [
                'freight_booking_ids' => $bookings->pluck('id')->all(),
                'transporter_id' => $bookings->first()->transporter_id,
            ]);

            return;
        }

        $weights = $bookings->mapWithKeys(function (FreightBooking $booking): array {
            $balance = (float) ($booking->balance_amount ?? 0);

            return [$booking->id => $balance < -0.01 ? round(abs($balance), 2) : 0.0];
        });
        $weightSum = round($weights->sum(), 2);

        if ($weightSum <= 0.01) {
            $equalShare = round($increment / $bookings->count(), 2);
            $allocations = $bookings->mapWithKeys(fn (FreightBooking $b) => [$b->id => $equalShare]);
        } else {
            $allocations = $weights->map(fn (float $weight) => round($increment * ($weight / $weightSum), 2));
        }

        $allocatedSum = round($allocations->sum(), 2);
        $remainder = round($increment - $allocatedSum, 2);
        if (abs($remainder) > 0.01) {
            $firstId = $allocations->keys()->first();
            $allocations[$firstId] = round($allocations[$firstId] + $remainder, 2);
        }

        $lrNumbers = $bookings
            ->map(fn (FreightBooking $b) => $b->lr?->lr_number)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $resolvedLrNumber = $lrNumber ?? ($lrNumbers !== [] ? implode(', ', $lrNumbers) : 'N/A');

        if ($increment > 0) {
            $transporter->advance_amount = round((float) ($transporter->advance_amount ?? 0) + $increment, 2);
            $transporter->save();

            AdvancePayment::create([
                'company_id' => $bookings->first()->company_id,
                'transporter_id' => $transporter->id,
                'advance_amount' => $increment,
                'payment_date' => now()->toDateString(),
                'remark' => "Linked negative balance adjustment: LR#{$resolvedLrNumber}",
            ]);
        }

        foreach ($bookings as $booking) {
            $allocation = (float) ($allocations[$booking->id] ?? 0);
            if ($allocation > 0) {
                $booking->negative_adjusted_amount = round(
                    (float) ($booking->negative_adjusted_amount ?? 0) + $allocation,
                    2
                );
            }
            $booking->balance_amount = $booking->balanceFromAdvance();
            $booking->save();
        }
    }

    public function applyNegativeAdjustmentForSingleBooking(FreightBooking $freightBooking, ?string $lrNumber = null): void
    {
        $freightBooking->loadMissing('customAmounts');

        $requiredAdjustedTotal = $this->requiredNegativeAdjustedAmount($freightBooking);
        $alreadyAdjusted = round((float) ($freightBooking->negative_adjusted_amount ?? 0), 2);
        $incrementalAdjustAmount = round($requiredAdjustedTotal - $alreadyAdjusted, 2);
        $resolvedLrNumber = $lrNumber ?? $freightBooking->lr?->lr_number ?? 'N/A';

        if (abs($incrementalAdjustAmount) <= 0.01) {
            $freightBooking->negative_adjusted_amount = $requiredAdjustedTotal;
            $freightBooking->balance_amount = $freightBooking->balanceFromAdvance();
            $freightBooking->save();

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
        $freightBooking->balance_amount = $freightBooking->balanceFromAdvance();
        $freightBooking->save();
    }

    private function requiredNegativeAdjustedAmount(FreightBooking $freightBooking): float
    {
        $freightBooking->loadMissing('customAmounts');

        $basePayable = (float) $freightBooking->tr_amount
            - (float) ($freightBooking->tds_amount ?? 0)
            - (float) ($freightBooking->damage_amount ?? 0)
            - (float) ($freightBooking->shortage_amount ?? 0)
            + (float) ($freightBooking->detention ?? 0)
            + (float) ($freightBooking->deviation ?? 0)
            - (float) ($freightBooking->misc_expense ?? 0)
            + $freightBooking->customAmountsTotal();

        $advance = (float) ($freightBooking->advance_amount ?? 0);

        return max(0.0, round($advance - $basePayable, 2));
    }

}
