<?php

namespace App\Http\Requests\Concerns;

use App\Services\TransporterTdsCalculator;
use Illuminate\Validation\Validator;

trait ValidatesTransporterTdsOverride
{
    protected function tdsOverrideFieldRules(): array
    {
        return [
            'tds_percentage' => 'required|numeric|min:0|max:100',
            'tds_adjustment_remark' => 'nullable|string|max:1000',
        ];
    }

    protected function validateTdsOverrideAgainstCalculated(
        Validator $validator,
        bool $declarationGiven,
        string $panNumber,
        bool $panActive
    ): void {
        $calculator = app(TransporterTdsCalculator::class);
        $calculated = $calculator->calculate($declarationGiven, $panNumber, $panActive);
        $submitted = round((float) $this->input('tds_percentage'), 2);
        if (abs($submitted - round($calculated, 2)) <= 0.01) {
            return;
        }
        if (! filled(trim((string) $this->input('tds_adjustment_remark')))) {
            $validator->errors()->add(
                'tds_adjustment_remark',
                'A remark is required when TDS percentage differs from the calculated rate.'
            );
        }
    }
}
