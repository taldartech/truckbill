<?php
namespace App\Http\Controllers;

use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use Illuminate\Http\Request;
use App\Helpers\TdsHelper;
use Yajra\DataTables\Facades\DataTables;

class FreightBookingController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getFreightBookingsData($request);
        }

        $query = FreightBooking::with(['lr', 'linkedFreightBooking', 'company']);

        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('company_id', auth()->user()->company_id);
        }
        if ($request->filled('lr_id')) {
            $query->where('lr_id', $request->lr_id);
        }
        if ($request->filled('is_delivered')) {
            $query->where('is_delivered', $request->is_delivered);
        }
        if ($request->filled('is_cancelled')) {
            $query->where('is_cancelled', $request->is_cancelled);
        }
        $bookings = $query->orderByDesc('id')->paginate(20);
        $lrs = LorryReceipt::all();
        return view('freight_bookings.index', compact('bookings', 'lrs'));
    }

    public function getFreightBookingsData(Request $request)
    {
        try {
            $query = FreightBooking::with(['lr', 'linkedFreightBooking', 'company'])
                ->leftJoin('lorry_receipts', 'freight_bookings.lr_id', '=', 'lorry_receipts.id')
                ->select('freight_bookings.*');

            // Only apply company_id filter for non-super_admin users
            if (auth()->user()->userRole->name !== 'super_admin') {
                $query->where('freight_bookings.company_id', auth()->user()->company_id);
            }

            return DataTables::of($query)
                ->addColumn('lr_number', function ($row) {
                    return $row->lr ? $row->lr->lr_number : '-';
                })
                ->addColumn('voucher_number', function ($row) {
                    return $row->voucher_number ?? '-';
                })
                ->addColumn('tr_rate_formatted', function ($row) {
                    return $row->tr_rate ? '₹' . number_format($row->tr_rate, 2) : '₹0.00';
                })
                ->addColumn('tr_amount_formatted', function ($row) {
                    return $row->tr_amount ? '₹' . number_format($row->tr_amount, 2) : '₹0.00';
                })
                ->addColumn('advance_formatted', function ($row) {
                    return $row->advance ? '₹' . number_format($row->advance, 2) : '₹0.00';
                })
                ->addColumn('balance_formatted', function ($row) {
                    $balance = ($row->tr_amount ?? 0) - ($row->advance ?? 0);
                    return '₹' . number_format($balance, 2);
                })
                ->addColumn('gr_date_formatted', function ($row) {
                    return $row->lr && $row->lr->gr_date ? date('d-m-Y', strtotime($row->lr->gr_date)) : '-';
                })
                ->addColumn('is_delivered_badge', function ($row) {
                    if ($row->is_delivered == '1') {
                        return '<span class="badge bg-success">Yes</span>';
                    } else {
                        return '<span class="badge bg-warning">No</span>';
                    }
                })
                ->addColumn('is_cancelled_badge', function ($row) {
                    if ($row->is_cancelled == '1') {
                        return '<span class="badge bg-danger">Yes</span>';
                    } else {
                        return '<span class="badge bg-secondary">No</span>';
                    }
                })
                ->addColumn('actions', function ($row) {
                    return $this->getActionButtons($row);
                })
                ->filterColumn('lr_number', function ($query, $keyword) {
                    $query->whereHas('lr', function ($q) use ($keyword) {
                        $q->where('lr_number', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('lr_number', 'lorry_receipts.lr_number $1')
                ->rawColumns(['is_delivered_badge', 'is_cancelled_badge', 'actions'])
                ->make(true);
        } catch (\Exception $e) {
            \Log::error('DataTables error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getActionButtons($freightBooking)
    {
        $actions = '<div class="btn-group" role="group">';
        
        // View button
        $actions .= '<a href="' . route('freight_bookings.show', $freightBooking->id) . '" class="btn btn-sm btn-outline-info" title="View">';
        $actions .= '<i class="bi bi-eye"></i>';
        $actions .= '</a>';
        
        // Edit button
        $actions .= '<a href="' . route('freight_bookings.edit', $freightBooking->id) . '" class="btn btn-sm btn-outline-primary" title="Edit">';
        $actions .= '<i class="bi bi-pencil"></i>';
        $actions .= '</a>';
        
        // Delete button
        $actions .= '<form method="POST" action="' . route('freight_bookings.destroy', $freightBooking->id) . '" style="display: inline;">';
        $actions .= csrf_field();
        $actions .= method_field('DELETE');
        $actions .= '<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm(\'Are you sure you want to delete this freight booking?\')">';
        $actions .= '<i class="bi bi-trash"></i>';
        $actions .= '</button>';
        $actions .= '</form>';
        
        $actions .= '</div>';
        
        return $actions;
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

        $lorry_receipt = \App\Models\LorryReceipt::with(['transporter:id,name', 'truck:id,truck_number'])
        ->select('id', 'lr_number', 'transporter_id', 'truck_id','customer_id','consignor_id','consignee_id','good_type')
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
                
                return view('freight_bookings.create', [
                    'companies' => $companies,
                    'lrs' => $lrs,
                    'linkedBookings' => $linkedBookings,
                    'goodsTypes' => $goodsTypes,
                    'lorry_receipt' => $lorry_receipt,
                    
                    'transporter' => $transporter ?? null,
                    'tdsPercentages' => $tdsPercentages,
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

    public function store(Request $request)
    {
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
        
        $freightBooking = FreightBooking::create(array_merge($data, [
            'lr_number' => $lorryReceipt->lr_number,
            'voucher_number' => $nextVoucherNumber,
            'pan_verified' => (bool)$data['pan_verified'],
            'company_id' => $data['company_id']
        ]));
        
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

    public function edit(FreightBooking $freight_booking)
    {
        // Get the LorryReceipt details
        $lorryReceipt = LorryReceipt::find($freight_booking->lr_id);
        
        // Get the transporter details
        $transporter = \App\Models\Transporter::find($lorryReceipt->transporter_id);
        
        // Get the truck details
        $truck = \App\Models\Truck::find($lorryReceipt->truck_id);
        
        // Get all LRs
        $lrs = LorryReceipt::all();
        
        // Get goods types
        $goodsTypes = \App\Models\GoodType::all();
        
        // Get linked bookings
        $linkedBookings = FreightBooking::where('id', '!=', $freight_booking->id)->get();
        
        return view('freight_bookings.edit', [
            'freight_booking' => $freight_booking,
            'lrs' => $lrs,
            'linkedBookings' => $linkedBookings,
            'goodsTypes' => $goodsTypes,
            'transporter' => $transporter,
            'truck' => $truck,
            'preFilled' => [
                'lr_number' => $freight_booking->lr ? $freight_booking->lr->lr_number : '',
                'lr_id' => $freight_booking->lr_id,
                'transporter_id' => $freight_booking->lr ? $freight_booking->lr->transporter_id : '',
                'transporter_name' => $transporter ? $transporter->name : '',
                'truck_id' => $freight_booking->lr ? $freight_booking->lr->truck_id : '',
                'truck_number' => $truck ? $truck->truck_number : '',
                'pan_verified' => $transporter ? $transporter->pan_active : 0,
                'is_owner' => $transporter ? $transporter->is_owner : 0,
            ]
        ]);
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

    public function update(Request $request, FreightBooking $freight_booking)
    {
        $data = $request->validate([
            'lr_id' => 'required|exists:lorry_receipts,id',
            'tr_fr_rate' => 'required|numeric',
            'tr_amount' => 'required|numeric',
            'advance_amount' => 'required|numeric',
            'balance_amount' => 'required|numeric',
            'tds_amount' => 'nullable|numeric',
            'shortage' => 'nullable|numeric',
            'good_type' => 'nullable|string',
            'detention' => 'nullable|numeric',
            'deviation' => 'nullable|numeric',
            'misc_expense' => 'nullable|numeric',
            'damage_qty' => 'nullable|numeric',
            'damage_rate' => 'nullable|numeric',
            'damage_amount' => 'nullable|numeric',
            'shortage_qty' => 'nullable|numeric',
            'shortage_rate' => 'nullable|numeric',
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

        // Update freight booking with lorry receipt's LR number
        $freight_booking->update(array_merge($data, [
            'lr_number' => $lorryReceipt->lr_number
        ]));

        // Update lorry receipt status
        if ($lorryReceipt) {
            $deliveryStatus = 'Pending';
            if ($data['is_cancelled'] === '1') {
                $deliveryStatus = 'Cancelled';
            } elseif ($data['is_delivered'] === '1') {
                $deliveryStatus = 'Delivered';
            }
            $lorryReceipt->delivery_status = $deliveryStatus;
            $lorryReceipt->damage_amount = $freight_booking->damage_amount;
            $lorryReceipt->shortage_amount = $freight_booking->shortage_amount;
            $lorryReceipt->save();
        }

        return redirect()->route('freight_bookings.index')->with('success', 'Freight Booking updated successfully.');
    }

    public function destroy(FreightBooking $freight_booking)
    {
        $freight_booking->delete();
        return redirect()->route('freight_bookings.index')->with('success', 'Freight Booking deleted.');
    }
}
