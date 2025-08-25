<?php

namespace App\Http\Controllers;

use App\Models\Consignee;
use Illuminate\Http\Request;

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
    
    private function getConsigneesData(Request $request)
    {
        $query = Consignee::where('company_id', auth()->user()->company_id)
            ->with(['city', 'state', 'country']);
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('address', 'like', "%{$searchValue}%")
                  ->orWhere('contact_number', 'like', "%{$searchValue}%")
                  ->orWhere('gst_number', 'like', "%{$searchValue}%")
                  ->orWhere('pan_number', 'like', "%{$searchValue}%")
                  ->orWhereHas('city', function($cityQuery) use ($searchValue) {
                      $cityQuery->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('state', function($stateQuery) use ($searchValue) {
                      $stateQuery->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('country', function($countryQuery) use ($searchValue) {
                      $countryQuery->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }
        
        // Handle column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1: // Name
                            $query->where('name', 'like', "%{$searchValue}%");
                            break;
                        case 2: // City
                            $query->whereHas('city', function($cityQuery) use ($searchValue) {
                                $cityQuery->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 3: // State
                            $query->whereHas('state', function($stateQuery) use ($searchValue) {
                                $stateQuery->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 4: // Country
                            $query->whereHas('country', function($countryQuery) use ($searchValue) {
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
        $data = $consignees->map(function($consignee, $index) use ($start) {
            return [
                'DT_RowIndex' => $start + $index + 1,
                'id' => $consignee->id,
                'name' => '<a href="javascript:void(0)" class="text-primary fw-bold consignee-name-link" data-id="' . $consignee->id . '" style="cursor: pointer; text-decoration: none;">' . ($consignee->name ?: '-') . '</a>',
                'city' => $consignee->city?->name ?? '-',
                'state' => $consignee->state?->name ?? '-',
                'country' => $consignee->country?->name ?? '-',
                'contact_number' => $consignee->contact_number ?: '-',
                'actions' => $this->getActionButtons($consignee)
            ];
        });
        
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    private function getActionButtons($consignee)
    {
        $buttons = '<div class="d-flex gap-1">';
        
        // Edit button
        $editUrl = route('consignees.edit', $consignee->id);
        $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info" title="Edit Consignee">';
        $buttons .= '<i class="bi bi-pencil"></i></a>';
        
        // Delete button
        $deleteUrl = route('consignees.destroy', $consignee->id);
        $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this consignee?\')">';
        $buttons .= csrf_field() . method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete Consignee">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';
        
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
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'contact_number' => [
                'nullable',
                'digits:10',
            ],
            'gst_number' => 'nullable|string|max:255',
            'pan_number' => 'nullable|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
        ]);

        if (!empty($validated['pan_number'])) {
            $validated['pan_number'] = strtoupper($validated['pan_number']);
        }
        if (!empty($validated['gst_number'])) {
            $validated['gst_number'] = strtoupper($validated['gst_number']);
        }

        $validated['company_id'] = auth()->user()->company_id;
        
        $consignee = Consignee::create($validated);
        return redirect()->route('consignees.index')
            ->with('success', 'Consignee created successfully!');
    }



    public function edit(Consignee $consignee)
    {
        $countries = \App\Models\Country::all();
        $states = $consignee->country_id ? \App\Models\State::where('country_id', $consignee->country_id)->get() : collect();
        $cities = $consignee->state_id ? \App\Models\City::where('state_id', $consignee->state_id)->get() : collect();
        return view('consignees.edit', compact('consignee', 'countries', 'states', 'cities'));
    }

    public function update(Request $request, Consignee $consignee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'contact_number' => [
                'nullable',
                'digits:10',
            ],
            'gst_number' => 'nullable|string|max:255',
            'pan_number' => 'nullable|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
        ]);

        if (!empty($validated['pan_number'])) {
            $validated['pan_number'] = strtoupper($validated['pan_number']);
        }
        if (!empty($validated['gst_number'])) {
            $validated['gst_number'] = strtoupper($validated['gst_number']);
        }

        $consignee->update($validated);
        return redirect()->route('consignees.index')
            ->with('success', 'Consignee updated successfully!');
    }

    public function destroy(Consignee $consignee)
    {
        $consignee->delete();
        return redirect()->route('consignees.index')
            ->with('success', 'Consignee deleted successfully!');
    }
    
    public function getDetails($id)
    {
        $consignee = Consignee::with(['city', 'state', 'country', 'company'])->find($id);

        if (!$consignee) {
            return response()->json(['error' => 'Consignee not found'], 404);
        }

        return response()->json([
            'id' => $consignee->id,
            'name' => $consignee->name ?: 'Not provided',
            'address' => $consignee->address ?: 'Not provided',
            'contact_number' => $consignee->contact_number ?: 'Not provided',
            'email' => $consignee->email ?: 'Not provided',
            'gst_number' => $consignee->gst_number ?: 'Not provided',
            'pan_number' => $consignee->pan_number ?: 'Not provided',
            'city' => $consignee->city?->name ?: 'Not provided',
            'state' => $consignee->state?->name ?: 'Not provided',
            'country' => $consignee->country?->name ?: 'Not provided',
            'company' => $consignee->company?->name ?: 'Not provided',
            'created_at' => \Carbon\Carbon::parse($consignee->created_at)->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') ?: 'Not available',
            'updated_at' => \Carbon\Carbon::parse($consignee->updated_at)->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') ?: 'Not available'
        ]);
    }

    /**
     * Get consignee data for API
     */
    public function getConsigneeApi($id)
    {
        try {
            $query = Consignee::with(['city', 'state', 'country']);
            
            // Apply company filter for non-super admin users
            if (auth()->user()->userRole->name !== 'super_admin') {
                $query->where('company_id', auth()->user()->company_id);
            }
            
            $consignee = $query->findOrFail($id);
            
            return response()->json([
                'id' => $consignee->id,
                'name' => $consignee->name,
                'contact_number' => $consignee->contact_number,
                'address' => $consignee->address,
                'city' => $consignee->city?->name,
                'state' => $consignee->state?->name,
                'country' => $consignee->country?->name,
                'gst_number' => $consignee->gst_number,
                'pan_number' => $consignee->pan_number,
                'email' => $consignee->email
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching consignee: ' . $e->getMessage());
            return response()->json(['error' => 'Consignee not found'], 404);
        }
    }
}