<?php
namespace App\Http\Controllers;

use App\Models\City;
use App\Models\State;
use App\Models\Country;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $countries = Country::all();
        $states = State::with('country')->get();
        
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getCitiesData($request);
        }
        
        return view('master.cities', compact('states', 'countries'));
    }
    
    private function getCitiesData(Request $request)
    {
        $query = City::with('state', 'state.country');
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhereHas('state', function($stateQuery) use ($searchValue) {
                      $stateQuery->where('name', 'like', "%{$searchValue}%")
                                 ->orWhereHas('country', function($countryQuery) use ($searchValue) {
                                     $countryQuery->where('name', 'like', "%{$searchValue}%");
                                 });
                  });
            });
        }
        
        // Handle column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1: // Name column
                            $query->where('name', 'like', "%{$searchValue}%");
                            break;
                        case 2: // State column
                            $query->whereHas('state', function($stateQuery) use ($searchValue) {
                                $stateQuery->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 3: // Country column
                            $query->whereHas('state.country', function($countryQuery) use ($searchValue) {
                                $countryQuery->where('name', 'like', "%{$searchValue}%");
                            });
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
                case 0: // ID
                    $query->orderBy('id', $orderDirection);
                    break;
                case 1: // Name
                    $query->orderBy('name', $orderDirection);
                    break;
                case 2: // State
                    $query->join('states', 'cities.state_id', '=', 'states.id')
                          ->orderBy('states.name', $orderDirection)
                          ->select('cities.*');
                    break;
                case 3: // Country
                    $query->join('states', 'cities.state_id', '=', 'states.id')
                          ->join('countries', 'states.country_id', '=', 'countries.id')
                          ->orderBy('countries.name', $orderDirection)
                          ->select('cities.*');
                    break;
                default:
                    $query->orderBy('id', 'asc');
            }
        } else {
            $query->orderBy('id', 'asc');
        }
        
        // Get total count before pagination
        $totalRecords = City::count();
        $filteredRecords = $query->count();
        
        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $cities = $query->skip($start)->take($length)->get();
        
        // Format data for DataTables
        $data = $cities->map(function($city) {
            return [
                'id' => $city->id,
                'name' => $city->name,
                'state' => $city->state->name,
                'country' => $city->state->country->name,
                'actions' => $this->getActionButtons($city)
            ];
        });
        
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    private function getActionButtons($city)
    {
        if (auth()->user()->userRole?->name !== 'master_admin') {
            return '';
        }

        $editUrl = route('master.cities.edit', $city->id);
        $deleteUrl = route('master.cities.destroy', $city->id);
        
        return '
            <div class="app-index-table-actions">
                <a href="' . $editUrl . '" class="btn btn-sm btn-icon btn-action-edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this city?\')">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="btn btn-sm btn-icon btn-action-delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        ';
    }

    public function create()
    {
        $countries = Country::all();
        return view('master.cities-form', compact('countries'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    \Illuminate\Validation\Rule::unique('cities')->where(function ($query) use ($request) {
                        return $query->where('state_id', $request->state_id);
                    })
                ],
                'state_id' => 'required|exists:states,id',
            ]);

            // Get country_id from the selected state
            $state = \App\Models\State::find($request->state_id);
            $validated['country_id'] = $state->country_id;

            $city = City::create($validated);

            // Check if it's an AJAX request
            if ($request->ajax() || $request->has('from_modal')) {
                return response()->json([
                    'success' => true,
                    'message' => 'City created successfully',
                    'city' => $city
                ]);
            }

            return redirect()->route('master.cities.index')->with('success', 'City created successfully');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors
            if ($request->ajax() || $request->has('from_modal')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return back()->withErrors($e->errors())->withInput();
            
        } catch (\Exception $e) {
            // Handle other errors (like database constraints)
            $errorMessage = 'Failed to create city';
            
            // Check for duplicate entry error
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $errorMessage = 'A city with this name already exists in the selected state';
            }
            
            if ($request->ajax() || $request->has('from_modal')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            return back()->with('error', $errorMessage)->withInput();
        }
    }

    public function edit(City $city)
    {
        $states = State::with('country')->get();
        $countries = Country::all();
        return view('master.cities-form', compact('city', 'states', 'countries'));
    }

    public function update(Request $request, City $city)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('cities')->where(function ($query) use ($request) {
                    return $query->where('state_id', $request->state_id);
                })->ignore($city->id)
            ],
            'state_id' => 'required|exists:states,id',
        ]);

        // Get country_id from the selected state
        $state = \App\Models\State::find($request->state_id);
        $validated['country_id'] = $state->country_id;

        $city->update($validated);
        return redirect()->route('master.cities.index')->with('success', 'City updated successfully');
    }

    public function show(City $city)
    {
        return response()->json($city);
    }

    public function destroy(City $city)
    {
        $city->delete();
        return redirect()->route('master.cities.index')->with('success', 'City deleted successfully');
    }
}