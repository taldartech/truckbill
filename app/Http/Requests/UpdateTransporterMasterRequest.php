<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTransporterMasterFields;
use App\Models\TransporterMaster;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTransporterMasterRequest extends FormRequest
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
        /** @var TransporterMaster $transporterMaster */
        $transporterMaster = $this->route('transporter_master');

        return $this->transporterMasterFieldRules($transporterMaster->id);
    }
}
