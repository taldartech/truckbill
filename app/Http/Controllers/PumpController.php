<?php



namespace App\Http\Controllers;

use App\Models\Pump;
use Illuminate\Http\Request;

class PumpController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getPumpsData($request);
        }

        return view('master.pump'); // You can create a Blade file for listing
    }

    private function getPumpsData(Request $request)
    {
        $query = Pump::query();

        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('pump_name', 'like', "%{$searchValue}%")
                  ->orWhere('contact_name', 'like', "%{$searchValue}%")
                  ->orWhere('contact_number', 'like', "%{$searchValue}%");
            });
        }

        // Handle column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1: // Pump Name
                            $query->where('pump_name', 'like', "%{$searchValue}%");
                            break;
                        case 2: // Contact Name
                            $query->where('contact_name', 'like', "%{$searchValue}%");
                            break;
                        case 3: // Contact Number
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
                case 0: $query->orderBy('id', $orderDirection); break;
                case 1: $query->orderBy('pump_name', $orderDirection); break;
                case 2: $query->orderBy('contact_name', $orderDirection); break;
                case 3: $query->orderBy('contact_number', $orderDirection); break;
                default: $query->orderBy('id', 'asc');
            }
        } else {
            $query->orderBy('id', 'asc');
        }

        // Count records
        $totalRecords = Pump::count();
        $filteredRecords = $query->count();

        // Pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $pumps = $query->skip($start)->take($length)->get();

        // Format data
        $data = $pumps->map(function($pump) {
            return [
                'id' => $pump->id,
                'pump_name' => $pump->pump_name,
                'contact_name' => $pump->contact_name,
                'contact_number' => $pump->contact_number,
                'actions' => $this->getActionButtons($pump),
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    private function getActionButtons($pump)
    {
        $editUrl = route('pumps.edit', $pump->id);
        $deleteUrl = route('pumps.destroy', $pump->id);

        return '
            <div class="d-flex gap-1">
                <a href="' . $editUrl . '" class="btn btn-sm btn-info">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this pump?\')">
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
        return view('master.pump-form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pump_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20|unique:pumps',
        ]);

        Pump::create($validated);

        return redirect()->route('master.pump.index')->with('success', 'Pump created successfully');
    }

    public function edit(Pump $pump)
    {
        return view('master.pump-form', compact('pump'));
    }

    public function update(Request $request, Pump $pump)
    {
        $validated = $request->validate([
            'pump_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20|unique:pumps,contact_number,' . $pump->id,
        ]);

        $pump->update($validated);

        return redirect()->route('master.pump.index')->with('success', 'Pump updated successfully');
    }

    public function destroy(Pump $pump)
    {
        $pump->delete();
        return redirect()->route('master.pump.index')->with('success', 'Pump deleted successfully');
    }

    public function show(Pump $pump)
    {
        return response()->json($pump);
    }
}
