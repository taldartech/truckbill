<?php

namespace App\Imports;

use App\Models\Pump;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class PumpImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows
{
    public $errors = [];
    public $imported = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $data = $this->normalizeRow($row->toArray());

            $validationErrors = $this->validateRow($data);

            if (!empty($validationErrors)) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validationErrors,
                ];

                continue;
            }

            Pump::create([
                'user_id' => Auth::user()->company->user_id,
                'pump_name' => $data['pump_name'],
                'contact_name' => $data['contact_name'],
                'contact_number' => $data['contact_number'],
                'remark' => $data['remark'] ?? null,
            ]);

            $this->imported++;
        }
    }

  private function normalizeRow(array $row): array
{
    $normalized = [];

    foreach ($row as $key => $value) {

        $key = Str::snake(trim($key));

        if ($key === 'contact_number' && is_numeric($value)) {
            $value = (string) $value;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        $normalized[$key] = $value;
    }

    if (empty($normalized['contact_number'])) {
        $normalized['contact_number'] = $normalized['contact_no'] ?? $normalized['mobile_number'] ?? $normalized['mobile'] ?? $normalized['phone'] ?? null;
    }

    if (empty($normalized['pump_name'])) {
        $normalized['pump_name'] = $normalized['name'] ?? null;
    }

    return $normalized;
}

    private function validateRow(array $data): array
    {
        $validator = Validator::make($data, [
            'pump_name' => [
                'required',
                'string',
                'max:255',
            ],
            'contact_name' => [
                'required',
                'string',
                'max:255',
            ],
            'contact_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('pumps', 'contact_number')
                    ->where('user_id', Auth::user()->company->user_id),
            ],
            'remark' => [
                'nullable',
                'string',
            ],
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return [];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}