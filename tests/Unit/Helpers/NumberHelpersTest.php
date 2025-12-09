<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

class NumberHelpersTest extends TestCase
{
    public function test_get_indian_currency_whole_and_paise(): void
    {
        $this->assertSame('One  Rupees ', getIndianCurrency(1.00));
        $this->assertSame('One  Rupees And Twenty Five Paise', getIndianCurrency(1.25));
        $this->assertSame('One Lakh Ten  Rupees ', getIndianCurrency(100010));
    }
}

