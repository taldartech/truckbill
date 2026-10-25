<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

class NumberHelpersTest extends TestCase
{
    public function test_round_up_tds_amount_rounds_up_to_integer(): void
    {
        $this->assertSame(101.0, roundUpTdsAmount(100.001));
        $this->assertSame(100.0, roundUpTdsAmount(100.00));
        $this->assertSame(100.0, roundUpTdsAmount(99.991));
        $this->assertSame(2000.0, roundUpTdsAmount(2000.00));
        $this->assertSame(2001.0, roundUpTdsAmount(2000.001));
    }

    public function test_get_indian_currency_whole_and_paise(): void
    {
        $this->assertSame('One  Rupees ', getIndianCurrency(1.00));
        $this->assertSame('One  Rupees And Twenty Five Paise', getIndianCurrency(1.25));
        $this->assertSame('One Lakh Ten  Rupees ', getIndianCurrency(100010));
    }

    public function test_format_indian_number_uses_lakh_crore_grouping(): void
    {
        $this->assertSame('10,50,000.00', formatIndianNumber(1050000));
        $this->assertSame('1,00,000.00', formatIndianNumber(100000));
        $this->assertSame('999.00', formatIndianNumber(999));
        $this->assertSame('1,000.00', formatIndianNumber(1000));
        $this->assertSame('1,23,45,678.90', formatIndianNumber(12345678.9));
        $this->assertSame('-10,50,000.00', formatIndianNumber(-1050000));
        $this->assertSame('1,050', formatIndianNumber(1050, 0));
    }

    public function test_get_indian_currency_edge_cases_and_larger_numbers(): void
    {
        // $number >= 21 in units/tens group
        $this->assertStringContainsString('Twenty Five', getIndianCurrency(25.00));
        $this->assertStringContainsString('Ninety Nine', getIndianCurrency(99.00));

        // Paise < 21
        $this->assertStringContainsString('Five Paise', getIndianCurrency(1.05));
        $this->assertStringContainsString('Fifteen Paise', getIndianCurrency(1.15));

        // Paise only (no rupees)
        $this->assertStringContainsString('Fifty', getIndianCurrency(0.50));
        $this->assertStringContainsString('Five Paise', getIndianCurrency(0.05));

        // Hundred with and without remainder
        $res100 = getIndianCurrency(100.00);
        $this->assertStringContainsString('Hundred', $res100);

        $res105 = getIndianCurrency(105.00);
        $this->assertStringContainsString('Hundred', $res105);
        $this->assertStringContainsString('Five', $res105);

        $res125 = getIndianCurrency(125.00);
        $this->assertStringContainsString('Hundred', $res125);
        $this->assertStringContainsString('Twenty Five', $res125);

        // Plural groups (> 9 in thousands, lakhs, crores)
        $res50k = getIndianCurrency(50000.00);
        $this->assertStringContainsString('Fifty', $res50k);
        $this->assertStringContainsString('Thousand', $res50k);

        $res25k = getIndianCurrency(25000.00);
        $this->assertStringContainsString('Twenty Five', $res25k);
        $this->assertStringContainsString('Thousand', $res25k);

        // Large number with crores, lakhs, thousands, hundreds
        $result = getIndianCurrency(123456789.75);
        $this->assertStringContainsString('Crore', $result);
        $this->assertStringContainsString('Lakh', $result);
        $this->assertStringContainsString('Seventy Five Paise', $result);

        // Zero
        $this->assertSame('', getIndianCurrency(0.00));
    }
}

