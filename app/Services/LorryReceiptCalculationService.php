<?php

namespace App\Services;

use App\Models\AdvancePayment;
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
        ?float $shortageAmount,
        ?float $damageAmount,
    ): array {
        if ($isFixedAmount) {
            $actual = $actualAmountWhenFixed;
        } else {
            $actual = min($weight, $unloadingWeight) * $frRate;
        }

        return [
            'actual_amount' => $actual,
            'amount' => $actual - ($shortageAmount ?? 0.0) - ($damageAmount ?? 0.0),
        ];
    }

    public function syncFreightBookingsForUpdatedLorryReceipt(
        LorryReceipt $lorryReceipt,
        FreightBooking $freightBooking,
        array $validated,
    ): void {
        $updateData = [];
        $updateData['good_type'] = $validated['good_type'];

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

        if ($freightBooking->is_fixed_amount) {
            $updateData['tr_amount'] = $freightBooking->tr_amount;
        } else {
            $updateData['tr_amount'] = $freightBooking->tr_fr_rate * min($lorryReceipt->weight, $lorryReceipt->unloading_weight);
        }

        if ($freightBooking->canChangeTdsAmount()) {
            if ($freightBooking->is_fixed_amount) {
                $updateData['tds_amount'] = roundUpTdsAmount($updateData['tr_amount'] * ($updateData['transporter_tds_percentage'] / 100));
            } else {
                $updateData['tds_amount'] = roundUpTdsAmount($freightBooking->tr_fr_rate * $lorryReceipt->weight * ($updateData['transporter_tds_percentage'] / 100));
            }
        } else {
            $updateData['tds_amount'] = $freightBooking->tds_amount;
        }

        $updateData['balance_amount'] =
            $updateData['tr_amount']
            - $freightBooking->shortage_amount
            - $freightBooking->damage_amount
            - $freightBooking->misc_expense
            + $freightBooking->detention
            + $freightBooking->deviation
            - $updateData['tds_amount']
            - $freightBooking->advance_amount
            + $freightBooking->negative_adjusted_amount;

        if ($updateData['balance_amount'] < 0) {
            $updateData['negative_adjusted_amount'] = $freightBooking->negative_adjusted_amount - $updateData['balance_amount'];
            $transporter = Transporter::find($validated['transporter_id']);
            $transporter->advance_amount = $transporter->advance_amount - $updateData['balance_amount'];
            $transporter->save();
            AdvancePayment::create([
                'company_id' => $freightBooking->company_id,
                'transporter_id' => $transporter->id,
                'advance_amount' => $updateData['balance_amount'],
                'payment_date' => now()->toDateString(),
                'remark' => "Negative balance adjustment: LR#{$lorryReceipt->lr_number}",
            ]);
            $updateData['balance_amount'] = 0;
        }

        $freightBooking->update($updateData);
    }
}
