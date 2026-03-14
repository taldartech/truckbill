<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTruckTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->canActAsAdmin();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $truckType = $this->route('truck_type');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('truck_types', 'name')->ignore($truckType->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This truck type name already exists.',
        ];
    }
}
