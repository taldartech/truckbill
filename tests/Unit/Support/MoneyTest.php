<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_format_rupees_with_paise_decimals(): void
    {
        $this->assertSame('25164.24', Money::format(25164.24));
        $this->assertSame('20000.00', Money::format(20000));
    }

    public function test_to_paise_for_razorpay_only(): void
    {
        $this->assertSame(2516424, Money::toPaise(25164.24));
        $this->assertSame(2000000, Money::toPaise(20000));
    }
}
