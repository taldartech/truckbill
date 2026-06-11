<?php

namespace App\Http\Requests\Concerns;

use App\Models\City;
use App\Models\State;
use App\Models\TransporterMaster;
use App\Models\User;
use Illuminate\Validation\Rule;

trait ValidatesTransporterMasterFields
{
    /**
     * @return array<string, mixed>
     */
    protected function transporterMasterFieldRules(?int $ignoreMasterId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('transporter_masters', 'email')->ignore($ignoreMasterId),
                Rule::unique('users', 'email')->ignore(
                    User::query()->where('transporter_master_id', $ignoreMasterId)->value('id')
                ),
            ],
            'pan_number' => [
                'required',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                Rule::unique('transporter_masters', 'pan_number')->ignore($ignoreMasterId),
            ],
            'gst_number' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            ],
            'gst_type' => ['nullable', 'in:composition,regular'],
            'pan_active' => ['required', 'boolean'],
            'address' => ['nullable', 'string', 'max:500'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'state_id' => [
                'nullable',
                'exists:states,id',
                function ($attr, $value, $fail): void {
                    if ($value && $this->input('country_id')) {
                        $state = State::find($value);
                        if ($state && (int) $state->country_id !== (int) $this->input('country_id')) {
                            $fail('The selected state does not belong to the selected country.');
                        }
                    }
                },
            ],
            'city_id' => [
                'nullable',
                'exists:cities,id',
                function ($attr, $value, $fail): void {
                    if ($value && $this->input('state_id')) {
                        $city = City::find($value);
                        if ($city && (int) $city->state_id !== (int) $this->input('state_id')) {
                            $fail('The selected city does not belong to the selected state.');
                        }
                    }
                },
            ],
        ];
    }

    public function prepareTransporterMasterPayload(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['name'],
            'email' => TransporterMaster::normalizeEmail($validated['email'] ?? null),
            'pan_number' => strtoupper($validated['pan_number']),
            'gst_number' => ! empty($validated['gst_number']) ? strtoupper($validated['gst_number']) : null,
            'gst_type' => $validated['gst_type'] ?? null,
            'pan_active' => (bool) $validated['pan_active'],
            'address' => $validated['address'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'state_id' => $validated['state_id'] ?? null,
            'country_id' => $validated['country_id'] ?? null,
        ];
    }
}
