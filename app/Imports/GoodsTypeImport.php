<?php

namespace App\Imports;

use App\Models\GoodType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class GoodsTypeImport implements
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

            GoodType::create([
                'user_id' => Auth::user()->company->user_id,
                'name' => $data['name'],
            ]);

            $this->imported++;
        }
    }

    private function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $key = Str::snake(trim($key));

            if (is_string($value)) {
                $value = trim($value);
            }

            $normalized[$key] = $value;
        }

        if (empty($normalized['name'])) {
            $normalized['name'] = $normalized['goods_name'] ?? $normalized['goods_type_name'] ?? $normalized['goods_type'] ?? null;
        }

        return $normalized;
    }

    private function validateRow(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('good_types', 'name')
                    ->where('user_id', Auth::user()->company->user_id),
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