<?php
namespace App\Http\Controllers;

use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use Illuminate\Http\Request;

class FreightBookingController extends Controller
{
    public function index(Request $request)
    {
        $query = FreightBooking::with(['lr', 'linkedFreightBooking']);
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

    public function create()
    {
        $lrs = LorryReceipt::all();
        $linkedBookings = FreightBooking::all();
        $goodsTypes = \App\Models\GoodType::all();
        return view('freight_bookings.create', compact('lrs', 'linkedBookings', 'goodsTypes'));
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
            'shortage' => 'nullable|numeric',
            'good_type' => 'nullable|string',
            'detention' => 'nullable|numeric',
            'deviation' => 'nullable|numeric',
            'misc_expense' => 'nullable|numeric',
            'misc_income' => 'nullable|numeric',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'linked_freight_booking_id' => 'nullable|exists:freight_bookings,id',
        ]);

        $freightBooking = FreightBooking::create($data);
        
        // Update lorry receipt status
        $lorryReceipt = LorryReceipt::find($data['lr_id']);
        if ($lorryReceipt) {
            $deliveryStatus = 'Pending';
            if ($data['is_cancelled'] === '1') {
                $deliveryStatus = 'Cancelled';
            } elseif ($data['is_delivered'] === '1') {
                $deliveryStatus = 'Delivered';
            }
            $lorryReceipt->delivery_status = $deliveryStatus;
            $lorryReceipt->save();
        }

        return redirect()->route('freight_bookings.index')->with('success', 'Freight Booking created successfully.');
    }

    public function edit(FreightBooking $freight_booking)
    {
        $lrs = LorryReceipt::all();
        $linkedBookings = FreightBooking::where('id', '!=', $freight_booking->id)->get();
        return view('freight_bookings.edit', compact('freight_booking', 'lrs', 'linkedBookings'));
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
            'misc_income' => 'nullable|numeric',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'linked_freight_booking_id' => 'nullable|exists:freight_bookings,id',
        ]);

        // Update freight booking
        $freight_booking->update($data);

        // Update lorry receipt status
        $lorryReceipt = LorryReceipt::find($data['lr_id']);
        if ($lorryReceipt) {
            $deliveryStatus = 'Pending';
            if ($data['is_cancelled'] === '1') {
                $deliveryStatus = 'Cancelled';
            } elseif ($data['is_delivered'] === '1') {
                $deliveryStatus = 'Delivered';
            }
            $lorryReceipt->delivery_status = $deliveryStatus;
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
