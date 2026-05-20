<?php

namespace Database\Seeders\Support;

/**
 * Deterministic fake PAN strings for bulk seeding (globally unique across salts).
 * Format matches Indian PAN pattern length (10 chars): [A-Z]{5}[0-9]{4}[A-Z]{1}
 */
final class PanGenerator
{
    public static function forSequence(int $sequence, string $salt): string
    {
        $n = (crc32($salt) ^ (($sequence * 1_103_515_245 + 12_345) & 0x7FFFFFFF)) % 2_147_483_647;

        $letters = '';
        for ($i = 0; $i < 5; $i++) {
            $letters .= chr(65 + ($n % 26));
            $n = intdiv($n, 26);
        }

        $digits = sprintf('%04d', $sequence % 10000);
        $last = chr(65 + ($sequence % 26));

        return $letters.$digits.$last;
    }
}
