<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTruckOwnerRequest;
use App\Http\Requests\UpdateTruckOwnerRequest;
use App\Models\AdvancePayment;
use App\Models\City;
use App\Models\Country;
use App\Models\Driver;
use App\Models\State;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\TruckType;
use App\Services\FreightBookingTdsSync;
use App\Services\TransporterTdsCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TruckOwnerController extends Controller
{
    public function __construct(
        private readonly TransporterTdsCalculator $tdsCalculator,
        private readonly FreightBookingTdsSync $freightBookingTdsSync,
    ) {}

    private function ensureCompanyContext(): void
    {
        if (Auth::user()->userRole->name === 'super_admin' && ! session('super_admin_company_id')) {
            abort(403, 'Please enter a company first.');
        }
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getTruckOwnersData($request);
        }

        $truckTypes = TruckType::orderBy('name')->get();

        return view('truck_owner.index', compact('truckTypes'));
    }

    private function getTruckOwnersData(Request $request)
    {
        try {
            $query = Transporter::with([
                'company',
                'transporterMaster.city',
                'trucks:id,transporter_id,truck_number',
            ])->where('is_owner', 1); // Show only truck owners

            if (Auth::user()->company_id) {
                $query->where('company_id', Auth::user()->company_id);
            }

            // Handle search
            if ($request->has('search') && $request->search['value']) {
                $searchValue = $request->search['value'];
                $query->where(function ($q) use ($searchValue) {
                    $q->whereHas('transporterMaster', fn ($m) => $m->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('pan_number', 'like', "%{$searchValue}%"))
                        ->orWhere('transporters.mobile_number', 'like', "%{$searchValue}%")
                        ->orWhereHas('transporterMaster', fn ($m) => $m->whereHas('city', fn ($c) => $c->where('name', 'like', "%{$searchValue}%")));
                });
            }

            // Handle individual column searches
            if ($request->has('columns')) {
                foreach ($request->columns as $index => $column) {
                    if (! empty($column['search']['value'])) {
                        $searchValue = $column['search']['value'];
                        switch ($index) {
                            case 0: // Name
                                $query->whereHas('transporterMaster', fn ($m) => $m->where('name', 'like', "%{$searchValue}%"));
                                break;
                            case 1: // Short Name
                                $query->where('transporters.short_name', 'like', "%{$searchValue}%");
                                break;
                            case 2: // PAN Number
                                $query->whereHas('transporterMaster', fn ($m) => $m->where('pan_number', 'like', "%{$searchValue}%"));
                                break;
                            case 3: // Location (City)
                                $query->whereHas('transporterMaster.city', fn ($cityQuery) => $cityQuery->where('name', 'like', "%{$searchValue}%"));
                                break;
                            case 4: // Contact Number (Mobile)
                                $query->where('transporters.mobile_number', 'like', "%{$searchValue}%");
                                break;
                        }
                    }
                }
            }

            // Handle ordering
            if ($request->has('order')) {
                $orderColumn = $request->order[0]['column'];
                $orderDir = $request->order[0]['dir'];

                switch ($orderColumn) {
                    case 0: // Name
                        $query->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                            ->orderBy('transporter_masters.name', $orderDir)
                            ->select('transporters.*');
                        break;
                    case 1: // Short Name
                        $query->orderBy('transporters.short_name', $orderDir);
                        break;
                    case 2: // PAN Number
                        $query->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                            ->orderBy('transporter_masters.pan_number', $orderDir)
                            ->select('transporters.*');
                        break;
                    case 3: // Location (City)
                        $query->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
                            ->leftJoin('cities', 'transporter_masters.city_id', '=', 'cities.id')
                            ->orderBy('cities.name', $orderDir)
                            ->select('transporters.*');
                        break;
                    case 4: // Contact Number (Mobile)
                        $query->orderBy('transporters.mobile_number', $orderDir);
                        break;
                    case 5: // Declaration Given
                        $query->orderBy('transporters.declaration_given', $orderDir);
                        break;
                    case 6: // Book Balance
                        $query->orderBy('transporters.advance_amount', $orderDir);
                        break;
                    default:
                        $query->orderBy('transporters.id', 'desc');
                }
            } else {
                $query->orderBy('transporters.id', 'desc');
            }

            $totalQuery = Transporter::where('is_owner', 1);
            if (Auth::user()->company_id) {
                $totalQuery->where('company_id', Auth::user()->company_id);
            }
            $totalRecords = $totalQuery->count();
            $filteredRecords = $query->count();

            $start = $request->start ?? 0;
            $length = $request->length ?? 10;

            $truckOwners = $query->skip($start)->take($length)->get();

            $data = [];
            foreach ($truckOwners as $index => $truckOwner) {
                $truckNumbers = $truckOwner->trucks->pluck('truck_number')->toArray();
                $truckList = implode(', ', $truckNumbers);
                $data[] = [
                    'name' => '<a href="#" class="truck-owner-name-link text-brand-primary fw-bold" data-bs-toggle="tooltip" title="Trucks: '.htmlspecialchars($truckList).'" data-id="'.$truckOwner->id.'" style="text-decoration: none;">'.($truckOwner->name ?? 'N/A').'</a>',
                    'short_name' => $truckOwner->short_name ?? 'N/A',
                    'pan_number' => $truckOwner->pan_number ?? 'N/A',
                    'location' => $truckOwner->city->name ?? 'N/A',
                    'contact_number' => $truckOwner->mobile_number ?? 'N/A',
                    'declaration_given' => $truckOwner->declaration_given ? 'Yes' : 'No',
                    'advance_amount' => '₹'.number_format($truckOwner->advance_amount, 2),
                    'actions' => $this->getActionButtons($truckOwner),
                ];
            }

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('TruckOwner DataTables Error: '.$e->getMessage());

            return response()->json([
                'draw' => intval($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading data.',
            ]);
        }
    }

    private function getActionButtons($truckOwner)
    {
        $is_admin = Auth::user()->canActAsAdmin();
        $buttons = '<div class="d-flex gap-1">';

        // Add Truck Button (modal trigger for truck owners)
        $buttons .= '<button type="button" class="btn btn-sm btn-view add-truck-btn" aria-label="Add Truck" title="Add Truck" data-bs-placement="top" data-bs-toggle="tooltip" data-transporter-id="'.$truckOwner->id.'" data-transporter-name="'.htmlspecialchars($truckOwner->name).'">';
        $buttons .= '<i class="bi bi-truck"></i></button>';

        if ($is_admin) {
            // Advance Payment Button (same as transporters)
            $buttons .= '<button type="button" class="btn btn-sm btn-view advance-payment-btn" aria-label="Advance Payment" title="Advance Payment" data-bs-placement="top" data-bs-toggle="tooltip" data-transporter-id="'.$truckOwner->id.'" data-transporter-name="'.htmlspecialchars($truckOwner->name).'">';
            $buttons .= '<i class="bi bi-currency-rupee"></i>';
            $buttons .= '</button>';

            // Edit Button (same as transporters)
            $editUrl = route('truck_owners.edit', $truckOwner->id);
            $buttons .= '<a href="'.$editUrl.'" class="btn btn-sm btn-edit" title="Edit Truck Owner" aria-label="Edit Truck Owner" data-bs-placement="top" data-bs-toggle="tooltip">';
            $buttons .= '<i class="bi bi-pencil"></i></a>';

            // Delete Button (AJAX-based)
            $buttons .= '<button type="button" class="btn btn-sm btn-danger delete-truck-owner-btn" aria-label="Delete Truck Owner" title="Delete Truck Owner" data-bs-placement="top" data-bs-toggle="tooltip" data-truck-owner-id="'.$truckOwner->id.'" data-truck-owner-name="'.htmlspecialchars($truckOwner->name).'">';
            $buttons .= '<i class="bi bi-trash"></i></button>';
        }
        $buttons .= '</div>';

        return $buttons;
    }

    public function create()
    {
        $countries = Country::all();
        $states = collect();
        $cities = collect();

        if (old('country_id')) {
            $states = State::where('country_id', old('country_id'))
                ->orderBy('name')
                ->get();
        }

        if (old('state_id')) {
            $cities = City::where('state_id', old('state_id'))
                ->orderBy('name')
                ->get();
        }

        return view('truck_owner.create', compact('countries', 'states', 'cities'));
    }

    public function store(StoreTruckOwnerRequest $request)
    {
        $validated = $request->validated();

        $shortName = $validated['short_name'] ?? null;
        if ($shortName) {
            $existing = Transporter::where('short_name', $shortName)->where('company_id', $validated['company_id'])->where('is_owner', 1)->exists();
            if ($existing) {
                $suffix = 1;
                do {
                    $shortName = ($validated['short_name'] ?? '').'-'.$suffix;
                    $existing = Transporter::where('short_name', $shortName)->where('company_id', $validated['company_id'])->where('is_owner', 1)->exists();
                    $suffix++;
                } while ($existing);
            }
            $validated['short_name'] = $shortName;
        }

        $validated['advance_amount'] = $validated['advance_amount'] ?? 0;
        $validated['is_owner'] = 1;

        if ($request->filled('transporter_master_id')) {
            $master = TransporterMaster::findOrFail($request->transporter_master_id);
            $validated['transporter_master_id'] = $master->id;
            $calculatedTds = $this->tdsCalculator->calculate((bool) $validated['declaration_given'], (string) $master->pan_number, (bool) $master->pan_active);
            $submittedTds = round((float) $validated['tds_percentage'], 2);
            $remark = abs($submittedTds - round($calculatedTds, 2)) > 0.01
                ? trim((string) ($validated['tds_adjustment_remark'] ?? ''))
                : null;
            Transporter::create([
                'transporter_master_id' => $validated['transporter_master_id'],
                'company_id' => $validated['company_id'],
                'is_owner' => 1,
                'contact_person_name' => $validated['contact_person_name'] ?? null,
                'mobile_number' => $validated['mobile_number'],
                'short_name' => $validated['short_name'] ?? null,
                'declaration_given' => $validated['declaration_given'],
                'total_trucks' => $validated['total_trucks'],
                'advance_amount' => $validated['advance_amount'],
                'tds_percentage' => $submittedTds,
                'tds_adjustment_remark' => $remark,
            ]);

            return redirect()->route('truck_owners.index')->with('success', 'Truck Owner created successfully!');
        }

        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);
        $validated['pan_number'] = strtoupper($validated['pan_number']);
        $validated['gst_number'] = ! empty($validated['gst_number']) ? strtoupper($validated['gst_number']) : null;
        $calculatedTds = $this->tdsCalculator->calculate((bool) $validated['declaration_given'], $validated['pan_number'], $validated['pan_active']);
        $submittedTds = round((float) $validated['tds_percentage'], 2);
        $tdsRemark = abs($submittedTds - round($calculatedTds, 2)) > 0.01
            ? trim((string) ($validated['tds_adjustment_remark'] ?? ''))
            : null;

        $masterData = [
            'name' => $validated['name'],
            'email' => $validated['pan_number'].'@truckowner.placeholder',
            'pan_number' => $validated['pan_number'],
            'gst_number' => $validated['gst_number'],
            'gst_type' => null,
            'pan_active' => $validated['pan_active'],
            'address' => $validated['address'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'state_id' => $validated['state_id'] ?? null,
            'country_id' => $validated['country_id'] ?? null,
        ];
        $master = TransporterMaster::create($masterData);

        Transporter::create([
            'transporter_master_id' => $master->id,
            'company_id' => $validated['company_id'],
            'is_owner' => 1,
            'contact_person_name' => $validated['contact_person_name'] ?? null,
            'mobile_number' => $validated['mobile_number'],
            'short_name' => $validated['short_name'] ?? null,
            'declaration_given' => $validated['declaration_given'],
            'total_trucks' => $validated['total_trucks'],
            'advance_amount' => $validated['advance_amount'],
            'tds_percentage' => $submittedTds,
            'tds_adjustment_remark' => $tdsRemark,
        ]);

        return redirect()->route('truck_owners.index')->with('success', 'Truck Owner created successfully!');
    }

    public function calculateTruckOwnerTdsPercentage(Request $request)
    {
        $panNumber = (string) $request->input('pan_number', '');
        $panActive = filter_var($request->input('pan_active'), FILTER_VALIDATE_BOOLEAN);
        $declarationGiven = $request->input('declaration_given') == '1';

        $tdsPercentage = $this->tdsCalculator->calculate($declarationGiven, $panNumber, $panActive);

        return response()->json([
            'tds_percentage' => number_format($tdsPercentage, 2),
        ]);
    }

    public function show(Transporter $truck_owner)
    {
        if (Auth::user()->company_id && $truck_owner->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this truck owner.');
        }
        $truck_owner->load(['company', 'transporterMaster.city.state.country']);

        // Ensure this is actually a truck owner (is_owner = 1)
        if (! $truck_owner->is_owner) {
            abort(404, 'Truck owner not found.');
        }

        // Get last 10 advance payments for this truck owner
        $advancePayments = AdvancePayment::where('transporter_id', $truck_owner->id)
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

        // Get registered trucks for this truck owner
        $trucks = Truck::where('transporter_id', $truck_owner->id)
            ->with('driver')
            ->get()
            ->map(function ($truck) {
                return [
                    'id' => $truck->id,
                    'truck_number' => $truck->truck_number,
                    'truck_type' => $truck->truck_type,
                    'driver_name' => optional($truck->driver)->name ?: 'Not assigned',
                    'driver_mobile' => optional($truck->driver)->mobile ?: 'Not provided',
                ];
            });

        return response()->json([
            'id' => $truck_owner->id,
            'name' => $truck_owner->name,
            'short_name' => $truck_owner->short_name,
            'contact_person_name' => $truck_owner->contact_person_name,
            'gst_number' => $truck_owner->gst_number ?: 'Not provided',
            'pan_number' => $truck_owner->pan_number,
            'pan_active' => $truck_owner->pan_active ? 'Yes' : 'No',
            'mobile_number' => $truck_owner->mobile_number,
            'address' => $truck_owner->address ?: 'Not provided',
            'country' => optional($truck_owner->transporterMaster?->city?->state?->country)->name ?: 'Not specified',
            'state' => optional($truck_owner->transporterMaster?->city?->state)->name ?: 'Not specified',
            'city' => optional($truck_owner->transporterMaster?->city)->name ?: 'Not specified',
            'company' => optional($truck_owner->company)->name ?: 'Not specified',
            'is_owner' => 'Truck Owner',
            'declaration_given' => $truck_owner->declaration_given ? 'Yes' : 'No',
            'tds_percentage' => $truck_owner->tds_percentage ?: '0',
            'tds_adjustment_remark' => $truck_owner->tds_adjustment_remark ?: '',
            'total_trucks' => $truck_owner->total_trucks,
            'advance_amount' => '₹'.number_format($truck_owner->advance_amount, 2),
            'created_at' => $truck_owner->created_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'updated_at' => $truck_owner->updated_at->setTimezone('Asia/Kolkata')->format('d-m-Y H:i'),
            'advance_payments' => $advancePayments,
            'trucks' => $trucks,
        ]);
    }

    public function edit(Transporter $truck_owner)
    {
        if (Auth::user()->company_id && $truck_owner->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this truck owner.');
        }
        // Load the trucks relationship with driver information and transporterMaster for name/address accessors
        $truck_owner->load(['trucks.driver', 'transporterMaster.city.state.country']);

        $trucks = Truck::where('transporter_id', $truck_owner->id)->get();
        $countries = Country::all();
        $truckTypes = TruckType::orderBy('name')->get();

        return view('truck_owner.edit', compact('truck_owner', 'trucks', 'countries', 'truckTypes'));
    }

    public function update(UpdateTruckOwnerRequest $request, Transporter $truck_owner)
    {
        if (Auth::user()->company_id && $truck_owner->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this truck owner.');
        }
        $validated = $request->validated();

        $master = $truck_owner->transporterMaster;
        $previousTds = round((float) $truck_owner->tds_percentage, 2);
        $submittedTds = round((float) $validated['tds_percentage'], 2);
        $calculatedTds = $this->tdsCalculator->calculate(
            (bool) $validated['declaration_given'],
            (string) $master->pan_number,
            (bool) $master->pan_active
        );
        $tdsRemark = abs($submittedTds - round($calculatedTds, 2)) > 0.01
            ? trim((string) ($validated['tds_adjustment_remark'] ?? ''))
            : null;

        $updateData = [
            'company_id' => $validated['company_id'],
            'contact_person_name' => $validated['contact_person_name'] ?? null,
            'mobile_number' => $validated['mobile_number'],
            'declaration_given' => $validated['declaration_given'],
            'total_trucks' => $validated['total_trucks'],
            'advance_amount' => $validated['advance_amount'] ?? 0,
            'tds_percentage' => $submittedTds,
            'tds_adjustment_remark' => $tdsRemark,
        ];

        if (auth()->user()->userRole->name === 'super_admin') {
            $updateData['short_name'] = $validated['short_name'] ?? null;
            $updateData['beneficiary_id'] = $validated['beneficiary_id'] ?? null;
        }

        $truck_owner->update($updateData);

        if (abs($previousTds - $submittedTds) > 0.01) {
            $this->freightBookingTdsSync->syncForTransporter($truck_owner, $submittedTds);
        }

        return redirect()->route('truck_owners.index')->with('success', 'Truck Owner updated successfully!');
    }

    public function destroy(Transporter $truck_owner)
    {
        if (Auth::user()->company_id && $truck_owner->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this truck owner.');
        }
        // Soft delete the truck owner
        $truck_owner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Truck Owner deleted successfully!',
        ]);
    }
}
