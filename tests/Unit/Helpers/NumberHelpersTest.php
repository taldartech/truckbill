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
}

