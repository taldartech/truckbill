<?php
namespace App\Http\Controllers;

use App\Models\VoucherPayment;
// use App\Models\Voucher;
use App\Models\PaymentType;
use App\Models\PaymentMode;
use App\Models\Pump;
use Illuminate\Http\Request;

class VoucherPaymentController extends Controller
{
    public function index(Request $request)
    {
        // Start with base query
        $baseQuery = \App\Models\VoucherPayment::with([
            'voucher', 
            'company',
            'freight_booking.lr.customer',
            'freight_booking.lr.truck',
            'freight_booking.lr.consignor.city',
            'freight_booking.lr.consignee.city'
        ]);

        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $baseQuery->where('company_id', auth()->user()->company_id);
        }
        
        // Apply filters
        if ($request->filled('payment_status')) {
            $baseQuery->where('payment_status', $request->payment_status);
        }
        
        if ($request->filled('voucher_number')) {
            $baseQuery->where('voucher_number', 'like', '%' . $request->voucher_number . '%');
        }
        
        // Get unique voucher numbers with their latest payment and total amount
        $voucherGroups = $baseQuery->select('voucher_number')
            ->selectRaw('MAX(id) as latest_payment_id')
            ->selectRaw('SUM(amount) as total_amount')
            ->groupBy('voucher_number')
            ->orderByDesc('latest_payment_id')
            ->paginate(20)
            ->appends($request->except('page'));
            
        // Get the actual payment records with all relationships
        $voucherNumbers = $voucherGroups->pluck('voucher_number')->toArray();
        $latestPaymentIds = $voucherGroups->pluck('latest_payment_id')->toArray();
        
        // Get the latest payment for each voucher number to display representative data
        $payments = \App\Models\VoucherPayment::with([
            'voucher', 
            'company',
            'freight_booking.lr.customer',
            'freight_booking.lr.truck',
            'freight_booking.lr.consignor.city',
            'freight_booking.lr.consignee.city'
        ])->whereIn('id', $latestPaymentIds)->get()->keyBy('voucher_number');
        
        // Get total amounts for each voucher
        $totalAmounts = [];
        foreach ($voucherGroups as $group) {
            $totalAmounts[$group->voucher_number] = $group->total_amount;
        }
        
        $companies = \App\Models\Company::all();
        return view('voucher_payments.index', compact('payments', 'voucherGroups', 'totalAmounts', 'companies'));
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
        $pumps = Pump::all();
        
        return view('voucher_payments.create', compact('voucher_number', 'paymentTypes', 'paymentModes', 'freight_booking_id','lorry_receipt','companies', 'balanceAmount','pumps'));
    }

    public function store(Request $request)
    {
        // Validate basic fields
        $data = $request->validate([
            'freight_booking_id' => 'required|exists:freight_bookings,id',
            'voucher_number' => 'required|string',
            'payment_type_id' => 'required|exists:payment_types,id',
            'payment_mode_id' => 'required|exists:payment_modes,id',
            'payment_type' => 'required|string',
            'payment_mode' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'remark' => 'nullable|string',
            'payment_status' => 'required|in:pending,in-progress,paid',
            'pump_id' => 'nullable|exists:pumps,id',
        ]);

        // Add company_id to data
        $data['company_id'] = auth()->user()->company_id;

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
        $pumps = Pump::all();
        
        // Get lorry receipt and freight booking from request parameters
        $lorry_receipt_id = $request->query('lorry_receipt_id', $voucher_payment->lorry_receipt_id);
        $freight_booking_id = $request->query('freight_booking_id', $voucher_payment->freight_booking_id);
        
        // Get the lorry receipt
        $lorry_receipt = \App\Models\LorryReceipt::with(['transporter:id,name', 'truck:id,truck_number', 'customer:id,name', 'consignor:id,name', 'consignee:id,name'])
            ->select('id', 'lr_number', 'transporter_id', 'truck_id', 'customer_id', 'consignor_id', 'consignee_id')
            ->where('id', $lorry_receipt_id)
            ->get();
             // Get freight booking details with relationships
        $balanceAmount = null;
        $freightBooking = null;
        if ($freight_booking_id) {
            $freightBooking = \App\Models\FreightBooking::with(['transporter'])
                ->find($freight_booking_id);
            if ($freightBooking) {
                $balanceAmount = $freightBooking->balance_amount;
            }
        }

        return view('voucher_payments.edit', compact('voucher_payment', 'vouchers', 'paymentTypes', 'paymentModes', 'lorry_receipt', 'freight_booking_id', 'companies', 'balanceAmount', 'pumps', 'freightBooking'));
    }

    public function update(Request $request, VoucherPayment $voucher_payment)
    {
        $data = $request->validate([
            'freight_booking_id' => 'required|exists:freight_bookings,id',
            'voucher_number' => 'required|string',
            'payment_type_id' => 'required|exists:payment_types,id',
            'payment_mode_id' => 'required|exists:payment_modes,id',
            'payment_type' => 'required|string',
            'payment_mode' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'reference_transaction_id' => 'nullable|string',
            'remark' => 'nullable|string',
            'payment_status' => 'required|in:pending,in-progress,paid',
            'company_id' => 'required|exists:companies,id',
            'pump_id' => 'nullable|exists:pumps,id',
        ]);
        
        // Get the freight booking
        $freightBooking = \App\Models\FreightBooking::findOrFail($data['freight_booking_id']);
        
        // Calculate the difference in amount
        $amountDifference = $data['amount'] - $voucher_payment->amount;
        
        // Check if the amount has changed
        if ($amountDifference != 0) {
            // Validate that advance amount won't exceed TR amount or go below 0
            $newAdvanceAmount = $freightBooking->advance_amount + $amountDifference;
            
            if ($newAdvanceAmount > $freightBooking->tr_amount) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Advance amount cannot exceed TR amount. Maximum allowed: ₹' . 
                        number_format($freightBooking->tr_amount - ($freightBooking->advance_amount - $voucher_payment->amount), 2));
            }
            
            if ($newAdvanceAmount < 0) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Advance amount cannot be negative. Minimum allowed: ₹0');
            }
            
            // Update advance_amount
            $freightBooking->advance_amount = $newAdvanceAmount;
            
            // Recalculate balance_amount
            $freightBooking->balance_amount = $freightBooking->tr_amount - $freightBooking->advance_amount;
            
            // Save the freight booking
            $freightBooking->save();
        }
        
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
        
        return redirect()->route('voucher_payments.index')
            ->with('success', 'Voucher Payment updated successfully' . 
                ($amountDifference != 0 ? '. Freight booking advance and balance amounts have been adjusted.' : '.'));
    }

    public function bulkPayment(Request $request)
    {
        try {
            // Log the incoming request for debugging
            \Log::info('Bulk payment request received:', [
                'all_data' => $request->all(),
                'payment_type' => $request->payment_type,
                'payment_mode' => $request->payment_mode,
                'lorry_receipts' => $request->lorry_receipts,
                'freight_bookings' => $request->freight_bookings,
                'amounts' => $request->amounts
            ]);

            // Validate the request
            $validated = $request->validate([
                'payment_type' => 'required|string',
                'payment_mode' => 'required|string',
                'reference_transaction_id' => 'nullable|string',
                'remark' => 'nullable|string',
                'lorry_receipts' => 'required|array',
                'freight_bookings' => 'required|array',
                'amounts' => 'required|array',
                'company_id' => 'required|exists:companies,id',
                'pump_id' => 'nullable|exists:pumps,id',
            ]);
           
    
            // Get payment type and mode
            $paymentType = PaymentType::where('name', $validated['payment_type'])->first();
            $paymentMode = PaymentMode::where('name', $validated['payment_mode'])->first();

            if (!$paymentType || !$paymentMode) {
                return redirect()->back()->with('error', 'Invalid payment type or mode');
            }

            $reference_transaction_id = $validated['reference_transaction_id'] ?? null;
            $remark = $validated['remark'] ?? null;
            $payment_status = $paymentMode->name === 'cash' || $paymentMode->name === 'adjust in advance' ? 'in-progress' : 'pending';
            $company_id = $validated['company_id'];

            // Process each voucher payment
            $lorryReceipts = $validated['lorry_receipts'];
            $freightBookings = $validated['freight_bookings'];
            $amounts = $validated['amounts'];

            \Log::info('Starting to process voucher payments:', [
                'lorry_receipts' => $lorryReceipts,
                'freight_bookings' => $freightBookings,
                'amounts' => $amounts
            ]);

            // foreach ($lorryReceipts as $index => $lorryReceiptId) {
            //     \Log::info('Processing voucher payment for LR:', [
            //         'lorry_receipt_id' => $lorryReceiptId,
            //         'freight_booking_id' => $freightBookings[$lorryReceiptId] ?? null,
            //         'amount' => $amounts[$lorryReceiptId] ?? null
            //     ]);

            //     $freightBookingId = $freightBookings[$lorryReceiptId] ?? null;
            //     $amount = $amounts[$lorryReceiptId] ?? 0;

            //     if (!$freightBookingId || $amount <= 0) {
            //         \Log::warning('Skipping voucher payment due to invalid data:', [
            //             'lorry_receipt_id' => $lorryReceiptId,
            //             'freight_booking_id' => $freightBookingId,
            //             'amount' => $amount
            //         ]);
            //         continue;
            //     }

            //     // Get the freight booking
            //     $freightBooking = \App\Models\FreightBooking::find($freightBookingId);
            //     if (!$freightBooking) {
            //         \Log::error('Freight booking not found:', [
            //             'freight_booking_id' => $freightBookingId
            //         ]);
            //         continue;
            //     }

            //     // Create voucher payment
            //     $paymentData = [
            //         'voucher_number' => $freightBooking->voucher_number,
            //         'freight_booking_id' => $freightBooking->id,
            //         'payment_type' => $validated['payment_type'],
            //         'payment_mode' => $validated['payment_mode'],
            //         'amount' => $amount,
            //         'remark' => $remark,
            //         'payment_status' => $payment_status,
            //         'company_id' => $company_id
            //     ];

            //     \Log::info('Creating voucher payment with data:', $paymentData);

            //     // Explicitly specify the fields to create
            //     $voucherPayment = VoucherPayment::create([
            //         'freight_booking_id' => $freightBooking->id,
            //         'voucher_number' => $freightBooking->voucher_number,
            //         'payment_type' => $validated['payment_type'],
            //         'payment_mode' => $validated['payment_mode'],
            //         'amount' => $amount,
            //         'remark' => $remark,
            //         'payment_status' => $payment_status,
            //         'company_id' => $company_id
            //     ]);

            //     if ($voucherPayment) {
            //         \Log::info('Successfully created voucher payment:', [
            //             'id' => $voucherPayment->id,
            //             'voucher_id' => $voucherPayment->voucher_id,
            //             'amount' => $voucherPayment->amount
            //         ]);

            //         // Update freight booking advance amount
            //         if (($freightBooking->advance_amount + $amount) > $freightBooking->tr_amount) {
            //             \Log::error('Advance amount exceeds TR amount:', [
            //                 'freight_booking_id' => $freightBooking->id,
            //                 'advance_amount' => $freightBooking->advance_amount,
            //                 'tr_amount' => $freightBooking->tr_amount,
            //                 'new_amount' => $amount
            //             ]);
            //             continue;
            //         }

            //         // Update advance amount
            //         $freightBooking->advance_amount += $amount;
                    
            //         // Recalculate balance amount
            //         $freightBooking->balance_amount = $freightBooking->tr_amount - $freightBooking->advance_amount;
                    
            //         // Save the freight booking
            //         $freightBooking->save();
                    
            //         \Log::info('Successfully updated freight booking:', [
            //             'freight_booking_id' => $freightBooking->id,
            //             'new_advance_amount' => $freightBooking->advance_amount,
            //             'new_balance_amount' => $freightBooking->balance_amount
            //         ]);
            //     } else {
            //         \Log::error('Failed to create voucher payment:', $paymentData);
            //     }

            //     $freightBooking = \App\Models\FreightBooking::find($freightBookingId);
            //     if (!$freightBooking) {
            //         continue;
            //     }

            //     // Create voucher payment
            //     VoucherPayment::create([
            //         'voucher_id' => $freightBooking->voucher_id, // Use voucher_id from freight booking
            //         'payment_type' => $validated['payment_type'],
            //         'payment_mode' => $validated['payment_mode'],
            //         'amount' => $amount,
            //         'transaction_id' => $reference_transaction_id,
            //         'remark' => $remark,
            //         'payment_status' => $payment_status,
            //         'company_id' => $company_id
            //     ]);

            //     // Log the creation
            //     \Log::info('Created voucher payment:', [
            //         'voucher_id' => $freightBooking->voucher_id,
            //         'amount' => $amount,
            //         'freight_booking_id' => $freightBooking->id,
            //         'lorry_receipt_id' => $lorryReceiptId,
            //         'company_id' => $company_id
            //     ]);
            // }
            foreach ($lorryReceipts as $index => $lorryReceiptId) {
                $freightBookingId = $freightBookings[$lorryReceiptId] ?? null;
                $amount = $amounts[$lorryReceiptId] ?? 0;
            
                if (!$freightBookingId || $amount <= 0) {
                    \Log::warning('Skipping due to invalid data', compact('lorryReceiptId', 'freightBookingId', 'amount'));
                    continue;
                }
            
                $freightBooking = \App\Models\FreightBooking::find($freightBookingId);
                if (!$freightBooking) {
                    \Log::error('Freight booking not found', ['freight_booking_id' => $freightBookingId]);
                    continue;
                }
            
                if (($freightBooking->advance_amount + $amount) > $freightBooking->tr_amount) {
                    \Log::error('Advance amount exceeds TR amount', [
                        'freight_booking_id' => $freightBooking->id,
                        'advance_amount' => $freightBooking->advance_amount,
                        'tr_amount' => $freightBooking->tr_amount,
                        'new_amount' => $amount
                    ]);
                    continue;
                }
            
                // Create voucher payment
                $voucherPayment = VoucherPayment::create([
                    'voucher_id' => $freightBooking->voucher_id,
                    'freight_booking_id' => $freightBooking->id,
                    'voucher_number' => $freightBooking->voucher_number,
                    'payment_type' => $validated['payment_type'],
                    'payment_mode' => $validated['payment_mode'],
                    'amount' => $amount,
                    'transaction_id' => $reference_transaction_id,
                    'remark' => $remark,
                    'payment_status' => $payment_status,
                    'company_id' => $company_id
                ]);
            
                // Update advance and balance
                $freightBooking->advance_amount += $amount;
                $freightBooking->balance_amount = $freightBooking->tr_amount - $freightBooking->advance_amount;
                $freightBooking->save();
            
                \Log::info('Voucher payment processed', [
                    'lorry_receipt_id' => $lorryReceiptId,
                    'amount' => $amount,
                    'voucher_payment_id' => $voucherPayment->id
                ]);
            }
            

            return redirect()->route('voucher_payments.index')
                ->with('success', 'Bulk voucher payments created successfully.');

        } catch (\Exception $e) {
            \Log::error('Bulk voucher payment error: ' . $e->getMessage(), [
                'request' => $request->all()
            ]);
            return redirect()->back()
                ->with('error', 'An error occurred while processing bulk payments. Please try again.');
        }
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

    public function dailyPayments(Request $request)
    {
        // Get selected date (default to today)
        $selectedDate = $request->get('date', date('Y-m-d'));
        
        // Build query for voucher payments
        $query = VoucherPayment::with([
            'freight_booking.lr.customer',
            'freight_booking.lr.truck', 
            'freight_booking.lr.consignor.city',
            'freight_booking.lr.consignee.city',
            'company', 
            'pump'
        ])->whereDate('created_at', $selectedDate);
        
        // Filter by company for operators
        if (auth()->user()->userRole->name === 'operator') {
            $query->where('company_id', auth()->user()->company_id);
        }
        
        // Get payments with pagination
        $payments = $query->orderByDesc('created_at')->paginate(15)->appends($request->query());
        
        return view('voucher_payments.daily', compact('payments', 'selectedDate'));
    }
    
    /**
     * Display details for a specific voucher number
     */
    public function show($voucherNumber)
    {
        // Get all payments for this voucher number
        $query = VoucherPayment::with([
            'voucher',
            'company',
            'freight_booking.lr.customer',
            'freight_booking.lr.truck',
            'freight_booking.lr.consignor.city',
            'freight_booking.lr.consignee.city',
            'pump'
        ])->where('voucher_number', $voucherNumber);
        
        // Only apply company_id filter for non-super_admin users
        if (auth()->user()->userRole->name !== 'super_admin') {
            $query->where('company_id', auth()->user()->company_id);
        }
        
        $payments = $query->orderByDesc('created_at')->get();
        
        // If no payments found, redirect back with error
        if ($payments->isEmpty()) {
            return redirect()->route('voucher_payments.index')
                ->with('error', 'No payments found for voucher number: ' . $voucherNumber);
        }
        
        // Get the first payment to access related data
        $firstPayment = $payments->first();
        
        // Calculate total amount
        $totalAmount = $payments->sum('amount');
        
        // Get freight booking details
        $freightBooking = $firstPayment->freight_booking;
        
        return view('voucher_payments.show', compact('payments', 'voucherNumber', 'totalAmount', 'freightBooking'));
    }
    
    /**
     * Remove the specified voucher payment from storage (soft delete).
     *
     * @param  \App\Models\VoucherPayment  $voucher_payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(VoucherPayment $voucher_payment)
    {
        try {
            // Get the freight booking and voucher number before deletion
            $freightBooking = $voucher_payment->freight_booking;
            $voucherNumber = $voucher_payment->voucher_number;
            
            // Soft delete the voucher payment
            $voucher_payment->delete();
            
            // If this was accessed from the voucher detail page, redirect back there
            if (url()->previous() === route('voucher_payments.show', $voucherNumber)) {
                return redirect()->route('voucher_payments.show', $voucherNumber)
                    ->with('success', 'Payment deleted successfully.');
            }
            
            // Otherwise redirect to the index page
            return redirect()->route('voucher_payments.index')
                ->with('success', 'Payment deleted successfully.');
                
        } catch (\Exception $e) {
            \Log::error('Error deleting voucher payment: ' . $e->getMessage(), [
                'voucher_payment_id' => $voucher_payment->id,
                'user_id' => auth()->id()
            ]);
            
            return redirect()->back()
                ->with('error', 'An error occurred while deleting the payment. Please try again.');
        }
    }
}
