<?php

namespace App\Support;

class LrCopyPdfTemplate
{
    public static function normalizeKey(?string $raw): string
    {
        $raw = (string) $raw;

        return match (true) {
            in_array($raw, ['default', 'a', 'classic'], true) => 'classic',
            in_array($raw, ['modern', 'b', 'modern_b', 'modern_a'], true) => 'modern',
            in_array($raw, ['compact', 'c', 'modern_c'], true) => 'compact',
            default => 'classic',
        };
    }

    public static function viewName(string $key): string
    {
        return match ($key) {
            'modern' => 'reports.exports.lr-copy-modern-a',
            'compact' => 'reports.exports.lr-copy-modern-c',
            default => 'reports.exports.lr_copy',
        };
    }
}
