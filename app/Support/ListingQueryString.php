<?php

namespace App\Support;

use Illuminate\Http\Request;

class ListingQueryString
{
    public const LORRY_RECEIPT_KEYS = [
        'gr_date_from',
        'gr_date_to',
        'lr_number',
        'customer_id',
        'transporter_id',
        'truck_id',
        'delivery_status',
    ];

    public const FREIGHT_BOOKING_KEYS = [
        'search',
        'start_date',
        'end_date',
        'pending_memo',
        'lr_number',
        'customer_id',
        'transporter_id',
        'truck_id',
        'truck_owner_id',
    ];

    /**
     * @return array<string, scalar>
     */
    public static function params(?Request $request = null, ?array $keys = null): array
    {
        $request ??= request();
        $keys ??= self::FREIGHT_BOOKING_KEYS;

        $source = self::collectSource($request, $keys);
        $source = self::normalizeFreightSearch($source, $keys);

        return self::filter($source, $keys);
    }

    public static function suffix(?Request $request = null, ?array $keys = null): string
    {
        $params = self::params($request, $keys);

        return $params === [] ? '' : '?'.http_build_query($params);
    }

    public static function currentSuffix(array $keys): string
    {
        return self::suffix(request(), $keys);
    }

    public static function scalarValue(?Request $request, string $key, ?array $keys = null): string
    {
        $params = self::params($request, $keys);

        if (! array_key_exists($key, $params)) {
            return '';
        }

        return (string) $params[$key];
    }

    /**
     * @param  array<string>  $keys
     * @return array<string, mixed>
     */
    private static function collectSource(Request $request, array $keys): array
    {
        $source = [];

        foreach ($keys as $key) {
            if ($request->exists($key)) {
                $source[$key] = $request->input($key);
            }
        }

        if (in_array('search', $keys, true) && $request->exists('filter_search')) {
            $source['filter_search'] = $request->input('filter_search');
        }

        return $source;
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  array<string>  $keys
     * @return array<string, mixed>
     */
    private static function normalizeFreightSearch(array $source, array $keys): array
    {
        if (! in_array('search', $keys, true)) {
            return $source;
        }

        $search = $source['search'] ?? null;
        $filterSearch = $source['filter_search'] ?? null;

        if (! self::isScalarValue($search) && self::isScalarValue($filterSearch)) {
            $source['search'] = $filterSearch;
        }

        unset($source['filter_search']);

        return $source;
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, scalar>
     */
    private static function filter(array $source, array $keys): array
    {
        $filtered = [];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $source)) {
                continue;
            }

            $value = $source[$key];

            if (! self::isScalarValue($value)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

    private static function isScalarValue(mixed $value): bool
    {
        return is_string($value) || is_int($value) || is_float($value) || is_bool($value);
    }
}
