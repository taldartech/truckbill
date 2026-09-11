<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\City;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\PaymentMode;
use App\Models\PaymentType;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use App\Services\FreightBookingCalculationService;
use App\Services\Notifications\VoucherPaymentNotificationService;
use App\Services\TransporterAdvanceLedgerService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class VoucherPaymentController extends Controller
{
    public function __construct(
        private readonly VoucherPaymentNotificationService $voucherPaymentNotificationService,
        private readonly FreightBookingCalculationService $freightBookingCalculationService,
    ) {}

    private function bulkPaymentRequestLogContext(Request $request): array
    {
        $lorryReceipts = $request->input('lorry_receipts', []);
        $freightBookings = $request->input('freight_bookings', []);
        $amounts = $request->input('amounts', []);

        return [
            'payment_type' => $request->input('payment_type'),
            'payment_mode' => $request->input('payment_mode'),
            'company_id' => $request->input('company_id'),
            'pump_id' => $request->input('pump_id'),
            'date' => $request->input('date'),
            'lorry_receipts_count' => is_array($lorryReceipts) ? count($lorryReceipts) : 0,
            'freight_bookings_count' => is_array($freightBookings) ? count($freightBookings) : 0,
            'amounts_count' => is_array($amounts) ? count($amounts) : 0,
            'has_reference_transaction_id' => $request->filled('reference_transaction_id'),
            'has_remark' => $request->filled('remark'),
        ];
    }

    /**
     * Display the daily payments report
     *
     * @return View
     */
    public function dailyPayments(Request $request)
    {
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
            'creator.branch',
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
            'creator.branch',
        ])->whereDate('date', $selectedDate);

        if (Auth::user()->company_id) {
            $query->where('company_id', Auth::user()->company_id);
            $query1->where('company_id', Auth::user()->company_id);
        }

        // Apply Truck No filter
        if ($request->filled('truck_no')) {
            $query->whereHas('freight_booking.lr.truck', function ($q) use ($request) {
                $q->where('truck_number', 'like', '%'.$request->truck_no.'%');
            });
            $query1->whereHas('freight_booking.lr.truck', function ($q) use ($request) {
                $q->where('truck_number', 'like', '%'.$request->truck_no.'%');
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
                $q->where('lr_number', 'like', '%'.$request->lr_no.'%');
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

        $linkedLrIds = $payments1->pluck('freight_booking.linked_lr_id')
            ->filter()
            ->unique()
            ->values();

        $linkedLorryReceiptsByLinkedId = collect();
        if ($linkedLrIds->isNotEmpty()) {
            $linkedLorryReceiptsByLinkedId = LorryReceipt::with([
                'consignor.city',
                'consignee.city',
            ])
                ->whereIn('linked_lr_id', $linkedLrIds)
                ->whereNotNull('linked_lr_id')
                ->where('company_id', auth()->user()->company_id)
                ->get()
                ->groupBy('linked_lr_id');
        }

        $paymentModes = PaymentMode::select('name')->get();

        // Get branches for filter dropdown
        $branchesQuery = Branch::orderBy('branch_name');
        if (Auth::user()->company_id) {
            $branchesQuery->where('company_id', Auth::user()->company_id);
        }
        $branches = $branchesQuery->get();

        return view('voucher_payments.daily', [
            'payments' => $payments,
            'payments1' => $payments1,
            'selectedDate' => $selectedDate,
            'selectedVoucherPaymentTransporter' => $this->selectedTransporterFilterOptionForRequest($request),
            'selectedVoucherPaymentSourceCity' => $this->selectedCityFilterOptionForRequest($request, 'source'),
            'selectedVoucherPaymentDestinationCity' => $this->selectedCityFilterOptionForRequest($request, 'destination'),
            'paymentModes' => $paymentModes,
            'branches' => $branches,
            'linkedLorryReceiptsByLinkedId' => $linkedLorryReceiptsByLinkedId,
        ]);
    }

    public function index(Request $request)
    {
        $baseQuery = VoucherPayment::query();

        if (Auth::user()->company_id) {
            $baseQuery->where('company_id', Auth::user()->company_id);
        }

        // Apply filters
        if ($request->filled('payment_status')) {
            $baseQuery->where('payment_status', $request->payment_status);
        }

        if ($request->filled('voucher_number')) {
            $baseQuery->where('voucher_number', 'like', '%'.$request->voucher_number.'%');
        }

        // New filters
        if ($request->filled('truck_no')) {
            $baseQuery->whereHas('freight_booking.lr.truck', function ($q) use ($request) {
                $q->where('truck_number', 'like', '%'.$request->truck_no.'%');
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
                $q->where('lr_number', 'like', '%'.$request->lr_no.'%');
            });
        }

        if ($request->filled('freight_booking_no')) {
            $baseQuery->whereHas('freight_booking', function ($q) use ($request) {
                $q->where('id', 'like', '%'.$request->freight_booking_no.'%')
                    ->orWhere('voucher_number', 'like', '%'.$request->freight_booking_no.'%');
            });
        }

        $voucherGroups = (clone $baseQuery)->select('voucher_number')
            ->selectRaw('MAX(id) as latest_payment_id')
            ->selectRaw('SUM(amount) as total_amount')
            ->groupBy('voucher_number')
            ->orderByDesc('latest_payment_id')
            ->paginate(20)
            ->appends($request->except('page'));

        $latestPaymentIds = $voucherGroups->pluck('latest_payment_id')->filter()->values()->all();

        $payments = collect();
        if ($latestPaymentIds !== []) {
            $payments = VoucherPayment::query()
                ->with([
                    'company',
                    'freight_booking.lr.customer',
                    'freight_booking.lr.truck',
                    'freight_booking.lr.consignor.city',
                    'freight_booking.lr.consignee.city',
                    'freight_booking.transporter.transporterMaster',
                ])
                ->whereIn('id', $latestPaymentIds)
                ->get()
                ->keyBy(fn (VoucherPayment $payment): string => (string) $payment->voucher_number);
        }

        return view('voucher_payments.index', [
            'payments' => $payments,
            'voucherGroups' => $voucherGroups,
            'selectedVoucherPaymentTransporter' => $this->selectedTransporterFilterOptionForRequest($request),
            'selectedVoucherPaymentSourceCity' => $this->selectedCityFilterOptionForRequest($request, 'source'),
            'selectedVoucherPaymentDestinationCity' => $this->selectedCityFilterOptionForRequest($request, 'destination'),
        ]);
    }

    public function create(Request $request)
    {
        $company = auth()->user()->company;
        $company->loadMissing('subscription');
        if (! $company->subscriptionCanWrite()) {
            return redirect()->route('freight_bookings.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        $freight_booking_id = $request->freight_booking_id;
        $lorry_receipt_id = $request->lorry_receipt_id;
        // $lorry_receipt = \App\Models\LorryReceipt::select('id', 'lr_number','transporter_id','truck_id')
        //     ->when($lorry_receipt_id, function ($query) use ($lorry_receipt_id) {
        //         $query->where('id', $lorry_receipt_id);
        //     })
        //     ->get();

        $lorry_receipt = LorryReceipt::with(['transporter.transporterMaster', 'truck:id,truck_number'])
            ->select('id', 'lr_number', 'transporter_id', 'truck_id', 'customer_id', 'consignor_id', 'consignee_id')
            ->when($lorry_receipt_id, function ($query) use ($lorry_receipt_id) {
                $query->where('id', $lorry_receipt_id);
            })
            ->get();

        $vouchers = FreightBooking::select('voucher_number')
            ->when($freight_booking_id, function ($query) use ($freight_booking_id) {
                $query->where('id', $freight_booking_id);
            })
            ->get();
        $voucher_number = $vouchers->first()->voucher_number;

        // Get balance amount from freight booking
        $balanceAmount = null;
        if ($freight_booking_id) {
            $freightBooking = FreightBooking::find($freight_booking_id);
            if ($freightBooking) {
                if ($freightBooking->isLinked()) {
                    return redirect()->route('freight_bookings.index')
                        ->with('error', 'Linked freight bookings must be paid via combined voucher payment.');
                }

                $balanceAmount = $freightBooking->balance_amount;
            }
        }

        $paymentTypes = PaymentType::all();
        $paymentModes = PaymentMode::all();
        $pumps = Pump::where('user_id', Auth::user()->company->user_id)->get();

        return view('voucher_payments.create', compact('voucher_number', 'paymentTypes', 'paymentModes', 'freight_booking_id', 'lorry_receipt', 'balanceAmount', 'pumps'));
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
            'date' => 'nullable|date',
        ]);
        // If payment date is omitted, keep behavior aligned with legacy flow.
        if ($request->date == null) {
            $data['date'] = $request->created_at;
        }

        // Add company_id to data
        $data['company_id'] = auth()->user()->company_id;

        $freightBooking = FreightBooking::query()->findOrFail($data['freight_booking_id']);
        try {
            $this->freightBookingCalculationService->assertIndividualPaymentAllowed($freightBooking, false);
        } catch (\DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        // Check for duplicate payment on the same day
        $paymentDate = $data['date'] ?? now()->toDateString();
        $duplicatePayment = VoucherPayment::where('freight_booking_id', $data['freight_booking_id'])
            ->where('amount', $data['amount'])
            ->where('payment_type_id', $data['payment_type_id'])
            ->where('payment_mode_id', $data['payment_mode_id'])
            ->whereDate('date', $paymentDate)
            ->where('company_id', $data['company_id'])
            ->first();

        if ($duplicatePayment && ! $request->has('confirm_duplicate')) {
            // Return warning with duplicate payment details
            $freightBooking = FreightBooking::find($data['freight_booking_id']);

            return redirect()->back()
                ->withInput()
                ->with('warning', 'A similar payment already exists for this booking on '.date('d-m-Y', strtotime($paymentDate)).':
                    <br><strong>Amount:</strong> '.number_format($duplicatePayment->amount, 2).'
                    <br><strong>Payment Type:</strong> '.$duplicatePayment->payment_type.'
                    <br><strong>Payment Mode:</strong> '.$duplicatePayment->payment_mode.'
                    <br><strong>Created:</strong> '.$duplicatePayment->created_at->format('d-m-Y H:i:s').'
                    <br><br>If you want to proceed anyway, please confirm by checking "Confirm duplicate payment" and resubmit.')
                ->with('duplicate_payment_id', $duplicatePayment->id);
        }

        try {
            $voucherPayment = DB::transaction(function () use ($data) {
                // Use row-level locking to prevent race conditions
                $freightBooking = FreightBooking::lockForUpdate()->findOrFail($data['freight_booking_id']);
                $freightBooking->loadMissing('customAmounts');

                $effectivePayableAmount = $freightBooking->effectivePayableAmount();

                // Validate that advance amount won't exceed effective payable amount
                // Allow small tolerance (0.01) for floating point precision
                $newAdvanceAmount = $freightBooking->advance_amount + $data['amount'];
                if ($newAdvanceAmount > $effectivePayableAmount + 0.01) {
                    throw new \DomainException('Advance amount cannot exceed effective payable amount '.number_format($effectivePayableAmount, 2).'. Maximum allowed: '.number_format($effectivePayableAmount - $freightBooking->advance_amount, 2));
                }

                // Validate payment doesn't exceed balance
                if ($data['amount'] > $freightBooking->balance_amount) {
                    throw new \DomainException('Payment amount '.number_format($data['amount'], 2).' cannot exceed balance amount '.number_format($freightBooking->balance_amount, 2).'.');
                }

                // Create voucher payment
                $voucherPayment = VoucherPayment::create($data);

                $this->freightBookingCalculationService->recalculateFromVoucherPayments($freightBooking);
                $this->freightBookingCalculationService->applyNegativeAdjustment($freightBooking);

                return $voucherPayment;
            });

            $voucherPayment->loadMissing(['company', 'freight_booking.lr', 'freight_booking.transporter.transporterMaster']);
            $this->voucherPaymentNotificationService->notifyForCurrentStatus($voucherPayment, auth()->user());

            try {
                $voucherPayment->loadMissing(['company', 'freight_booking.transporter', 'freight_booking.truck', 'pump']);
                app(\App\Services\AccountingPostingService::class)->postVoucherPayment($voucherPayment);
            } catch (\Throwable $e) {
                \Log::error('Accounting post voucher payment failed: '.$e->getMessage());
            }

            // Redirect back to lorry receipts page with success message
            return redirect()->route('lorry_receipts.index')
                ->with('success', 'Payment allocated successfully.');
        } catch (\DomainException $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Error processing voucher payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $data,
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
     * @return View
     */
    public function show($voucherNumber)
    {
        // Get all payments for this voucher number with relationships
        $payments = VoucherPayment::with([
            'freight_booking.lr.customer',
            'freight_booking.lr.consignor.city',
            'freight_booking.lr.consignee.city',
            'freight_booking.lr.truck',
            'freight_booking.lr.transporter',
            'freight_booking.company',
            'pump',
            'creator.branch',
            'updater.branch',
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
            'firstPayment' => $firstPayment,
        ]);
    }

    public function edit(Request $request, VoucherPayment $voucher_payment)
    {
        if (Auth::user()->company_id && $voucher_payment->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this voucher payment.');
        }
        $company = auth()->user()->company;
        $company->loadMissing('subscription');
        if (! $company->subscriptionCanWrite()) {
            return redirect()->route('voucher_payments.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        $paymentTypes = PaymentType::all();
        $paymentModes = PaymentMode::all();
        $pumps = Pump::where('user_id', Auth::user()->company->user_id)->get();

        // Get lorry receipt and freight booking from request parameters
        $lorry_receipt_id = $request->query('lorry_receipt_id', $voucher_payment->lorry_receipt_id);
        $freight_booking_id = $request->query('freight_booking_id', $voucher_payment->freight_booking_id);

        // Get the lorry receipt
        $lorry_receipt = LorryReceipt::with(['transporter.transporterMaster', 'truck:id,truck_number', 'customer:id,name', 'consignor:id,name', 'consignee:id,name'])
            ->select('id', 'lr_number', 'transporter_id', 'truck_id', 'customer_id', 'consignor_id', 'consignee_id')
            ->where('id', $lorry_receipt_id)
            ->get();
        // Get freight booking details with relationships
        $balanceAmount = null;
        $freightBooking = null;
        if ($freight_booking_id) {
            $freightBooking = FreightBooking::with(['transporter.transporterMaster'])
                ->find($freight_booking_id);
            if ($freightBooking) {
                $balanceAmount = $freightBooking->balance_amount;
            }
        }

        $lockPaymentMode = $voucher_payment->payment_status === 'paid'
            || (
                $voucher_payment->payment_status === 'in-progress'
                && $this->isBankOrFuelPaymentModeName($voucher_payment->payment_mode)
            );

        return view('voucher_payments.edit', compact(
            'voucher_payment',
            'paymentTypes',
            'paymentModes',
            'lorry_receipt',
            'freight_booking_id',
            'balanceAmount',
            'pumps',
            'freightBooking',
            'lockPaymentMode'
        ));
    }

    public function update(Request $request, VoucherPayment $voucher_payment)
    {
        $activeCompanyId = auth()->user()->company_id;
        if (Auth::user()->company_id && $voucher_payment->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this voucher payment.');
        }
        $company = auth()->user()->company;
        $company->loadMissing('subscription');
        if (! $company->subscriptionCanWrite()) {
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
        if ((int) $data['company_id'] !== (int) $activeCompanyId) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid company context for this request.');
        }
        $data['company_id'] = (int) $activeCompanyId;

        if (empty($data['date'])) {
            $data['date'] = now()->toDateString();
        }

        if (
            $voucher_payment->payment_status === 'in-progress'
            && $this->isBankOrFuelPaymentModeName($voucher_payment->payment_mode)
            && (int) $data['payment_mode_id'] !== (int) $voucher_payment->payment_mode_id
        ) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Payment mode cannot be changed while a bank or fuel payment is in progress.');
        }

        $oldFreightBookingId = (int) $voucher_payment->freight_booking_id;
        $newFreightBookingId = (int) $data['freight_booking_id'];

        DB::beginTransaction();

        try {
            $previousPaymentStatus = $voucher_payment->payment_status;

            $idsToLock = collect([$oldFreightBookingId, $newFreightBookingId])->unique()->sort()->values()->all();
            foreach ($idsToLock as $id) {
                FreightBooking::lockForUpdate()->findOrFail($id);
            }

            $newFreightBooking = FreightBooking::findOrFail($newFreightBookingId);
            $effectivePayableAmount = $newFreightBooking->effectivePayableAmount();

            $sumOtherPaymentsOnNew = (float) VoucherPayment::where('freight_booking_id', $newFreightBookingId)
                ->where('id', '!=', $voucher_payment->id)
                ->sum('amount');

            $projectedAdvanceOnNew = $sumOtherPaymentsOnNew + (float) $data['amount'];
            if ($projectedAdvanceOnNew > $effectivePayableAmount + 0.01) {
                DB::rollBack();
                $maxAllowed = max(0, $effectivePayableAmount - $sumOtherPaymentsOnNew);

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Total payments for this freight booking cannot exceed effective payable amount '.number_format($effectivePayableAmount, 2).'. Maximum for this payment: '.number_format($maxAllowed, 2));
            }

            if ($projectedAdvanceOnNew < -0.01) {
                DB::rollBack();

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid payment amount.');
            }

            $oldFbModel = FreightBooking::find($oldFreightBookingId);
            $wasAdjustInAdvance = $this->isAdjustInAdvancePaymentMode($voucher_payment->payment_mode);
            $nowAdjustInAdvance = $this->isAdjustInAdvancePaymentMode($data['payment_mode']);

            $transporterIdsToLock = collect();
            if ($wasAdjustInAdvance && $oldFbModel && $oldFbModel->transporter_id) {
                $transporterIdsToLock->push((int) $oldFbModel->transporter_id);
            }
            if ($nowAdjustInAdvance && $newFreightBooking->transporter_id) {
                $transporterIdsToLock->push((int) $newFreightBooking->transporter_id);
            }
            foreach ($transporterIdsToLock->unique()->sort()->values() as $tid) {
                Transporter::lockForUpdate()->findOrFail($tid);
            }

            if ($wasAdjustInAdvance) {
                if (! $oldFbModel || ! $oldFbModel->transporter_id) {
                    DB::rollBack();

                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Cannot reverse adjust in advance: original freight booking has no transporter.');
                }
                $this->recordAdvancePaymentForTransporter(
                    (int) $oldFbModel->transporter_id,
                    (float) $voucher_payment->amount,
                    $oldFbModel,
                    'Voucher payment edit: reverse adjust in advance'
                );
            }

            if ($nowAdjustInAdvance) {
                if (! $newFreightBooking->transporter_id) {
                    DB::rollBack();

                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Adjust in advance requires a transporter on the freight booking.');
                }
                $transporter = Transporter::query()->find($newFreightBooking->transporter_id);
                if ((float) $data['amount'] > (float) $transporter->advance_amount + 0.01) {
                    DB::rollBack();

                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Transporter advance ('.number_format((float) $transporter->advance_amount, 2).') is less than payment amount '.number_format((float) $data['amount'], 2).'.');
                }
                $this->recordAdvancePaymentForTransporter(
                    (int) $newFreightBooking->transporter_id,
                    -(float) $data['amount'],
                    $newFreightBooking,
                    'Voucher payment edit: adjust in advance'
                );
            }

            $paymentModeChanged = (int) $voucher_payment->payment_mode_id !== (int) $data['payment_mode_id'];
            if ($paymentModeChanged) {
                $oldMode = $voucher_payment->payment_mode;
                $newMode = $data['payment_mode'];
                $oldStatus = $voucher_payment->payment_status;

                if (
                    $this->isCashOrAdjustPaymentModeName($oldMode)
                    && $this->isBankOrFuelPaymentModeName($newMode)
                ) {
                    if ($oldStatus === 'in-progress') {
                        $data['payment_status'] = 'pending';
                    } else {
                        $data['payment_status'] = $this->defaultPaymentStatusForPaymentModeName($newMode);
                    }
                } elseif (
                    $this->isBankOrFuelPaymentModeName($oldMode)
                    && $this->isCashOrAdjustPaymentModeName($newMode)
                ) {
                    if ($oldStatus === 'pending') {
                        $data['payment_status'] = 'in-progress';
                    } else {
                        $data['payment_status'] = $this->defaultPaymentStatusForPaymentModeName($newMode);
                    }
                } else {
                    $data['payment_status'] = $this->defaultPaymentStatusForPaymentModeName($newMode);
                }
            }

            $adminAutoPaid = false;
            if (
                auth()->user()->userRole
                && auth()->user()->canActAsAdmin()
                && $this->isBankOrFuelPaymentModeName($data['payment_mode'])
                && $data['payment_status'] === 'in-progress'
                && $request->filled('reference_transaction_id')
            ) {
                $data['payment_status'] = 'paid';
                $adminAutoPaid = true;
            }

            $statusError = $this->validatePaymentStatusRulesForUpdate($voucher_payment, $data['payment_mode'], $data['payment_status']);
            if ($statusError !== null) {
                DB::rollBack();

                return redirect()->back()->withInput()->with('error', $statusError);
            }

            if (
                ! $adminAutoPaid
                && ! $this->isValidStatusTransition(
                    $voucher_payment->payment_status,
                    $data['payment_status'],
                    $voucher_payment,
                    $data['payment_mode']
                )
            ) {
                DB::rollBack();

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid payment status transition for this payment mode (e.g. cannot skip from pending to paid, or use pending where it is not allowed).');
            }

            if ($voucher_payment->linked_voucher_id != null) {
                $linked_voucher = VoucherLinked::find($voucher_payment->linked_voucher_id);
                if ($linked_voucher) {
                    $linked_voucher->update([
                        'amount' => $linked_voucher->amount + ($data['amount'] - $voucher_payment->amount),
                    ]);
                }
            }

            $voucher_payment->update($data);

            foreach ($idsToLock as $fbId) {
                $this->recalculateFreightBookingFromVoucherPayments($fbId);
            }

            $affectedBookings = FreightBooking::query()
                ->whereIn('id', $idsToLock)
                ->get();
            $this->freightBookingCalculationService->applyNegativeAdjustmentsForBookings($affectedBookings);

            DB::commit();

            $voucher_payment->refresh();
            if ($previousPaymentStatus !== $voucher_payment->payment_status) {
                $voucher_payment->loadMissing(['company', 'freight_booking.lr', 'freight_booking.transporter.transporterMaster']);
                $this->voucherPaymentNotificationService->notifyForCurrentStatus($voucher_payment, auth()->user());
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating voucher payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_id' => $voucher_payment->id,
                'request_data' => $data,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while updating the payment. Please try again.');
        }

        return redirect()->route('voucher_payments.index')
            ->with('success', 'Voucher Payment updated successfully. Freight booking advance and balance have been synced from payments.');
    }

    public function bulkPayment(Request $request)
    {
        $activeCompanyId = auth()->user()->company_id;
        try {
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
            if ((int) $validated['company_id'] !== (int) $activeCompanyId) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid company context for this request.');
            }

            // Get payment type and mode
            $paymentType = PaymentType::where('name', $validated['payment_type'])->first();
            $paymentMode = PaymentMode::where('name', $validated['payment_mode'])->first();

            if (! $paymentType || ! $paymentMode) {
                return redirect()->back()->with('error', 'Invalid payment type or mode');
            }

            $reference_transaction_id = $validated['reference_transaction_id'] ?? null;
            $remark = $validated['remark'] ?? null;
            $payment_status = $paymentMode->name === 'cash' || $paymentMode->name === 'adjust in advance' ? 'in-progress' : 'pending';
            $company_id = (int) $activeCompanyId;

            // Process each voucher payment
            $lorryReceipts = $validated['lorry_receipts'];
            $freightBookings = $validated['freight_bookings'];
            $amounts = $validated['amounts'];

            $processedBookings = [];

            foreach ($lorryReceipts as $index => $lorryReceiptId) {
                $freightBookingId = $freightBookings[$lorryReceiptId] ?? null;
                $amount = $amounts[$lorryReceiptId] ?? 0;

                if (! $freightBookingId || $amount <= 0) {
                    continue;
                }

                $freightBooking = FreightBooking::with('customAmounts')->find($freightBookingId);
                if (! $freightBooking) {
                    \Log::error('Freight booking not found', ['freight_booking_id' => $freightBookingId]);

                    continue;
                }

                $effectivePayableAmount = $freightBooking->effectivePayableAmount();

                try {
                    $this->freightBookingCalculationService->assertIndividualPaymentAllowed($freightBooking, false);
                } catch (\DomainException $e) {
                    \Log::error('Bulk payment rejected for linked booking', [
                        'freight_booking_id' => $freightBooking->id,
                        'message' => $e->getMessage(),
                    ]);

                    continue;
                }

                if (($freightBooking->advance_amount + $amount) > $effectivePayableAmount + 0.01) {
                    \Log::error('Advance amount exceeds effective payable amount', [
                        'freight_booking_id' => $freightBooking->id,
                        'advance_amount' => $freightBooking->advance_amount,
                        'effective_payable_amount' => $effectivePayableAmount,
                        'new_amount' => $amount,
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
                $this->freightBookingCalculationService->recalculateFromVoucherPayments($freightBooking);
                $processedBookings[$freightBooking->id] = $freightBooking->fresh();
            }

            $this->freightBookingCalculationService->applyNegativeAdjustmentsForBookings(
                collect($processedBookings)->values()
            );

            return redirect()->route('voucher_payments.index')
                ->with('success', 'Bulk voucher payments created successfully.');
        } catch (\Exception $e) {
            \Log::error('Bulk voucher payment error: '.$e->getMessage(), [
                'request_context' => $this->bulkPaymentRequestLogContext($request),
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
        if (! auth()->user()->canActAsAdmin()) {
            return redirect()->back()->with('error', 'You do not have permission to update payment status.');
        }

        $validated = $request->validate([
            'payment_ids' => 'nullable|array',
            'payment_ids.*' => 'required|integer|exists:voucher_payments,id',
            'multiple_payment_ids' => 'nullable|array',
            'multiple_payment_ids.*' => 'required|integer|exists:voucher_linkeds,id',
            'new_status' => 'required|in:pending,in-progress,paid',
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

            // Don't allow updating paid payments
            if ($currentStatus === 'paid') {
                return redirect()->back()->with('error', 'Paid payments cannot be updated.');
            }
            $voucher_ids = $linkedPayments
                ->pluck('voucher_ids')
                ->flatMap(function ($voucherIds) {
                    $decoded = json_decode($voucherIds, true);

                    return is_array($decoded) ? $decoded : [];
                })
                ->all();
            $voucher_ids = array_merge($payment_ids, $voucher_ids);
            $voucherPayments = VoucherPayment::whereIn('id', $voucher_ids)->get();
            foreach ($voucherPayments as $payment) {
                if ($payment->payment_status === 'paid') {
                    return redirect()->back()->with('error', 'Paid payments cannot be updated.');
                }
                if (! $this->isValidStatusTransition($payment->payment_status, $validated['new_status'], $payment)) {
                    return redirect()->back()->with('error', 'Invalid status transition for one or more payments (paid cannot revert; bank/fuel in progress cannot go to pending; cash/advance cannot use pending).');
                }
            }
            foreach ($voucherPayments as $payment) {
                $payment->payment_status = $validated['new_status'];
                $payment->save();
            }

            foreach ($voucherPayments as $payment) {
                $payment->refresh();
                $payment->loadMissing(['company', 'freight_booking.lr', 'freight_booking.transporter.transporterMaster']);
                $this->voucherPaymentNotificationService->notifyForCurrentStatus($payment, auth()->user());
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
            $newUrl = $parsedUrl['path'].'?'.$newQuery;

            return redirect($newUrl)->with('success', $updatedCount.' payments updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error in bulk update: '.$e->getMessage(), [
                'payment_ids' => $payment_ids,
                'multiple_payment_ids' => $multiple_payment_ids,
                'new_status' => $validated['new_status'],
            ]);

            return redirect()->back()->with('error', 'Error updating payment status: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified voucher payment from storage (soft delete).
     *
     * @return Response
     */
    public function destroy(VoucherPayment $voucher_payment)
    {
        if (Auth::user()->company_id && $voucher_payment->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this voucher payment.');
        }
        $company = auth()->user()->company;
        $company->loadMissing('subscription');
        if (! $company->subscriptionCanWrite()) {
            return redirect()->route('voucher_payments.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        if ($voucher_payment->payment_status === 'paid') {
            return redirect()->back()
                ->with('error', 'Paid payments cannot be deleted.');
        }

        try {
            DB::transaction(function () use ($voucher_payment) {
                $linked_voucher_id = $voucher_payment->linked_voucher_id;
                $affectedBookings = collect();

                if ($linked_voucher_id) {
                    $vouchers = VoucherPayment::where('linked_voucher_id', $linked_voucher_id)->get();

                    foreach ($vouchers as $voucher) {
                        $freightBooking = FreightBooking::lockForUpdate()->findOrFail($voucher->freight_booking_id);
                        $voucher->delete();
                        $this->freightBookingCalculationService->recalculateFromVoucherPayments($freightBooking);
                        $affectedBookings->put($freightBooking->id, $freightBooking->fresh());
                    }

                    $linked_voucher = VoucherLinked::find($linked_voucher_id);
                    if ($linked_voucher) {
                        $linked_voucher->delete();
                    }
                } else {
                    $freightBooking = FreightBooking::lockForUpdate()->findOrFail($voucher_payment->freight_booking_id);
                    $voucher_payment->delete();
                    $this->freightBookingCalculationService->recalculateFromVoucherPayments($freightBooking);
                    $affectedBookings->put($freightBooking->id, $freightBooking->fresh());
                }

                $this->freightBookingCalculationService->applyNegativeAdjustmentsForBookings($affectedBookings->values());
            });

            // Redirect to the index page
            return redirect()->route('voucher_payments.index')
                ->with('success', 'Payment deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Error deleting voucher payment: '.$e->getMessage(), [
                'voucher_payment_id' => $voucher_payment->id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred while deleting the payment. Please try again.');
        }
    }

    /**
     * Update the payment status for a single payment
     *
     * @return Response
     */
    public function updateStatus(Request $request)
    {
        if (! auth()->user()->canActAsAdmin()) {
            return redirect()->back()->with('error', 'You do not have permission to update payment status.');
        }

        $validated = $request->validate([
            'payment_id' => 'required|integer|exists:voucher_payments,id',
            'status' => 'required|in:pending,in-progress,paid',
            'redirect' => 'nullable|string',
        ]);

        try {
            $payment = VoucherPayment::findOrFail($validated['payment_id']);

            // Don't allow updating paid payments
            if ($payment->payment_status === 'paid') {
                return redirect()->back()->with('error', 'Paid payments cannot be updated.');
            }

            if (! $this->isValidStatusTransition($payment->payment_status, $validated['status'], $payment)) {
                return redirect()->back()->with('error', 'Invalid status transition. Paid cannot revert; bank/fuel in progress cannot go to pending; cash and adjust in advance cannot use pending.');
            }

            $oldPaymentStatus = $payment->payment_status;

            $payment->payment_status = $validated['status'];
            $payment->save();

            if ($oldPaymentStatus !== $validated['status']) {
                $payment->loadMissing(['company', 'freight_booking.lr', 'freight_booking.transporter.transporterMaster']);
                $this->voucherPaymentNotificationService->notifyForCurrentStatus($payment, auth()->user());
            }

            // Redirect to the specified URL or back to the previous page
            if (! empty($validated['redirect'])) {
                return redirect($validated['redirect'])
                    ->with('success', 'Payment status updated successfully to '.ucfirst($validated['status']));
            }

            return redirect()->back()
                ->with('success', 'Payment status updated successfully to '.ucfirst($validated['status']));
        } catch (\Exception $e) {
            \Log::error('Error updating payment status: '.$e->getMessage(), [
                'payment_id' => $validated['payment_id'],
                'status' => $validated['status'],
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('error', 'An error occurred while updating the payment status. Please try again.');
        }
    }

    /**
     * @return array{id: int, text: string}|null
     */
    private function selectedTransporterFilterOptionForRequest(Request $request): ?array
    {
        if (! $request->filled('transporter')) {
            return null;
        }

        $companyId = Auth::user()?->company_id;
        if (! $companyId) {
            return null;
        }

        $id = (int) $request->input('transporter');
        $row = Transporter::query()
            ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
            ->where('transporters.company_id', $companyId)
            ->where('transporters.id', $id)
            ->select('transporters.id', 'transporters.is_owner')
            ->addSelect('transporter_masters.name as master_name')
            ->first();

        if ($row === null) {
            return null;
        }

        $name = (string) ($row->getAttribute('master_name') ?? '');
        $text = $name !== '' ? $name : 'Not Set';
        $text .= $row->is_owner ? ' (Owner)' : ' (Agency)';

        return [
            'id' => (int) $row->id,
            'text' => $text,
        ];
    }

    /**
     * @return array{id: int, text: string}|null
     */
    private function selectedCityFilterOptionForRequest(Request $request, string $requestKey): ?array
    {
        if (! $request->filled($requestKey)) {
            return null;
        }

        $id = (int) $request->input($requestKey);
        $row = City::query()->where('id', $id)->select(['id', 'name'])->first();

        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'text' => (string) $row->name,
        ];
    }

    private function isBankOrFuelPaymentModeName(?string $paymentMode): bool
    {
        $n = strtolower(trim((string) $paymentMode));

        return $n === 'online'
            || $n === 'bank transfer'
            || str_contains($n, 'bank')
            || $n === 'fuel'
            || str_contains($n, 'fuel')
            || str_contains($n, 'pump');
    }

    private function isCashOrAdjustPaymentModeName(?string $paymentMode): bool
    {
        $n = strtolower(trim((string) $paymentMode));

        return $n === 'cash' || $n === 'adjust in advance';
    }

    private function validatePaymentStatusRulesForUpdate(VoucherPayment $original, string $newModeName, string $newStatus): ?string
    {
        if ($original->payment_status === 'paid' && $newStatus !== 'paid') {
            return 'Paid payments cannot be reverted to a previous status.';
        }
        if ($this->isCashOrAdjustPaymentModeName($newModeName) && $newStatus === 'pending') {
            return 'Cash and adjust in advance payments cannot use Pending status.';
        }
        if (
            $original->payment_status === 'in-progress'
            && $this->isBankOrFuelPaymentModeName($original->payment_mode)
            && $newStatus === 'pending'
        ) {
            return 'Bank or fuel payments that are in progress cannot be set back to Pending.';
        }

        return null;
    }

    private function defaultPaymentStatusForPaymentModeName(string $paymentModeName): string
    {
        $name = strtolower(trim($paymentModeName));
        if ($name === 'cash' || $name === 'adjust in advance') {
            return 'in-progress';
        }
        if (
            $name === 'bank transfer'
            || $name === 'fuel'
            || str_contains($name, 'fuel')
            || str_contains($name, 'pump')
            || str_contains($name, 'bank')
        ) {
            return 'pending';
        }

        return 'pending';
    }

    private function isAdjustInAdvancePaymentMode(?string $paymentMode): bool
    {
        return $paymentMode !== null && strtolower(trim($paymentMode)) === 'adjust in advance';
    }

    private function recordAdvancePaymentForTransporter(int $transporterId, float $signedAmount, FreightBooking $freightBooking, string $remarkNote): void
    {
        $lrNumber = $freightBooking->lr?->lr_number ?? (string) $freightBooking->id;

        app(TransporterAdvanceLedgerService::class)->recordAdvancePayment(
            companyId: (int) $freightBooking->company_id,
            transporterId: $transporterId,
            advanceAmount: $signedAmount,
            paymentDate: now()->toDateString(),
            remark: 'LR#'.$lrNumber.' – '.$remarkNote,
            actor: auth()->user(),
            notifyCredit: false
        );
    }

    private function recalculateFreightBookingFromVoucherPayments(int $freightBookingId): void
    {
        $freightBooking = FreightBooking::lockForUpdate()->findOrFail($freightBookingId);
        $this->freightBookingCalculationService->recalculateFromVoucherPayments($freightBooking);
    }

    /**
     * @param  string|null  $newPaymentModeName  After update row mode; used so cash→bank can become pending without being rejected using the old cash mode.
     */
    private function isValidStatusTransition(
        string $currentStatus,
        string $newStatus,
        ?VoucherPayment $payment = null,
        ?string $newPaymentModeName = null
    ): bool {
        if ($currentStatus === 'paid') {
            return false;
        }
        if ($currentStatus === $newStatus) {
            return true;
        }
        if ($payment !== null) {
            $modeForPendingTarget = $newPaymentModeName ?? $payment->payment_mode;
            if ($newStatus === 'pending' && $this->isCashOrAdjustPaymentModeName($modeForPendingTarget)) {
                return false;
            }
            if (
                $newStatus === 'pending'
                && $currentStatus === 'in-progress'
                && $this->isBankOrFuelPaymentModeName($payment->payment_mode)
            ) {
                return false;
            }
        }

        $validTransitions = [
            'pending' => ['in-progress'],
            'in-progress' => ['paid', 'pending'],
            'paid' => [],
        ];

        return in_array($newStatus, $validTransitions[$currentStatus] ?? [], true);
    }
}
