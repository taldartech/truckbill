<?php

namespace App\Http\Controllers;

use App\Models\VoucherPayment;
use App\Models\PaymentType;
use App\Models\PaymentMode;
use App\Models\Pump;
use App\Models\Company;
use App\Models\City;
use App\Models\Transporter;
use App\Models\Truck;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\VoucherLinked;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherPaymentController extends Controller
{
    private function ensureCompanyContext(): void
    {
        if (Auth::user()->userRole->name === 'super_admin' && ! session('super_admin_company_id')) {
            abort(403, 'Please enter a company first.');
        }
    }

    /**
     * Display the daily payments report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function dailyPayments(Request $request)
    {
        $this->ensureCompanyContext();
        // Get selected date (default to today)
        $selectedDate = $request->get('date', date('Y-m-d'));

        // Start building the query with relationships
        $query = VoucherPayment::with([
            'freight_booking.lr',
            'company',
            'pump',
            'freight_booking.transporter.transporterMaster',
            'freight_booking.lr.consignor.city',
            'freight_booking.lr.consignee.city',
            'freight_booking.lr.truck',
            'creator.branch'
        ])->whereDate('date', $selectedDate)
            ->whereNull('linked_voucher_id');
        $query1 = VoucherLinked::with([
            'freight_booking.lr',
            'company',
            'pump',
            'freight_booking.transporter.transporterMaster',
            'freight_booking.lr.consignor.city',
            'freight_booking.lr.consignee.city',
            'freight_booking.lr.truck',
            'creator.branch'
        ])->whereDate('date', $selectedDate);

        if (Auth::user()->company_id) {
            $query->where('company_id', Auth::user()->company_id);
            $query1->where('company_id', Auth::user()->company_id);
        }

        // Apply Truck No filter
        if ($request->filled('truck_no')) {
            $query->whereHas('freight_booking.lr.truck', function ($q) use ($request) {
                $q->where('truck_number', 'like', '%' . $request->truck_no . '%');
            });
            $query1->whereHas('freight_booking.lr.truck', function ($q) use ($request) {
                $q->where('truck_number', 'like', '%' . $request->truck_no . '%');
            });
        }

        // Apply Transporter filter
        if ($request->filled('transporter')) {
            $query->whereHas('freight_booking.transporter', function ($q) use ($request) {
                $q->where('id', $request->transporter);
            });
            $query1->whereHas('freight_booking.transporter', function ($q) use ($request) {
                $q->where('id', $request->transporter);
            });
        }

        // Apply Source filter
        if ($request->filled('source')) {
            $query->whereHas('freight_booking.lr.consignor', function ($q) use ($request) {
                $q->where('city_id', $request->source);
            });
        }

        // Apply Destination filter
        if ($request->filled('destination')) {
            $query->whereHas('freight_booking.lr.consignee', function ($q) use ($request) {
                $q->where('city_id', $request->destination);
            });
        }

        // Apply Lorry Receipt No filter
        if ($request->filled('lr_no')) {
            $query->whereHas('freight_booking.lr', function ($q) use ($request) {
                $q->where('lr_number', 'like', '%' . $request->lr_no . '%');
            });
        }

        // Apply Status filter
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
            $query1->where('payment_status', $request->status);
        }

        if ($request->filled('payment_mode')) {
            $query->where('payment_mode', $request->payment_mode);
            $query1->where('payment_mode', $request->payment_mode);
        }

        // Apply Branch filter
        if ($request->filled('branch')) {
            $query->whereHas('creator', function ($q) use ($request) {
                $q->where('branch_id', $request->branch);
            });
            $query1->whereHas('creator', function ($q) use ($request) {
                $q->where('branch_id', $request->branch);
            });
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->get();
        $payments1 = $query1->orderBy('created_at', 'desc')
            ->get();
        // Get filter data for dropdowns
        $transporters = \App\Models\Transporter::with('transporterMaster')
            ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
            ->orderBy('transporter_masters.name')
            ->select('transporters.*')
            ->get();
        $cities = \App\Models\City::orderBy('name')->get();
        $paymentModes = \App\Models\PaymentMode::select('name')->get();

        // Get branches for filter dropdown
        $branchesQuery = \App\Models\Branch::orderBy('branch_name');
        if (Auth::user()->company_id) {
            $branchesQuery->where('company_id', Auth::user()->company_id);
        }
        $branches = $branchesQuery->get();

        return view('voucher_payments.daily', [
            'payments' => $payments,
            'payments1' => $payments1,
            'selectedDate' => $selectedDate,
            'transporters' => $transporters,
            'cities' => $cities,
            'paymentModes' => $paymentModes,
            'branches' => $branches
        ]);
    }
    public function index(Request $request)
    {
        $this->ensureCompanyContext();
        // Start with base query
        $baseQuery = \App\Models\VoucherPayment::with([
            // 'voucher',
            'company',
            'freight_booking.lr.customer',
            'freight_booking.lr.truck',
            'freight_booking.lr.consignor.city',
            'freight_booking.lr.consignee.city',
            'freight_booking.transporter.transporterMaster', // Add transporter relationship
            'pump'
        ]);

        if (Auth::user()->company_id) {
            $baseQuery->where('company_id', Auth::user()->company_id);
        }

        // Apply filters
        if ($request->filled('payment_status')) {
            $baseQuery->where('payment_status', $request->payment_status);
        }

        if ($request->filled('voucher_number')) {
            $baseQuery->where('voucher_number', 'like', '%' . $request->voucher_number . '%');
        }

        // New filters
        if ($request->filled('truck_no')) {
            $baseQuery->whereHas('freight_booking.lr.truck', function ($q) use ($request) {
                $q->where('truck_number', 'like', '%' . $request->truck_no . '%');
            });
        }

        if ($request->filled('transporter')) {
            $baseQuery->whereHas('freight_booking.transporter', function ($q) use ($request) {
                $q->where('id', $request->transporter);
            });
        }

        if ($request->filled('source')) {
            $baseQuery->whereHas('freight_booking.lr.consignor', function ($q) use ($request) {
                $q->where('city_id', $request->source);
            });
        }

        if ($request->filled('destination')) {
            $baseQuery->whereHas('freight_booking.lr.consignee', function ($q) use ($request) {
                $q->where('city_id', $request->destination);
            });
        }

        if ($request->filled('lr_no')) {
            $baseQuery->whereHas('freight_booking.lr', function ($q) use ($request) {
                $q->where('lr_number', 'like', '%' . $request->lr_no . '%');
            });
        }

        if ($request->filled('freight_booking_no')) {
            $baseQuery->whereHas('freight_booking', function ($q) use ($request) {
                $q->where('id', 'like', '%' . $request->freight_booking_no . '%')
                    ->orWhere('voucher_number', 'like', '%' . $request->freight_booking_no . '%');
            });
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
            // 'voucher',
            'company',
            'freight_booking.lr.customer',
            'freight_booking.lr.truck',
            'freight_booking.lr.consignor.city',
            'freight_booking.lr.consignee.city',
            'freight_booking.transporter.transporterMaster' // Add transporter relationship
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
        $this->ensureCompanyContext();
        $company = auth()->user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        $companies = \App\Models\Company::all();
        $freight_booking_id = $request->freight_booking_id;
        $lorry_receipt_id = $request->lorry_receipt_id;
        // $lorry_receipt = \App\Models\LorryReceipt::select('id', 'lr_number','transporter_id','truck_id')
        //     ->when($lorry_receipt_id, function ($query) use ($lorry_receipt_id) {
        //         $query->where('id', $lorry_receipt_id);
        //     })
        //     ->get();

        $lorry_receipt = \App\Models\LorryReceipt::with(['transporter.transporterMaster', 'truck:id,truck_number'])
            ->select('id', 'lr_number', 'transporter_id', 'truck_id', 'customer_id', 'consignor_id', 'consignee_id')
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
        $pumps = Pump::where('user_id', Auth::user()->company->user_id)->get();

        return view('voucher_payments.create', compact('voucher_number', 'paymentTypes', 'paymentModes', 'freight_booking_id', 'lorry_receipt', 'companies', 'balanceAmount', 'pumps'));
    }

    public function store(Request $request)
    {
        $this->ensureCompanyContext();
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
            'date' => 'nullable|date',
        ]);
        //if date is null than store created_at date
        if ($request->date == null) {
            $data['date'] = $request->created_at;
        }

        // Add company_id to data
        $data['company_id'] = auth()->user()->company_id;

        // Check for duplicate payment on the same day
        $paymentDate = $data['date'] ?? now()->toDateString();
        $duplicatePayment = \App\Models\VoucherPayment::where('freight_booking_id', $data['freight_booking_id'])
            ->where('amount', $data['amount'])
            ->where('payment_type_id', $data['payment_type_id'])
            ->where('payment_mode_id', $data['payment_mode_id'])
            ->whereDate('date', $paymentDate)
            ->where('company_id', $data['company_id'])
            ->first();

        if ($duplicatePayment && !$request->has('confirm_duplicate')) {
            // Return warning with duplicate payment details
            $freightBooking = \App\Models\FreightBooking::find($data['freight_booking_id']);
            return redirect()->back()
                ->withInput()
                ->with('warning', 'A similar payment already exists for this booking on ' . date('d-m-Y', strtotime($paymentDate)) . ':
                    <br><strong>Amount:</strong> ₹' . number_format($duplicatePayment->amount, 2) . '
                    <br><strong>Payment Type:</strong> ' . $duplicatePayment->payment_type . '
                    <br><strong>Payment Mode:</strong> ' . $duplicatePayment->payment_mode . '
                    <br><strong>Created:</strong> ' . $duplicatePayment->created_at->format('d-m-Y H:i:s') . '
                    <br><br>If you want to proceed anyway, please confirm by checking "Confirm duplicate payment" and resubmit.')
                ->with('duplicate_payment_id', $duplicatePayment->id);
        }

        // Begin transaction to ensure atomicity
        \DB::beginTransaction();

        try {
            // Use row-level locking to prevent race conditions
            $freightBooking = \App\Models\FreightBooking::lockForUpdate()->findOrFail($data['freight_booking_id']);

            // Calculate effective payable amount (considering all deductions)
            $effectivePayableAmount = $freightBooking->tr_amount
                - ($freightBooking->tds_amount ?? 0)
                - ($freightBooking->damage_amount ?? 0)
                - ($freightBooking->shortage_amount ?? 0)
                + ($freightBooking->detention ?? 0)
                + ($freightBooking->deviation ?? 0)
                - ($freightBooking->misc_expense ?? 0);

            // Validate that advance amount won't exceed effective payable amount
            // Allow small tolerance (0.01) for floating point precision
            $newAdvanceAmount = $freightBooking->advance_amount + $data['amount'];
            if ($newAdvanceAmount > $effectivePayableAmount + 0.01) {
                \DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Advance amount cannot exceed effective payable amount ₹' . number_format($effectivePayableAmount, 2) . '. Maximum allowed: ₹' . number_format($effectivePayableAmount - $freightBooking->advance_amount, 2));
            }

            // Validate payment doesn't exceed balance
            if ($data['amount'] > $freightBooking->balance_amount) {
                \DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Payment amount ₹' . number_format($data['amount'], 2) . ' cannot exceed balance amount ₹' . number_format($freightBooking->balance_amount, 2) . '.');
            }

            // Create voucher payment
            $voucherPayment = VoucherPayment::create($data);

            // Update advance_amount
            $freightBooking->advance_amount += $data['amount'];

            // Recalculate balance_amount using full formula
            $freightBooking->balance_amount = $effectivePayableAmount - $freightBooking->advance_amount;

            // Save the freight booking
            $freightBooking->save();

            \DB::commit();

            // Redirect back to lorry receipts page with success message
            return redirect()->route('lorry_receipts.index')
                ->with('success', 'Payment allocated successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error processing voucher payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $data
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while processing the payment. Please try again.');
        }
    }

    /**
     * Display the specified voucher payment.
     *
     * @param  string  $voucherNumber
     * @return \Illuminate\View\View
     */
    public function show($voucherNumber)
    {
        $this->ensureCompanyContext();
        // Get all payments for this voucher number with relationships
        $payments = VoucherPayment::with([
            'freight_booking.lr.customer',
            'freight_booking.lr.consignor.city',
            'freight_booking.lr.consignee.city',
            'freight_booking.lr.truck',
            'freight_booking.company',
            'pump'
        ])->where('voucher_number', $voucherNumber)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($payments->isEmpty()) {
            abort(404, 'Voucher not found');
        }
        if (Auth::user()->company_id) {
            $payments = $payments->filter(fn ($p) => $p->company_id === Auth::user()->company_id);
            if ($payments->isEmpty()) {
                abort(403, 'Unauthorized access to this voucher.');
            }
        }

        // Get the first payment to get common data
        $firstPayment = $payments->first();
        $freightBooking = $firstPayment->freight_booking;
        $totalAmount = $payments->sum('amount');

        return view('voucher_payments.show', [
            'voucherNumber' => $voucherNumber,
            'freightBooking' => $freightBooking,
            'totalAmount' => $totalAmount,
            'payments' => $payments,
            'firstPayment' => $firstPayment
        ]);
    }

    public function edit(Request $request, VoucherPayment $voucher_payment)
    {
        $this->ensureCompanyContext();
        if (Auth::user()->company_id && $voucher_payment->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this voucher payment.');
        }
        $company = auth()->user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('voucher_payments.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        $vouchers = \App\Models\FreightBooking::select('id', 'voucher_number')->get();
        $paymentTypes = PaymentType::all();
        $paymentModes = PaymentMode::all();
        $companies = \App\Models\Company::all();
        $pumps = Pump::where('user_id', Auth::user()->company->user_id)->get();

        // Get lorry receipt and freight booking from request parameters
        $lorry_receipt_id = $request->query('lorry_receipt_id', $voucher_payment->lorry_receipt_id);
        $freight_booking_id = $request->query('freight_booking_id', $voucher_payment->freight_booking_id);

        // Get the lorry receipt
        $lorry_receipt = \App\Models\LorryReceipt::with(['transporter.transporterMaster', 'truck:id,truck_number', 'customer:id,name', 'consignor:id,name', 'consignee:id,name'])
            ->select('id', 'lr_number', 'transporter_id', 'truck_id', 'customer_id', 'consignor_id', 'consignee_id')
            ->where('id', $lorry_receipt_id)
            ->get();
        // Get freight booking details with relationships
        $balanceAmount = null;
        $freightBooking = null;
        if ($freight_booking_id) {
            $freightBooking = \App\Models\FreightBooking::with(['transporter.transporterMaster'])
                ->find($freight_booking_id);
            if ($freightBooking) {
                $balanceAmount = $freightBooking->balance_amount;
            }
        }

        return view('voucher_payments.edit', compact('voucher_payment', 'vouchers', 'paymentTypes', 'paymentModes', 'lorry_receipt', 'freight_booking_id', 'companies', 'balanceAmount', 'pumps', 'freightBooking'));
    }

    public function update(Request $request, VoucherPayment $voucher_payment)
    {
        $this->ensureCompanyContext();
        if (Auth::user()->company_id && $voucher_payment->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this voucher payment.');
        }
        $company = auth()->user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('voucher_payments.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        if ($voucher_payment->payment_status === 'paid') {
            return redirect()->back()
                ->with('error', 'Paid payments cannot be edited.');
        }
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
            'date' => 'nullable|date',
        ]);

        if (empty($data['date'])) {
            $data['date'] = now()->toDateString();
        }

        // Calculate the difference in amount (needed outside try block)
        $amountDifference = $data['amount'] - $voucher_payment->amount;

        // Begin transaction to ensure atomicity
        \DB::beginTransaction();

        try {
            // Use row-level locking to prevent race conditions
            $freightBooking = \App\Models\FreightBooking::lockForUpdate()->findOrFail($data['freight_booking_id']);

            // Check if the amount has changed
            if ($amountDifference != 0) {
                // Calculate effective payable amount (considering all deductions)
                $effectivePayableAmount = $freightBooking->tr_amount
                    - ($freightBooking->tds_amount ?? 0)
                    - ($freightBooking->damage_amount ?? 0)
                    - ($freightBooking->shortage_amount ?? 0)
                    + ($freightBooking->detention ?? 0)
                    + ($freightBooking->deviation ?? 0)
                    - ($freightBooking->misc_expense ?? 0);

                // Calculate new advance amount after this payment change
                $newAdvanceAmount = $freightBooking->advance_amount + $amountDifference;

                // Validate that advance amount won't exceed effective payable amount
                // Allow small tolerance (0.01) for floating point precision
                if ($newAdvanceAmount > $effectivePayableAmount + 0.01) {
                    \DB::rollBack();
                    $maxAllowed = $effectivePayableAmount - ($freightBooking->advance_amount - $voucher_payment->amount);
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Advance amount cannot exceed effective payable amount ₹' . number_format($effectivePayableAmount, 2) . '. Maximum allowed: ₹' . number_format($maxAllowed, 2));
                }

                // Validate that advance amount won't go below 0
                if ($newAdvanceAmount < 0) {
                    \DB::rollBack();
                    $minAllowed = -($freightBooking->advance_amount - $voucher_payment->amount);
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Advance amount cannot be negative. Minimum allowed: ₹' . number_format($minAllowed, 2));
                }

                // Validate new payment amount doesn't exceed balance
                $newBalanceAfterChange = $freightBooking->balance_amount - $amountDifference;
                if ($newBalanceAfterChange < 0) {
                    \DB::rollBack();
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Payment amount increase would cause negative balance. Maximum allowed: ₹' . number_format($voucher_payment->amount + $freightBooking->balance_amount, 2));
                }

                // Update advance_amount
                $freightBooking->advance_amount = $newAdvanceAmount;

                // Recalculate balance_amount using full formula
                $freightBooking->balance_amount = $effectivePayableAmount - $freightBooking->advance_amount;

                // Save the freight booking
                $freightBooking->save();
            }

            // Update payment status if conditions are met (before updating voucher payment)
            if (
                auth()->user()->canActAsAdmin() &&
                in_array($request->payment_mode, ['bank', 'online', 'fuel']) &&
                $request->payment_status === 'in-progress' &&
                $request->filled('reference_transaction_id')
            ) {
                $data['payment_status'] = 'paid';
            }

            // Update linked voucher if exists (inside transaction)
            if ($voucher_payment->linked_voucher_id != null) {
                $linked_voucher = VoucherLinked::find($voucher_payment->linked_voucher_id);
                if ($linked_voucher) {
                    $linked_voucher->update([
                        'amount' => $linked_voucher->amount + ($data['amount'] - $voucher_payment->amount)
                    ]);
                }
            }

            // Update voucher payment (inside transaction to ensure atomicity)
            $voucher_payment->update($data);

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error updating voucher payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_id' => $voucher_payment->id,
                'request_data' => $data
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the payment. Please try again.');
        }

        return redirect()->route('voucher_payments.index')
            ->with('success', 'Voucher Payment updated successfully' .
                ($amountDifference != 0 ? '. Freight booking advance and balance amounts have been adjusted.' : '.'));
    }

    public function bulkPayment(Request $request)
    {
        $this->ensureCompanyContext();
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
                'date' => 'nullable|date',
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
                    'company_id' => $company_id,
                    'pump_id' => $validated['pump_id'],
                    'date' => $validated['date'],
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
        $this->ensureCompanyContext();
        if (! auth()->user()->canActAsAdmin()) {
            return redirect()->back()->with('error', 'You do not have permission to update payment status.');
        }

        $validated = $request->validate([
            'payment_ids' => 'nullable|array',
            'payment_ids.*' => 'required|integer|exists:voucher_payments,id',
            'multiple_payment_ids' => 'nullable|array',
            'multiple_payment_ids.*' => 'required|integer|exists:voucher_linkeds,id',
            'new_status' => 'required|in:pending,in-progress,paid'
        ]);

        $payment_ids = $validated['payment_ids'] ?? [];
        $multiple_payment_ids = $validated['multiple_payment_ids'] ?? [];

        try {
            // Get all selected payments
            $payments = VoucherPayment::whereIn('id', $payment_ids)->get();
            $linkedPayments = VoucherLinked::whereIn('id', $multiple_payment_ids)->get();

            // Check if all payments have the same current status
            $currentStatuses = $payments->pluck('payment_status')->unique();
            $linkedStatuses = $linkedPayments->pluck('payment_status')->unique();
            $currentStatuses = $currentStatuses->merge($linkedStatuses)->unique();

            if ($currentStatuses->count() > 1) {
                return redirect()->back()->with('error', 'Selected payments must have the same status.');
            }
            $currentStatus = $currentStatuses->first();

            // Validate status transition
            if (!$this->isValidStatusTransition($currentStatus, $validated['new_status'])) {
                return redirect()->back()->with('error', 'Invalid status transition. Payments can only progress from pending to in-progress to paid.');
            }

            // Don't allow updating paid payments
            if ($currentStatus === 'paid') {
                return redirect()->back()->with('error', 'Paid payments cannot be updated.');
            }
            $voucher_ids = [];
            foreach ($multiple_payment_ids as $id) {
                $voucher_ids = array_merge($voucher_ids, json_decode(VoucherLinked::find($id)->voucher_ids));
            }
            $voucher_ids = array_merge($payment_ids, $voucher_ids);
            // Update all payments individually to trigger auditing
            $voucherPayments = VoucherPayment::whereIn('id', $voucher_ids)->get();
            foreach ($voucherPayments as $payment) {
                $payment->payment_status = $validated['new_status'];
                $payment->save();
            }

            $linkedPayments = VoucherLinked::whereIn('id', $multiple_payment_ids)->get();
            foreach ($linkedPayments as $linkedPayment) {
                $linkedPayment->payment_status = $validated['new_status'];
                $linkedPayment->save();
            }

            $updatedCount = count($payment_ids) + count($multiple_payment_ids);

            $previousUrl = url()->previous();
            $parsedUrl = parse_url($previousUrl);
            parse_str($parsedUrl['query'] ?? '', $queryParams);
            if (isset($queryParams['status']) && $queryParams['status'] !== '') {
                $queryParams['status'] = $validated['new_status'];
            }
            $newQuery = http_build_query($queryParams);
            $newUrl = $parsedUrl['path'] . '?' . $newQuery;
            return redirect($newUrl)->with('success', $updatedCount . ' payments updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error in bulk update: ' . $e->getMessage(), [
                'payment_ids' => $payment_ids,
                'multiple_payment_ids' => $multiple_payment_ids,
                'new_status' => $validated['new_status']
            ]);

            return redirect()->back()->with('error', 'Error updating payment status: ' . $e->getMessage());
        }
    }

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

    /**
     * Remove the specified voucher payment from storage (soft delete).
     *
     * @param  \App\Models\VoucherPayment  $voucher_payment
     * @return \Illuminate\Http\Response
     */
    public function destroy(VoucherPayment $voucher_payment)
    {
        $this->ensureCompanyContext();
        if (Auth::user()->company_id && $voucher_payment->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this voucher payment.');
        }
        $company = auth()->user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('voucher_payments.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        if ($voucher_payment->payment_status === 'paid') {
            return redirect()->back()
                ->with('error', 'Paid payments cannot be deleted.');
        }

        // Begin transaction to ensure atomicity
        \DB::beginTransaction();

        try {
            // Get the freight booking and voucher number before deletion
            $linked_voucher_id = $voucher_payment->linked_voucher_id;

            if ($linked_voucher_id) {
                // Handle linked voucher payments
                $vouchers = VoucherPayment::where('linked_voucher_id', $linked_voucher_id)->get();

                foreach ($vouchers as $voucher) {
                    // Use row-level locking to prevent race conditions
                    $freightBooking = \App\Models\FreightBooking::lockForUpdate()->findOrFail($voucher->freight_booking_id);

                    // Calculate effective payable amount (considering all deductions)
                    $effectivePayableAmount = $freightBooking->tr_amount
                        - ($freightBooking->tds_amount ?? 0)
                        - ($freightBooking->damage_amount ?? 0)
                        - ($freightBooking->shortage_amount ?? 0)
                        + ($freightBooking->detention ?? 0)
                        + ($freightBooking->deviation ?? 0)
                        - ($freightBooking->misc_expense ?? 0);

                    // Update advance_amount
                    $freightBooking->advance_amount -= $voucher->amount;

                    // Recalculate balance_amount using full formula
                    $freightBooking->balance_amount = $effectivePayableAmount - $freightBooking->advance_amount;

                    // Save the freight booking
                    $freightBooking->save();

                    // Delete the voucher payment
                    $voucher->delete();
                }

                // Delete the linked voucher record
                $linked_voucher = VoucherLinked::find($linked_voucher_id);
                if ($linked_voucher) {
                    $linked_voucher->delete();
                }
            } else {
                // Handle single voucher payment
                // Use row-level locking to prevent race conditions
                $freightBooking = \App\Models\FreightBooking::lockForUpdate()->findOrFail($voucher_payment->freight_booking_id);

                // Calculate effective payable amount (considering all deductions)
                $effectivePayableAmount = $freightBooking->tr_amount
                    - ($freightBooking->tds_amount ?? 0)
                    - ($freightBooking->damage_amount ?? 0)
                    - ($freightBooking->shortage_amount ?? 0)
                    + ($freightBooking->detention ?? 0)
                    + ($freightBooking->deviation ?? 0)
                    - ($freightBooking->misc_expense ?? 0);

                // Update advance_amount
                $freightBooking->advance_amount -= $voucher_payment->amount;

                // Recalculate balance_amount using full formula
                $freightBooking->balance_amount = $effectivePayableAmount - $freightBooking->advance_amount;

                // Save the freight booking
                $freightBooking->save();

                // Delete the voucher payment
                $voucher_payment->delete();
            }

            \DB::commit();

            // Redirect to the index page
            return redirect()->route('voucher_payments.index')
                ->with('success', 'Payment deleted successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('Error deleting voucher payment: ' . $e->getMessage(), [
                'voucher_payment_id' => $voucher_payment->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred while deleting the payment. Please try again.');
        }
    }

    /**
     * Update the payment status for a single payment
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request)
    {
        $this->ensureCompanyContext();
        if (! auth()->user()->canActAsAdmin()) {
            return redirect()->back()->with('error', 'You do not have permission to update payment status.');
        }

        $validated = $request->validate([
            'payment_id' => 'required|integer|exists:voucher_payments,id',
            'status' => 'required|in:pending,in-progress,paid',
            'redirect' => 'nullable|string'
        ]);

        try {
            $payment = VoucherPayment::findOrFail($validated['payment_id']);

            // Don't allow updating paid payments
            if ($payment->payment_status === 'paid') {
                return redirect()->back()->with('error', 'Paid payments cannot be updated.');
            }

            // Validate status progression
            if (!$this->isValidStatusTransition($payment->payment_status, $validated['status'])) {
                return redirect()->back()->with('error', 'Invalid status transition. Payments can only progress from pending to in-progress to paid.');
            }

            $payment->payment_status = $validated['status'];
            $payment->save();

            // Log the status change
            \Log::info('Payment status updated', [
                'payment_id' => $payment->id,
                'voucher_number' => $payment->voucher_number,
                'old_status' => $payment->getOriginal('payment_status'),
                'new_status' => $validated['status'],
                'updated_by' => auth()->id()
            ]);

            // Redirect to the specified URL or back to the previous page
            if (!empty($validated['redirect'])) {
                return redirect($validated['redirect'])
                    ->with('success', 'Payment status updated successfully to ' . ucfirst($validated['status']));
            }

            return redirect()->back()
                ->with('success', 'Payment status updated successfully to ' . ucfirst($validated['status']));
        } catch (\Exception $e) {
            \Log::error('Error updating payment status: ' . $e->getMessage(), [
                'payment_id' => $validated['payment_id'],
                'status' => $validated['status'],
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred while updating the payment status. Please try again.');
        }
    }

    /**
     * Check if the status transition is valid
     *
     * @param string $currentStatus
     * @param string $newStatus
     * @return bool
     */
    private function isValidStatusTransition($currentStatus, $newStatus)
    {
        // Valid transitions
        $validTransitions = [
            'pending' => ['in-progress'],
            'in-progress' => ['paid', 'pending'], // Allow going back to pending from in-progress
            'paid' => []
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }
}
