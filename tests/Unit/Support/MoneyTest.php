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

    public function test_round_rounds_to_two_decimal_places(): void
    {
        $this->assertSame(123.46, Money::round(123.456));
        $this->assertSame(99.99, Money::round('99.994'));
        $this->assertSame(10.0, Money::round(10));
    }

    public function test_display_formats_with_rupee_symbol_and_commas(): void
    {
        $this->assertSame('₹25,164.24', Money::display(25164.24));
        $this->assertSame('₹1,000.00', Money::display(1000));
        $this->assertSame('₹0.00', Money::display('0'));
    }

    public function test_from_paise_converts_to_rupees_float(): void
    {
        $this->assertSame(25164.24, Money::fromPaise(2516424));
        $this->assertSame(10.5, Money::fromPaise(1050));
        $this->assertSame(0.0, Money::fromPaise(0));
    }
}
