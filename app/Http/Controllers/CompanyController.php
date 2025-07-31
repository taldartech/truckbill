<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getCompaniesData($request);
        }
        
        return view('company.index');
    }
    
    private function getCompaniesData(Request $request)
    {
        $query = \App\Models\Company::query();
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('gst_number', 'like', "%{$searchValue}%")
                  ->orWhere('pan_number', 'like', "%{$searchValue}%")
                  ->orWhere('gst_scheme', 'like', "%{$searchValue}%")
                  ->orWhere('gst_type', 'like', "%{$searchValue}%")
                  ->orWhere('address', 'like', "%{$searchValue}%")
                  ->orWhere('contact_person', 'like', "%{$searchValue}%")
                  ->orWhere('contact_number', 'like', "%{$searchValue}%");
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
                        case 2: // GST Number
                            $query->where('gst_number', 'like', "%{$searchValue}%");
                            break;
                        case 3: // PAN Number
                            $query->where('pan_number', 'like', "%{$searchValue}%");
                            break;
                        case 4: // GST Scheme
                            $query->where('gst_scheme', 'like', "%{$searchValue}%");
                            break;
                        case 5: // GST Type
                            $query->where('gst_type', 'like', "%{$searchValue}%");
                            break;
                        case 6: // Address
                            $query->where('address', 'like', "%{$searchValue}%");
                            break;
                        case 7: // Contact Person
                            $query->where('contact_person', 'like', "%{$searchValue}%");
                            break;
                        case 8: // Contact Number
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
                case 0: // ID
                    $query->orderBy('id', $orderDirection);
                    break;
                case 1: // Name
                    $query->orderBy('name', $orderDirection);
                    break;
                case 2: // GST Number
                    $query->orderBy('gst_number', $orderDirection);
                    break;
                case 3: // PAN Number
                    $query->orderBy('pan_number', $orderDirection);
                    break;
                case 4: // GST Scheme
                    $query->orderBy('gst_scheme', $orderDirection);
                    break;
                case 5: // GST Type
                    $query->orderBy('gst_type', $orderDirection);
                    break;
                case 6: // Address
                    $query->orderBy('address', $orderDirection);
                    break;
                case 7: // Contact Person
                    $query->orderBy('contact_person', $orderDirection);
                    break;
                case 8: // Contact Number
                    $query->orderBy('contact_number', $orderDirection);
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Get total count before pagination
        $totalRecords = \App\Models\Company::count();
        $filteredRecords = $query->count();
        
        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $companies = $query->skip($start)->take($length)->get();
        
        // Format data for DataTables
        $data = $companies->map(function($company, $index) use ($start) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'gst_number' => $company->gst_number ?: '-',
                'pan_number' => $company->pan_number ?: '-',
                'gst_scheme' => $company->gst_scheme ? ucfirst($company->gst_scheme) : '-',
                'gst_type' => $company->gst_type ? ucfirst($company->gst_type) : '-',
                'address' => $company->address ?: '-',
                'contact_person' => $company->contact_person ?: '-',
                'contact_number' => $company->contact_number ?: '-',
                'actions' => $this->getActionButtons($company)
            ];
        });
        
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    private function getActionButtons($company)
    {
        $buttons = '<div class="d-flex gap-1">';
        
        // Edit button
        $editUrl = route('company.edit', $company->id);
        $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info" title="Edit Company">';
        $buttons .= '<i class="bi bi-pencil"></i></a>';
        
        // Delete button
        $deleteUrl = route('company.destroy', $company->id);
        $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this company?\')">';
        $buttons .= csrf_field() . method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete Company">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';
        
        $buttons .= '</div>';
        return $buttons;
    }

    public function edit($id)
    {
        $company = \App\Models\Company::findOrFail($id);
        return view('company.edit', compact('company'));
    }

    public function update(Request $request, $id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gst_number' => [
    'nullable',
    'string',
    'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'gst_scheme' => 'nullable|in:reverse charge,forward charge',
            'gst_type' => 'nullable|in:composition,regular',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => [
    'nullable',
    'digits:10',
],
        ]);
        $company->update($validated);
        return redirect()->route('company.index')->with('success', 'Company updated successfully!');
    }

    public function destroy($id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $company->delete();
        return redirect()->route('company.index')->with('success', 'Company deleted successfully!');
    }
    public function create()
    {
        return view('company.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gst_number' => [
                'nullable',
                'string',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'gst_scheme' => 'nullable|in:reverse charge,forward charge',
            'gst_type' => 'nullable|in:composition,regular',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => [
    'nullable',
    'digits:10',
],
        ]);
        
        // Set default GST type to regular if not provided
        if (!$request->filled('gst_type')) {
            $validated['gst_type'] = 'regular';
        }
        
        \App\Models\Company::create($validated);
        return redirect()->route('company.create')->with('success', 'Company registered successfully!');
    }
}
