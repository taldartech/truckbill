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

class LorryReceiptController extends Controller
{
    public function index(Request $request)
    {
        $statuses = ['Pending', 'Delivered', 'Cancelled','In-Progress'];
        $query = \App\Models\LorryReceipt::with(['truck', 'transporter', 'consignor', 'consignee', 'company','customer']);

        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('company_id', auth()->user()->company_id);
        }

        if ($request->customer_id) {
            $query->where('customer_id', $request->customer_id);
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

        $lrs = $query->orderBy('id', 'desc')->paginate(10);
        return view('lorry_receipts.index', [
            'lrs' => $lrs,
            'statuses' => $statuses
        ]);
    }

    public function create()
    {
        $companies = \App\Models\Company::all();
        $trucks = Truck::all();
        $transporters = Transporter::all();
        $customers = Customer::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $consignees = Consignee::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $goodsTypes = GoodType::all();
        $statuses = ['Pending', 'Delivered', 'Cancelled','In-Progress'];
        
        return view('lorry_receipts.create', compact('companies', 'customers','trucks', 'transporters', 'consignees', 'goodsTypes', 'statuses'));
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
            'no_of_units' => 'required|numeric|min:0',
            'fr_rate' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'damage_amount' => 'nullable|numeric',
            'shortage_amount' => 'nullable|numeric',
        ]);

        // Set truck_id to null if not provided
        $validated['truck_id'] = $validated['truck_id'] ?? null;
        
        // Set default delivery_status
        $validated['delivery_status'] = 'Pending';
        
        // Check if lr_number already exists
        if (\App\Models\LorryReceipt::where('lr_number', $validated['lr_number'])->exists()) {
            return redirect()->back()->withErrors(['lr_number' => 'LR Number already exists. Please use a unique LR Number.']);
        }

        // Add company_id to validated data
        $validated['company_id'] = auth()->user()->company_id;
        
        \App\Models\LorryReceipt::create($validated);
        return redirect()->route('lorry_receipts.index')->with('success', 'Lorry Receipt created successfully!');
    }

    public function edit(\App\Models\LorryReceipt $lorry_receipt)
    {
        // Eager load the truck relationship
        $lorry_receipt = $lorry_receipt->load('truck');
        
        $customers = \App\Models\Customer::all();
        $consignees = \App\Models\Consignee::all();
        $goodsTypes = \App\Models\GoodType::all();
        
        // Get trucks filtered by the current transporter's company
        $trucks = \App\Models\Truck::when($lorry_receipt->transporter_id, function ($query) use ($lorry_receipt) {
            return $query->where('transporter_id', $lorry_receipt->transporter_id);
        })->get();

        $transporters = \App\Models\Transporter::all();
        $statuses = ['Pending', 'Delivered', 'Cancelled','In-Progress'];
        
        return view('lorry_receipts.edit', [
            'lr' => $lorry_receipt,
            'trucks' => $trucks,
            'transporters' => $transporters,
            'statuses' => $statuses,
            'customers' => $customers,
            'consignees' => $consignees,
            'goodsTypes' => $goodsTypes,
        ]);
    }

    public function update(Request $request, \App\Models\LorryReceipt $lorry_receipt)
    {
        $validated = $request->validate([
            'truck_id' => 'nullable|exists:trucks,id',
            'transporter_id' => 'nullable|exists:transporters,id',
            'delivery_status' => 'nullable|in:Pending,Delivered,Cancelled,In-Progress',
            'customer_id' => 'required|integer',
            'consignor_contact_number' => [
                'nullable',
                'digits:10',
            ],
            'consignee_contact_number' => [
                'nullable',
                'digits:10',
            ],
            'source_city' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'source_state' => 'required|string|max:100',
            'destination_state' => 'required|string|max:100',
            'weight' => 'required|numeric',
            'no_of_units' => 'required|integer',
            'good_type' => 'required|string|max:100',
            'fr_rate' => 'required|numeric',
            'amount' => 'required|numeric',
            'linked_lr_id' => 'nullable|integer',
            'gr_date' => 'required|date',
            'memo_id' => 'nullable|integer',
            'consignor_id' => 'required|integer',
            'consignee_id' => 'required|integer',
            'lane' => 'required|string',
            'damage_amount' => 'nullable|numeric',
            'shortage_amount' => 'nullable|numeric',
            'lr_number' => 'required|string|max:100',
        ]);
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
            'consignee'
        ])->findOrFail($id);

        return response()->json($lorry_receipt);
    }
}
