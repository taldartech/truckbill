<?php

namespace App\Services;

use App\Models\Company;
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

        if ($this->isDummyPan($transporter, $master?->pan_number)) {
            return 0.00;
        }

        return $this->tdsCalculator->calculate(
            (bool) $transporter->declaration_given,
            (string) ($master?->pan_number ?? ''),
            (bool) ($master?->pan_active ?? false),
        );
    }

    private function isDummyPan(Transporter $transporter, ?string $panNumber): bool
    {
        if ($panNumber === null || $panNumber === '') {
            return false;
        }

        if (! $transporter->company_id) {
            return false;
        }

        $expectedDummyPan = Company::dummyTransporterTruckOwnerPanNumber((int) $transporter->company_id);

        return strtoupper($panNumber) === $expectedDummyPan;
    }

    public function hasMismatch(Transporter $transporter): bool
    {
        if ($this->isDummyPan($transporter, $transporter->transporterMaster?->pan_number)) {
            return false;
        }

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
