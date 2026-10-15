<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransporterRequest;
use App\Http\Requests\UpdateTransporterRequest;
use App\Models\AdvancePayment;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Services\FreightBookingTdsSync;
use App\Services\TransporterTdsCalculator;
use App\Services\TransporterTdsRefresh;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use App\Imports\TransporterImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class TransporterController extends Controller
{
    public function __construct(
        private readonly TransporterTdsCalculator $tdsCalculator,
        private readonly FreightBookingTdsSync $freightBookingTdsSync,
        private readonly TransporterTdsRefresh $transporterTdsRefresh,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getTransportersData($request);
        }

        return view('transporters.index');
    }


    public function bulkUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
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
            $import = new \App\Imports\TransporterImport();

            Excel::import(
                $import,
                $request->file('file')
            );

            $total = $import->successCount + count($import->errors);

            if (! empty($import->errors)) {
                return response()->json([
                    'success' => $import->successCount > 0,
                    'partial_success' => $import->successCount > 0,
                    'message' => $import->successCount > 0
                        ? $import->successCount . ' transporter(s) imported successfully. Some rows have errors.'
                        : 'No transporter records were imported.',
                    'imported' => $import->successCount,
                    'success_count' => $import->successCount,
                    'total' => $total,
                    'errors' => $import->errors,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $import->successCount . ' transporter(s) imported successfully.',
                'imported' => $import->successCount,
                'success_count' => $import->successCount,
                'total' => $total,
                'errors' => [],
            ]);

        } catch (\Throwable $e) {
            \Log::error('Transporter bulk upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process the file. Please check the file and try again.',
                'errors' => [$e->getMessage()],
            ], 500);
        }
    }


public function bulkUploadTemplate()
{
    $headers = [
        'PAN Number',
        'Transport Name',
        'Email',
        'GST Number',
        'PAN Active',
        'Contact Person Name',
        'Mobile Number',
        'Country',
        'State',
        'City',
        'Address',
        'Declaration Given',
        'TDS Percentage',
        'TDS Adjustment Remark',
        'Short Name',
        'Advance Amount',
    ];

    $callback = function () use ($headers) {

        $file = fopen('php://output', 'w');

        fputcsv($file, $headers);

        fclose($file);
    };

    return response()->streamDownload(
        $callback,
        'transporter_template.csv',
        [
            'Content-Type' => 'text/csv',
        ]
    );
}



    private function getTransportersData(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $orderColumn = (int) data_get($request->order, '0.column', 0);
            $orderDirection = strtolower((string) data_get($request->order, '0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

            $hasGlobalSearch = $request->has('search') && ! empty($request->search['value']);
            $hasColumnSearch = false;
            if ($request->has('columns')) {
                foreach ($request->columns as $column) {
                    if (! empty($column['search']['value'])) {
                        $hasColumnSearch = true;
                        break;
                    }
                }
            }

            $baseTransporterQuery = Transporter::query()
                ->where('is_owner', 0);

            if ($companyId) {
                $baseTransporterQuery->where('company_id', $companyId);
            }

            $totalRecords = (clone $baseTransporterQuery)->count();
            $start = max((int) ($request->start ?? 0), 0);
            $length = max((int) ($request->length ?? 10), 1);

            $requiresJoinQuery = $hasGlobalSearch || $hasColumnSearch || in_array($orderColumn, [0, 3], true);

            if (! $requiresJoinQuery) {
                $localSortColumns = [
                    1 => 'short_name',
                    2 => 'beneficiary_id',
                    4 => 'contact_person_name',
                    5 => 'mobile_number',
                    6 => 'advance_amount',
                ];
                $sortColumn = $localSortColumns[$orderColumn] ?? 'created_at';

                $transporters = (clone $baseTransporterQuery)
                    ->with([
                        'transporterMaster:id,name,pan_number',
                        'company:id,name',
                    ])
                    ->orderBy($sortColumn, $orderDirection)
                    ->offset($start)
                    ->limit($length)
                    ->get([
                        'id',
                        'transporter_master_id',
                        'company_id',
                        'short_name',
                        'beneficiary_id',
                        'contact_person_name',
                        'mobile_number',
                        'advance_amount',
                        'declaration_given',
                    ]);

                $data = $transporters->map(function ($transporter, $index) use ($start) {
                    return [
                        'sr_no' => $start + $index + 1,
                        'name' => '<a href="javascript:void(0)" class="text-brand-primary fw-bold transporter-name-link" data-id="'.$transporter->id.'" style="cursor: pointer; text-decoration: none;">'.($transporter->transporterMaster?->name ?: '-').'</a>',
                        'short_name' => $transporter->short_name ?: '-',
                        'beneficiary_id' => $transporter->beneficiary_id ?: '-',
                        'pan_number' => $transporter->transporterMaster?->pan_number ?: '-',
                        'contact_person' => $transporter->contact_person_name ?: '-',
                        'contact_number' => $transporter->mobile_number ?: '-',
                        'declaration_given' => $transporter->declaration_given ? 'Yes' : 'No',
                        'advance_amount' => ''.number_format($transporter->advance_amount ?: 0, 2),
                        'company' => $transporter->company?->name ?: '-',
                        'actions' => $this->getActionButtons((object) [
                            'id' => $transporter->id,
                            'transporter_name' => $transporter->transporterMaster?->name,
                            'transporter_pan_number' => $transporter->transporterMaster?->pan_number, 
                        ]),
                    ];
                });

                return response()->json([
                    'draw' => intval($request->draw),
                    'recordsTotal' => $totalRecords,
                    'recordsFiltered' => $totalRecords,
                    'data' => $data,
                ]);
            }

            if (! $hasGlobalSearch && ! $hasColumnSearch && in_array($orderColumn, [0, 3], true)) {
                $orderByMasterColumn = $orderColumn === 0 ? 'transporter_masters.name' : 'transporter_masters.pan_number';

                $orderedIds = (clone $baseTransporterQuery)
                    ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                    ->orderBy($orderByMasterColumn, $orderDirection)
                    ->offset($start)
                    ->limit($length)
                    ->pluck('transporters.id');

                $transportersById = Transporter::query()
                    ->with([
                        'transporterMaster:id,name,pan_number',
                        'company:id,name',
                    ])
                    ->whereIn('id', $orderedIds)
                    ->get([
                        'id',
                        'transporter_master_id',
                        'company_id',
                        'short_name',
                        'beneficiary_id',
                        'contact_person_name',
                        'mobile_number',
                        'advance_amount',
                        'declaration_given',
                    ])
                    ->keyBy('id');

                $data = $orderedIds->values()->map(function ($id, $index) use ($start, $transportersById) {
                    $transporter = $transportersById->get($id);
                    if (! $transporter) {
                        return null;
                    }

                    return [
                        'sr_no' => $start + $index + 1,
                        'name' => '<a href="javascript:void(0)" class="text-brand-primary fw-bold transporter-name-link text-nowrap" data-id="'.$transporter->id.'" style="cursor: pointer; text-decoration: none; white-space: nowrap;">'.($transporter->transporterMaster?->name ?: '-').'</a>',
                        'short_name' => $transporter->short_name ?: '-',
                        'beneficiary_id' => $transporter->beneficiary_id ?: '-',
                        'pan_number' => $transporter->transporterMaster?->pan_number ?: '-',
                        'contact_person' => $transporter->contact_person_name ?: '-',
                        'contact_number' => $transporter->mobile_number ?: '-',
                        'declaration_given' => $transporter->declaration_given ? 'Yes' : 'No',
                        'advance_amount' => ''.number_format($transporter->advance_amount ?: 0, 2),
                        'company' => $transporter->company?->name ?: '-',
                        'actions' => $this->getActionButtons((object) [
                            'id' => $transporter->id,
                            'transporter_name' => $transporter->transporterMaster?->name,
                        ]),
                        'actions' => $this->getActionButtons((object) [
                       'id' => $transporter->id,
                       'transporter_name' => $transporter->transporterMaster?->name,
                       'transporter_pan_number' => $transporter->transporterMaster?->pan_number,
                        ]),
                    ];
                })->filter()->values();

                return response()->json([
                    'draw' => intval($request->draw),
                    'recordsTotal' => $totalRecords,
                    'recordsFiltered' => $totalRecords,
                    'data' => $data,
                ]);
            }

            $query = Transporter::query()
                ->leftJoin('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->leftJoin('companies', 'transporters.company_id', '=', 'companies.id')
                ->where('transporters.is_owner', 0)
                ->select([
                    'transporters.id',
                    'transporters.short_name',
                    'transporters.beneficiary_id',
                    'transporters.contact_person_name',
                    'transporters.mobile_number',
                    'transporters.advance_amount',
                    'transporters.declaration_given',
                    'transporters.created_at',
                    'transporter_masters.name as transporter_name',
                    'transporter_masters.pan_number as transporter_pan_number',
                    'companies.name as company_name',
                ]);

            if ($companyId) {
                $query->where('transporters.company_id', $companyId);
            }

            // Handle global search
            if ($hasGlobalSearch) {
                $searchValue = $request->search['value'];
                $query->where(function ($q) use ($searchValue) {
                    $q->where('transporter_masters.name', 'like', "%{$searchValue}%")
                        ->orWhere('transporter_masters.pan_number', 'like', "%{$searchValue}%")
                        ->orWhere('transporters.mobile_number', 'like', "%{$searchValue}%")
                        ->orWhere('companies.name', 'like', "%{$searchValue}%")
                        ->orWhere('transporters.short_name', 'like', "%{$searchValue}%")
                        ->orWhere('transporters.beneficiary_id', 'like', "%{$searchValue}%");
                });
            }

            // Handle column-specific search
            if ($request->has('columns')) {
                foreach ($request->columns as $index => $column) {
                    if (! empty($column['search']['value'])) {
                        $searchValue = $column['search']['value'];
                        switch ($index) {
                            case 0: // Name
                                $query->where('transporter_masters.name', 'like', "%{$searchValue}%");
                                break;
                            case 1: // Short Name
                                $query->where('transporters.short_name', 'like', "%{$searchValue}%");
                                break;
                            case 2: // Beneficiary ID
                                $query->where('transporters.beneficiary_id', 'like', "%{$searchValue}%");
                                break;
                            case 3: // PAN Number
                                $query->where('transporter_masters.pan_number', 'like', "%{$searchValue}%");
                                break;
                            case 4: // Contact Person
                                $query->where('transporters.contact_person_name', 'like', "%{$searchValue}%");
                                break;
                            case 5: // Contact Number
                                $query->where('transporters.mobile_number', 'like', "%{$searchValue}%");
                                break;
                            case 6: // Book Balance
                                $searchValue = floatval($searchValue);
                                $query->where('transporters.advance_amount', $searchValue);
                                break;
                        }
                    }
                }
            }

            // Handle sorting
            switch ($orderColumn) {
                case 0: // Name
                    $query->orderBy('transporter_masters.name', $orderDirection);
                    break;
                case 1: // Short Number
                    $query->orderBy('transporters.short_name', $orderDirection);
                    break;
                case 2: // Beneficiary ID
                    $query->orderBy('transporters.beneficiary_id', $orderDirection);
                    break;
                case 3: // PAN Number
                    $query->orderBy('transporter_masters.pan_number', $orderDirection);
                    break;
                case 4: // Contact Person
                    $query->orderBy('transporters.contact_person_name', $orderDirection);
                    break;
                case 5: // Contact Number
                    $query->orderBy('transporters.mobile_number', $orderDirection);
                    break;
                case 6: // Book Balance
                    $query->orderBy('transporters.advance_amount', $orderDirection);
                    break;
                default:
                    $query->orderBy('transporters.created_at', 'desc');
            }

            // Skip expensive joined count when no filters are applied.
            $filteredRecords = ($hasGlobalSearch || $hasColumnSearch)
                ? (clone $query)->reorder()->count('transporters.id')
                : $totalRecords;

            // Handle pagination
            $transporters = $query->offset($start)->limit($length)->get();

            // Format data for DataTables
            $data = $transporters->map(function ($transporter, $index) use ($start) {
                return [
                    'sr_no' => $start + $index + 1,
                    'name' => '<a href="javascript:void(0)" class="text-brand-primary fw-bold transporter-name-link text-nowrap" data-id="'.$transporter->id.'" style="cursor: pointer; text-decoration: none; white-space: nowrap;">'.($transporter->transporter_name ?: '-').'</a>',
                    'short_name' => $transporter->short_name ?: '-',
                    'beneficiary_id' => $transporter->beneficiary_id ?: '-',
                    'pan_number' => $transporter->transporter_pan_number ?: '-',
                    'contact_person' => $transporter->contact_person_name ?: '-',

                    'contact_number' => $transporter->mobile_number ?: '-',
                    'declaration_given' => $transporter->declaration_given ? 'Yes' : 'No',
                    'advance_amount' => ''.number_format($transporter->advance_amount ?: 0, 2),
                    'company' => $transporter->company_name ?: '-',
                    'actions' => $this->getActionButtons($transporter),
                ];
            });

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('DataTables error in transporters: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'draw' => intval($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading data: '.$e->getMessage(),
            ], 500);
        }
    }

    private function getActionButtons($transporter)
    {
        $buttons = '<div class="app-index-table-actions">';
        $transporterName = $transporter->transporter_name ?? $transporter->name;

        $buttons .= '<button type="button" class="btn btn-sm btn-icon btn-action-advance advance-payment-btn" title="Advance Payment" data-bs-toggle="tooltip" data-transporter-id="'.$transporter->id.'" data-transporter-name="'.htmlspecialchars((string) $transporterName).'">';
        $buttons .= '<i class="bi bi-currency-rupee"></i>';
        $buttons .= '</button>';

        $editUrl = route('transporters.edit', $transporter->id);
        $buttons .= '<a href="'.$editUrl.'" class="btn btn-sm btn-icon btn-action-edit" title="Edit Transporter" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-pencil"></i></a>';

       $deleteUrl = route('transporters.destroy', $transporter->id);

       $buttons .= '<form action="'.$deleteUrl.'" method="POST" style="display:inline-block;" class="transporter-delete-form">';
       $buttons .= csrf_field().method_field('DELETE');

       $buttons .= '<button type="button"
      class="btn btn-sm btn-icon btn-action-delete transporter-delete-btn"
      data-bs-toggle="modal"
      data-bs-target="#transporterDeleteModal"
      data-transporter-name="'.e($transporter->transporter_name ?? $transporter->name ?? '-').'" 
      data-transporter-pan="'.e($transporter->transporter_pan_number ?? $transporter->pan_number ?? '-').'" 
      title="Delete Transporter">
     <i class="bi bi-trash"></i>
     </button>';
       $buttons .= '</form>';
        $buttons .= '</div>';
        return $buttons;
    }

    public function show($id)
    {
        $transporter = Transporter::with(['company', 'transporterMaster.city.state.country'])->findOrFail($id);

        if (Auth::user()->company_id && $transporter->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this transporter.');
        }

        // Get last 10 advance payments for this transporter
        $advancePayments = AdvancePayment::where('transporter_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => ''.number_format($payment->advance_amount, 2),
                    'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d-m-Y') : 'Not specified',
                    'remark' => $payment->remark ?: 'No remarks',
                    'created_at' => $payment->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
                ];
            });

        return response()->json([
            'id' => $transporter->id,
            'name' => $transporter->name,
            'short_name' => $transporter->short_name,
            'contact_person_name' => $transporter->contact_person_name,
            'gst_number' => $transporter->gst_number ?: 'Not provided',
            'pan_number' => $transporter->pan_number,
            'pan_active' => $transporter->pan_active ? 'Yes' : 'No',
            'mobile_number' => $transporter->mobile_number,
            'address' => $transporter->address ?: 'Not provided',
            'country' => optional($transporter->transporterMaster?->city?->state?->country)->name ?: 'Not specified',
            'state' => optional($transporter->transporterMaster?->city?->state)->name ?: 'Not specified',
            'city' => optional($transporter->transporterMaster?->city)->name ?: 'Not specified',
            'company' => optional($transporter->company)->name ?: 'Not specified',
            'is_owner' => $transporter->is_owner ? 'Truck Owner' : 'Agency',
            'declaration_given' => $transporter->declaration_given ? 'Yes' : 'No',
            'tds_percentage' => $transporter->tds_percentage ?: '0',
            'tds_adjustment_remark' => $transporter->tds_adjustment_remark ?: '',
            'advance_amount' => ''.number_format($transporter->advance_amount, 2),
            'created_at' => $transporter->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'updated_at' => $transporter->updated_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'advance_payments' => $advancePayments,
        ]);
    }

    public function create()
    {
        $countries = Country::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
        $states = [];
        $cities = [];

        if (old('country_id')) {
            $states = State::query()
                ->select(['id', 'name'])
                ->where('country_id', old('country_id'))
                ->orderBy('name')
                ->get();
        }

        if (old('state_id')) {
            $cities = City::query()
                ->select(['id', 'name'])
                ->where('state_id', old('state_id'))
                ->orderBy('name')
                ->get();
        }

        return view('transporters.create', compact('countries', 'states', 'cities'));
    }

    public function store(StoreTransporterRequest $request)
    {
        $validated = $request->validated();
        $companyId = (int) ($validated['company_id'] ?? 0);
        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber($companyId);

        $shortName = $validated['short_name'] ?? null;

        $validated['advance_amount'] = $validated['advance_amount'] ?? 0;
        $validated['is_owner'] = 0;
        $declarationGiven = (bool) $validated['declaration_given'];
        $validated['total_trucks'] = 0;

        if ($request->filled('transporter_master_id')) {
            $master = TransporterMaster::query()
                ->select(['id', 'pan_number', 'pan_active'])
                ->findOrFail($request->transporter_master_id);
            $validated['transporter_master_id'] = $master->id;
            $isDummyPan = strtoupper((string) $master->pan_number) === $dummyPan;

            $submittedTds = round((float) $validated['tds_percentage'], 2);
            $calculated = $this->tdsCalculator->calculate($declarationGiven, (string) $master->pan_number, (bool) $master->pan_active);

            if ($isDummyPan) {
                // Default UI baseline is 0%; keep the user-submitted value and always require a remark.
                $remark = trim((string) ($validated['tds_adjustment_remark'] ?? ''));
                if ($remark === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'tds_adjustment_remark' => ['TDS adjustment remark is required for dummy PAN.'],
                    ]);
                }
            } elseif ($declarationGiven) {
                $submittedTds = 0.00;
                $remark = null;
            } else {
                $remark = abs($submittedTds - round($calculated, 2)) > 0.01
                    ? trim((string) ($validated['tds_adjustment_remark'] ?? ''))
                    : null;
            }
            $validated['declaration_given'] = $declarationGiven;
            $validated['declaration_194c6_date'] = $declarationGiven ? ($validated['declaration_194c6_date'] ?? now()->toDateString()) : null;
            $validated['tds_percentage'] = $isDummyPan ? $submittedTds : ($declarationGiven ? 0.00 : $submittedTds);
            $validated['tds_adjustment_remark'] = $isDummyPan ? $remark : ($declarationGiven ? null : $remark);
            $validated['is_msme'] = $request->boolean('is_msme');
            $validated['msme_reg_no'] = $request->input('msme_reg_no');
            Transporter::create($validated);

            return redirect()->route('transporters.index')->with('success', 'Transporter created successfully!');
        }

        $email = TransporterMaster::normalizeEmail($validated['email'] ?? null);

        DB::transaction(function () use ($validated, $declarationGiven, $email, $dummyPan, $request) {
            $masterData = [
                'name' => $validated['name'],
                'email' => $email,
                'pan_number' => strtoupper($validated['pan_number']),
                'gst_number' => ! empty($validated['gst_number']) ? strtoupper($validated['gst_number']) : null,
                'gst_type' => $validated['gst_type'] ?? null,
                'pan_active' => (bool) $validated['pan_active'],
                'address' => $validated['address'] ?? null,
                'city_id' => $validated['city_id'] ?? null,
                'state_id' => $validated['state_id'] ?? null,
                'country_id' => $validated['country_id'] ?? null,
                'is_msme' => $request->boolean('is_msme'),
                'msme_reg_no' => $request->input('msme_reg_no'),
            ];
            $master = TransporterMaster::create($masterData);

            $isDummyPan = strtoupper((string) $master->pan_number) === $dummyPan;
            $submittedTds = round((float) $validated['tds_percentage'], 2);
            $calculated = $this->tdsCalculator->calculate($declarationGiven, (string) $master->pan_number, (bool) $master->pan_active);
            if ($isDummyPan) {
                // Default UI baseline is 0%; keep the user-submitted value and always require a remark.
                $remark = trim((string) ($validated['tds_adjustment_remark'] ?? ''));
                if ($remark === '') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'tds_adjustment_remark' => ['TDS adjustment remark is required for dummy PAN.'],
                    ]);
                }
            } else {
                $remark = abs($submittedTds - round($calculated, 2)) > 0.01
                    ? trim((string) ($validated['tds_adjustment_remark'] ?? ''))
                    : null;
            }

            $transporterData = [
                'transporter_master_id' => $master->id,
                'company_id' => $validated['company_id'],
                'is_owner' => 0,
                'contact_person_name' => $validated['contact_person_name'] ?? null,
                'mobile_number' => $validated['mobile_number'],
                'short_name' => $validated['short_name'] ?? null,
                'advance_amount' => $validated['advance_amount'] ?? 0,
                'declaration_given' => $declarationGiven,
                'declaration_194c6_date' => $declarationGiven ? ($validated['declaration_194c6_date'] ?? now()->toDateString()) : null,
                'total_trucks' => 0,
                'tds_percentage' => $isDummyPan ? $submittedTds : ($declarationGiven ? 0.00 : $submittedTds),
                'tds_adjustment_remark' => $isDummyPan ? $remark : ($declarationGiven ? null : $remark),
                'is_msme' => $request->boolean('is_msme'),
                'msme_reg_no' => $request->input('msme_reg_no'),
            ];
            Transporter::create($transporterData);
        });

        return redirect()->route('transporters.index')->with('success', 'Transporter created successfully!');
    }

    public function edit(Transporter $transporter)
    {
        if (Auth::user()->company_id && $transporter->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this transporter.');
        }
        $transporter->load(['transporterMaster.city.state.country']);
        // Filter trucks to show only those belonging to this transporter
        $trucks = Truck::where('transporter_id', $transporter->id)->get();
        $countries = Country::all();
        $states = [];
        $cities = [];

        if (old('country_id')) {
            $states = State::where('country_id', old('country_id'))->get();
        }

        if (old('state_id')) {
            $cities = City::where('state_id', old('state_id'))->get();
        }

        // $countries = \App\Models\Country::all();
        // $states = $transporter->country_id ? \App\Models\State::where('country_id', $transporter->country_id)->get() : collect();
        // $cities = $transporter->state_id ? \App\Models\City::where('state_id', $transporter->state_id)->get() : collect();
        $calculatedTdsPercentage = $this->transporterTdsRefresh->calculatedPercentage($transporter);
        $hasTdsMismatch = $this->transporterTdsRefresh->hasMismatch($transporter);
        $storedTdsPercentage = round((float) $transporter->tds_percentage, 2);

        return view('transporters.edit', compact(
            'transporter',
            'trucks',
            'countries',
            'states',
            'cities',
            'calculatedTdsPercentage',
            'hasTdsMismatch',
            'storedTdsPercentage',
        ));
    }

    public function refreshTds(Transporter $transporter): RedirectResponse
    {
        if (Auth::user()->company_id && $transporter->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this transporter.');
        }

        try {
            $result = $this->transporterTdsRefresh->refresh($transporter);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('transporters.edit', $transporter)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('transporters.edit', $transporter)
            ->with('success', sprintf(
                'TDS percentage updated from %s%% to %s%%.',
                number_format($result['previous'], 2),
                number_format($result['new'], 2),
            ));
    }

    public function update(UpdateTransporterRequest $request, Transporter $transporter)
    {
        if (Auth::user()->company_id && $transporter->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this transporter.');
        }
        $validated = $request->validated();
        $master = $transporter->transporterMaster;
        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber((int) ($validated['company_id'] ?? $transporter->company_id ?? 0));
        $previousTds = round((float) $transporter->tds_percentage, 2);
        $submittedTds = round((float) $validated['tds_percentage'], 2);
        $calculated = $this->tdsCalculator->calculate(
            (bool) $validated['declaration_given'],
            (string) $master->pan_number,
            (bool) $master->pan_active
        );

        $isDummyPan = strtoupper((string) $master->pan_number) === $dummyPan;
        if ($isDummyPan) {
            // Default UI baseline is 0%; keep the user-submitted value and always require a remark.
            $tdsRemark = trim((string) ($validated['tds_adjustment_remark'] ?? ''));
            if ($tdsRemark === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'tds_adjustment_remark' => ['TDS adjustment remark is required for dummy PAN.'],
                ]);
            }
        } else {
            $tdsRemark = abs($submittedTds - round($calculated, 2)) > 0.01
                ? trim((string) ($validated['tds_adjustment_remark'] ?? ''))
                : null;
        }

        $declGiven = (bool) ($validated['declaration_given'] ?? false);
        $updateData = [
            'company_id' => $validated['company_id'],
            'contact_person_name' => $validated['contact_person_name'] ?? null,
            'mobile_number' => $validated['mobile_number'],
            'advance_amount' => $validated['advance_amount'] ?? 0,
            'declaration_given' => $declGiven,
            'declaration_194c6_date' => $declGiven ? ($validated['declaration_194c6_date'] ?? now()->toDateString()) : null,
            'total_trucks' => $validated['total_trucks'] ?? 0,
            'tds_percentage' => $isDummyPan ? $submittedTds : ($declGiven ? 0.00 : $submittedTds),
            'tds_adjustment_remark' => $isDummyPan ? $tdsRemark : ($declGiven ? null : $tdsRemark),
            'is_msme' => $request->boolean('is_msme'),
            'msme_reg_no' => $request->input('msme_reg_no'),
        ];

        if (auth()->user()->userRole->name === 'super_admin') {
            $updateData['short_name'] = $validated['short_name'] ?? null;
            $updateData['beneficiary_id'] = $validated['beneficiary_id'] ?? null;
        }

        $transporter->update($updateData);

        if ($master) {
            $master->update([
                'is_msme' => $request->boolean('is_msme'),
                'msme_reg_no' => $request->input('msme_reg_no'),
            ]);
        }

        if (abs($previousTds - $submittedTds) > 0.01) {
            $this->freightBookingTdsSync->syncForTransporter($transporter, $submittedTds);
        }

        return redirect()->route('transporters.index')->with('success', 'Transporter updated successfully!');
    }

    public function calculateTdsPercentage(Request $request)
    {
        $panNumber = strtoupper((string) $request->input('pan_number', ''));
        $panActive = filter_var($request->input('pan_active'), FILTER_VALIDATE_BOOLEAN);
        $declarationGiven = $request->input('declaration_given') == '1';

        // Dummy PANs are company-scoped and intentionally do not match real PAN regex.
        // Since the dummy helper forces the first character to be a digit, we can safely return 0.00 for UI purposes.
        if ($panNumber !== '' && strlen($panNumber) === 10 && ctype_digit($panNumber[0])) {
            $tdsPercentage = 0.00;
        } else {
            $tdsPercentage = $this->tdsCalculator->calculate($declarationGiven, $panNumber, $panActive);
        }

        return response()->json([
            'tds_percentage' => number_format($tdsPercentage, 2),
        ]);
    }

    /**
     * Look up TransporterMaster by PAN. Optional company_id + is_owner check for "already registered" in same company as transporter (0) or truck owner (1).
     */
    public function getTransporterMasterByPan(Request $request)
    {
        $panNumber = strtoupper(trim($request->query('pan_number', '')));

        $companyIdRaw = $request->query('company_id');
        $companyId = null;
        if ($companyIdRaw !== null && $companyIdRaw !== '' && is_numeric((string) $companyIdRaw)) {
            $companyId = (int) $companyIdRaw;
        }

        $dummyPan = $companyId !== null
            ? Company::dummyTransporterTruckOwnerPanNumber($companyId)
            : null;

        $isDummyPan = $dummyPan !== null && $panNumber === $dummyPan;

        if (strlen($panNumber) !== 10 || (! $isDummyPan && ! preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $panNumber))) {
            return response()->json(['error' => 'Invalid PAN number'], 422);
        }

        $isOwner = $request->has('is_owner') ? (int) $request->query('is_owner') : null;

        // Dummy PAN should always behave as "not found" so the UI always opens the new master form.
        if ($isDummyPan) {
            return response()->json([
                'found' => false,
                'already_registered' => false,
                'is_dummy_pan' => true,
            ]);
        }

        $master = TransporterMaster::with(['city', 'state', 'country'])
            ->where('pan_number', $panNumber)
            ->first();

        $alreadyRegistered = false;
        if ($master && $companyId !== null && $companyId !== '' && $isOwner !== null) {
            $alreadyRegistered = Transporter::where('transporter_master_id', $master->id)
                ->where('company_id', (int) $companyId)
                ->where('is_owner', (int) $isOwner)
                ->exists();
        }

        if (! $master) {
            return response()->json([
                'found' => false,
                'already_registered' => false,
                'is_dummy_pan' => false,
            ]);
        }

        return response()->json([
            'found' => true,
            'already_registered' => $alreadyRegistered,
            'is_dummy_pan' => false,
            'master' => [
                'id' => $master->id,
                'name' => $master->name,
                'email' => $master->email,
                'pan_number' => $master->pan_number,
                'gst_number' => $master->gst_number,
                'gst_type' => $master->gst_type,
                'pan_active' => $master->pan_active,
                'address' => $master->address,
                'city_id' => $master->city_id,
                'state_id' => $master->state_id,
                'country_id' => $master->country_id,
                'city' => $master->city?->name,
                'state' => $master->state?->name,
                'country' => $master->country?->name,
            ],
        ]);
    }

    public function destroy(Request $request, $id)
{
    $request->validate([
        'delete_remark' => ['required', 'string', 'max:1000'],
    ]);

    $transporter = Transporter::findOrFail($id);

    $transporter->deleted_by = Auth::id();
    $transporter->deleted_remark = $request->input('delete_remark');
    $transporter->save();

    $transporter->delete();

    return redirect()
    ->route('transporters.index')
    ->with('success', 'Transporter deleted successfully!');
}

    /**
     * Get truck owners for API
     */
    public function getTruckOwnersApi()
    {
        try {
            $query = Transporter::with('transporterMaster')
                ->where('is_owner', 1);

            if (Auth::user()->company_id) {
                $query->where('company_id', Auth::user()->company_id);
            }

            $truckOwners = $query->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name]);

            return response()->json($truckOwners);
        } catch (\Exception $e) {
            \Log::error('Error fetching truck owners: '.$e->getMessage());

            return response()->json(['error' => 'Failed to fetch truck owners'], 500);
        }
    }

    /**
     * Get agencies for API
     */
    public function getAgenciesApi()
    {
        try {
            $query = Transporter::with('transporterMaster')
                ->where('is_owner', 0);

            if (Auth::user()->company_id) {
                $query->where('company_id', Auth::user()->company_id);
            }

            $agencies = $query->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                ->orderBy('transporter_masters.name')
                ->select('transporters.*')
                ->get()
                ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name]);

            return response()->json($agencies);
        } catch (\Exception $e) {
            \Log::error('Error fetching agencies: '.$e->getMessage());

            return response()->json(['error' => 'Failed to fetch agencies'], 500);
        }
    }

    //
}
