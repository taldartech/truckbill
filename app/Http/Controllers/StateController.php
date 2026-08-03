<?php
namespace App\Http\Controllers;

use App\Models\State;
use App\Models\Country;
use Illuminate\Http\Request;

class StateController extends Controller
{
    public function index(Request $request)
    {
        $countries = Country::all();
        
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getStatesData($request);
        }
        
        return view('master.states', compact('countries'));
    }
    
    private function getStatesData(Request $request)
    {
        $query = State::with('country');
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
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
                        case 1: // Name column
                            $query->where('name', 'like', "%{$searchValue}%");
                            break;
                        case 2: // Country column
                            $query->whereHas('country', function($countryQuery) use ($searchValue) {
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
                case 2: // Country
                    $query->join('countries', 'states.country_id', '=', 'countries.id')
                          ->orderBy('countries.name', $orderDirection)
                          ->select('states.*');
                    break;
                default:
                    $query->orderBy('id', 'asc');
            }
        } else {
            $query->orderBy('id', 'asc');
        }
        
        // Get total count before pagination
        $totalRecords = State::count();
        $filteredRecords = $query->count();
        
        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $states = $query->skip($start)->take($length)->get();
        
        // Format data for DataTables
        $data = $states->map(function($state) {
            return [
                'id' => $state->id,
                'name' => $state->name,
                'country' => $state->country->name,
                'actions' => $this->getActionButtons($state)
            ];
        });
        
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    private function getActionButtons($state)
    {
        if (auth()->user()->userRole?->name !== 'master_admin') {
            return '';
        }

        $editUrl = route('master.states.edit', $state->id);
        $deleteUrl = route('master.states.destroy', $state->id);
        
        return '
            <div class="d-flex gap-1">
                <a href="' . $editUrl . '" class="btn btn-sm btn-edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this state?\')">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        ';
    }

    public function create()
    {
        $countries = Country::all();
        return view('master.states-form', compact('countries'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    \Illuminate\Validation\Rule::unique('states')->where(function ($query) use ($request) {
                        return $query->where('country_id', $request->country_id);
                    })
                ],
                'code' => 'nullable|string|max:10',
                'country_id' => 'required|exists:countries,id',
            ]);

            $state = State::create($validated);

            // Check if it's an AJAX request
            if ($request->ajax() || $request->has('from_modal')) {
                return response()->json([
                    'success' => true,
                    'message' => 'State created successfully',
                    'state' => $state
                ]);
            }

            return redirect()->route('master.states.index')->with('success', 'State created successfully');
            
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
            $errorMessage = 'Failed to create state';
            
            // Check for duplicate entry error
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $errorMessage = 'A state with this name already exists in the selected country';
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

    public function edit(State $state)
    {
        $countries = Country::all();
        return view('master.states-form', compact('state', 'countries'));
    }

    public function update(Request $request, State $state)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('states')->where(function ($query) use ($request) {
                    return $query->where('country_id', $request->country_id);
                })->ignore($state->id)
            ],
            'code' => 'nullable|string|max:10',
            'country_id' => 'required|exists:countries,id',
        ]);

        $state->update($validated);
        return redirect()->route('master.states.index')->with('success', 'State updated successfully');
    }

    public function show(State $state)
    {
        return response()->json($state);
    }

    public function destroy(State $state)
    {
        $state->delete();
        return redirect()->route('master.states.index')->with('success', 'State deleted successfully');
    }
}
