<?php

namespace App\Support;

class FreightChallanTemplate
{
    public static function normalizeKey(?string $raw): string
    {
        $raw = (string) $raw;

        return match (true) {
            in_array($raw, ['default', 'a', 'classic', 'voucher'], true) => 'classic',
            in_array($raw, ['modern', 'b', 'detailed', 'challan_b', 'form_b'], true) => 'detailed',
            default => 'classic',
        };
    }

    public static function viewName(string $key): string
    {
        return match ($key) {
            'detailed' => 'reports.exports.freight-challan-form-b',
            default => 'reports.exports.voucher-receipt',
        };
    }
}
