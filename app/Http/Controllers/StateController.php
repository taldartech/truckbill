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
        $editUrl = route('master.states.edit', $state->id);
        $deleteUrl = route('master.states.destroy', $state->id);
        
        return '
            <div class="d-flex gap-1">
                <a href="' . $editUrl . '" class="btn btn-sm btn-info">
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'country_id' => 'required|exists:countries,id',
        ]);

        State::create($validated);

        if ($request->has('from_modal')) {
            return redirect()->back()->with('success', 'State created successfully from modal.');
        }
        return redirect()->route('master.states.index')->with('success', 'State created successfully');
    }

    public function edit(State $state)
    {
        $countries = Country::all();
        return view('master.states-form', compact('state', 'countries'));
    }

    public function update(Request $request, State $state)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
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
