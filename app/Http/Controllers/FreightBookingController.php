<?php
namespace App\Http\Controllers;

use App\Models\AdvancePayment;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\VoucherPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use App\Models\Pump;

class FreightBookingController extends Controller
{
    public function index(Request $request)
    {

        $pumps = Pump::all();
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getFreightBookingsData($request);
        }

        $query = FreightBooking::with(['lr.truck', 'linkedFreightBooking', 'company', 'transporter']);

        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('freight_bookings.company_id', auth()->user()->company_id);
        }

        // Default date range: last 15 days
        $endDate = $request->input('end_date', date('Y-m-d'));
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-15 days')));
        
        // Apply date range filter if provided
        if ($request->filled('start_date')) {
            $startDate = $request->start_date;
        }
        if ($request->filled('end_date')) {
            $endDate = $request->end_date;
        }
        
        // Ensure date range doesn't exceed 3 months
        $maxEndDate = (new \DateTime($startDate))->modify('+3 months')->format('Y-m-d');
        if ($endDate > $maxEndDate) {
            $endDate = $maxEndDate;
        }
        
        $query->whereDate('freight_bookings.created_at', '>=', $startDate)
              ->whereDate('freight_bookings.created_at', '<=', $endDate);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('voucher_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('lr', function($lrQuery) use ($search) {
                      $lrQuery->where('lr_number', 'LIKE', "%{$search}%")
                             ->orWhere('source_city', 'LIKE', "%{$search}%")
                             ->orWhere('destination_city', 'LIKE', "%{$search}%")
                             ->orWhereHas('truck', function($truckQuery) use ($search) {
                                 $truckQuery->where('truck_number', 'LIKE', "%{$search}%");
                             })
                             ->orWhereHas('transporter', function($transporterQuery) use ($search) {
                                 $transporterQuery->where('name', 'LIKE', "%{$search}%")
                                                 ->orWhere('short_name', 'LIKE', "%{$search}%");
                             });
                  });
            });
        }
        
        // Transporter filter
        if ($request->filled('transporter_id')) {
            $query->where(function($q) use ($request) {
                $q->where('transporter_id', $request->transporter_id)
                  ->orWhere('transporter_name', 'LIKE', "%{$request->transporter_id}%");
            });
        }
        
        // Truck owner filter
        if ($request->filled('truck_owner_id')) {
            $query->whereHas('lr.truck', function($q) use ($request) {
                $q->where('transporter_id', $request->truck_owner_id);
            });
        }

        $bookings = $query->orderByDesc('freight_bookings.id')->paginate(20);
        
        // Get filter data
        $transporters = Transporter::where('is_owner', 0)->orderBy('name')->get();
        $truckOwners = Transporter::where('is_owner', 1)->orderBy('name')->get();
        $lrs = LorryReceipt::all();
        $paymentTypes = \App\Models\PaymentType::all();
        $paymentModes = \App\Models\PaymentMode::all();
        
        return view('freight_bookings.index', compact(
            'bookings', 
            'lrs', 
            'paymentTypes', 
            'paymentModes',
            'transporters',
            'truckOwners',
            'startDate',
            'endDate',
            'pumps'
        ));
    }

    public function getFreightBookingsData(Request $request)
    {
        try {
            \Log::info('FreightBookings DataTables request started');
            
            $query = FreightBooking::with(['lr.truck', 'linkedFreightBooking', 'company', 'transporter'])
                ->select('freight_bookings.*');

            // Only apply company_id filter for non-super_admin users
            if (auth()->user()->userRole->name !== 'super_admin') {
                $query->where('freight_bookings.company_id', auth()->user()->company_id);
            }
            
            \Log::info('Query built, processing DataTables');

            $dataTable = DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    // Hide checkbox if freight booking is delivered and balance is zero
                    if ($row->is_delivered && ($row->balance_amount <= 0)) {
                        return '<span class="text-muted" title="No checkbox available: Delivered with zero balance"><i class="bi bi-dash-circle"></i></span>';
                    }
                    
                    return sprintf(
                        '<input type="checkbox" class="row-checkbox" value="%s" data-lr="%s" data-truck="%s" data-balance="%s" data-voucher_number="%s" data-is-delivered="%s">',
                        $row->id,
                        $row->lr ? $row->lr->lr_number : '',
                        $row->lr && $row->lr->truck ? $row->lr->truck->truck_number : '',
                        $row->balance_amount ?? 0,
                        $row->voucher_number ?? '',
                        $row->is_delivered ? '1' : '0'
                    );
                })
                ->addColumn('fr_number', function ($row) {
                    return $row->id ? $row->id : '-';
                })
                ->addColumn('lr_number', function ($row) {
                    return $row->lr ? $row->lr->lr_number : '-';
                })
                ->addColumn('source_city', function ($row) {
                    return $row->lr && $row->lr->source_city ? $row->lr->source_city : '-';
                })
                ->addColumn('destination_city', function ($row) {
                    return $row->lr && $row->lr->destination_city ? $row->lr->destination_city : '-';
                })
                ->addColumn('transporter', function ($row) {
                    $transporterName = $row->transporter_name ?? ($row->transporter ? $row->transporter->name : '-');
                    $badge = '';
                    
                    if ($row->transporter && $row->transporter->is_owner) {
                        $badge = '<span class="badge bg-success ms-1" title="Truck Owner"><i class="bi bi-truck"></i></span>';
                    } else {
                        $badge = '<span class="badge bg-info ms-1" title="Agency"><i class="bi bi-building"></i></span>';
                    }
                    
                    return $transporterName . ' ' . $badge;
                })
                ->addColumn('truck_number', function ($row) {
                    return $row->lr && $row->lr->truck ? $row->lr->truck->truck_number : '-';
                })
               
                ->addColumn('tr_rate_formatted', function ($row) {
                    return $row->tr_fr_rate ? '₹' . number_format($row->tr_fr_rate, 2) : '₹0.00';
                })
                // ->addColumn('tr_amount_formatted', function ($row) {
                //     return $row->tr_amount ? '₹' . number_format($row->tr_amount, 2) : '₹0.00';
                // })
                ->addColumn('advance_formatted', function ($row) {
                    return $row->advance_amount ? '₹' . number_format($row->advance_amount, 2) : '₹0.00';
                })
                ->addColumn('balance_formatted', function ($row) {
                   
                    return $row->balance_amount ? '₹' . number_format($row->balance_amount, 2) : '₹0.00';
                })
                ->addColumn('gr_date_formatted', function ($row) {
                    return $row->lr && $row->lr->gr_date ? date('d-m-Y', strtotime($row->lr->gr_date)) : '-';
                })
                ->addColumn('truck_number', function ($row) {
                    return $row->lr && $row->lr->truck ? $row->lr->truck->truck_number : '-';
                })
                // ->addColumn('is_delivered_badge', function ($row) {
                //     if ($row->is_delivered == '1') {
                //         return '<span class="badge bg-success">Yes</span>';
                //     } else {
                //         return '<span class="badge bg-warning">No</span>';
                //     }
                // })
                // ->addColumn('is_cancelled_badge', function ($row) {
                //     if ($row->is_cancelled == '1') {
                //         return '<span class="badge bg-danger">Yes</span>';
                //     } else {
                //         return '<span class="badge bg-secondary">No</span>';
                //     }
                // })
                ->addColumn('actions', function ($row) {
                    return $this->getActionButtons($row);
                })
                ->filterColumn('lr_number', function ($query, $keyword) {
                    $query->whereHas('lr', function ($q) use ($keyword) {
                        $q->where('lr_number', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['checkbox', 'actions', 'transporter']);
            
            \Log::info('DataTables processing complete');
            return $dataTable->make(true);
        } catch (\Exception $e) {
            \Log::error('DataTables error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ], 200);
        }
    }

    private function getActionButtons($freightBooking)
    {
        $actions = '<div class="btn-group" role="group">';
        
        // // View button
        // $actions .= '<a href="' . route('freight_bookings.show', $freightBooking->id) . '" class="btn btn-sm btn-outline-info" title="View">';
        // $actions .= '<i class="bi bi-eye"></i>';
        // $actions .= '</a>';
        
        // Edit button
        if(auth()->check() && auth()->user()->userRole->name === 'admin') {
        $actions .= '<a href="' . route('freight_bookings.edit', $freightBooking->id) . '" class="btn btn-sm btn-outline-primary" title="Edit">';
        $actions .= '<i class="bi bi-pencil"></i>';
        $actions .= '</a>';
        }

        // Individual Voucher Payment button
        if ($freightBooking->voucher_number && $freightBooking->balance_amount > 0) {
            $actions .= '<button type="button" class="btn btn-sm btn-outline-success individual-payment-btn" ';
            $actions .= 'data-id="' . $freightBooking->id . '" ';
            $actions .= 'data-lr="' . ($freightBooking->lr ? $freightBooking->lr->lr_number : 'N/A') . '" ';
            $actions .= 'data-voucher_number="' . $freightBooking->voucher_number . '" ';
            $actions .= 'data-balance="' . $freightBooking->balance_amount . '" ';
            $actions .= 'data-truck="' . ($freightBooking->lr && $freightBooking->lr->truck ? $freightBooking->lr->truck->truck_number : 'N/A') . '" ';
            $actions .= 'title="Make Voucher Payment">';
            $actions .= '<i class="bi bi-credit-card"></i>';
            $actions .= '</button>';
        }

        // Delete button
        if(auth()->check() && auth()->user()->userRole->name === 'admin') {
        $actions .= '<form method="POST" action="' . route('freight_bookings.destroy', $freightBooking->id) . '" style="display: inline;">';
        $actions .= csrf_field();
        $actions .= method_field('DELETE');
        $actions .= '<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm(\'Are you sure you want to delete this freight booking?\')">';
        $actions .= '<i class="bi bi-trash"></i>';
        $actions .= '</button>';
        $actions .= '</form>';
        }
        $actions .= '</div>';
        
        return $actions;
    }

    public function getFreightBookingDetails($id)
{
    $freightBooking = FreightBooking::with([
        'lr' => function ($query) {
            $query->with(['transporter', 'truck', 'consignor', 'consignee']);
        }
    ])->find($id);

    if (!$freightBooking) {
        return response()->json(['error' => 'Freight Booking not found'], 404);
    }

    return response()->json([
        'freight_booking' => $freightBooking,
        'lr' => $freightBooking->lr
    ]);
}

    public function create(Request $request)
    {
        $companies = \App\Models\Company::all();
        $lrs = LorryReceipt::all();
        $linkedBookings = FreightBooking::all();
        $goodsTypes = \App\Models\GoodType::all();
        $lorry_receipt_id = $request->lorry_receipt_id;

        // Get TDS percentages from config
        $tdsPercentages = [
            'default' => env('TDS_PERCENTAGE_DEFAULT', 20),
            'agency' => env('TDS_PERCENTAGE_AGENCY', 2),
            'owner' => env('TDS_PERCENTAGE_OWNER', 1)
        ];

        $lorry_receipt = \App\Models\LorryReceipt::with([
            'transporter:id,name,tds_percentage,is_owner', 
            'truck:id,truck_number',
            'customer:id,name',
            'consignor:id,name', 
            'consignee:id,name'
        ])
        ->select('id', 'lr_number', 'transporter_id', 'truck_id','customer_id','consignor_id','consignee_id','good_type','source_city','destination_city','source_state','destination_state','weight','no_of_units','amount')
        ->when($lorry_receipt_id, function ($query) use ($lorry_receipt_id) {
            $query->where('id', $lorry_receipt_id);
        })
        ->get();

        // If lorry_receipt_id is provided, fetch LR number and related data
        if ($request->has('lorry_receipt_id')) {
            $lr = \App\Models\LorryReceipt::find($request->lorry_receipt_id);
            if ($lr) {
                // Fetch transporter and truck details directly from their models
                $transporter = \App\Models\Transporter::find($lr->transporter_id);
                $truck = \App\Models\Truck::find($lr->truck_id);
                     // Prepare values for TDS calculation
            $isOwner          = $transporter ? $transporter->is_owner : 0;
            $declarationGiven = $transporter ? $transporter->declaration_given : 0;
            $panActive        = $transporter ? $transporter->pan_active : 0;
            $panNumber        = $transporter ? $transporter->pan_number : '';
            $trAmount         = $lr->freight_amount ?? 0; // adjust this field if amount is stored differently

            // =============================
            // TDS CALCULATION LOGIC
            // =============================
            $tdsPercentage = 0;

            // CASE 1: is_owner = 0, declaration_given = 1 → 0% TDS
            if (($isOwner == 1 && $declarationGiven == 1 )&& ($panActive == 1 || $panActive == 0)) {
                $tdsPercentage = 0;
            }
            // // CASE 2: is_owner = 0, declaration_given = 0, pan_active = 0 → 20% TDS
            elseif ($isOwner == 1 && $declarationGiven == 0 && $panActive == 0) {
                $tdsPercentage = 20;
            }
            // CASE 3: is_owner = 0, declaration_given = 0, pan_active = 1 → check PAN letter
            elseif ($isOwner == 1 && $declarationGiven == 0 && $panActive == 1) {
                if (strlen($panNumber) >= 4) {
                    $fourthChar = strtoupper($panNumber[3]);
                    if ($fourthChar == 'P' || $fourthChar == 'H') {
                        $tdsPercentage = 1;
                    } else {
                        $tdsPercentage = 2;
                    }
                } else {
                    $tdsPercentage = 2; // fallback if PAN invalid
                }
            }
            // // CASE 4: is_owner = 1, pan_active = 0 → 20% TDS
            elseif ($isOwner == 0 && $panActive == 0) {
                $tdsPercentage = 20;
            }
            // CASE 5: is_owner = 1, pan_active = 1 → 2% TDS
            elseif ($isOwner == 0 && $panActive == 1) {
                $fourthChar = strtoupper($panNumber[3]);
                if ($fourthChar === 'C' || $fourthChar === 'F' || $fourthChar === 'A' || $fourthChar === 'B' || $fourthChar === 'J' || $fourthChar === 'L' || $fourthChar === 'T') {
                    $tdsPercentage = 2;
                } else {
                    $tdsPercentage = 1;
                }
            }

            // $tdsAmount = $trAmount * ($tdsPercentage / 100);
                
                return view('freight_bookings.create', [
                    'companies' => $companies,
                    'lrs' => $lrs,
                    'linkedBookings' => $linkedBookings,
                    'goodsTypes' => $goodsTypes,
                    'lorry_receipt' => $lorry_receipt,
                    'weight' => $lr->weight,
                    
                    'transporter' => $transporter ?? null,
                    'tdsPercentages' => $tdsPercentages,
                    'tdsPercentage'   => $tdsPercentage, // send to view
                    // 'tdsAmount'       => $tdsAmount,     // send to view
                    'preFilled' => [
                        'lr_number' => $lr->lr_number,
                        'lr_id' => $lr->id,
                        'transporter_id' => $lr->transporter_id,
                        'transporter_name' => $transporter ? $transporter->name : '',
                        'truck_id' => $lr->truck_id,
                        'truck_number' => $truck ? $truck->truck_number : '',
                        'pan_verified' => $transporter ? $transporter->pan_active : 0,
                        'pan_number' => $transporter ? $transporter->pan_number : '',
                        'is_owner' => $transporter ? $transporter->is_owner : 0,
                        'declaration_given' => $transporter ? $transporter->declaration_given : 0,
                        'goods_type' => $lr->good_type,
                        
                        
                    ]
                ]);
            }
        }

        return view('freight_bookings.create', [
            'companies' => $companies,
            'lrs' => $lrs,
            'linkedBookings' => $linkedBookings,
            'goodsTypes' => $goodsTypes,
            'lorry_receipt' => $lorry_receipt,
            'transporter' => null,
            'tdsPercentages' => $tdsPercentages,
            'preFilled' => []
        ]);
    }


    public function bulkAction(Request $request)
{
    $selectedIds = $request->input('selected_ids');

    if (empty($selectedIds) || !is_array($selectedIds)) {
        return redirect()->back()->with('error', 'Please select at least one freight booking.');
    }

    // Now $selectedIds contains all selected row IDs
    FreightBooking::whereIn('id', $selectedIds)->update([
        // your update logic
    ]);

    return redirect()->back()->with('success', 'Selected freight bookings updated successfully.');
}


    /**
     * Confirm the freight booking details before final submission
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function confirm(Request $request)
    {
        // Validate the form data
        $validator = Validator::make($request->all(), [
            'lr_id' => 'required|exists:lorry_receipts,id',
            'tr_fr_rate' => 'required|numeric',
            'tr_amount' => 'required|numeric',
            'advance_amount' => 'nullable|numeric',
            'balance_amount' => 'required|numeric',
            'tds_amount' => 'nullable|numeric',
            'eway_bill_number' => 'nullable|string',
            'good_type' => 'nullable|string',
            'pan_verified' => 'required|boolean',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'company_id' => 'required|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Get the validated form data
        $formData = $validator->validated();

        // Get the lorry receipt with its relationships
        $lorry_receipt = LorryReceipt::with(['transporter', 'truck', 'customer', 'consignor', 'consignee'])
            ->findOrFail($formData['lr_id']);

        // Return the confirmation view with the form data and lorry receipt
        return view('freight_bookings.confirm', compact('formData', 'lorry_receipt'));
    }

    /**
     * Store a newly created freight booking in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if the form has been confirmed
        if (!$request->has('confirmed')) {
            return redirect()->route('freight_bookings.create')
                ->with('error', 'Please confirm the freight booking details before submission.');
        }
        
        $data = $request->validate([
            'lr_id' => 'required|exists:lorry_receipts,id',
            'tr_fr_rate' => 'required|numeric',
            'tr_amount' => 'required|numeric',
            'advance_amount' => 'nullable|numeric',
            'balance_amount' => 'required|numeric',
            'tds_amount' => 'nullable|numeric',
            'eway_bill_number' => 'nullable|string',
            'good_type' => 'nullable|string',
            
            'pan_verified' => 'required|boolean',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'company_id' => 'required|exists:companies,id',
           
            
        ]);

        // Get the lorry receipt to get its LR number
        $lorryReceipt = LorryReceipt::find($data['lr_id']);
        if (!$lorryReceipt) {
            return redirect()->back()->withErrors(['lr_id' => 'Invalid lorry receipt']);
        }

        // Create freight booking with lorry receipt's LR number
        // Get the next voucher number
        $nextVoucherNumber = FreightBooking::max('voucher_number') + 1;
        
        // Get transporter information from lorry receipt
        $transporterInfo = [];
        if ($lorryReceipt->transporter) {
            $transporterInfo = [
                'transporter_id' => $lorryReceipt->transporter_id,
                'transporter_name' => $lorryReceipt->transporter->name,
                'transporter_tds_percentage' => $lorryReceipt->transporter->tds_percentage
            ];
        }

        $freightBooking = FreightBooking::create(array_merge($data, [
            'lr_number' => $lorryReceipt->lr_number,
            'voucher_number' => $nextVoucherNumber,
            'pan_verified' => (bool)$data['pan_verified'],
            'company_id' => $data['company_id']
        ], $transporterInfo));
        
        // Update lorry receipt status and damage amount
        if ($lorryReceipt) {
            $deliveryStatus = 'Pending';
            if ($data['is_cancelled'] === '1') {
                $deliveryStatus = 'Cancelled';
            } elseif ($data['is_delivered'] === '1') {
                $deliveryStatus = 'Delivered';
            }
            else{
                $deliveryStatus = 'In-Progress';
            }
            $lorryReceipt->delivery_status = $deliveryStatus;
            $lorryReceipt->freight_booking_id = $freightBooking->id;
            $lorryReceipt->save();
        }

        if ($request->has('action') && $request->action === 'save_and_pay') {
            // Redirect to voucher payment creation page with freight booking ID
            return redirect()->route('voucher_payments.create', ['freight_booking_id' => $freightBooking->id])
                ->with('success', 'Freight Booking created successfully. Ready to make payment.');
        }

        return redirect()->route('lorry_receipts.index')->with('success', 'Freight Booking created successfully.');
    }

    public function show($id)
    {
        
        try {
            $freightBooking = FreightBooking::with(['lr', 'linkedFreightBooking', 'company'])->findOrFail($id);
            
            // Check if user has access to this freight booking (company-based filtering)
            if (auth()->user()->userRole->name !== 'super_admin' && 
                $freightBooking->company_id !== auth()->user()->company_id) {
                abort(403, 'Unauthorized access to freight booking.');
            }
            $pumps = Pump::all();
            
            // Load payment types and modes for the voucher payment modal
            $paymentTypes = \App\Models\PaymentType::all();
            $paymentModes = \App\Models\PaymentMode::all();
            
            // Get existing payment data for this freight booking if available
            $existingPayment = \App\Models\VoucherPayment::where('freight_booking_id', $id)
                ->orderBy('created_at', 'desc')
                ->first();
            
            $payments = \App\Models\VoucherPayment::where('freight_booking_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return view('freight_bookings.show', compact('freightBooking', 'paymentTypes', 'paymentModes', 'existingPayment','pumps','payments'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'Freight booking not found.');
        } catch (\Exception $e) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'An error occurred while loading the freight booking.');
        }
    }

    public function edit($id)
    {
        try {
            $freightBooking = FreightBooking::findOrFail($id);
            
            // Check if user has access to this freight booking (company-based filtering)
            if (auth()->user()->userRole->name !== 'super_admin' && 
                $freightBooking->company_id !== auth()->user()->company_id) {
                abort(403, 'Unauthorized access to freight booking.');
            }
            $companies = \App\Models\Company::all();
            // Get the LorryReceipt details
            $lorryReceipt = LorryReceipt::find($freightBooking->lr_id);
             // Get TDS percentages from config
             $tdsPercentages = [
                'default' => env('TDS_PERCENTAGE_DEFAULT', 20),
                'agency' => env('TDS_PERCENTAGE_AGENCY', 2),
                'owner' => env('TDS_PERCENTAGE_OWNER', 1)
        ];
        
        // Get the transporter details
        $transporter = \App\Models\Transporter::find($lorryReceipt->transporter_id);
        
        // Get the truck details
        $truck = \App\Models\Truck::find($lorryReceipt->truck_id);
        
        // Get all LRs
        $lrs = LorryReceipt::all();
        
        // Get goods types
        $goodsTypes = \App\Models\GoodType::all();
        $lorry_receipt = \App\Models\LorryReceipt::with(['transporter:id,name', 'truck:id,truck_number'])
        ->select('id', 'lr_number', 'transporter_id', 'truck_id','customer_id','consignor_id','consignee_id','good_type')
        ->when($lorryReceipt, function ($query) use ($lorryReceipt) {
            $query->where('id', $lorryReceipt->id);
        })
        ->get();

        
        // Get linked bookings
        $linkedBookings = FreightBooking::where('id', '!=', $freightBooking->id)->get();
         // If lorry_receipt_id is provided, fetch LR number and related data
         if ($lorryReceipt) {
            $lr = \App\Models\LorryReceipt::find($lorryReceipt->id);
            if ($lr) {
                // Fetch transporter and truck details directly from their models
                $transporter = \App\Models\Transporter::find($lr->transporter_id);
                $truck = \App\Models\Truck::find($lr->truck_id);

                $isOwner          = $transporter ? $transporter->is_owner : 0;
                $declarationGiven = $transporter ? $transporter->declaration_given : 0;
                $panActive        = $transporter ? $transporter->pan_active : 0;
                $panNumber        = $transporter ? $transporter->pan_number : '';
                $trAmount         = $lr->freight_amount ?? 0; // adjust this field if amount is stored differently
    
                // =============================
                // TDS CALCULATION LOGIC
                // =============================
                $tdsPercentage = 0;
    
                // CASE 1: is_owner = 0, declaration_given = 1 → 0% TDS
                if (($isOwner == 1 && $declarationGiven == 1 )&& ($panActive == 1 || $panActive == 0)) {
                    $tdsPercentage = 0;
                }
                // // CASE 2: is_owner = 0, declaration_given = 0, pan_active = 0 → 20% TDS
                elseif ($isOwner == 1 && $declarationGiven == 0 && $panActive == 0) {
                    $tdsPercentage = 20;
                }
                // CASE 3: is_owner = 0, declaration_given = 0, pan_active = 1 → check PAN letter
                elseif ($isOwner == 1 && $declarationGiven == 0 && $panActive == 1) {
                    if (strlen($panNumber) >= 4) {
                        $fourthChar = strtoupper($panNumber[3]);
                        if ($fourthChar == 'P' || $fourthChar == 'H') {
                            $tdsPercentage = 1;
                        } else {
                            $tdsPercentage = 2;
                        }
                    } else {
                        $tdsPercentage = 2; // fallback if PAN invalid
                    }
                }
                // // CASE 4: is_owner = 1, pan_active = 0 → 20% TDS
                elseif ($isOwner == 0 && $panActive == 0) {
                    $tdsPercentage = 20;
                }
                // CASE 5: is_owner = 1, pan_active = 1 → 2% TDS
                elseif ($isOwner == 0 && $panActive == 1) {
                    $fourthChar = strtoupper($panNumber[3]);
                    if ($fourthChar === 'C' || $fourthChar === 'F' || $fourthChar === 'A' || $fourthChar === 'B' || $fourthChar === 'J' || $fourthChar === 'L' || $fourthChar === 'T') {
                        $tdsPercentage = 2;
                    } else {
                        $tdsPercentage = 1;
                    }
                }
    
                
                return view('freight_bookings.edit', [
                    'freightBooking' => $freightBooking,
                    'companies' => $companies,
                    'lrs' => $lrs,
                    'linkedBookings' => $linkedBookings,
                    'goodsTypes' => $goodsTypes,
                    'lorry_receipt' => $lorry_receipt,
                    'weight' => $lr->weight,
                    
                    'transporter' => $transporter ?? null,
                    'tdsPercentages' => $tdsPercentages,
                    'tdsPercentage'   => $tdsPercentage, // send to view
                    'preFilled' => [
                        'lr_number' => $lr->lr_number,
                        'lr_id' => $lr->id,
                        'transporter_id' => $lr->transporter_id,
                        'transporter_name' => $transporter ? $transporter->name : '',
                        'truck_id' => $lr->truck_id,
                        'truck_number' => $truck ? $truck->truck_number : '',
                        'pan_verified' => $transporter ? $transporter->pan_active : 0,
                        'is_owner' => $transporter ? $transporter->is_owner : 0,
                        'goods_type' => $lr->good_type,
                        'memo_id' => $lr->memo_id
                        
                        
                    ]
                ]);
            }
        }
        
        return view('freight_bookings.edit', [
            'freightBooking' => $freightBooking,
            'lorry_receipt' => $lrs,
            'linkedBookings' => $linkedBookings,
            'goods_type' => $goodsTypes,
            'transporter' => $transporter,
            'truck' => $truck,
            'preFilled' => [
                'lr_number' => $freightBooking->lr ? $freightBooking->lr->lr_number : '',
                'lr_id' => $freightBooking->lr_id,
                'transporter_id' => $freightBooking->lr ? $freightBooking->lr->transporter_id : '',
                'transporter_name' => $transporter ? $transporter->name : '',
                'truck_id' => $freightBooking->lr ? $freightBooking->lr->truck_id : '',
                'truck_number' => $truck ? $truck->truck_number : '',
                'pan_verified' => $transporter ? $transporter->pan_active : 0,
                'is_owner' => $transporter ? $transporter->is_owner : 0,
            ]
        ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'Freight booking not found.');
        } catch (\Exception $e) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'An error occurred while loading the freight booking for editing.');
        }
    }

    public function getLorryReceipt($id)
    {
        $lr = LorryReceipt::find($id);
        if (!$lr) {
            return response()->json(['error' => 'LR not found'], 404);
        }
        return response()->json($lr);
    }

    public function getTransporter($id)
    {
        $transporter = \App\Models\Transporter::find($id);
        if (!$transporter) {
            return response()->json(['error' => 'Transporter not found'], 404);
        }
        return response()->json($transporter);
    }

    public function getTruck($id)
    {
        $truck = \App\Models\Truck::find($id);
        if (!$truck) {
            return response()->json(['error' => 'Truck not found'], 404);
        }
        return response()->json($truck);
    }

    public function update(Request $request, FreightBooking $freightBooking)
    {
        // Prevent updates to cancelled freight bookings
        if ($freightBooking->is_cancelled) {
            return redirect()->back()->withErrors(['general' => 'Cannot update a cancelled freight booking. Please contact administrator if changes are needed.']);
        }

        // Add debug logging
        \Log::info('Freight booking update started', [
            'freight_booking_id' => $freightBooking->id,
            'request_data' => $request->all()
        ]);
       

        $data = $request->validate([
            'lr_id' => 'required|exists:lorry_receipts,id',
            'tr_fr_rate' => 'required|numeric',
            'tr_amount' => 'required|numeric',
            'advance_amount' => 'required|numeric',
            'balance_amount' => 'required|numeric',
            'tds_amount' => 'nullable|numeric',
            'good_type' => 'nullable|string',
            'detention' => 'nullable|numeric',
            'deviation' => 'nullable|numeric',
            'misc_expense' => 'nullable|numeric',
            'damage_amount' => 'nullable|numeric',
            'shortage_amount' => 'nullable|numeric',
            'pan_verified' => 'required|boolean',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'linked_freight_booking_id' => 'nullable|exists:freight_bookings,id',
            'eway_bill_number' => 'nullable|string',
        ]);

       
        // Get the lorry receipt to get its LR number
        $lorryReceipt = LorryReceipt::find($data['lr_id']);
        if (!$lorryReceipt) {
            return redirect()->back()->withErrors(['lr_id' => 'Invalid lorry receipt']);
        }
        // Use the new TR amount from form data instead of existing
        $newTrAmount = $data['tr_amount'];

        // Calculate balance on the server side using new TR amount
        $calculatedBalance = $newTrAmount
            - $data['advance_amount']
            - ($data['tds_amount'] ?? 0)
            + ($data['detention'] ?? 0)
            + ($data['deviation'] ?? 0)
            - ($data['misc_expense'] ?? 0)
            - ($data['damage_amount'] ?? 0)
            - ($data['shortage_amount'] ?? 0);
    
        // Skip balance validation - let frontend handle it
        // The balance amount will be recalculated and stored correctly

       

        // Update freight booking with lorry receipt's LR number
        $freightBooking->update(array_merge($data, [
            'lr_number' => $lorryReceipt->lr_number
        ]));

    

        // Update lorry receipt status
        if ($lorryReceipt) {
            // Refresh the lorry receipt to get latest data (in case memo_id was updated via AJAX)
            $lorryReceipt->refresh();
            
            \Log::info('FREIGHT BOOKING UPDATE: Starting LR status update', [
                'freight_booking_id' => $freightBooking->id,
                'lr_id' => $lorryReceipt->id,
                'lr_number' => $lorryReceipt->lr_number,
                'current_memo_id' => $lorryReceipt->memo_id,
                'current_delivery_status' => $lorryReceipt->delivery_status,
                'is_delivered_from_form' => $data['is_delivered'] ?? 'not_set',
                'is_cancelled_from_form' => $data['is_cancelled'] ?? 'not_set'
            ]);

            $deliveryStatus = 'Pending';
            // dd($data);
            if (isset($data['is_cancelled']) && $data['is_cancelled'] === '1') {
                // When freight booking is cancelled, lorry receipt goes back to Pending
                $deliveryStatus = 'Pending';
                \Log::info('FREIGHT BOOKING UPDATE: Setting status to Pending (cancelled)');
            } elseif (isset($data['is_delivered']) && $data['is_delivered'] === '1') {
                // Only set to 'Delivered' if memo_id exists on the lorry receipt
                if (!empty($lorryReceipt->memo_id)) {
                    $deliveryStatus = 'Delivered';
                    \Log::info('FREIGHT BOOKING UPDATE: Setting status to Delivered (memo_id exists)', ['memo_id' => $lorryReceipt->memo_id]);
                } else {
                    // If is_delivered is true but no memo_id, keep it as In-Progress
                    $deliveryStatus = 'In-Progress';
                    \Log::info('FREIGHT BOOKING UPDATE: Setting status to In-Progress (no memo_id)');
                }
            } else {
                // If not delivered and not cancelled, set to In-Progress
                $deliveryStatus = 'In-Progress';
                \Log::info('FREIGHT BOOKING UPDATE: Setting status to In-Progress (default)');
            }
            
            $lorryReceipt->delivery_status = $deliveryStatus;
            $lorryReceipt->damage_amount = $freightBooking->damage_amount;
            $lorryReceipt->shortage_amount = $freightBooking->shortage_amount;
            $lorryReceipt->amount = $lorryReceipt->amount-($freightBooking->damage_amount + $freightBooking->shortage_amount);
            $lorryReceipt->save();
            
            \Log::info('FREIGHT BOOKING UPDATE: Final LR state after save', [
                'lr_id' => $lorryReceipt->id,
                'lr_number' => $lorryReceipt->lr_number,
                'memo_id' => $lorryReceipt->memo_id,
                'delivery_status' => $lorryReceipt->delivery_status
            ]);
        }

        return redirect()->route('lorry_receipts.index')->with('success', 'Freight Booking updated successfully.');
    }

    public function destroy(FreightBooking $freight_booking)
    {
        $freight_booking->delete();
        return redirect()->route('freight_bookings.index')->with('success', 'Freight Booking deleted.');
    }

    /**
     * Process voucher payment for selected freight bookings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function voucherPayment(Request $request)
    {
        try {
            \Log::info('Voucher payment request received', $request->all());
            
            // Validate the request
            $validated = $request->validate([
                'payment_type_id' => 'required|exists:payment_types,id',
                'payment_mode_id' => 'required|exists:payment_modes,id',
                'payment_type' => 'required|string',
                'payment_mode' => 'required|string',
                'booking_ids' => 'required|array',
                'booking_ids.*' => 'required|integer|exists:freight_bookings,id',
                'payment_amounts' => 'required|array',
                'payment_amounts.*' => 'required|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
                'reference_number' => 'nullable|string',
                'notes' => 'nullable|string',
                'pump_id' => 'nullable|exists:pumps,id',
            ]);

            \Log::info('Validation passed', $validated);

            // Begin transaction
            \DB::beginTransaction();
            
            $processedPayments = [];
            
            // Process each booking payment
            foreach ($validated['booking_ids'] as $index => $bookingId) {
                $amount = $validated['payment_amounts'][$index] ?? 0;
                
                if ($amount > 0) {
                    $freightBooking = FreightBooking::findOrFail($bookingId);
                    
                    // Get payment type and mode from their IDs
                    $paymentType = \App\Models\PaymentType::find($validated['payment_type_id']);
                    $paymentMode = \App\Models\PaymentMode::find($validated['payment_mode_id']);
                    
                    // Determine payment status based on payment mode
                    $paymentStatus = 'pending'; // Default status
                    
                    // Set status based on payment mode
                    if (strtolower($paymentMode->name) === 'cash' || strtolower($paymentMode->name) === 'adjust in advance') {
                        $paymentStatus = 'in-progress';
                    } else if (strtolower($paymentMode->name) === 'bank transfer' || 
                              strtolower($paymentMode->name) === 'fuel' || 
                              strpos(strtolower($paymentMode->name), 'pump') !== false) {
                        $paymentStatus = 'pending';
                    }
                    
                    // Create voucher payment record
                    $voucherPayment = \App\Models\VoucherPayment::create([
                        'freight_booking_id' => $bookingId,
                        'voucher_number' => $freightBooking->voucher_number,
                        'payment_type' => $paymentType->name,
                        'payment_mode' => $paymentMode->name,
                        'payment_type_id' => $validated['payment_type_id'],
                        'payment_mode_id' => $validated['payment_mode_id'],
                        'pump_id' => $validated['pump_id'] ?? null,
                        'amount' => $amount,
                        'remark' => $validated['notes'] ?? null,
                        'payment_status' => $paymentStatus,
                        'company_id' => auth()->user()->company_id
                    ]);
                    
                    \Log::info('Payment status set based on payment mode', [
                        'payment_mode' => $paymentMode->name,
                        'payment_status' => $paymentStatus
                    ]);
                    
                    // Update freight booking balance
                    // $newadvance_amount = $freightBooking->advance_amount + $amount;
                    // $newBalance = max(0, $freightBooking->balance_amount - $amount);
                    // $freightBooking->update([
                    //     'balance_amount' => $newBalance,
                    //     'advance_amount' => $newadvance_amount
                    // ]);
                    // Update freight booking balance / transporter advance
if (strtolower($paymentMode->name) === 'adjust in advance') {
    // make sure transporter exists for this booking
    $transporter = \App\Models\Transporter::find($freightBooking->transporter_id);

    if (!$transporter) {
        throw new \Exception("Transporter not found for booking ID {$bookingId}");
    }

    if ($amount > $transporter->advance_amount) {
        throw new \Exception("Allocated amount ₹{$amount} cannot exceed transporter available advance ₹{$transporter->advance_amount}");
    }

    // reduce from transporter advance
    $newTransporterAdvance = $transporter->advance_amount - $amount;
    $transporter->update([
        'advance_amount' => $newTransporterAdvance
    ]);

    // update only balance in freight booking
    $newBalance = max(0, $freightBooking->balance_amount - $amount);
    $freightBooking->update([
        'balance_amount' => $newBalance
    ]);

    \Log::info('Adjusted from transporter advance', [
        'transporter_id' => $transporter->id,
        'old_advance' => $transporter->getOriginal('advance_amount'),
        'deducted' => $amount,
        'new_advance' => $newTransporterAdvance,
        'booking_id' => $bookingId,
        'new_balance' => $newBalance
    ]);

} else {
    // normal payment flow (cash, bank, fuel, etc.)
    $newAdvanceAmount = $freightBooking->advance_amount + $amount;
    $newBalance = max(0, $freightBooking->balance_amount - $amount);

    $freightBooking->update([
        'balance_amount' => $newBalance,
        'advance_amount' => $newAdvanceAmount
    ]);

    \Log::info('Processed normal payment for booking', [
        'booking_id' => $bookingId,
        'amount' => $amount,
        'new_balance' => $newBalance,
        'new_advance_amount' => $newAdvanceAmount
    ]);
}

                    $processedPayments[] = $voucherPayment->id;
                    
                   
                }
            }
            
            \DB::commit();
            
            \Log::info('Voucher payment completed successfully', [
                'processed_payments' => $processedPayments
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Voucher payment processed successfully',
                'processed_count' => count($processedPayments)
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in voucher payment', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Voucher payment error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process bulk payment for selected freight bookings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkPayment(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'payment_type' => 'required|string',
                'payment_mode' => 'required|string',
                'payment_date' => 'required|date',
                'reference_number' => 'nullable|string',
                'notes' => 'nullable|string',
                'booking_ids' => 'required|array',
                'booking_ids.*' => 'required|integer|exists:freight_bookings,id',
                'payment_amounts' => 'required|array',
                'payment_amounts.*' => 'required|numeric|min:0'
            ]);

            // Begin transaction
            \DB::beginTransaction();
            
            // Calculate total amount
            $totalAmount = array_sum($validated['payment_amounts']);
            
            // Create payment record
            $payment = \App\Models\Payment::create([
                'payment_type' => $validated['payment_type'],
                'payment_mode' => $validated['payment_mode'],
                'payment_date' => $validated['payment_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'total_amount' => $totalAmount,
                'company_id' => auth()->user()->company_id,
                'created_by' => auth()->id()
            ]);
            
            // Process each booking payment
            foreach ($validated['booking_ids'] as $index => $bookingId) {
                $amount = $validated['payment_amounts'][$index] ?? 0;
                
                if ($amount > 0) {
                    $freightBooking = FreightBooking::findOrFail($bookingId);
                    
                    // Create payment detail
                    \App\Models\PaymentDetail::create([
                        'payment_id' => $payment->id,
                        'freight_booking_id' => $bookingId,
                        'amount' => $amount,
                        'created_by' => auth()->id()
                    ]);
                    
                    // Update freight booking balance
                    $newBalance = max(0, $freightBooking->balance_amount - $amount);
                    $freightBooking->update([
                        'balance_amount' => $newBalance,
                        'is_paid' => ($newBalance == 0) ? 1 : 0
                    ]);
                }
            }
            
            \DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk payment processed successfully',
                'payment_id' => $payment->id
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Bulk payment error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history for selected freight bookings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $validated = $request->validate([
                'freight_booking_ids' => 'required|array',
                'freight_booking_ids.*' => 'required|integer|exists:freight_bookings,id'
            ]);

            $payments = \App\Models\VoucherPayment::with([
                'freight_booking.lr',
                'freight_booking.transporter'
            ])
            ->whereIn('freight_booking_id', $validated['freight_booking_ids'])
            ->orderBy('created_at', 'desc')
            ->get();

            \Log::info('Payment history query result:', [
                'freight_booking_ids' => $validated['freight_booking_ids'],
                'payments_count' => $payments->count(),
                'payments' => $payments->toArray()
            ]);

            return response()->json([
                'success' => true,
                'payments' => $payments
            ]);

        } catch (\Exception $e) {
            \Log::error('Payment history fetch error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk assign memo ID to lorry receipts and update freight bookings
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkMemoId(Request $request)
    {
        try {
            $validated = $request->validate([
                'memo_id' => 'required|string|max:255',
                'lr_ids' => 'required|array',
                'lr_ids.*' => 'required|integer|exists:lorry_receipts,id',
                'freight_booking_ids' => 'required|array',
                'freight_booking_ids.*' => 'required|integer|exists:freight_bookings,id'
            ]);

            \DB::transaction(function () use ($validated) {
                // Update memo_id and delivery_status for lorry receipts
                \App\Models\LorryReceipt::whereIn('id', $validated['lr_ids'])
                    ->update([
                        'memo_id' => $validated['memo_id'],
                        'delivery_status' => 'Delivered'
                    ]);

                // Update is_delivered for freight bookings
                FreightBooking::whereIn('id', $validated['freight_booking_ids'])
                    ->update(['is_delivered' => 1]);

                \Log::info('Bulk memo ID assignment completed', [
                    'memo_id' => $validated['memo_id'],
                    'lr_ids' => $validated['lr_ids'],
                    'freight_booking_ids' => $validated['freight_booking_ids']
                ]);
            });

            return redirect()->route('freight_bookings.index')
                ->with('success', 'Memo ID assigned successfully to ' . count($validated['lr_ids']) . ' lorry receipts and ' . count($validated['freight_booking_ids']) . ' freight bookings updated.');

        } catch (\Exception $e) {
            \Log::error('Bulk memo ID assignment error: ' . $e->getMessage());
            
            return redirect()->back()
                ->withErrors(['general' => 'An error occurred while assigning memo ID: ' . $e->getMessage()]);
        }
    }
}
