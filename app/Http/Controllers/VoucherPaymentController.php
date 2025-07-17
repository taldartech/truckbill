<?php
namespace App\Http\Controllers;

use App\Models\VoucherPayment;
// use App\Models\Voucher;
use App\Models\PaymentType;
use App\Models\PaymentMode;
use Illuminate\Http\Request;

class VoucherPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\VoucherPayment::with(['voucher', 'company']);

        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('company_id', auth()->user()->company_id);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        $payments = $query->orderByDesc('id')->paginate(20);
        $companies = \App\Models\Company::all();
        return view('voucher_payments.index', compact('payments','companies'));
    }

    public function create(Request $request)
    {
        $companies = \App\Models\Company::all();
        $freight_booking_id = $request->freight_booking_id;
        $lorry_receipt_id = $request->lorry_receipt_id;
        // $lorry_receipt = \App\Models\LorryReceipt::select('id', 'lr_number','transporter_id','truck_id')
        //     ->when($lorry_receipt_id, function ($query) use ($lorry_receipt_id) {
        //         $query->where('id', $lorry_receipt_id);
        //     })
        //     ->get();

        $lorry_receipt = \App\Models\LorryReceipt::with(['transporter:id,name', 'truck:id,truck_number'])
    ->select('id', 'lr_number', 'transporter_id', 'truck_id','customer_id','consignor_id','consignee_id')
    ->when($lorry_receipt_id, function ($query) use ($lorry_receipt_id) {
        $query->where('id', $lorry_receipt_id);
    })
    ->get();
        
        $vouchers = \App\Models\FreightBooking::select('voucher_number')
            ->when($freight_booking_id, function ($query) use ($freight_booking_id) {
                $query->where('id', $freight_booking_id);
            })
            ->get();
            $voucher_number = $vouchers->first()->voucher_number;
            
        // Get balance amount from freight booking
        $balanceAmount = null;
        if ($freight_booking_id) {
            $freightBooking = \App\Models\FreightBooking::find($freight_booking_id);
            if ($freightBooking) {
                $balanceAmount = $freightBooking->balance_amount;
            }
        }
            
        $paymentTypes = PaymentType::all();
        $paymentModes = PaymentMode::all();
        
        return view('voucher_payments.create', compact('voucher_number', 'paymentTypes', 'paymentModes', 'freight_booking_id','lorry_receipt','companies', 'balanceAmount'));
    }

    public function store(Request $request)
    {
        // Validate basic fields
        $data = $request->validate([
            'freight_booking_id' => 'required|exists:freight_bookings,id',
            'voucher_number' => 'required|string',
            'payment_type' => 'required|string',
            'payment_mode' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'remark' => 'nullable|string',
            'payment_status' => 'required|in:pending,in-progress,paid',
            'company_id' => 'required|exists:companies,id',
        
           
        ]);

        // Create voucher payment
        $voucherPayment = VoucherPayment::create($data);
        
        // Get the freight booking and update advance_amount
        $freightBooking = \App\Models\FreightBooking::findOrFail($data['freight_booking_id']);
        
        // Validate that advance amount won't exceed TR amount
        if (($freightBooking->advance_amount + $data['amount']) > $freightBooking->tr_amount) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Advance amount cannot exceed TR amount.');
        }

        // Update advance_amount
        $freightBooking->advance_amount += $data['amount'];
        
        // Recalculate balance_amount
        $freightBooking->balance_amount = $freightBooking->tr_amount - $freightBooking->advance_amount;
        
        // Save the freight booking
        $freightBooking->save();

        // Redirect back to lorry receipts page with success message
        return redirect()->route('lorry_receipts.index')
            ->with('success', 'Payment allocated successfully.');
    }

    public function edit(Request $request, VoucherPayment $voucher_payment)
    {
        $vouchers = \App\Models\FreightBooking::select('id', 'voucher_number')->get();
        $paymentTypes = PaymentType::all();
        $paymentModes = PaymentMode::all();
        $companies = \App\Models\Company::all();
        
        // Get lorry receipt and freight booking from request parameters
        $lorry_receipt_id = $request->query('lorry_receipt_id', $voucher_payment->lorry_receipt_id);
        $freight_booking_id = $request->query('freight_booking_id', $voucher_payment->freight_booking_id);
        
        // Get the lorry receipt
        $lorry_receipt = \App\Models\LorryReceipt::with(['transporter:id,name', 'truck:id,truck_number', 'customer:id,name', 'consignor:id,name', 'consignee:id,name'])
            ->select('id', 'lr_number', 'transporter_id', 'truck_id', 'customer_id', 'consignor_id', 'consignee_id')
            ->where('id', $lorry_receipt_id)
            ->get();
             // Get balance amount from freight booking
        $balanceAmount = null;
        if ($freight_booking_id) {
            $freightBooking = \App\Models\FreightBooking::find($freight_booking_id);
            if ($freightBooking) {
                $balanceAmount = $freightBooking->balance_amount;
            }
        }

        return view('voucher_payments.edit', compact('voucher_payment', 'vouchers', 'paymentTypes', 'paymentModes', 'lorry_receipt', 'freight_booking_id', 'companies','balanceAmount'));
    }

    public function update(Request $request, VoucherPayment $voucher_payment)
    {
        $data = $request->validate([
            'freight_booking_id' => 'required|exists:freight_bookings,id',
            'voucher_number' => 'required|string',
          
            'payment_type' => 'required|string',
            'payment_mode' => 'required|string',
            
            'reference_transaction_id' => 'nullable|string',
            'remark' => 'nullable|string',
            'payment_status' => 'required|in:pending,in-progress,paid',
            'company_id' => 'required|exists:companies,id',
        ]);
        
        // Update payment status if conditions are met
        if (
            auth()->user()->userRole &&
            auth()->user()->userRole->name === 'admin' &&
            in_array($request->payment_mode, ['bank', 'online', 'fuel']) &&
            $request->payment_status === 'in-progress' &&
            $request->filled('reference_transaction_id')
        ) {
            $data['payment_status'] = 'paid';
        }
        
        $voucher_payment->update($data);
        
        return redirect()->route('voucher_payments.index')->with('success', 'Voucher Payment updated successfully.');
    }

    public function destroy(VoucherPayment $voucher_payment)
    {
        $voucher_payment->delete();
        return redirect()->route('voucher_payments.index')->with('success', 'Voucher Payment deleted.');
    }

    /**
     * Update payment status for multiple payments
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'required|integer|exists:voucher_payments,id',
            'new_status' => 'required|in:pending,in-progress,paid'
        ]);

        try {
            VoucherPayment::whereIn('id', $validated['payment_ids'])
                ->update(['payment_status' => $validated['new_status']]);

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment status: ' . $e->getMessage()
            ], 500);
        }
    }
}
