<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Truck;
use App\Models\Transporter;
use App\Models\Customer;
use App\Models\Consignee;
use App\Models\GoodType;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\AdvancePaymentController;

class LorryReceiptController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        // dd($request->all());
        if ($request->ajax()) {
            return $this->getLorryReceiptsData($request);
        }

        $statuses = ['Pending', 'Delivered', 'Cancelled','In-Progress'];
        $customers = \App\Models\Consignee::all();
        $query = \App\Models\LorryReceipt::with(['truck', 'transporter', 'consignor', 'consignee', 'company','customer','freightBooking']);

        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('company_id', auth()->user()->company_id);
        }

        if ($request->customer_id || $request->customer) {
            $query->where('customer_id', $request->customer_id ?? $request->customer);
        }

        if ($request->truck_id) {
            $query->where('truck_id', $request->truck_id);
        }

        if ($request->transporter_id) {
            $query->where('transporter_id', $request->transporter_id);
        }

        if ($request->delivery_status) {
            $query->where('delivery_status', $request->delivery_status);
        }

        if ($request->gr_date_from) {
            $query->where('gr_date', '>=', $request->gr_date_from);
        }

        if ($request->gr_date_to) {
            $query->where('gr_date', '<=', $request->gr_date_to);
        }

        if ($request->truck_number) {
            $query->whereHas('truck', function($q) use ($request) {
                $q->where('truck_number', 'like', '%' . $request->truck_number . '%');
            });
        }

        $lrs = $query->orderBy('id', 'desc')->paginate(10);

        // Get payment types and payment modes
        $paymentTypes = \App\Models\PaymentType::all();
        $paymentModes = \App\Models\PaymentMode::all();
        $customers = Consignee::where('company_id', auth()->user()->company_id)->orderBy('name')->get();

    //     // Handle voucher payment submission
    //     if ($request->isMethod('post')) {
    // //   dd("heelo");
           
    //         try {
    //             // Get all required inputs
    //             $voucherNumber = $request->input('voucher_number');
    //             $paymentType = $request->input('payment_type');
    //             $paymentMode = $request->input('payment_mode');
    //             $amount = $request->input('amount', 0);
    //             $paymentStatus = $request->input('payment_status');
    //             $selectedLRs = $request->input('lr_numbers', []);

    //             // Validate inputs
    //             if (empty($selectedLRs)) {
    //                 return redirect()->back()->with('error', 'Please select at least one Lorry Receipt');
    //             }

    //             if (!$voucherNumber) {
    //                 return redirect()->back()->with('error', 'Please enter voucher number');
    //             }

    //             if (!$paymentType) {
    //                 return redirect()->back()->with('error', 'Please select payment type');
    //             }

    //             if (!$paymentMode) {
    //                 return redirect()->back()->with('error', 'Please select payment mode');
    //             }

    //             if ($amount <= 0) {
    //                 return redirect()->back()->with('error', 'Please enter valid payment amount');
    //             }

    //             // Get the freight booking ID from the first selected LR
    //             $firstLR = \App\Models\LorryReceipt::where('lr_number', $selectedLRs[0])->first();
    //             if (!$firstLR || !$firstLR->freight_booking_id) {
    //                 return redirect()->back()->with('error', 'Please create Freight Booking first');
    //             }

    //             $freightBooking = \App\Models\FreightBooking::findOrFail($firstLR->freight_booking_id);

    //             // Validate payment amount
    //             if ($freightBooking->advance_amount + $amount > $freightBooking->tr_amount) {
    //                 $remaining = $freightBooking->tr_amount - $freightBooking->advance_amount;
    //                 return redirect()->back()->with('error', 'Payment amount exceeds remaining balance. Remaining amount: ' . number_format($remaining, 2));
    //             }

    //             // Create voucher payment with all required fields
    //             $voucherPayment = \App\Models\VoucherPayment::create([
    //                 'freight_booking_id' => $freightBooking->id,
    //                 'voucher_number' => $voucherNumber,
    //                 'payment_type' => $paymentType,
    //                 'payment_mode' => $paymentMode,
    //                 'amount' => $amount,
    //                 'reference_transaction_id' => $request->input('reference_transaction_id', null),
    //                 'remark' => $request->input('remark', null),
    //                 'payment_status' => $paymentStatus,
    //                 'company_id' => auth()->user()->company_id,
    //             ]);

    //             if (!$voucherPayment) {
    //                 throw new \Exception('Failed to create voucher payment');
    //             }

    //             // Update freight booking
    //             $freightBooking->advance_amount += $amount;
    //             $freightBooking->balance_amount = $freightBooking->tr_amount - $freightBooking->advance_amount;
                
    //             if (!$freightBooking->save()) {
    //                 throw new \Exception('Failed to update freight booking');
    //             }
        return view('lorry_receipts.index', [
            'lrs' => $lrs,
            'statuses' => $statuses,
            'paymentTypes' => $paymentTypes,
            'paymentModes' => $paymentModes,
            'customers' => $customers,
        ]);
    }

    public function getLorryReceiptsData(Request $request)
    {
        try {
            \Log::info('DataTables request started');
            
            $query = LorryReceipt::with(['truck', 'transporter', 'consignor', 'consignee', 'company', 'customer']);

            // Only apply company_id filter for non-super_admin users
            if (auth()->user()->userRole->name !== 'super_admin') {
                $query->where('company_id', auth()->user()->company_id);
            }

            \Log::info('Query built successfully');

            return DataTables::of($query)
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="rowCheckbox" value="' . $row->id . '" ' .
                    'data-lr="' . $row->lr_number . '" ' .
                    'data-truck="' . ($row->truck ? $row->truck->truck_number : '') . '" ' .
                    'data-freight_booking_id="' . $row->freight_booking_id . '" ' .
                    'data-balance="' . ($row->freightBooking ? $row->freightBooking->balance_amount : 0) . '" ' .
                    'data-voucher_number="' . ($row->freightBooking ? $row->freightBooking->voucher_number : 0) . '" ' .
                    'data-payment-status="' . strtolower($row->delivery_status) . '">';
            })
           
            ->addColumn('sr_no', function ($row) {
                static $counter = 0;
                return ++$counter;
            })
            ->addColumn('customer_name', function ($row) {
                return $row->customer ? $row->customer->name : '-';
            })
                ->addColumn('consignor_name', function ($row) {
                    return $row->consignor ? $row->consignor->name : '-';
                })
                ->addColumn('consignee_name', function ($row) {
                    return $row->consignee ? $row->consignee->name : '-';
                })
                ->addColumn('truck_number', function ($row) {
                    return $row->truck ? $row->truck->truck_number : '-';
                })
                ->addColumn('gr_date_formatted', function ($row) {
                    return $row->gr_date ? date('d-m-Y', strtotime($row->gr_date)) : '-';
                })
                ->addColumn('amount_formatted', function ($row) {
                    return $row->amount ? '₹' . number_format($row->amount, 2) : '₹0.00';
                })
                ->addColumn('status_badge', function ($row) {
                    $statusClass = match($row->delivery_status) {
                        'Pending' => 'warning',
                        'Delivered' => 'success',
                        'Cancelled' => 'danger',
                        'In-Progress' => 'info',
                        default => 'secondary'
                    };
                    return '<span class="badge bg-' . $statusClass . '">' . ($row->delivery_status ?? 'Unknown') . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    return $this->getActionButtons($row);
                })
                ->rawColumns(['checkbox', 'sr_no', 'lr_number', 'customer_name', 'consignor_name', 'consignee_name', 'truck_number', 'gr_date_formatted', 'amount_formatted', 'status_badge', 'actions'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('DataTables error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getActionButtons($lorryReceipt)
    {
        $buttons = '<div class="d-flex gap-1">';
        
        // Freight booking button
        $buttons .= '<a href="' . route('freight_bookings.create', $lorryReceipt->id) . '" class="btn btn-sm btn-success" title="Freight Booking" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-truck"></i>';
        $buttons .= '</a>';
        
        // Edit button
        $editUrl = route('lorry_receipts.edit', $lorryReceipt->id);
        $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info" title="Edit" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-pencil"></i></a>';
        
        // Delete button
        $deleteUrl = route('lorry_receipts.destroy', $lorryReceipt->id);
        $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this lorry receipt?\')">';
        $buttons .= csrf_field() . method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete" data-bs-toggle="tooltip">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';
        
        $buttons .= '</div>';
        
        return $buttons;
    }

    public function create()
    {
        $companies = \App\Models\Company::all();
        // $trucks = Truck::all();
        $trucks = Truck::with(['transporter', 'driver'])->get(); // Load both transporter and driver relationships

        $transporters = Transporter::all();
        $truckOwners = Transporter::where('is_owner', 1)->get();
        $agencies = Transporter::where('is_owner', 0)->get();
        $customers = Customer::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $consignees = Consignee::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $goodsTypes = GoodType::all();
        $statuses = ['Pending', 'Delivered', 'Cancelled','In-Progress'];
        $tdsPercentages = [
            'default' => env('TDS_PERCENTAGE_DEFAULT', 20),
            'agency' => env('TDS_PERCENTAGE_AGENCY', 2),
            'owner' => env('TDS_PERCENTAGE_OWNER', 1)
        ];

        
        return view('lorry_receipts.create', compact('companies', 'customers','trucks', 'transporters', 'consignees', 'goodsTypes', 'statuses','truckOwners','agencies','tdsPercentages'));
    }
    public function getTdsInfo($id)
{
    $transporter = Transporter::findOrFail($id);

    return response()->json([
        'pan_active' => $transporter->pan_active,
        'is_owner' => $transporter->is_owner,
        'declaration_given' => $transporter->declaration_given,
    ]);
}


    public function bulkPayment(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'memo_id' => 'required|integer',
                'lr_ids' => 'required|array',
                'lr_ids.*' => 'integer|exists:lorry_receipts,id'
            ]);

            // Get the lorry receipts that already have memo_id
            $existingMemoIds = LorryReceipt::whereIn('id', $validated['lr_ids'])
                ->whereNotNull('memo_id')
                ->pluck('lr_number')
                ->toArray();

            if (!empty($existingMemoIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The following LR numbers already have memo_id: ' . implode(', ', $existingMemoIds)
                ], 422);
            }

            // Check freight_booking_id for selected lorry receipts
            $lorryReceipts = LorryReceipt::whereIn('id', $validated['lr_ids'])
                ->get();

            $noFreightBooking = $lorryReceipts->filter(function($lr) {
                return !$lr->freight_booking_id;
            })->pluck('lr_number')->toArray();

            if (!empty($noFreightBooking)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The following LR numbers do not have a freight booking: ' . implode(', ', $noFreightBooking)
                ], 422);
            }

              // ✅ Step: Ensure all selected LRs belong to same customer
        $customerIds = $lorryReceipts->pluck('customer_id')->unique();

        if ($customerIds->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Selected LRs belong to different customers. Please select LRs from the same customer only.'
            ], 422);
        }

        $customerId = $customerIds->first();

         // ✅ Step: Ensure memo_id is not used by another customer
         $memoUsedByOtherCustomer = LorryReceipt::where('memo_id', $validated['memo_id'])
         ->where('customer_id', '!=', $customerId)
         ->exists();

     if ($memoUsedByOtherCustomer) {
         return response()->json([
             'success' => false,
             'message' => 'This memo ID is already used by another customer. Please choose a unique one.'
         ], 422);
     }

            // Get freight booking IDs
            $freightBookingIds = $lorryReceipts->pluck('freight_booking_id')->toArray();

            // Update is_delivered for freight bookings
            \App\Models\FreightBooking::whereIn('id', $freightBookingIds)
                ->update(['is_delivered' => 1]);

            // Update delivery status for lorry receipts
            LorryReceipt::whereIn('id', $validated['lr_ids'])
                ->update(['delivery_status' => 'Delivered']);

            // Log the request data for debugging
            \Log::info('Bulk Payment Request:', [
                'memo_id' => $validated['memo_id'],
                'lr_ids' => $validated['lr_ids'],
                'freight_booking_ids' => $freightBookingIds
            ]);

            // Update memo_id for selected lorry receipts
            $updatedCount = LorryReceipt::whereIn('id', $validated['lr_ids'])
                ->update(['memo_id' => $validated['memo_id']]);

            \Log::info('Bulk Payment Update Result:', [
                'updated_count' => $updatedCount,
                'ids' => $validated['lr_ids']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Memo ID updated successfully for selected lorry receipts'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Bulk Payment Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Memo ID: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'customer_id' => 'required|exists:consignees,id',
            'lr_number' => 'required|string|max:100|unique:lorry_receipts,lr_number',
            'memo_id' => 'nullable|integer',
            'truck_id' => 'nullable|exists:trucks,id',
            'transporter_id' => 'nullable|exists:transporters,id',
            'consignor_id' => 'required|exists:consignees,id',
            'consignor_contact_number' => 'nullable|string|max:20',
            'consignee_id' => 'required|exists:consignees,id',
            'consignee_contact_number' => 'nullable|string|max:20',
            'gr_date' => 'required|date',
            'company_id' => 'required|exists:companies,id',
        
            'delivery_status' => 'nullable|in:Pending,Delivered,Cancelled,In-Progress',
            'source_city' => 'required|string|max:100',
            'source_state' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'destination_state' => 'required|string|max:100',
            'lane' => 'required|string|max:100',
            'good_type' => 'required|string|max:100',
            'weight' => 'required|numeric|min:0',
            'no_of_units' => 'nullable|numeric|min:1|max:100000',
            'fr_rate' => 'required|numeric|min:1|max:20000',
            'amount' => 'required|numeric|min:0',
            'damage_amount' => 'nullable|numeric',
            'shortage_amount' => 'nullable|numeric',
            'transporter_type' => 'nullable|in:owner,agency',
        ]);
        // dd($validated);

        // Check if consignor and consignee are the same
        if ($validated['consignor_id'] === $validated['consignee_id']) {
            return redirect()->back()->withErrors([
                'consignor_id' => 'Consignor and Consignee cannot be the same.',
                'consignee_id' => 'Consignor and Consignee cannot be the same.'
            ])->withInput();
        }

        // Set truck_id to null if not provided
        $validated['truck_id'] = $validated['truck_id'] ?? null;
        // dd($validated);
        
        // Set default delivery_status
        $validated['delivery_status'] = 'Pending';
        // dd($validated);

        
        // Check if lr_number already exists
        if (\App\Models\LorryReceipt::where('lr_number', $validated['lr_number'])->exists()) {
            return redirect()->back()->withErrors(['lr_number' => 'LR Number already exists. Please use a unique LR Number.']);
        }

        // Note: Amount field is auto-calculated but user can override the value
        // No validation check needed - store whatever amount the user enters

        // Add company_id to validated data
        $validated['company_id'] = auth()->user()->company_id;
        // dd($validated);

        
        \App\Models\LorryReceipt::create($validated);
        // dd($validated);
        return redirect()->route('lorry_receipts.index')->with('success', 'Lorry Receipt created successfully!');
    }

    public function checkLrNumberUniqueness(Request $request)
    {
        $lrNumber = $request->input('lr_number');
        
        if (!$lrNumber) {
            return response()->json(['valid' => false, 'message' => 'LR Number is required']);
        }
        
        $exists = \App\Models\LorryReceipt::where('lr_number', $lrNumber)->exists();
        
        if ($exists) {
            return response()->json([
                'valid' => false, 
                'message' => 'LR Number already exists. Please use a unique LR Number.'
            ]);
        }
        
        return response()->json([
            'valid' => true, 
            'message' => 'LR Number is available'
        ]);
    }

    public function edit(\App\Models\LorryReceipt $lorry_receipt)
    {
        // Eager load all necessary relationships
        $lorry_receipt = $lorry_receipt->load(['truck.transporter', 'truck.driver', 'customer', 'transporter', 'consignor', 'consignee']);
        
        $companies = \App\Models\Company::all();
        $trucks = Truck::with(['transporter', 'driver'])->get(); // Load both transporter and driver relationships

        $transporters = Transporter::all();
        $truckOwners = Transporter::where('is_owner', 1)->get();
        $agencies = Transporter::where('is_owner', 0)->get();
        $customers = Customer::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $consignees = Consignee::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $goodsTypes = GoodType::all();
        $statuses = ['Pending', 'Delivered', 'Cancelled','In-Progress'];
        $tdsPercentages = [
            'default' => env('TDS_PERCENTAGE_DEFAULT', 20),
            'agency' => env('TDS_PERCENTAGE_AGENCY', 2),
            'owner' => env('TDS_PERCENTAGE_OWNER', 1)
        ];
        $statuses = ['Pending', 'Delivered', 'Cancelled','In-Progress'];
        
        return view('lorry_receipts.edit', [
            'lorry_receipt' => $lorry_receipt,
            'trucks' => $trucks,
            'transporters' => $transporters,
            'statuses' => $statuses,
            'customers' => $customers,
            'consignors' => $consignees, // Using consignees data for consignors dropdown
            'consignees' => $consignees,
            'goodsTypes' => $goodsTypes,
            'truckOwners' => $truckOwners,
            'agencies' => $agencies,
            'tdsPercentages' => $tdsPercentages,
        ]);
    }

    public function update(Request $request, \App\Models\LorryReceipt $lorry_receipt)
    {
        // $validated = $request->validate([
        //     'truck_id' => 'nullable|exists:trucks,id',
        //     'transporter_id' => 'nullable|exists:transporters,id',
        //     'delivery_status' => 'nullable|in:Pending,Delivered,Cancelled,In-Progress',
        //     'customer_id' => 'required|integer',
        //     'consignor_contact_number' => [
        //         'nullable',
        //         'digits:10',
        //     ],
        //     'consignee_contact_number' => [
        //         'nullable',
        //         'digits:10',
        //     ],
        //     'source_city' => 'required|string|max:100',
        //     'destination_city' => 'required|string|max:100',
        //     'source_state' => 'required|string|max:100',
        //     'destination_state' => 'required|string|max:100',
        //     'weight' => 'required|numeric',
        //     'no_of_units' => 'required|integer',
        //     'good_type' => 'required|string|max:100',
        //     'fr_rate' => 'required|numeric',
        //     'amount' => 'required|numeric',
        //     'linked_lr_id' => 'nullable|integer',
        //     'gr_date' => 'required|date',
        //     'memo_id' => 'nullable|integer',
        //     'consignor_id' => 'required|integer',
        //     'consignee_id' => 'required|integer',
        //     'lane' => 'required|string',
        //     'damage_amount' => 'nullable|numeric',
        //     'shortage_amount' => 'nullable|numeric',
        //     'lr_number' => 'required|string|max:100',
        // ]);
        // $lorry_receipt->update($validated);
        $validated = $request->validate([
            'customer_id' => 'required|exists:consignees,id',
           'lr_number' => 'required|string|max:100|unique:lorry_receipts,lr_number,' . $lorry_receipt->id . ',id',
            'memo_id' => 'nullable|integer',
            'truck_id' => 'nullable|exists:trucks,id',
            'transporter_id' => 'nullable|exists:transporters,id',
            'consignor_id' => 'required|exists:consignees,id',
            'consignor_contact_number' => 'nullable|string|max:20',
            'consignee_id' => 'required|exists:consignees,id',
            'consignee_contact_number' => 'nullable|string|max:20',
            'gr_date' => 'required|date',
            'company_id' => 'required|exists:companies,id',
        
            'delivery_status' => 'nullable|in:Pending,Delivered,Cancelled,In-Progress',
            'source_city' => 'required|string|max:100',
            'source_state' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'destination_state' => 'required|string|max:100',
            'lane' => 'required|string|max:100',
            'good_type' => 'required|string|max:100',
            'weight' => 'required|numeric|min:0',
            'no_of_units' => 'nullable|numeric|min:1|max:100000',
            'fr_rate' => 'required|numeric|min:1|max:20000',
            'amount' => 'required|numeric|min:0',
            'damage_amount' => 'nullable|numeric',
            'shortage_amount' => 'nullable|numeric',
            'transporter_type' => 'nullable|in:owner,agency',
        ]);

        // Check if consignor and consignee are the same
        if ($validated['consignor_id'] === $validated['consignee_id']) {
            return redirect()->back()->withErrors([
                'consignor_id' => 'Consignor and Consignee cannot be the same.',
                'consignee_id' => 'Consignor and Consignee cannot be the same.'
            ])->withInput();
        }

        // Set truck_id to null if not provided
        $validated['truck_id'] = $validated['truck_id'] ?? null;
        
        // Set default delivery_status
        $validated['delivery_status'] = 'Pending';
        
        // Check if lr_number already exists
        // if (\App\Models\LorryReceipt::where('lr_number', $validated['lr_number'])->exists()) {
        //     return redirect()->back()->withErrors(['lr_number' => 'LR Number already exists. Please use a unique LR Number.']);
        // }
         // Calculate expected amount from weight and freight rate
        //  $expectedAmount = ($validated['weight'] * $validated['fr_rate'])->round(2);
        $expectedAmount = round($validated['weight'] * $validated['fr_rate'], 2);
        
         // Check if entered amount matches calculated amount
         if ($validated['amount'] != $expectedAmount) {
             return redirect()->back()->withErrors([
                 'amount' => 'The entered amount does not match the calculated amount. ' . 
                            'Expected amount is: ' . $expectedAmount . 
                            ' (Weight: ' . $validated['weight'] . ' × FR Rate: ' . $validated['fr_rate'] . ')'
             ])->withInput();
         }
 

        // Add company_id to validated data
        $validated['company_id'] = auth()->user()->company_id;
        
        $lorry_receipt->update($validated);
        return redirect()->route('lorry_receipts.index')->with('success', 'Lorry Receipt updated successfully!');
    }

    public function destroy(\App\Models\LorryReceipt $lorry_receipt)
    {
        $lorry_receipt->delete();
        return redirect()->route('lorry_receipts.index')->with('success', 'Lorry Receipt deleted successfully!');
    }

    public function details($id)
    {
        $lorry_receipt = \App\Models\LorryReceipt::with([
            'truck',
            'transporter',
            'consignor',
            'consignee',
            'customer' // Add customer relationship
        ])->findOrFail($id);

        return response()->json($lorry_receipt);
    }

    /**
     * Process bulk voucher payments for multiple lorry receipts
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkVoucherPayment(Request $request)
    {
        try {
            // Log the incoming request for debugging
            \Log::info('Bulk voucher payment request received:', [
                'request_data' => $request->all()
            ]);

            // Validate the request structure
            $validated = $request->validate([
                'payments' => 'required|array',
                'payments.*.lorry_receipt_id' => 'required|integer|exists:lorry_receipts,id',
                'payments.*.freight_booking_id' => 'required|integer|exists:freight_bookings,id',
                'payments.*.amount' => 'required|numeric|min:0.01',
                'payments.*.voucher_number' => 'required|string'
            ]);

            $payments = $validated['payments'];
            $successCount = 0;
            $errors = [];

            // Process each payment
            foreach ($payments as $index => $payment) {
                try {
                    // Get the lorry receipt and check company ownership
                    $lorryReceipt = \App\Models\LorryReceipt::findOrFail($payment['lorry_receipt_id']);
                    
                    // Security check: Ensure user can only process payments for their company
                    if (auth()->user()->userRole->name !== 'super_admin' && 
                        $lorryReceipt->company_id !== auth()->user()->company_id) {
                        $errors[] = "Security violation: LR #{$lorryReceipt->lr_number} does not belong to your company";
                        continue;
                    }

                    // Get the freight booking
                    $freightBooking = \App\Models\FreightBooking::findOrFail($payment['freight_booking_id']);
                    
                    // Verify freight booking belongs to the lorry receipt
                    if ($freightBooking->lorry_receipt_id != $lorryReceipt->id) {
                        $errors[] = "Freight booking #{$payment['freight_booking_id']} does not belong to LR #{$lorryReceipt->lr_number}";
                        continue;
                    }

                    // Validate payment amount doesn't exceed remaining balance
                    $remainingBalance = $freightBooking->tr_amount - $freightBooking->advance_amount;
                    if ($payment['amount'] > $remainingBalance) {
                        $errors[] = "Payment amount ₹{$payment['amount']} exceeds remaining balance ₹{$remainingBalance} for LR #{$lorryReceipt->lr_number}";
                        continue;
                    }

                    // Create voucher payment record
                    $voucherPayment = \App\Models\VoucherPayment::create([
                        'freight_booking_id' => $freightBooking->id,
                        'voucher_number' => $payment['voucher_number'],
                        'payment_type' => 'voucher', // Default payment type
                        'payment_mode' => 'cash',    // Default payment mode
                        'amount' => $payment['amount'],
                        'payment_status' => 'delivered',
                        'company_id' => $lorryReceipt->company_id
                    ]);

                    // Update freight booking advance and balance amounts
                    $freightBooking->advance_amount += $payment['amount'];
                    $freightBooking->balance_amount = $freightBooking->tr_amount - $freightBooking->advance_amount;
                    $freightBooking->save();

                    \Log::info("Voucher payment processed successfully", [
                        'lr_id' => $lorryReceipt->id,
                        'lr_number' => $lorryReceipt->lr_number,
                        'freight_booking_id' => $freightBooking->id,
                        'amount' => $payment['amount'],
                        'new_advance_amount' => $freightBooking->advance_amount,
                        'new_balance_amount' => $freightBooking->balance_amount
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    \Log::error("Error processing payment for LR #{$payment['lorry_receipt_id']}", [
                        'error' => $e->getMessage(),
                        'payment' => $payment
                    ]);
                    $errors[] = "Error processing payment for LR #{$payment['lorry_receipt_id']}: {$e->getMessage()}";
                }
            }

            // Return appropriate response
            if ($successCount > 0 && empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully processed {$successCount} voucher payments"
                ]);
            } elseif ($successCount > 0 && !empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => "Processed {$successCount} payments with {count($errors)} errors",
                    'errors' => $errors
                ], 207); // 207 Multi-Status
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process voucher payments',
                    'errors' => $errors
                ], 422);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Bulk voucher payment validation error:', [
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Bulk voucher payment error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing voucher payments: ' . $e->getMessage()
            ], 500);
        }
    }
}
