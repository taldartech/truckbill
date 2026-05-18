<?php

namespace Tests\Unit\Support;

use App\Support\ListingQueryString;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class ListingQueryStringTest extends TestCase
{
    public function test_params_filters_allowed_listing_keys(): void
    {
        $request = Request::create('/freight_bookings', 'GET', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'pending_memo' => '1',
            'page' => '2',
            '_token' => 'should-not-pass',
        ]);

        $params = ListingQueryString::params($request, ListingQueryString::FREIGHT_BOOKING_KEYS);

        $this->assertSame([
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'pending_memo' => '1',
        ], $params);
    }

    public function test_params_ignores_datatables_search_array_and_uses_filter_search(): void
    {
        $request = Request::create('/freight_bookings/datatable', 'GET', [
            'search' => ['value' => '', 'regex' => 'false'],
            'filter_search' => 'ABC-123',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-25',
        ]);

        $params = ListingQueryString::params($request, ListingQueryString::FREIGHT_BOOKING_KEYS);

        $this->assertSame([
            'search' => 'ABC-123',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-25',
        ], $params);
    }

    public function test_params_reads_listing_filters_from_post_body(): void
    {
        $request = Request::create('/lorry_receipts/1', 'PUT', [
            'gr_date_from' => '2026-01-01',
            'gr_date_to' => '2026-01-31',
            'lr_number' => 'LR-100',
        ]);

        $params = ListingQueryString::params($request, ListingQueryString::LORRY_RECEIPT_KEYS);

        $this->assertSame([
            'gr_date_from' => '2026-01-01',
            'gr_date_to' => '2026-01-31',
        ], $params);
    }

    public function test_suffix_builds_query_string_for_lorry_receipt_filters(): void
    {
        $request = Request::create('/lorry_receipts', 'GET', [
            'gr_date_from' => '2026-01-01',
            'gr_date_to' => '2026-01-31',
        ]);

        $this->assertSame(
            '?gr_date_from=2026-01-01&gr_date_to=2026-01-31',
            ListingQueryString::suffix($request, ListingQueryString::LORRY_RECEIPT_KEYS)
        );
    }
}
