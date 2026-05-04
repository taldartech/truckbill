<?php

namespace App\Services;

class TransporterTdsCalculator
{
    /**
     * System TDS % from declaration, PAN active flag, and PAN number (same rules for truck owner and agency).
     * Declaration → 0%. Inactive PAN or invalid length → 20%. Fourth character: P/H → 1%, C/F/A/B/J/L/T → 2%, else 20%.
     */
    public function calculate(bool $declarationGiven, string $panNumber, bool $panActive): float
    {
        if ($declarationGiven) {
            return 0.00;
        }

        if (! $panActive) {
            return 20.00;
        }

        if (strlen($panNumber) < 4) {
            return 20.00;
        }

        $fourthChar = strtoupper(substr($panNumber, 3, 1));

        if (in_array($fourthChar, ['P', 'H'], true)) {
            return 1.00;
        }

        if (in_array($fourthChar, ['C', 'F', 'A', 'B', 'J', 'L', 'T'], true)) {
            return 2.00;
        }

        return 20.00;
    }
}
