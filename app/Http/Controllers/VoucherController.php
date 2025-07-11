<?php
namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\FreightBooking;
use App\Models\Truck;
use App\Models\Transporter;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function paymentsSection(Voucher $voucher)
    {
        $voucher->load('payments');
        return view('vouchers._payments_section', compact('voucher'))->render();
    }

    public function show(Voucher $voucher)
    {
        $voucher->load(['freightBooking', 'truck', 'transporter', 'payments']);
        return view('vouchers.show', compact('voucher'));
    }
    public function index(Request $request)
    {
        $freightBookings = FreightBooking::all();
        $trucks = Truck::all();
        $transporters = Transporter::all();
        $query = Voucher::with(['freightBooking', 'truck', 'transporter', 'company']);
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('company_id', auth()->user()->company_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $vouchers = $query->orderByDesc('id')->paginate(20);
        return view('vouchers.index', compact('vouchers', 'freightBookings', 'trucks', 'transporters'));
    }

    public function create()
    {
        $freightBookings = FreightBooking::all();
        $trucks = Truck::all();
        $transporters = Transporter::all();
        return view('vouchers.create', compact('freightBookings', 'trucks', 'transporters'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'freight_booking_id' => 'required|exists:freight_bookings,id',
            'truck_id' => 'required|exists:trucks,id',
            'transporter_id' => 'required|exists:transporters,id',
            'pan_verified' => 'boolean',
            'status' => 'required|in:pending,completed',
        ]);

        // Create voucher first to get the ID
        $voucher = Voucher::create($data);

        // Update voucher number using the ID
        $voucher->update([
            'voucher_number' => $voucher->id,
            'formatted_voucher_number' => 'V-' . str_pad($voucher->id, 6, '0', STR_PAD_LEFT)
        ]);

        return redirect()->route('vouchers.show', $voucher)->with('success', 'Voucher created successfully.');
    }

    public function edit(Voucher $voucher)
    {
        $freightBookings = FreightBooking::all();
        $trucks = Truck::all();
        $transporters = Transporter::all();
        return view('vouchers.edit', compact('voucher', 'freightBookings', 'trucks', 'transporters'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $data = $request->validate([
            'freight_booking_id' => 'required|exists:freight_bookings,id',
            'truck_id' => 'required|exists:trucks,id',
            'transporter_id' => 'required|exists:transporters,id',
            'pan_verified' => 'boolean',
            'status' => 'required|in:pending,completed',
        ]);
        $voucher->update($data);
        return redirect()->route('vouchers.index')->with('success', 'Voucher updated successfully.');
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return redirect()->route('vouchers.index')->with('success', 'Voucher deleted.');
    }
}
