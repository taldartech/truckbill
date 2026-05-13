<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransporterRequest;
use App\Http\Requests\UpdateTransporterRequest;
use App\Models\Company;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\User;
use App\Services\FreightBookingTdsSync;
use App\Services\Notifications\TransporterOnboardingNotificationService;
use App\Services\TransporterTdsCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class TransporterController extends Controller
{
    public function __construct(
        private readonly TransporterOnboardingNotificationService $transporterOnboardingNotificationService,
        private readonly TransporterTdsCalculator $tdsCalculator,
        private readonly FreightBookingTdsSync $freightBookingTdsSync,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getTransportersData($request);
        }

        return view('transporters.index');
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

            $baseTransporterQuery = \App\Models\Transporter::query()
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
                        'advance_amount' => '₹'.number_format($transporter->advance_amount ?: 0, 2),
                        'company' => $transporter->company?->name ?: '-',
                        'actions' => $this->getActionButtons((object) [
                            'id' => $transporter->id,
                            'transporter_name' => $transporter->transporterMaster?->name,
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

                $transportersById = \App\Models\Transporter::query()
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
                        'name' => '<a href="javascript:void(0)" class="text-brand-primary fw-bold transporter-name-link" data-id="'.$transporter->id.'" style="cursor: pointer; text-decoration: none;">'.($transporter->transporterMaster?->name ?: '-').'</a>',
                        'short_name' => $transporter->short_name ?: '-',
                        'beneficiary_id' => $transporter->beneficiary_id ?: '-',
                        'pan_number' => $transporter->transporterMaster?->pan_number ?: '-',
                        'contact_person' => $transporter->contact_person_name ?: '-',
                        'contact_number' => $transporter->mobile_number ?: '-',
                        'declaration_given' => $transporter->declaration_given ? 'Yes' : 'No',
                        'advance_amount' => '₹'.number_format($transporter->advance_amount ?: 0, 2),
                        'company' => $transporter->company?->name ?: '-',
                        'actions' => $this->getActionButtons((object) [
                            'id' => $transporter->id,
                            'transporter_name' => $transporter->transporterMaster?->name,
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

            $query = \App\Models\Transporter::query()
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
                    'name' => '<a href="javascript:void(0)" class="text-brand-primary fw-bold transporter-name-link" data-id="'.$transporter->id.'" style="cursor: pointer; text-decoration: none;">'.($transporter->transporter_name ?: '-').'</a>',
                    'short_name' => $transporter->short_name ?: '-',
                    'beneficiary_id' => $transporter->beneficiary_id ?: '-',
                    'pan_number' => $transporter->transporter_pan_number ?: '-',
                    'contact_person' => $transporter->contact_person_name ?: '-',

                    'contact_number' => $transporter->mobile_number ?: '-',
                    'declaration_given' => $transporter->declaration_given ? 'Yes' : 'No',
                    'advance_amount' => '₹'.number_format($transporter->advance_amount ?: 0, 2),
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
        $buttons = '<div class="d-flex gap-1">';
        $transporterName = $transporter->transporter_name ?? $transporter->name;

        $buttons .= '<button type="button" class="btn btn-sm btn-view advance-payment-btn " title="Advance Payment" data-bs-toggle="tooltip" data-transporter-id="'.$transporter->id.'" data-transporter-name="'.htmlspecialchars((string) $transporterName).'">';
        $buttons .= '<i class="bi bi-currency-rupee "></i>';
        $buttons .= '</button>';

        // Edit button
        $editUrl = route('transporters.edit', $transporter->id);
        $buttons .= '<a href="'.$editUrl.'" class="btn btn-sm btn-edit " title="Edit Transporter" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-pencil "></i></a>';

        // Delete button
        $deleteUrl = route('transporters.destroy', $transporter->id);
        $buttons .= '<form action="'.$deleteUrl.'" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this transporter?\')">';
        $buttons .= csrf_field().method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete Transporter" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';

        $buttons .= '</div>';

        return $buttons;
    }

    public function show($id)
    {
        $transporter = \App\Models\Transporter::with(['company', 'transporterMaster.city.state.country'])->findOrFail($id);

        if (Auth::user()->company_id && $transporter->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this transporter.');
        }

        // Get last 10 advance payments for this transporter
        $advancePayments = \App\Models\AdvancePayment::where('transporter_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => '₹'.number_format($payment->advance_amount, 2),
                    'payment_date' => $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : 'Not specified',
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
            'advance_amount' => '₹'.number_format($transporter->advance_amount, 2),
            'created_at' => $transporter->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'updated_at' => $transporter->updated_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'advance_payments' => $advancePayments,
        ]);
    }

    public function create()
    {
        $countries = \App\Models\Country::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
        $states = [];
        $cities = [];

        if (old('country_id')) {
            $states = \App\Models\State::query()
                ->select(['id', 'name'])
                ->where('country_id', old('country_id'))
                ->orderBy('name')
                ->get();
        }

        if (old('state_id')) {
            $cities = \App\Models\City::query()
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
            $submittedTds = round((float) $validated['tds_percentage'], 2);
            $calculated = $this->tdsCalculator->calculate($declarationGiven, (string) $master->pan_number, (bool) $master->pan_active);
            $remark = abs($submittedTds - round($calculated, 2)) > 0.01
                ? trim((string) ($validated['tds_adjustment_remark'] ?? ''))
                : null;
            $validated['declaration_given'] = $declarationGiven;
            $validated['tds_percentage'] = $submittedTds;
            $validated['tds_adjustment_remark'] = $remark;
            Transporter::create($validated);

            return redirect()->route('transporters.index')->with('success', 'Transporter created successfully!');
        }

        $onboardingData = DB::transaction(function () use ($validated, $declarationGiven) {
            $masterData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'pan_number' => strtoupper($validated['pan_number']),
                'gst_number' => ! empty($validated['gst_number']) ? strtoupper($validated['gst_number']) : null,
                'gst_type' => $validated['gst_type'] ?? null,
                'pan_active' => (bool) $validated['pan_active'],
                'address' => $validated['address'] ?? null,
                'city_id' => $validated['city_id'] ?? null,
                'state_id' => $validated['state_id'] ?? null,
                'country_id' => $validated['country_id'] ?? null,
            ];
            $master = TransporterMaster::create($masterData);

            $submittedTds = round((float) $validated['tds_percentage'], 2);
            $calculated = $this->tdsCalculator->calculate($declarationGiven, (string) $master->pan_number, (bool) $master->pan_active);
            $remark = abs($submittedTds - round($calculated, 2)) > 0.01
                ? trim((string) ($validated['tds_adjustment_remark'] ?? ''))
                : null;

            $transporterData = [
                'transporter_master_id' => $master->id,
                'company_id' => $validated['company_id'],
                'is_owner' => 0,
                'contact_person_name' => $validated['contact_person_name'] ?? null,
                'mobile_number' => $validated['mobile_number'],
                'short_name' => $validated['short_name'] ?? null,
                'advance_amount' => $validated['advance_amount'] ?? 0,
                'declaration_given' => $declarationGiven,
                'total_trucks' => 0,
                'tds_percentage' => $submittedTds,
                'tds_adjustment_remark' => $remark,
            ];
            Transporter::create($transporterData);

            $transporterRoleId = \DB::table('user_roles')->where('name', 'transporter')->value('id');
            User::create([
                'name' => $master->name,
                'email' => $master->email,
                'password' => bcrypt($master->mobile_number),
                'user_role_id' => $transporterRoleId,
                'company_id' => null,
                'transporter_master_id' => $master->id,
            ]);

            return [
                'email' => $master->email,
                'name' => $master->name,
            ];
        });

        Password::sendResetLink(['email' => $onboardingData['email']]);
        $this->transporterOnboardingNotificationService->sendAccountCreated($onboardingData['email'], $onboardingData['name']);

        return redirect()->route('transporters.index')->with('success', 'Transporter created successfully! A password setup link has been sent to '.$onboardingData['email']);
    }

    public function edit(\App\Models\Transporter $transporter)
    {
        if (Auth::user()->company_id && $transporter->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this transporter.');
        }
        $transporter->load(['transporterMaster.city.state.country']);
        // Filter trucks to show only those belonging to this transporter
        $trucks = \App\Models\Truck::where('transporter_id', $transporter->id)->get();
        $countries = \App\Models\Country::all();
        $states = [];
        $cities = [];

        if (old('country_id')) {
            $states = \App\Models\State::where('country_id', old('country_id'))->get();
        }

        if (old('state_id')) {
            $cities = \App\Models\City::where('state_id', old('state_id'))->get();
        }

        // $countries = \App\Models\Country::all();
        // $states = $transporter->country_id ? \App\Models\State::where('country_id', $transporter->country_id)->get() : collect();
        // $cities = $transporter->state_id ? \App\Models\City::where('state_id', $transporter->state_id)->get() : collect();
        return view('transporters.edit', compact('transporter', 'trucks', 'countries', 'states', 'cities'));
    }

    public function update(UpdateTransporterRequest $request, \App\Models\Transporter $transporter)
    {
        if (Auth::user()->company_id && $transporter->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this transporter.');
        }
        $validated = $request->validated();
        $master = $transporter->transporterMaster;
        $previousTds = round((float) $transporter->tds_percentage, 2);
        $submittedTds = round((float) $validated['tds_percentage'], 2);
        $calculated = $this->tdsCalculator->calculate(
            (bool) $validated['declaration_given'],
            (string) $master->pan_number,
            (bool) $master->pan_active
        );
        $tdsRemark = abs($submittedTds - round($calculated, 2)) > 0.01
            ? trim((string) ($validated['tds_adjustment_remark'] ?? ''))
            : null;

        $updateData = [
            'company_id' => $validated['company_id'],
            'contact_person_name' => $validated['contact_person_name'] ?? null,
            'mobile_number' => $validated['mobile_number'],
            'advance_amount' => $validated['advance_amount'] ?? 0,
            'declaration_given' => $validated['declaration_given'],
            'total_trucks' => $validated['total_trucks'] ?? 0,
            'tds_percentage' => $submittedTds,
            'tds_adjustment_remark' => $tdsRemark,
        ];

        if (auth()->user()->userRole->name === 'super_admin') {
            $updateData['short_name'] = $validated['short_name'] ?? null;
            $updateData['beneficiary_id'] = $validated['beneficiary_id'] ?? null;
        }

        $transporter->update($updateData);

        if (abs($previousTds - $submittedTds) > 0.01) {
            $this->freightBookingTdsSync->syncForTransporter($transporter, $submittedTds);
        }

        return redirect()->route('transporters.index')->with('success', 'Transporter updated successfully!');
    }

    public function calculateTdsPercentage(Request $request)
    {
        $panNumber = (string) $request->input('pan_number', '');
        $panActive = filter_var($request->input('pan_active'), FILTER_VALIDATE_BOOLEAN);
        $declarationGiven = $request->input('declaration_given') == '1';

        $tdsPercentage = $this->tdsCalculator->calculate($declarationGiven, $panNumber, $panActive);

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
        if (strlen($panNumber) !== 10 || ! preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $panNumber)) {
            return response()->json(['error' => 'Invalid PAN number'], 422);
        }

        $companyId = $request->query('company_id');
        $isOwner = $request->has('is_owner') ? (int) $request->query('is_owner') : null;

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
            ]);
        }

        return response()->json([
            'found' => true,
            'already_registered' => $alreadyRegistered,
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

    public function destroy(\App\Models\Transporter $transporter)
    {
        if (Auth::user()->company_id && $transporter->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this transporter.');
        }
        $transporter->delete();

        return redirect()->route('transporters.index')->with('success', 'Transporter deleted successfully!');
    }

    /**
     * Get truck owners for API
     */
    public function getTruckOwnersApi()
    {
        try {
            $query = \App\Models\Transporter::with('transporterMaster')
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
            $query = \App\Models\Transporter::with('transporterMaster')
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
