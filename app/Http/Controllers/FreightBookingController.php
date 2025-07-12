<?php
namespace App\Http\Controllers;

use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use Illuminate\Http\Request;

class FreightBookingController extends Controller
{
    public function index(Request $request)
    {
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

    public function create(Request $request)
    {
        $lrs = LorryReceipt::all();
        $linkedBookings = FreightBooking::all();
        $goodsTypes = \App\Models\GoodType::all();
        $lorry_receipt_id = $request->lorry_receipt_id;

 
        $lorry_receipt = \App\Models\LorryReceipt::with(['transporter:id,name', 'truck:id,truck_number'])
        ->select('id', 'lr_number', 'transporter_id', 'truck_id','customer_id','consignor_id','consignee_id')
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
                    'lorry_receipt' => $lorry_receipt,
                    'lrs' => $lrs,
                    'linkedBookings' => $linkedBookings,
                    'goodsTypes' => $goodsTypes,
                    'preFilled' => [
                        'lr_number' => $lr->lr_number,
                        'lr_id' => $lr->id,
                        'transporter_id' => $lr->transporter_id,
                        'transporter_name' => $transporter ? $transporter->name : '',
                        'truck_id' => $lr->truck_id,
                        'truck_number' => $truck ? $truck->truck_number : '',
                        'pan_verified' => $transporter ? $transporter->pan_active : 0,
                        'is_owner' => $transporter ? $transporter->is_owner : 0,
                    ]
                ]);
            }
        }

        return view('freight_bookings.create', compact('lrs', 'linkedBookings', 'goodsTypes', 'lorry_receipt'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'lr_id' => 'required|exists:lorry_receipts,id',
            'tr_fr_rate' => 'required|numeric',
            'tr_amount' => 'required|numeric',
            'advance_amount' => 'required|numeric',
            'balance_amount' => 'required|numeric',
            'tds_amount' => 'nullable|numeric',
            
            'good_type' => 'nullable|string',
            
            'pan_verified' => 'required|boolean',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
           
            
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
            'pan_verified' => (bool)$data['pan_verified']
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
            // $lorryReceipt->damage_amount = $data['damage_amount'];
            // $lorryReceipt->shortage_amount = $data['shortage_amount'];
            $lorryReceipt->freight_booking_id = $freightBooking->id;
            $lorryReceipt->save();
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
        
        return view('freight_bookings.edit', compact(
            'freight_booking', 
            'lrs', 
            'linkedBookings', 
            'goodsTypes',
            'transporter',
            'truck'
        ));
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
