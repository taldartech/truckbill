<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Truck;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Validation\Rule;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\User;
use App\Http\Requests\StoreTransporterRequest;
use App\Http\Requests\UpdateTransporterRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TransporterController extends Controller
{
    private function ensureCompanyContext(): void
    {
        if (Auth::user()->userRole->name === 'super_admin' && ! session('super_admin_company_id')) {
            abort(403, 'Please enter a company first.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureCompanyContext();
        if ($request->ajax()) {
            return $this->getTransportersData($request);
        }

        $companies = \App\Models\Company::all();
        return view('transporters.index', compact('companies'));
    }

    private function getTransportersData(Request $request)
    {
        try {
            \Log::info('DataTables request started for transporters');

            $query = \App\Models\Transporter::with(['company', 'transporterMaster'])
                ->where('is_owner', 0); // Default to show only Agencies

            if (Auth::user()->company_id) {
                $query->where('company_id', Auth::user()->company_id);
            }

            \Log::info('Query built successfully');

            // Handle global search
            if ($request->has('search') && $request->search['value']) {
                $searchValue = $request->search['value'];
                $query->where(function ($q) use ($searchValue) {
                    $q->whereHas('transporterMaster', fn ($m) => $m->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('pan_number', 'like', "%{$searchValue}%"))
                        ->orWhere('transporters.mobile_number', 'like', "%{$searchValue}%")
                        ->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'like', "%{$searchValue}%"))
                        ->orWhereHas('transporterMaster.city', fn ($cityQuery) => $cityQuery->where('name', 'like', "%{$searchValue}%"));
                });
            }

            // Handle column-specific search
            if ($request->has('columns')) {
                foreach ($request->columns as $index => $column) {
                    if (!empty($column['search']['value'])) {
                        $searchValue = $column['search']['value'];
                        switch ($index) {
                            case 0: // Name
                                $query->whereHas('transporterMaster', fn ($m) => $m->where('name', 'like', "%{$searchValue}%"));
                                break;
                            case 1: // Short Name
                                $query->where('transporters.short_name', 'like', "%{$searchValue}%");
                                break;
                            case 2: // Beneficiary ID
                                $query->where('transporters.beneficiary_id', 'like', "%{$searchValue}%");
                                break;
                            case 3: // PAN Number
                                $query->whereHas('transporterMaster', fn ($m) => $m->where('pan_number', 'like', "%{$searchValue}%"));
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
            if ($request->has('order')) {
                $orderColumn = $request->order[0]['column'];
                $orderDirection = $request->order[0]['dir'];

                switch ($orderColumn) {
                    case 0: // Name
                        $query->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                            ->orderBy('transporter_masters.name', $orderDirection)
                            ->select('transporters.*');
                        break;
                    case 1: // Short Number
                        $query->orderBy('transporters.short_name', $orderDirection);
                        break;
                    case 2: // Beneficiary ID
                        $query->orderBy('transporters.beneficiary_id', $orderDirection);
                        break;
                    case 3: // PAN Number
                        $query->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                            ->orderBy('transporter_masters.pan_number', $orderDirection)
                            ->select('transporters.*');
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
            } else {
                $query->orderBy('transporters.created_at', 'desc');
            }

            // Get total count before pagination
            $totalQuery = \App\Models\Transporter::where('is_owner', 0);
            if (Auth::user()->company_id) {
                $totalQuery->where('company_id', Auth::user()->company_id);
            }
            $totalRecords = $totalQuery->count();
            $filteredRecords = $query->count();

            // Handle pagination
            $start = $request->start ?? 0;
            $length = $request->length ?? 10;
            $transporters = $query->offset($start)->limit($length)->get();

            // Format data for DataTables
            $data = $transporters->map(function ($transporter, $index) use ($start) {
                return [
                    'sr_no' => $start + $index + 1,
                    'name' => '<a href="javascript:void(0)" class="text-brand-primary fw-bold transporter-name-link" data-id="' . $transporter->id . '" style="cursor: pointer; text-decoration: none;">' . ($transporter->name ?: '-') . '</a>',
                    'short_name' => $transporter->short_name ?: '-',
                    'beneficiary_id' => $transporter->beneficiary_id ?: '-',
                    'pan_number' => $transporter->pan_number ?: '-',
                    'contact_person' => $transporter->contact_person_name ?: '-',

                    'contact_number' => $transporter->mobile_number ?: '-',
                    'declaration_given' => $transporter->declaration_given ? 'Yes' : 'No',
                    'advance_amount' => '₹' . number_format($transporter->advance_amount ?: 0, 2),
                    'company' => optional($transporter->company)->name ?: '-',
                    'actions' => $this->getActionButtons($transporter)
                ];
            });

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('DataTables error in transporters: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'draw' => intval($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading data: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getActionButtons($transporter)
    {
        $buttons = '<div class="d-flex gap-1">';


        $buttons .= '<button type="button" class="btn btn-sm btn-view advance-payment-btn " title="Advance Payment" data-bs-toggle="tooltip" data-transporter-id="' . $transporter->id . '" data-transporter-name="' . htmlspecialchars($transporter->name) . '">';
        $buttons .= '<i class="bi bi-currency-rupee "></i>';
        $buttons .= '</button>';


        // Edit button
        $editUrl = route('transporters.edit', $transporter->id);
        $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-edit " title="Edit Transporter" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-pencil "></i></a>';

        // Delete button
        $deleteUrl = route('transporters.destroy', $transporter->id);
        $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this transporter?\')">';
        $buttons .= csrf_field() . method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete Transporter" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';

        $buttons .= '</div>';
        return $buttons;
    }

    public function show($id)
    {
        $this->ensureCompanyContext();
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
                    'amount' => '₹' . number_format($payment->advance_amount, 2),
                    'payment_date' => $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : 'Not specified',
                    'remark' => $payment->remark ?: 'No remarks',
                    'created_at' => $payment->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i')
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
            'advance_amount' => '₹' . number_format($transporter->advance_amount, 2),
            'created_at' => $transporter->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'updated_at' => $transporter->updated_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'advance_payments' => $advancePayments
        ]);
    }

    public function create()
    {
        $this->ensureCompanyContext();
        $companies = \App\Models\Company::all();

        // For create, we can't filter trucks as we don't have transporter_id yet
        $trucks = \App\Models\Truck::all();
        $countries = \App\Models\Country::all();
        $states = [];
        $cities = [];

        if (old('country_id')) {
            $states = \App\Models\State::where('country_id', old('country_id'))->get();
        }

        if (old('state_id')) {
            $cities = \App\Models\City::where('state_id', old('state_id'))->get();
        }
        return view('transporters.create', compact('companies', 'trucks', 'countries', 'states', 'cities'));
    }

    public function store(StoreTransporterRequest $request)
    {
        $this->ensureCompanyContext();
        $validated = $request->validated();

        $shortName = $validated['short_name'] ?? null;
        if ($shortName) {
            $existing = Transporter::where('short_name', $shortName)->where('company_id', $validated['company_id'])->exists();
            if ($existing) {
                $suffix = 1;
                do {
                    $shortName = ($validated['short_name'] ?? '') . '-' . $suffix;
                    $existing = Transporter::where('short_name', $shortName)->where('company_id', $validated['company_id'])->exists();
                    $suffix++;
                } while ($existing);
            }
            $validated['short_name'] = $shortName;
        }

        $validated['advance_amount'] = $validated['advance_amount'] ?? 0;
        $validated['is_owner'] = 0;
        $validated['declaration_given'] = false;
        $validated['total_trucks'] = 0;

        if ($request->filled('transporter_master_id')) {
            $master = TransporterMaster::findOrFail($request->transporter_master_id);
            $validated['transporter_master_id'] = $master->id;
            $validated['tds_percentage'] = $this->calculateTdsFromPan($master->pan_number, $master->pan_active);
            Transporter::create($validated);
            return redirect()->route('transporters.index')->with('success', 'Transporter created successfully!');
        }

        return DB::transaction(function () use ($validated) {
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

            

            $transporterData = [
                'transporter_master_id' => $master->id,
                'company_id' => $validated['company_id'],
                'is_owner' => 0,
                'contact_person_name' => $validated['contact_person_name'] ?? null,
                'mobile_number' => $validated['mobile_number'],
                'short_name' => $validated['short_name'] ?? null,
                'advance_amount' => $validated['advance_amount'] ?? 0,
                'declaration_given' => false,
                'total_trucks' => 0,
                'tds_percentage' => $this->calculateTdsFromPan($master->pan_number, $master->pan_active),
            ];
            Transporter::create($transporterData);

           

            $transporterRoleId = \DB::table('user_roles')->where('name', 'transporter')->value('id');
            $user = User::create([
                'name' => $master->name,
                'email' => $master->email,
                'password' => bcrypt($master->mobile_number),
                'user_role_id' => $transporterRoleId,
                'company_id' => null,
                'transporter_master_id' => $master->id,
            ]);

            Password::sendResetLink(['email' => $master->email]);

            return redirect()->route('transporters.index')->with('success', 'Transporter created successfully! A password setup link has been sent to ' . $master->email);
        });
    }

    private function calculateTdsFromPan(string $panNumber, bool $panActive): float
    {
        if (! $panActive) {
            return 20.00;
        }
        $fourthChar = strtoupper(substr($panNumber, 3, 1));
        $validChars = ['C', 'F', 'A', 'B', 'J', 'L', 'T'];
        $nonValidChars = ['P', 'H'];
        if (in_array($fourthChar, $validChars)) {
            return 2.00;
        }
        if (in_array($fourthChar, $nonValidChars)) {
            return 1.00;
        }
        return 20.00;
    }

    public function edit(\App\Models\Transporter $transporter)
    {
        $this->ensureCompanyContext();
        if (Auth::user()->company_id && $transporter->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this transporter.');
        }
        $transporter->load(['transporterMaster.city.state.country']);
        $companies = \App\Models\Company::all();
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
        return view('transporters.edit', compact('transporter', 'companies', 'trucks', 'countries', 'states', 'cities'));
    }

    public function update(UpdateTransporterRequest $request, \App\Models\Transporter $transporter)
    {
        $this->ensureCompanyContext();
        if (Auth::user()->company_id && $transporter->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this transporter.');
        }
        $validated = $request->validated();
        $updateData = [
            'company_id' => $validated['company_id'],
            'contact_person_name' => $validated['contact_person_name'] ?? null,
            'mobile_number' => $validated['mobile_number'],
            'advance_amount' => $validated['advance_amount'] ?? 0,
            'declaration_given' => $validated['declaration_given'] ?? false,
            'total_trucks' => $validated['total_trucks'] ?? 0,
        ];
        // Only update tds_percentage when explicitly provided; otherwise preserve existing (edit form does not send it)
        if (array_key_exists('tds_percentage', $validated)) {
            $updateData['tds_percentage'] = $validated['tds_percentage'];
        }

        if (auth()->user()->userRole->name === 'super_admin') {
            $updateData['short_name'] = $validated['short_name'] ?? null;
            $updateData['beneficiary_id'] = $validated['beneficiary_id'] ?? null;
        }

        $transporter->update($updateData);
        return redirect()->route('transporters.index')->with('success', 'Transporter updated successfully!');
    }

    public function calculateTdsPercentage(Request $request)
    {
        $panNumber = $request->input('pan_number');
        $panActive = $request->input('pan_active');

        $tdsPercentage = 0.00;

        if ($panActive == '0') {
            // PAN not active
            $tdsPercentage = 20.00;
        } elseif ($panNumber && strlen($panNumber) >= 4) {
            // PAN is active, check 4th character
            $fourthChar = strtoupper(substr($panNumber, 3, 1));
            $validChars = ['C', 'F', 'A', 'B', 'J', 'L', 'T'];
            $nonValidChars = ['P', 'H'];

            if (in_array($fourthChar, $validChars)) {
                $tdsPercentage = 2.00;
            } else if (in_array($fourthChar, $nonValidChars)) {
                $tdsPercentage = 1.00;
            } else {
                $tdsPercentage = 20.00;
            }
        }

        return response()->json([
            'tds_percentage' => number_format($tdsPercentage, 2)
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
        $this->ensureCompanyContext();
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
            \Log::error('Error fetching truck owners: ' . $e->getMessage());
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
            \Log::error('Error fetching agencies: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch agencies'], 500);
        }
    }

    //
}
