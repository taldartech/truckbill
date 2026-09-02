<?php

namespace Tests\Unit\Services;

use App\Services\FreightBookingAmountVerifier;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FreightBookingAmountVerifierTest extends TestCase
{
    public function test_verify_passes_for_consistent_amounts(): void
    {
        $amounts = [
            'is_fixed_amount' => false,
            'tr_fr_rate' => 1075,
            'tr_amount' => 41054.25,
            'loading_weight' => 41.34,
            'unloading_weight' => 38.19,
            'tds_amount' => 445,
            'advance_amount' => 41736,
            'balance_amount' => 0,
            'negative_adjusted_amount' => 23176.75,
            'shortage_amount' => 22050,
            'damage_amount' => 0,
            'detention' => 0,
            'deviation' => 0,
            'misc_expense' => 0,
            'custom_amounts_total' => 0,
            'voucher_payments_total' => 41736,
        ];

        FreightBookingAmountVerifier::verify($amounts);

        $this->assertTrue(true);
    }

    public function test_verify_passes_for_new_booking_without_advance(): void
    {
        $amounts = [
            'is_fixed_amount' => false,
            'tr_fr_rate' => 1000,
            'tr_amount' => 10000,
            'loading_weight' => 10,
            'unloading_weight' => 10,
            'tds_amount' => 2000,
            'advance_amount' => 0,
            'balance_amount' => 8000,
            'negative_adjusted_amount' => 0,
            'shortage_amount' => 0,
            'damage_amount' => 0,
            'detention' => 0,
            'deviation' => 0,
            'misc_expense' => 0,
            'custom_amounts_total' => 0,
            'voucher_payments_total' => 0,
        ];

        FreightBookingAmountVerifier::verify($amounts);

        $this->assertTrue(true);
    }

    #[DataProvider('invalidAmountProvider')]
    public function test_verify_throws_for_inconsistent_amounts(array $amounts, string $expectedMessagePart): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessagePart);

        FreightBookingAmountVerifier::verify($amounts);
    }

    /**
     * @return array<string, array{0: array<string, bool|float>, 1: string}>
     */
    public static function invalidAmountProvider(): array
    {
        $valid = [
            'is_fixed_amount' => false,
            'tr_fr_rate' => 1000,
            'tr_amount' => 10000,
            'loading_weight' => 10,
            'unloading_weight' => 10,
            'tds_amount' => 100,
            'advance_amount' => 10500,
            'balance_amount' => 0,
            'negative_adjusted_amount' => 600,
            'shortage_amount' => 0,
            'damage_amount' => 0,
            'detention' => 0,
            'deviation' => 0,
            'misc_expense' => 0,
            'custom_amounts_total' => 0,
            'voucher_payments_total' => 10500,
        ];

        return [
            'advance mismatch' => [
                array_merge($valid, ['advance_amount' => 10000]),
                'does not match voucher payments total',
            ],
            'transporter amount mismatch' => [
                array_merge($valid, ['tr_amount' => 9000]),
                'does not match rate x billable weight',
            ],
            'balance mismatch' => [
                array_merge($valid, ['balance_amount' => -100]),
                'Balance amount',
            ],
            'negative adjusted mismatch' => [
                array_merge($valid, ['negative_adjusted_amount' => 100]),
                'Negative adjusted amount',
            ],
        ];
    }
}
