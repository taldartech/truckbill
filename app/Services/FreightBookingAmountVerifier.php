<?php

namespace App\Services;

use InvalidArgumentException;

class FreightBookingAmountVerifier
{
    private const TOLERANCE = 0.01;

    /**
     * Verify freight booking monetary fields using inline calculations only.
     *
     * @param  array{
     *     is_fixed_amount: bool,
     *     tr_fr_rate: float,
     *     tr_amount: float,
     *     loading_weight: float,
     *     unloading_weight: float,
     *     tds_amount: float,
     *     advance_amount: float,
     *     balance_amount: float,
     *     negative_adjusted_amount: float,
     *     shortage_amount: float,
     *     damage_amount: float,
     *     detention: float,
     *     deviation: float,
     *     misc_expense: float,
     *     custom_amounts_total: float,
     *     voucher_payments_total: float,
     * }  $amounts
     *
     * @throws InvalidArgumentException
     */
    public static function verify(array $amounts): void
    {
        $isFixedAmount = (bool) ($amounts['is_fixed_amount'] ?? false);
        $trFrRate = (float) ($amounts['tr_fr_rate'] ?? 0);
        $trAmount = round((float) ($amounts['tr_amount'] ?? 0), 2);
        $loadingWeight = (float) ($amounts['loading_weight'] ?? 0);
        $unloadingWeight = (float) ($amounts['unloading_weight'] ?? $loadingWeight);
        $tdsAmount = round((float) ($amounts['tds_amount'] ?? 0), 2);
        $advanceAmount = round((float) ($amounts['advance_amount'] ?? 0), 2);
        $balanceAmount = round((float) ($amounts['balance_amount'] ?? 0), 2);
        $negativeAdjustedAmount = round((float) ($amounts['negative_adjusted_amount'] ?? 0), 2);
        $shortageAmount = round((float) ($amounts['shortage_amount'] ?? 0), 2);
        $damageAmount = round((float) ($amounts['damage_amount'] ?? 0), 2);
        $detention = round((float) ($amounts['detention'] ?? 0), 2);
        $deviation = round((float) ($amounts['deviation'] ?? 0), 2);
        $miscExpense = round((float) ($amounts['misc_expense'] ?? 0), 2);
        $customAmountsTotal = round((float) ($amounts['custom_amounts_total'] ?? 0), 2);
        $voucherPaymentsTotal = round((float) ($amounts['voucher_payments_total'] ?? 0), 2);

        if (! self::amountsMatch($advanceAmount, $voucherPaymentsTotal)) {
            throw new InvalidArgumentException(sprintf(
                'Advance amount (%.2f) does not match voucher payments total (%.2f).',
                $advanceAmount,
                $voucherPaymentsTotal
            ));
        }

        if (! $isFixedAmount) {
            $billableWeight = min($loadingWeight, $unloadingWeight);
            $expectedTrAmount = round($trFrRate * $billableWeight, 2);

            if (! self::amountsMatch($trAmount, $expectedTrAmount)) {
                throw new InvalidArgumentException(sprintf(
                    'Transporter amount (%.2f) does not match rate x billable weight (%.2f).',
                    $trAmount,
                    $expectedTrAmount
                ));
            }
        }

        $basePayable = round(
            $trAmount
            - $tdsAmount
            - $damageAmount
            - $shortageAmount
            + $detention
            + $deviation
            - $miscExpense
            + $customAmountsTotal,
            2
        );

        $expectedNegativeAdjusted = max(0.0, round($advanceAmount - $basePayable, 2));
        $expectedBalance = round($basePayable + $expectedNegativeAdjusted - $advanceAmount, 2);

        if (! self::amountsMatch($negativeAdjustedAmount, $expectedNegativeAdjusted)) {
            throw new InvalidArgumentException(sprintf(
                'Negative adjusted amount (%.2f) does not match expected value (%.2f).',
                $negativeAdjustedAmount,
                $expectedNegativeAdjusted
            ));
        }

        if (! self::amountsMatch($balanceAmount, $expectedBalance)) {
            throw new InvalidArgumentException(sprintf(
                'Balance amount (%.2f) does not match expected value (%.2f).',
                $balanceAmount,
                $expectedBalance
            ));
        }
    }

    private static function amountsMatch(float $left, float $right): bool
    {
        return abs($left - $right) <= self::TOLERANCE;
    }
}
