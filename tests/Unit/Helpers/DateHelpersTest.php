<?php

namespace Tests\Unit\Helpers;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DateHelpersTest extends TestCase
{
    public function test_format_date_with_valid_string_formats_to_default(): void
    {
        $this->assertSame('24 Oct 2026', formatDate('2026-10-24'));
        $this->assertSame('01 Jan 2025', formatDate('2025-01-01 15:30:00'));
    }

    public function test_format_date_with_carbon_instance(): void
    {
        $carbon = Carbon::create(2026, 5, 15, 10, 0, 0);
        $this->assertSame('15 May 2026', formatDate($carbon));
    }

    public function test_format_date_with_custom_format(): void
    {
        $this->assertSame('2026/10/24', formatDate('2026-10-24', 'Y/m/d'));
        $this->assertSame('24-10-2026', formatDate('2026-10-24', 'd-m-Y'));
    }

    public function test_format_date_with_null_empty_returns_default(): void
    {
        $this->assertSame('—', formatDate(null));
        $this->assertSame('—', formatDate(''));
        $this->assertSame('N/A', formatDate(null, 'd M Y', 'N/A'));
        $this->assertSame('Not Available', formatDate('', 'd M Y', 'Not Available'));
    }

    public function test_format_date_with_invalid_date_returns_default(): void
    {
        $this->assertSame('—', formatDate('invalid-date-string'));
        $this->assertSame('Invalid', formatDate('not-a-valid-date', 'd M Y', 'Invalid'));
    }

    public function test_format_datetime_with_valid_string_formats_to_default(): void
    {
        $this->assertSame('24 Oct 2026 02:30 PM', formatDateTime('2026-10-24 14:30:00'));
        $this->assertSame('15 May 2025 09:15 AM', formatDateTime('2025-05-15 09:15:00'));
    }

    public function test_format_datetime_with_carbon_instance(): void
    {
        $carbon = Carbon::create(2026, 12, 31, 23, 59, 0);
        $this->assertSame('31 Dec 2026 11:59 PM', formatDateTime($carbon));
    }

    public function test_format_datetime_with_custom_format(): void
    {
        $this->assertSame('2026-10-24 14:30', formatDateTime('2026-10-24 14:30:00', 'Y-m-d H:i'));
    }

    public function test_format_datetime_with_null_empty_returns_default(): void
    {
        $this->assertSame('—', formatDateTime(null));
        $this->assertSame('—', formatDateTime(''));
        $this->assertSame('N/A', formatDateTime(null, 'd M Y h:i A', 'N/A'));
    }

    public function test_format_datetime_with_invalid_date_returns_default(): void
    {
        $this->assertSame('—', formatDateTime('invalid-date-string'));
        $this->assertSame('Fallback', formatDateTime('invalid-date', 'd M Y h:i A', 'Fallback'));
    }

    public function test_format_carbon_date_directly(): void
    {
        $this->assertSame('2026', formatCarbonDate('2026-10-24', 'Y', '—'));
        $this->assertSame('default_val', formatCarbonDate(null, 'Y-m-d', 'default_val'));
        $this->assertSame('default_val', formatCarbonDate('', 'Y-m-d', 'default_val'));
        $this->assertSame('default_val', formatCarbonDate('invalid-string', 'Y-m-d', 'default_val'));
    }
}
