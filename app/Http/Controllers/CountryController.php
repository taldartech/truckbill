<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getCountriesData($request);
        }
        
        return view('master.countries');
    }
    
    private function getCountriesData(Request $request)
    {
        $query = Country::query();
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('code', 'like', "%{$searchValue}%");
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
                        case 2: // Code column
                            $query->where('code', 'like', "%{$searchValue}%");
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
                case 2: // Code
                    $query->orderBy('code', $orderDirection);
                    break;
                default:
                    $query->orderBy('id', 'asc');
            }
        } else {
            $query->orderBy('id', 'asc');
        }
        
        // Get total count before pagination
        $totalRecords = Country::count();
        $filteredRecords = $query->count();
        
        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $countries = $query->skip($start)->take($length)->get();
        
        // Format data for DataTables
        $data = $countries->map(function($country) {
            return [
                'id' => $country->id,
                'name' => $country->name,
                'code' => $country->code,
                'actions' => $this->getActionButtons($country)
            ];
        });
        
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    private function getActionButtons($country)
    {
        if (auth()->user()->userRole?->name !== 'master_admin') {
            return '';
        }

        $editUrl = route('master.countries.edit', $country->id);
        $deleteUrl = route('master.countries.destroy', $country->id);
        
        return '
            <div class="app-index-table-actions">
                <a href="' . $editUrl . '" class="btn btn-sm btn-icon btn-action-edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this country?\')">
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
        return view('master.countries-form');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:countries,name',
                'code' => 'required|string|max:10|unique:countries,code',
            ]);

            $country = Country::create($validated);

            // Check if it's an AJAX request
            if ($request->ajax() || $request->has('from_modal')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Country created successfully',
                    'country' => $country
                ]);
            }

            return redirect()->route('master.countries.index')->with('success', 'Country created successfully');
            
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
            $errorMessage = 'Failed to create country';
            
            // Check for duplicate entry error
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                if (str_contains($e->getMessage(), 'countries_name_unique')) {
                    $errorMessage = 'A country with this name already exists';
                } elseif (str_contains($e->getMessage(), 'countries_code_unique')) {
                    $errorMessage = 'A country with this code already exists';
                } else {
                    $errorMessage = 'This country already exists';
                }
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

    public function edit(Country $country)
    {
        return view('master.countries-form', compact('country'));
    }

    public function update(Request $request, Country $country)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
        ]);

        $country->update($validated);
        return redirect()->route('master.countries.index')->with('success', 'Country updated successfully');
    }

    public function destroy(Country $country)
    {
        $country->delete();
        return redirect()->route('master.countries.index')->with('success', 'Country deleted successfully');
    }
}