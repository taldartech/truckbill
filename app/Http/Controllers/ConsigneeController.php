<?php

namespace App\Http\Controllers;

use App\Models\Consignee;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Imports\ConsigneeImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class ConsigneeController extends Controller
{

    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getConsigneesData($request);
        }

        return view('consignees.index');
    }

   public function bulkUpload(Request $request)
{
    $validator = Validator::make($request->all(), [
        'file' => [
            'required',
            'file',
            'extensions:xlsx,xls,csv',
            'max:10240',
        ],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'File validation failed.',
            'errors' => $validator->errors()->all(),
        ], 422);
    }

    try {
        $import = app(ConsigneeImport::class);

        Excel::import(
            $import,
            $request->file('file')
        );

        $total = $import->successCount + count($import->errors);

        /*
         * PARTIAL / VALIDATION ERRORS
         */
        if (!empty($import->errors)) {
            return response()->json([
                'success' => $import->successCount > 0,
                'partial_success' => $import->successCount > 0,

                'message' => $import->successCount > 0
                    ? $import->successCount . ' customer(s) imported successfully. Some rows have errors.'
                    : 'No customer records were imported.',

                'imported' => $import->successCount,
                'success_count' => $import->successCount,
                'total' => $total,

                'errors' => $import->errors,
            ], 200);
        }

        /*
         * EVERYTHING SUCCESSFUL
         */
        return response()->json([
            'success' => true,

            'message' => $import->successCount .
                ' customer(s) imported successfully.',

            'imported' => $import->successCount,
            'success_count' => $import->successCount,
            'total' => $total,

            'errors' => [],
        ], 200);

    } catch (\Throwable $e) {

        Log::error('Customer bulk upload failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Unable to process the file. Please check the file and try again.',
            'errors' => [
                $e->getMessage()
            ],
        ], 500);
    }
}

public function bulkUploadTemplate()
{
    $headers = [
        'Name',
        'Contact Number',
        'Email',
        'GST Number',
        'PAN Number',
        'Country',
        'State',
        'City',
        'Address',
    ];

    return response()->streamDownload(function () use ($headers) {
        $handle = fopen('php://output', 'w');

        fputcsv($handle, $headers);

        fclose($handle);
    }, 'customer-bulk-upload-template.csv', [
        'Content-Type' => 'text/csv',
    ]);
}

    private function getConsigneesData(Request $request)
    {
        $query = Consignee::where('company_id', auth()->user()->company_id)
            ->with(['city', 'state', 'country']);

        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('address', 'like', "%{$searchValue}%")
                    ->orWhere('contact_number', 'like', "%{$searchValue}%")
                    ->orWhere('gst_number', 'like', "%{$searchValue}%")
                    ->orWhere('pan_number', 'like', "%{$searchValue}%")
                    ->orWhereHas('city', function ($cityQuery) use ($searchValue) {
                        $cityQuery->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('state', function ($stateQuery) use ($searchValue) {
                        $stateQuery->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('country', function ($countryQuery) use ($searchValue) {
                        $countryQuery->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Handle column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (! empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1: // Name
                            $query->where('name', 'like', "%{$searchValue}%");
                            break;
                        case 2: // City
                            $query->whereHas('city', function ($cityQuery) use ($searchValue) {
                                $cityQuery->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 3: // State
                            $query->whereHas('state', function ($stateQuery) use ($searchValue) {
                                $stateQuery->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 4: // Country
                            $query->whereHas('country', function ($countryQuery) use ($searchValue) {
                                $countryQuery->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 5: // Contact Number
                            $query->where('contact_number', 'like', "%{$searchValue}%");
                            break;
                    }
                }
            }
        }

        // Handle sorting
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDirection = $request->order[0]['dir'];

            switch ($orderColumn) {
                case 0: // Sr. No
                    // No sorting on Sr. No column
                    break;
                case 1: // Name
                    $query->orderBy('name', $orderDirection);
                    break;
                case 2: // City
                    $query->join('cities', 'consignees.city_id', '=', 'cities.id')
                        ->orderBy('cities.name', $orderDirection)
                        ->select('consignees.*');
                    break;
                case 3: // State
                    $query->join('states', 'consignees.state_id', '=', 'states.id')
                        ->orderBy('states.name', $orderDirection)
                        ->select('consignees.*');
                    break;
                case 4: // Country
                    $query->join('countries', 'consignees.country_id', '=', 'countries.id')
                        ->orderBy('countries.name', $orderDirection)
                        ->select('consignees.*');
                    break;
                case 5: // Contact Number
                    $query->orderBy('contact_number', $orderDirection);
                    break;
                default:
                    $query->orderBy('name', 'asc');
            }
        } else {
            $query->orderBy('name', 'asc');
        }

        // Get total count before pagination
        $totalRecords = Consignee::where('company_id', auth()->user()->company_id)->count();
        $filteredRecords = $query->count();

        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $consignees = $query->orderBy('name', 'asc')->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = $consignees->map(function ($consignee, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $consignee->id,
                'name' => '<a href="javascript:void(0)" class="text-brand-primary fw-bold consignee-name-link" data-id="'.$consignee->id.'" style="cursor: pointer; text-decoration: none;">'.($consignee->name ?: '-').'</a>',
                'city' => $consignee->city?->name ?? '-',
                'state' => $consignee->state?->name ?? '-',
                'country' => $consignee->country?->name ?? '-',
                'contact_number' => $consignee->contact_number ?: '-',
                'actions' => $this->getActionButtons($consignee),
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function getActionButtons($consignee)
    {
        $buttons = '<div class="app-index-table-actions">';

        // Edit button
        $editUrl = route('consignees.edit', $consignee->id);
        $buttons .= '<a href="'.$editUrl.'" class="btn btn-sm btn-icon btn-action-edit" title="Edit Consignee" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-pencil"></i></a>';

       // Delete button
        $deleteUrl = route('consignees.destroy', $consignee->id);

        $buttons .= '<form action="'.$deleteUrl.'" method="POST" class="consignee-delete-form">';
        $buttons .= csrf_field().method_field('DELETE');

        $buttons .= '<button type="button"
        class="btn btn-sm btn-icon btn-action-delete consignee-delete-btn"
        title="Delete Consignee"
        data-bs-toggle="modal"
        data-bs-target="#consigneeDeleteModal"
        data-consignee-id="'.$consignee->id.'"
        data-consignee-name="'.e($consignee->name ?? '-').'"
        data-consignee-contact="'.e($consignee->contact_number ?? '-').'"
        data-consignee-city="'.e($consignee->city?->name ?? '-').'"
        data-consignee-state="'.e($consignee->state?->name ?? '-').'"
        data-consignee-country="'.e($consignee->country?->name ?? '-').'"
        data-consignee-address="'.e($consignee->address ?? '-').'">';

        $buttons .= '<i class="bi bi-trash"></i>';
        $buttons .= '</button>';
        $buttons .= '</form>';
        $buttons .= '</div>';

        return $buttons;
    }

    public function create()
    {
        $countries = \App\Models\Country::all();
        $states = [];
        $cities = [];

        if (old('country_id')) {
            $states = \App\Models\State::where('country_id', old('country_id'))->get();
        }

        if (old('state_id')) {
            $cities = \App\Models\City::where('state_id', old('state_id'))->get();
        }

        return view('consignees.create', compact('countries', 'states', 'cities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'country_id' => 'required|exists:countries,id',
            'state_id' => [
                'required',
                'exists:states,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->country_id) {
                        $state = \App\Models\State::find($value);
                        if ($state && $state->country_id != $request->country_id) {
                            $fail('The selected state does not belong to the selected country.');
                        }
                    }
                },
            ],
            'city_id' => [
                'required',
                'exists:cities,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->state_id) {
                        $city = \App\Models\City::find($value);
                        if ($city && $city->state_id != $request->state_id) {
                            $fail('The selected city does not belong to the selected state.');
                        }
                    }
                    if ($value && $request->country_id) {
                        $city = \App\Models\City::find($value);
                        if ($city && $city->country_id != $request->country_id) {
                            $fail('The selected city does not belong to the selected country.');
                        }
                    }
                },
            ],
            'contact_number' => [
                'required',
                'digits:10',
                Rule::unique('consignees', 'contact_number')
                    ->where('company_id', auth()->user()->company_id)
                    ->withoutTrashed(),
            ],
            'gst_number' => [
                'nullable',
                'string',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',

            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',

            ],
            'email' => [
                'nullable',
                'email',

            ],

        ], [
            'contact_number.required' => 'The contact number is required.',
            'contact_number.digits' => 'The contact number must be exactly 10 digits.',
            'contact_number.unique' => 'This contact number is already used by another consignee in your company.',
        ]);
        $validated = $this->normalizeConsigneeValues($validated);
        $this->validateConsigneeUniquenessCreate($validated, auth()->user()->company_id);

        $validated['company_id'] = auth()->user()->company_id;

        try {
            $consignee = Consignee::create($validated);
        } catch (QueryException $exception) {
            if ($this->isConsigneeUniqueConstraintError($exception)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'pan_number' => 'This PAN number is already used by another consignee in your company.',
                ]);
            }
            throw $exception;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully!',
                'customer' => [
                    'id' => $consignee->id,
                    'name' => $consignee->name,
                ],
            ]);
        }

        return redirect()->route('consignees.index')
            ->with('success', 'Customer created successfully!');
    }

    private function validateConsigneeUniquenessCreate($data, $companyId)
    {
        $errors = [];

        // Query: non-deleted records from this company only
        $query = Consignee::where('company_id', $companyId)
            ->whereNull('deleted_at');

        // Check email
        if (! empty($data['email'])) {
            if ($query->clone()->where('email', $data['email'])->exists()) {
                $errors['email'] = 'This email is already used by another consignee in your company.';
            }
        }

        // Check PAN number
        if (! empty($data['pan_number'])) {
            if ($query->clone()->where('pan_number', $data['pan_number'])->exists()) {
                $errors['pan_number'] = 'This PAN number is already used by another consignee in your company.';
            }
        }

        if (! empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    public function edit(Consignee $consignee)
    {
        if (Auth::user()->company_id && $consignee->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this consignee.');
        }
        $countries = \App\Models\Country::all();
        $states = $consignee->country_id ? \App\Models\State::where('country_id', $consignee->country_id)->get() : collect();
        $cities = $consignee->state_id ? \App\Models\City::where('state_id', $consignee->state_id)->get() : collect();

        return view('consignees.edit', compact('consignee', 'countries', 'states', 'cities'));
    }

    public function update(Request $request, Consignee $consignee)
    {
        if (Auth::user()->company_id && $consignee->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this consignee.');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'country_id' => 'required|exists:countries,id',
            'state_id' => [
                'required',
                'exists:states,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->country_id) {
                        $state = \App\Models\State::find($value);
                        if ($state && $state->country_id != $request->country_id) {
                            $fail('The selected state does not belong to the selected country.');
                        }
                    }
                },
            ],
            'city_id' => [
                'required',
                'exists:cities,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value && $request->state_id) {
                        $city = \App\Models\City::find($value);
                        if ($city && $city->state_id != $request->state_id) {
                            $fail('The selected city does not belong to the selected state.');
                        }
                    }
                    if ($value && $request->country_id) {
                        $city = \App\Models\City::find($value);
                        if ($city && $city->country_id != $request->country_id) {
                            $fail('The selected city does not belong to the selected country.');
                        }
                    }
                },
            ],
            'contact_number' => [
                'required',
                'digits:10',
                Rule::unique('consignees', 'contact_number')
                    ->where('company_id', auth()->user()->company_id)
                    ->withoutTrashed()
                    ->ignore($consignee->id),
            ],
            'gst_number' => [
                'nullable',
                'string',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',

            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',

            ],
            'email' => [
                'nullable',
                'email',

            ],
        ], [
            'contact_number.required' => 'The contact number is required.',
            'contact_number.digits' => 'The contact number must be exactly 10 digits.',
            'contact_number.unique' => 'This contact number is already used by another consignee in your company.',
        ]);
        $validated = $this->normalizeConsigneeValues($validated);
        $this->validateConsigneeUniquenessUpdate($validated, $consignee->company_id, $consignee->id);

        $consignee->update($validated);

        return redirect()->route('consignees.index')
            ->with('success', 'Consignee updated successfully!');
    }

    private function validateConsigneeUniquenessUpdate($data, $companyId, $excludeId)
    {
        $errors = [];

        // Query: non-deleted records from this company, excluding current record
        $query = Consignee::where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('id', '!=', $excludeId);

        // Check email
        if (! empty($data['email'])) {
            if ($query->clone()->where('email', $data['email'])->exists()) {
                $errors['email'] = 'This email is already used by another consignee in your company.';
            }
        }

        // Check PAN number
        if (! empty($data['pan_number'])) {
            if ($query->clone()->where('pan_number', $data['pan_number'])->exists()) {
                $errors['pan_number'] = 'This PAN number is already used by another consignee in your company.';
            }
        }

        if (! empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeConsigneeValues(array $data): array
    {
        if (! empty($data['pan_number'])) {
            $data['pan_number'] = strtoupper((string) $data['pan_number']);
        }
        if (! empty($data['gst_number'])) {
            $data['gst_number'] = strtoupper((string) $data['gst_number']);
        }

        return $data;
    }

    private function isConsigneeUniqueConstraintError(QueryException $exception): bool
    {
        $errorCode = (string) ($exception->errorInfo[1] ?? '');
        $message = $exception->getMessage();

        return $errorCode === '1062'
            && str_contains($message, 'consignees_company_pan_unique');
    }

    public function destroy(Request $request, Consignee $consignee)
{
    if (Auth::user()->company_id && $consignee->company_id !== Auth::user()->company_id) {
        abort(403, 'Unauthorized access to this consignee.');
    }

    $request->validate([
        'delete_remark' => ['required', 'string', 'max:1000'],
    ]);

    $consignee->deleted_by = Auth::id();
    $consignee->deleted_remark = $request->input('delete_remark');
    $consignee->save();

    $consignee->delete();

    return redirect()->route('consignees.index')
        ->with('success', 'Consignee deleted successfully!');
}
    /**
     * Get consignee data for API
     * Consolidated method that returns all consignee details
     * Used for both form data and detail views
     */
    public function getConsigneeApi($id)
    {
        try {
            $query = Consignee::with(['city', 'state', 'country', 'company']);

            if (Auth::user()->company_id) {
                $query->where('company_id', Auth::user()->company_id);
            }

            $consignee = $query->findOrFail($id);

            return response()->json([
                'id' => $consignee->id,
                'name' => $consignee->name ?: 'Not provided',
                'contact_number' => $consignee->contact_number ?: 'Not provided',
                'address' => $consignee->address ?: 'Not provided',
                'email' => $consignee->email ?: 'Not provided',
                'gst_number' => $consignee->gst_number ?: 'Not provided',
                'pan_number' => $consignee->pan_number ?: 'Not provided',
                'city' => $consignee->city?->name ?: 'Not provided',
                'state' => $consignee->state?->name ?: 'Not provided',
                'country' => $consignee->country?->name ?: 'Not provided',
                'company' => $consignee->company?->name ?: 'Not provided',
                'created_at' => \Carbon\Carbon::parse($consignee->created_at)->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') ?: 'Not available',
                'updated_at' => \Carbon\Carbon::parse($consignee->updated_at)->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') ?: 'Not available',
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching consignee: '.$e->getMessage());

            return response()->json(['error' => 'Consignee not found'], 404);
        }
    }

}
