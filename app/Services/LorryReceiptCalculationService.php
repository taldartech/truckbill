<?php

namespace App\Services;

use App\Models\FreightBooking;
use App\Models\LorryReceipt;

class LorryReceiptCalculationService
{
    public function __construct(
        private readonly FreightBookingCalculationService $freightBookingCalculationService,
    ) {}

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
        $this->freightBookingCalculationService->recalculateFromLorryReceipt(
            $freightBooking,
            $lorryReceipt,
            $validated
        );

        $this->freightBookingCalculationService->applyNegativeAdjustment(
            $freightBooking,
            $lorryReceipt->lr_number
        );
    }
}
