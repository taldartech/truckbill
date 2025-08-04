<?php

namespace App\Http\Controllers;

use App\Models\TruckOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TruckOwnerController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getTruckOwnersData($request);
        }
        
        $companies = \App\Models\Company::all();
        return view('truck_owner.index', compact('companies'));
    }
    
    private function getTruckOwnersData(Request $request)
    {
        $query = \App\Models\Transporter::with(['company', 'bankAccounts' => function($q) {
            $q->select('transporter_id', 'bulk_payment_id');
        }])->where('is_owner', 1); // Show only truck owners

        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('company_id', auth()->user()->company_id);
        }
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('gst_number', 'like', "%{$searchValue}%")
                  ->orWhere('pan_number', 'like', "%{$searchValue}%")
                  ->orWhere('gst_type', 'like', "%{$searchValue}%")
                  ->orWhereHas('company', function($companyQuery) use ($searchValue) {
                      $companyQuery->where('name', 'like', "%{$searchValue}%");
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
                        case 2: // GST Number
                            $query->where('gst_number', 'like', "%{$searchValue}%");
                            break;
                        case 3: // GST Type
                            $query->where('gst_type', 'like', "%{$searchValue}%");
                            break;
                        case 4: // PAN Number
                            $query->where('pan_number', 'like', "%{$searchValue}%");
                            break;
                        case 8: // Company
                            $query->whereHas('company', function($companyQuery) use ($searchValue) {
                                $companyQuery->where('name', 'like', "%{$searchValue}%");
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
                case 0: // Sr. No (ID)
                    $query->orderBy('id', $orderDirection);
                    break;
                case 1: // Name
                    $query->orderBy('name', $orderDirection);
                    break;
                case 2: // GST Number
                    $query->orderBy('gst_number', $orderDirection);
                    break;
                case 3: // GST Type
                    $query->orderBy('gst_type', $orderDirection);
                    break;
                case 4: // PAN Number
                    $query->orderBy('pan_number', $orderDirection);
                    break;
                case 5: // Declaration Given
                    $query->orderBy('declaration_given', $orderDirection);
                    break;
                case 6: // Total Trucks
                    $query->orderBy('total_trucks', $orderDirection);
                    break;
                case 7: // Advance Amount
                    $query->orderBy('advance_amount', $orderDirection);
                    break;
                case 8: // Company
                    $query->join('companies', 'transporters.company_id', '=', 'companies.id')
                          ->orderBy('companies.name', $orderDirection)
                          ->select('transporters.*');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Get total count before pagination
        $totalQuery = \App\Models\Transporter::where('is_owner', 1);
        if (auth()->user()->userRole->name !== 'super_admin') {
            $totalQuery->where('company_id', auth()->user()->company_id);
        }
        $totalRecords = $totalQuery->count();
        $filteredRecords = $query->count();
        
        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $truckOwners = $query->skip($start)->take($length)->get();
        
        // Format data for DataTables
        $data = $truckOwners->map(function($truckOwner, $index) use ($start) {
            return [
                'sr_no' => $start + $index + 1,
                'name' => $truckOwner->name,
                'gst_number' => $truckOwner->gst_number ?: '-',
                'gst_type' => ucfirst($truckOwner->gst_type ?: '-'),
                'pan_number' => $truckOwner->pan_number ?: '-',
                'declaration_given' => $truckOwner->declaration_given ? 'Yes' : 'No',
                'total_trucks' => $truckOwner->total_trucks ?: 0,
                'advance_amount' => number_format($truckOwner->advance_amount ?: 0, 2),
                'company' => $truckOwner->company ? $truckOwner->company->name : '-',
                // 'actions' => $this->getActionButtons($truckOwner)
                'actions' => auth()->user()->userRole->name === 'admin'
                        ? $this->getActionButtons($truckOwner)
                        : ''  // empty string for non-admins
            ];
        });
        
        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }
    
    private function getActionButtons($truckOwner)
    {
        $buttons = '<div class="d-flex gap-1">';
        
        // Bank account button
        if($truckOwner->bankAccounts->isEmpty() || $truckOwner->bankAccounts->first()->bulk_payment_id === null) {
            $bankUrl = route('truck_owner_bank_accounts.create', ['truck_owner_id' => $truckOwner->id]);
            $buttons .= '<a href="' . $bankUrl . '" class="btn btn-sm btn-info" title="Add Bank Account">';
            $buttons .= '<i class="bi bi-bank"></i></a>';
        } else {
            $bankUrl = route('truck_owner_bank_accounts.index', ['truck_owner_id' => $truckOwner->id]);
            $buttons .= '<a href="' . $bankUrl . '" class="btn btn-sm btn-warning" title="View Bank Accounts">';
            $buttons .= '<i class="bi bi-bank"></i></a>';
        }
        
        // Edit button
        $editUrl = route('truck_owners.edit', $truckOwner->id);
        $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info">';
        $buttons .= '<i class="bi bi-pencil"></i></a>';

        // // Truck creation button
        $truckUrl = route('trucks.create', ['transporter_id' => $truckOwner->id]);
        $buttons .= '<a href="' . $truckUrl . '" class="btn btn-sm btn-info" title="Add Truck">';
        $buttons .= '<i class="bi bi-truck"></i></a>';
        
        // Delete button
        $deleteUrl = route('truck_owners.destroy', $truckOwner->id);
        $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Delete this truck owner?\')">';
        $buttons .= csrf_field() . method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';
        
        $buttons .= '</div>';
        return $buttons;
    }

    public function create()
    {
        $companies = \App\Models\Company::all();
        // For create, we can't filter trucks as we don't have transporter_id yet
        $trucks = \App\Models\Truck::all();
        return view('truck_owner.create', compact('companies', 'trucks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
           'gst_number' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'gst_type' => 'nullable|in:composition,regular',
            'is_owner' => 'nullable|boolean',
            'declaration_given' => 'required|boolean',
            'total_trucks' => 'required|integer|min:0',
            'advance_amount' => 'nullable|numeric',
            'pan_active' => 'required|boolean',
        ]);
        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }
        if (!isset($validated['is_owner'])) {
            $validated['is_owner'] = 1;
        }
        // Convert pan_active to boolean
        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);
        \App\Models\Transporter::create($validated);
        return redirect()->route('truck_owners.index')->with('success', 'TruckOwner created successfully!');
    }

    public function edit(\App\Models\Transporter $transporter)
    {
        $companies = \App\Models\Company::all();
        // Filter trucks to show only those belonging to this transporter
        $trucks = \App\Models\Truck::where('transporter_id', $transporter->id)->get();
        return view('truck_owner.edit', compact('transporter', 'companies', 'trucks'));
    }

    public function update(Request $request, \App\Models\Transporter $transporter)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
           'gst_number' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'
            ],
            'pan_number' => [
                'nullable',
                'string',
                'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'
            ],
            'gst_type' => 'nullable|in:composition,regular',
            'is_owner' => 'nullable|boolean',
            'declaration_given' => 'required|boolean',
            'total_trucks' => 'required|integer|min:0',
            'advance_amount' => 'nullable|numeric',
            'pan_active' => 'required|boolean',
        ]); 
        if (!isset($validated['advance_amount'])) {
            $validated['advance_amount'] = 0;
        }
        if (!isset($validated['is_owner'])) {
            $validated['is_owner'] = 1;
        }
        // Convert pan_active to boolean
        $validated['pan_active'] = filter_var($validated['pan_active'], FILTER_VALIDATE_BOOLEAN);
        $transporter->update($validated);
        return redirect()->route('truck_owner.index')->with('success', 'TruckOwner updated successfully!');
    }

    public function destroy(\App\Models\Transporter $transporter)
    {
        $transporter->delete();
        return redirect()->route('truck_owner.index')->with('success', 'TruckOwner deleted successfully!');
    }

    //
}
