<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Truck;
use App\Models\Transporter;
use App\Models\Customer;
use App\Models\Consignee;
use App\Models\GoodType;

class LorryReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\LorryReceipt::with(['truck', 'transporter']);
        if ($request->filled('gr_number')) {
            $query->where('gr_number', 'like', '%' . $request->gr_number . '%');
        }
        if ($request->filled('truck_id')) {
            $query->where('truck_id', $request->truck_id);
        }
        if ($request->filled('transporter_id')) {
            $query->where('transporter_id', $request->transporter_id);
        }
        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }
        if ($request->filled('gr_date_from')) {
            $query->whereDate('gr_date', '>=', $request->gr_date_from);
        }
        if ($request->filled('gr_date_to')) {
            $query->whereDate('gr_date', '<=', $request->gr_date_to);
        }
        $lrs = $query->orderByDesc('created_at')->get();
        $statuses = ['Pending', 'Delivered', 'Cancelled'];
        return view('lorry_receipts.index', compact('lrs', 'statuses'));
    }

    public function create()
    {
        $trucks = Truck::all();
        // $transporters = Transporter::all();
        $customers = Customer::all();
        $consignees = Consignee::all();
        $goodsTypes = GoodType::all();
        $statuses = ['Pending', 'Delivered', 'Cancelled'];
        return view('lorry_receipts.create', compact( 'customers','trucks', 'consignees', 'goodsTypes', 'statuses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'truck_id' => 'nullable|exists:trucks,id',
            'delivery_status' => 'required|in:Pending,Delivered,Cancelled',
            'consignor_id' => 'required|integer',
            'customer_id' => 'required|integer',
            'consignor_contact_number' => 'nullable|string|max:20',
            'source_city' => 'required|string|max:100',
            'destination_city' => 'required|string|max:100',
            'source_state' => 'required|string|max:100',
            'destination_state' => 'required|string|max:100',
            'weight' => 'required|numeric',
            'no_of_units' => 'required|integer',
            'good_type' => 'required|string|max:100',
            'fr_rate' => 'required|numeric',
            'amount' => 'required|numeric',
            'gr_date' => 'required|date',
            'gr_number' => 'required|string|max:100|unique:lorry_receipts,gr_number',
            'memo_id' => 'nullable|integer',
            'consignee_id' => 'required|integer',
            'consignee_contact_number' => 'nullable|string|max:20',
            'lane' => 'required|string',
            'damage_qty' => 'nullable|numeric',
            'damage_rate' => 'nullable|numeric',
            'shortage_qty' => 'nullable|numeric',
            'shortage_rate' => 'nullable|numeric',
            'service_tax' => 'nullable|numeric',
            'swachh_bharat_cess' => 'nullable|numeric',
            'lr_number' => 'required|string|max:100',
        ]);

        // Set truck_id to null if not provided
        $validated['truck_id'] = $validated['truck_id'] ?? null;
        
        \App\Models\LorryReceipt::create($validated);
        return redirect()->route('lorry_receipts.index')->with('success', 'Lorry Receipt created successfully!');
    }

    public function edit(\App\Models\LorryReceipt $lorry_receipt)
    {
        $trucks = \App\Models\Truck::all();
        $customers = \App\Models\Customer::all();
        $consignees = \App\Models\Consignee::all();
        $goodsTypes = \App\Models\GoodType::all();
        // $transporters = \App\Models\Transporter::all();
        $statuses = ['Pending', 'Delivered', 'Cancelled'];
        return view('lorry_receipts.edit', [
            'lr' => $lorry_receipt,
            'trucks' => $trucks,
            // 'transporters' => $transporters,
            'statuses' => $statuses,
            'customers' => $customers,
            'consignees' => $consignees,
            'goodsTypes' => $goodsTypes,
        ]);
    }

    public function update(Request $request, \App\Models\LorryReceipt $lorry_receipt)
    {
        $validated = $request->validate([
            'truck_id' => 'required|exists:trucks,id',
            // 'transporter_id' => 'required|exists:transporters,id',
            // 'eway_bill_number' => 'nullable|string|max:100',
            'delivery_status' => 'required|in:Pending,Delivered,Cancelled',
            'customer_id' => 'required|integer',
            'customer_contact_number' => 'nullable|string|max:20',
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
            'gr_number' => 'required|string|max:100|unique:lorry_receipts,gr_number,' . $lorry_receipt->id,
            'memo_id' => 'nullable|integer',
            'consignee_id' => 'required|integer',
            'consignee_contact_number' => 'nullable|string|max:20',
            'lane' => 'required|string',
            'damage_qty' => 'nullable|numeric',
            'damage_rate' => 'nullable|numeric',
            'shortage_qty' => 'nullable|numeric',
            'shortage_rate' => 'nullable|numeric',
            'service_tax' => 'nullable|numeric',
            'swachh_bharat_cess' => 'nullable|numeric',
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
