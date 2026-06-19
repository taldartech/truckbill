<?php

namespace App\Services;

use App\Models\Transporter;
use InvalidArgumentException;

class TransporterTdsRefresh
{
    public function __construct(
        private readonly TransporterTdsCalculator $tdsCalculator,
        private readonly FreightBookingTdsSync $freightBookingTdsSync,
    ) {}

    public function calculatedPercentage(Transporter $transporter): float
    {
        $master = $transporter->transporterMaster;

        return $this->tdsCalculator->calculate(
            (bool) $transporter->declaration_given,
            (string) ($master?->pan_number ?? ''),
            (bool) ($master?->pan_active ?? false),
        );
    }

    public function hasMismatch(Transporter $transporter): bool
    {
        $stored = round((float) $transporter->tds_percentage, 2);
        $calculated = round($this->calculatedPercentage($transporter), 2);

        return abs($stored - $calculated) > 0.01;
    }

    /**
     * @return array{previous: float, new: float}
     */
    public function refresh(Transporter $transporter): array
    {
        if (! $this->hasMismatch($transporter)) {
            throw new InvalidArgumentException('TDS percentage is already up to date.');
        }

        $previous = round((float) $transporter->tds_percentage, 2);
        $new = round($this->calculatedPercentage($transporter), 2);

        $transporter->update([
            'tds_percentage' => $new,
            'tds_adjustment_remark' => null,
        ]);

        $this->freightBookingTdsSync->syncForTransporter($transporter, $new);

        return [
            'previous' => $previous,
            'new' => $new,
        ];
    }
}
