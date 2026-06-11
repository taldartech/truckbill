<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTransporterMasterFields;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransporterMasterRequest extends FormRequest
{
    use ValidatesTransporterMasterFields;

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->userRole?->name === 'master_admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->transporterMasterFieldRules();
    }
}
