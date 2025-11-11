<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Validation\Rule;


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
        
        //if user is master admin than show all companies
        if (auth()->user()->userRole->name === 'master_admin') {
            $query = \App\Models\Company::query();
        } else {
            $query = \App\Models\Company::query()->where('user_id', auth()->user()->id);
        }
        
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
        $totalRecords = \App\Models\Company::where('user_id', auth()->user()->id)->count();
        $filteredRecords = $query->count();

        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $companies = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = $companies->map(function($company, $index) use ($start) {
            return [
                'id' => $index + $start + 1,
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
        //this edit button should only visible when their in no invoice created  for that financial year
        //take invoice value from 1 st april of that year to 21 march of next year
        //take latest year
       // Determine start and end of financial year
$startOfYear = Carbon::now()->month >= 4
? Carbon::create(Carbon::now()->year, 4, 1)->startOfDay()
: Carbon::create(Carbon::now()->year - 1, 4, 1)->startOfDay();

$endOfYear = $startOfYear->copy()->addYear()->subDay()->endOfDay();

// Count invoices for the company in financial year
    //take latest year
       // Determine start and end of financial year
       $invoiceCount = \App\Models\Invoice::where('company_id', $company->id)
       ->whereBetween('created_at', [$startOfYear, $endOfYear])
       ->count();
   if (auth()->user()->userRole->name !== 'master_admin') {
       
   
   if ($invoiceCount == 0) {
       $editUrl = route('company.edit', $company->id);
       $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info" title="Edit Company">
                       <i class="bi bi-pencil"></i></a>';
   } else {
       $buttons .= '<a href="javascript:void(0)" class="btn btn-sm btn-info disabled" title="Edit Disabled">
                       <i class="bi bi-pencil"></i></a>';
   }

       

        // Delete button
        $deleteUrl = route('company.destroy', $company->id);
        $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this company?\')">';
        $buttons .= csrf_field() . method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete Company">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';

        $buttons .= '</div>';
        return $buttons;
    }
    else{
        if ($company->is_blocked) {
            $unblockUrl = route('company.unblock', $company->id);
            $buttons .= '<a href="' . $unblockUrl . '" class="btn btn-sm btn-danger" title="Unblock User"
                          onclick="return confirm(\'Are you sure you want to unblock this company?\');">
                          <i class="bi bi-unlock"></i></a>';
        } else {
            $blockUrl = route('company.block', $company->id);
            $buttons .= '<a href="' . $blockUrl . '" class="btn btn-sm btn-info" title="Block User"
                          onclick="return confirm(\'Are you sure you want to block this company?\');">
                          <i class="bi bi-lock"></i></a>';
        }
        $buttons .= '</div>';
        return $buttons;
    }
}
public function block($id)
{
    $company = \App\Models\Company::findOrFail($id);
    $company->is_blocked = true;
    $company->save();
    return redirect()->back()->with('success', 'Company blocked successfully');
}
public function unblock($id)
{
    $company = \App\Models\Company::findOrFail($id);
    $company->is_blocked = false;
    $company->save();
    return redirect()->back()->with('success', 'Company unblocked successfully');
}



    public function edit($id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $countries = \App\Models\Country::all();
        $states = \App\Models\State::where('country_id', $company->country_id)->get();
        $cities = \App\Models\City::where('state_id', $company->state_id)->get();
        $companySlabs = explode(',', env('COMPANY_SLABS'));
        return view('company.edit', compact('company', 'countries', 'states', 'cities','companySlabs'));
    }

    public function update(Request $request, $id)
    {
        $company = \App\Models\Company::findOrFail($id);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/[A-Za-z]/', // ✅ ensures at least one alphabet is present
            ],
            'gst_number' => [
    'nullable',
    'string',
    'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
    function ($attribute, $value, $fail)use ($id) {
        if ($value && \App\Models\Company::where('gst_number', $value)  ->where('id', '!=', $id)->exists()) {
            $fail('The GST number has already been taken.');
        }
    },
],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                function ($attribute, $value, $fail)use ($id) {
                    if ($value && \App\Models\Company::where('pan_number', $value)->where('id', '!=', $id)->exists()) {
                        $fail('The PAN number has already been taken.');
                    }
                },  
            ],
            'company_type' => 'nullable|in:Broker,Agency,Both',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:250',
            'gst_scheme' => 'nullable|in:reverse charge,forward charge',
            'gst_type' => 'nullable|in:composition,regular',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => [
    'nullable',
    'digits:10',
    function ($attribute, $value, $fail)use ($id) {
        if ($value && \App\Models\Company::where('contact_number', $value)->where('id', '!=', $id)->exists()) {
            $fail('The contact number has already been taken.');
        }
    },
],
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            // 'pin_code' => 'nullable|string|max:10',
            'pin_code' => 'nullable|digits_between:1,10',
            'company_slab' => [
                'required',
                Rule::in(explode(',', env('COMPANY_SLABS'))),
            ],

        ], [
            'name.regex' => 'The name must contain at least one alphabetic character.', 
            'pin_code.max' => 'The pin code must be a maximum of 10 characters.',
        ]);
        // dd($validated);
        if ($request->hasFile('company_logo')) {
            // Store the file in the 'public/company_logos' directory
            $path = $request->file('company_logo')->store('company_logos', 'public');
    
           $validated['company_logo'] = $path;
        }
        $validated['user_id'] = auth()->user()->id;
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
        
        $companySlabs = explode(',', env('COMPANY_SLABS'));
    
        return view('company.create', compact('companySlabs'));
    }

    public function store(Request $request)
    {
        
      
        $validated = $request->validate([
            
            
          'name' => [
            'required',
            'string',
            'max:255',
            'regex:/[A-Za-z]/', // ✅ ensures at least one alphabet is present
        ],
        
   
            'gst_number' => [
                'nullable',
                'string',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
                function ($attribute, $value, $fail) {
                    if ($value && \App\Models\Company::where('gst_number', $value)->exists()) {
                        $fail('The GST number has already been taken.');
                    }
                },
            ],
    
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
                function ($attribute, $value, $fail) {
                    if ($value && \App\Models\Company::where('pan_number', $value)->exists()) {
                        $fail('The PAN number has already been taken.');
                    }
                },
            ],
            'gst_scheme' => 'nullable|in:reverse charge,forward charge',
            'gst_type' => 'nullable|in:composition,regular',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_number' => [
                'nullable',
                'digits:10',
                function ($attribute, $value, $fail) {
                    if ($value && \App\Models\Company::where('contact_number', $value)->exists()) {
                        $fail('The contact number has already been taken.');
                    }
                },
            ],
            'company_type' => 'nullable|in:Broker,Agency,Both',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:250',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            // 'pin_code' => 'nullable|string|max:10',
            'pin_code' => 'nullable|digits_between:1,10',
           'company_slab' => [
    'required',
    Rule::in(explode(',', env('COMPANY_SLABS'))),
],

            
        ], [
            'name.regex' => 'The name must contain at least one alphabetic character.', 
            'pin_code.max' => 'The pin code must be a maximum of 10 characters.',
        ]);

        // Set default GST type to regular if not provided
        if ($request->hasFile('company_logo')) {
            // Store the file in the 'public/company_logos' directory
            $path = $request->file('company_logo')->store('company_logos', 'public');
            $validated['company_logo'] = $path;
    
           
        }
        $validated['user_id'] = auth()->user()->id;
        if (!$request->filled('gst_type')) {
            $validated['gst_type'] = 'regular';
        }

        \App\Models\Company::create($validated);
        return redirect()->route('company.index')->with('success', 'Company registered successfully!');
    }
}
