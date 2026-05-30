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
}

