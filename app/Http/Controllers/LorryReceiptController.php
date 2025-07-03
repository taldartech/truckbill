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
        $statuses = ['Pending', 'Delivered', 'Cancelled'];
        $query = \App\Models\LorryReceipt::with(['truck', 'transporter', 'consignor', 'consignee']);

        

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

        $lrs = $query->paginate(10);
        return view('lorry_receipts.index', [
            'lrs' => $lrs,
            'statuses' => $statuses
        ]);
    }

    public function create()
    {
        $trucks = Truck::all();
        $transporters = Transporter::all();
        $customers = Customer::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $consignees = Consignee::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $goodsTypes = GoodType::all();
        $statuses = ['Pending', 'Delivered', 'Cancelled'];
        return view('lorry_receipts.create', compact( 'customers','trucks', 'transporters', 'consignees', 'goodsTypes', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:consignees,id',
            'lr_number' => 'required|string|max:100|unique:lorry_receipts,lr_number',
            'truck_id' => 'nullable|exists:trucks,id',
            'transporter_id' => 'nullable|exists:transporters,id',
            'consignor_id' => 'required|exists:consignees,id',
            'consignor_contact_number' => 'nullable|string|max:20',
            'consignee_id' => 'required|exists:consignees,id',
            'consignee_contact_number' => 'nullable|string|max:20',
            'gr_date' => 'required|date',
        
            
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

        \App\Models\LorryReceipt::create($validated);
        return redirect()->route('lorry_receipts.index')->with('success', 'Lorry Receipt created successfully!');
    }

    public function edit(\App\Models\LorryReceipt $lorry_receipt)
    {
        $trucks = \App\Models\Truck::all();
        $customers = \App\Models\Customer::all();
        $consignees = \App\Models\Consignee::all();
        $goodsTypes = \App\Models\GoodType::all();
        $transporters = \App\Models\Transporter::all();
        $statuses = ['Pending', 'Delivered', 'Cancelled'];
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
            'delivery_status' => 'nullable|in:Pending,Delivered,Cancelled',
            'customer_id' => 'required|integer',
            'consignor_contact_number' => 'nullable|string|max:20',
            'consignee_contact_number' => 'nullable|string|max:20',
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
            'consignee_id' => 'required|integer',
            'consignee_contact_number' => 'nullable|string|max:20',
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
}
