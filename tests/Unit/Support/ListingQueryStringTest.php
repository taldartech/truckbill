<?php

namespace Tests\Unit\Support;

use App\Support\ListingQueryString;
use Illuminate\Http\Request;
use Tests\TestCase;

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

    public function test_params_defaults_when_arguments_are_null(): void
    {
        // When $request and $keys are null, defaults to request() and FREIGHT_BOOKING_KEYS
        $this->app['request']->merge([
            'start_date' => '2026-03-01',
            'customer_id' => '5',
            'unsupported_key' => 'drop_me',
        ]);

        $params = ListingQueryString::params();

        $this->assertSame([
            'start_date' => '2026-03-01',
            'customer_id' => '5',
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

    public function test_params_keeps_existing_scalar_search_over_filter_search(): void
    {
        $request = Request::create('/freight_bookings', 'GET', [
            'search' => 'ExistingSearch',
            'filter_search' => 'IgnoredSearch',
        ]);

        $params = ListingQueryString::params($request, ListingQueryString::FREIGHT_BOOKING_KEYS);

        $this->assertSame([
            'search' => 'ExistingSearch',
        ], $params);
    }

    public function test_params_normalize_returns_source_when_search_key_not_in_keys(): void
    {
        $request = Request::create('/lorry_receipts', 'GET', [
            'lr_number' => 'LR-999',
            'filter_search' => 'ShouldNotBeNormalized',
        ]);

        $params = ListingQueryString::params($request, ListingQueryString::LORRY_RECEIPT_KEYS);

        $this->assertSame([
            'lr_number' => 'LR-999',
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
            'lr_number' => 'LR-100',
        ], $params);
    }

    public function test_filter_ignores_empty_strings_nulls_and_non_scalar_values(): void
    {
        $request = Request::create('/freight_bookings', 'GET', [
            'search' => '',
            'start_date' => null,
            'customer_id' => ['array_value' => 123],
            'truck_id' => 45,
            'pending_memo' => true,
        ]);

        $params = ListingQueryString::params($request, ListingQueryString::FREIGHT_BOOKING_KEYS);

        $this->assertSame([
            'pending_memo' => true,
            'truck_id' => 45,
        ], $params);
    }

    public function test_suffix_builds_query_string_or_returns_empty_when_no_params(): void
    {
        $request = Request::create('/lorry_receipts', 'GET', [
            'gr_date_from' => '2026-01-01',
            'gr_date_to' => '2026-01-31',
        ]);

        $this->assertSame(
            '?gr_date_from=2026-01-01&gr_date_to=2026-01-31',
            ListingQueryString::suffix($request, ListingQueryString::LORRY_RECEIPT_KEYS)
        );

        // Empty params returns empty string
        $emptyRequest = Request::create('/lorry_receipts', 'GET');
        $this->assertSame('', ListingQueryString::suffix($emptyRequest, ListingQueryString::LORRY_RECEIPT_KEYS));
        $this->assertSame('', ListingQueryString::suffix($emptyRequest));
    }

    public function test_current_suffix_uses_current_request(): void
    {
        $this->app['request']->merge([
            'gr_date_from' => '2026-02-01',
        ]);

        $this->assertSame(
            '?gr_date_from=2026-02-01',
            ListingQueryString::currentSuffix(ListingQueryString::LORRY_RECEIPT_KEYS)
        );
    }

    public function test_scalar_value_returns_value_or_empty_string(): void
    {
        $request = Request::create('/freight_bookings', 'GET', [
            'search' => 'Delhi',
            'truck_id' => 12,
        ]);

        // Key exists
        $this->assertSame('Delhi', ListingQueryString::scalarValue($request, 'search'));
        $this->assertSame('12', ListingQueryString::scalarValue($request, 'truck_id'));

        // Key does not exist
        $this->assertSame('', ListingQueryString::scalarValue($request, 'non_existent_key'));
        $this->assertSame('', ListingQueryString::scalarValue($request, 'start_date'));
    }
}
