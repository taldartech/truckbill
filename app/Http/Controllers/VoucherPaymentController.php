<?php
namespace App\Http\Controllers;

use App\Models\VoucherPayment;
use App\Models\Voucher;
use App\Models\PaymentType;
use App\Models\PaymentMode;
use Illuminate\Http\Request;

class VoucherPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = VoucherPayment::with('voucher');
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        $payments = $query->orderByDesc('id')->paginate(20);
        return view('voucher_payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $freight_booking_id = $request->freight_booking_id;
        
        $vouchers = \App\Models\FreightBooking::select('id', 'voucher_number')
            ->when($freight_booking_id, function ($query) use ($freight_booking_id) {
                $query->where('id', $freight_booking_id);
            })
            ->get();
            
        $paymentTypes = PaymentType::all();
        $paymentModes = PaymentMode::all();
        
        return view('voucher_payments.create', compact('vouchers', 'paymentTypes', 'paymentModes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'freight_booking_id' => 'required|exists:freight_bookings,id',
            'payment_type' => 'required|string',
            'payment_mode' => 'required|string',
            'transaction_id' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'remark' => 'nullable|string',
            'payment_status' => 'required|in:pending,in-progress,paid',
        ]);
        VoucherPayment::create($data);
        return redirect()->route('voucher_payments.index')->with('success', 'Voucher Payment created successfully.');
    }

    public function edit(VoucherPayment $voucher_payment)
    {
        $vouchers = \App\Models\FreightBooking::select('id', 'voucher_number')->get();
        $paymentTypes = PaymentType::all();
        $paymentModes = PaymentMode::all();
        return view('voucher_payments.edit', compact('voucher_payment', 'vouchers', 'paymentTypes', 'paymentModes'));
    }

    public function update(Request $request, VoucherPayment $voucher_payment)
    {
        $data = $request->validate([
            'voucher_id' => 'required|exists:vouchers,id',
            'payment_type' => 'required|string',
            'payment_mode' => 'required|string',
            'transaction_id' => 'required|string',
            'remark' => 'nullable|string',
            'payment_status' => 'required|in:pending,in-progress,paid',
        ]);
        $voucher_payment->update($data);
        return redirect()->route('voucher_payments.index')->with('success', 'Voucher Payment updated successfully.');
    }

    public function destroy(VoucherPayment $voucher_payment)
    {
        $voucher_payment->delete();
        return redirect()->route('voucher_payments.index')->with('success', 'Voucher Payment deleted.');
    }
}
