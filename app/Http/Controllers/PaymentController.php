<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PaymentLedger;
use App\Models\PromoCode;
use App\Models\UpcomingPlan;
use App\Services\Notifications\CompanyLifecycleNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CompanyLifecycleNotificationService $companyLifecycleNotificationService
    ) {}

    /**
     * Display a listing of companies for payment management (superadmin only)
     */
    public function index(Request $request)
    {
        // Only superadmin can access
        if (Auth::user()->userRole->name !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }

        $companies = Company::where('user_id', Auth::id())
            ->orderBy('plan_end_date', 'asc')
            ->get();

        return view('payment.index', compact('companies'));
    }

    /**
     * Show the form for creating a payment/plan management
     */
    public function create(Request $request)
    {
        // Only superadmin can access
        if (Auth::user()->userRole->name !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }

        $companyId = $request->get('company_id');
        if (! $companyId) {
            return redirect()->route('payment.index')
                ->with('error', 'Please select a company.');
        }

        $company = Company::findOrFail($companyId);

        if ($company->user_id != Auth::id()) {
            abort(403, 'Unauthorized access to this company.');
        }

        // Check if plan is expired
        $isExpired = false;
        if ($company->plan_end_date) {
            $isExpired = Carbon::parse($company->plan_end_date)->isBefore(today());
        }

        $truck_rate = [
            config('payments.truck_count_1') => config('payments.truck_rate_1'),
            config('payments.truck_count_2') => config('payments.truck_rate_2'),
            config('payments.truck_count_3') => config('payments.truck_rate_3'),
        ];

        $lr_credits = [
            config('payments.lr_credits_1') => config('payments.lr_credit_rate_1'),
            config('payments.lr_credits_2') => config('payments.lr_credit_rate_2'),
            config('payments.lr_credits_3') => config('payments.lr_credit_rate_3'),
        ];

        $annualMaintenanceAmount = [
            'Both' => config('payments.annual_maintenance_both'),
            'Broker' => config('payments.annual_maintenance_broker'),
            'Agency' => config('payments.annual_maintenance_agency'),
        ];

        $discount = config('payments.discount');

        $serviceGstPercentage = config('payments.service_gst_percentage');

        return view('payment.create', compact('company', 'isExpired', 'truck_rate', 'lr_credits', 'annualMaintenanceAmount', 'discount', 'serviceGstPercentage'));
    }

    /**
     * Store payment/plan update and create Razorpay order
     */
    public function store(Request $request)
    {
        // Only superadmin can access
        if (Auth::user()->userRole->name !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'want_to_renew' => 'nullable|in:yes,no',
            'truck_count' => 'nullable|integer|min:1',
            'lr_credits' => 'nullable|integer|min:0',
            'addon_truck_count' => 'nullable|integer|min:0',
            'addon_lr_credits' => 'nullable|integer|min:0',
            'tenure' => 'required|in:1,2,3',
            'promo_code' => 'nullable|string',
        ]);

        $company = Company::findOrFail($request->company_id);

        if ($company->user_id != Auth::id()) {
            abort(403, 'Unauthorized access to this company.');
        }

        // Check if plan is expired
        $isExpired = false;
        if ($company->plan_end_date) {
            $isExpired = Carbon::parse($company->plan_end_date)->isBefore(today());
        }

        // Determine payment type
        $paymentType = 'renewal';
        if (! $isExpired && $request->want_to_renew === 'no') {
            $paymentType = 'addon';
        }

        // Calculate payment amounts
        $calculationResult = $this->calculatePaymentAmounts($request, $company, $paymentType, $isExpired);

        // Validate promo code if provided
        $promoCode = null;
        $promoDiscount = 0;
        if ($request->promo_code) {
            $promoCode = $request->promo_code;
            $promoRequest = new Request(['code' => $request->promo_code]);
            $promoResponse = $this->applyPromoCode($promoRequest);
            if ($promoResponse->getStatusCode() !== 200) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid or expired promo code.',
                    ], 400);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid or expired promo code.');
            }
            $promoData = $promoResponse->getData();
            if ($promoData->discount_type === 'percentage') {
                $promoDiscount = ($calculationResult['subtotal'] * $promoData->discount_value) / 100;
            } else {
                $promoDiscount = $promoData->discount_value;
            }
        }
        if ($promoDiscount > $calculationResult['subtotal']) {
            $promoDiscount = 0;
            $promoCode = null;
        }
        $subtotalAfterDiscount = $calculationResult['subtotal'] - $promoDiscount;
        $gstAmount = ($subtotalAfterDiscount * config('payments.service_gst_percentage')) / 100;
        $totalAmount = $subtotalAfterDiscount + $gstAmount;

        // Initialize Razorpay
        $razorpayKey = config('payments.razorpay_key');
        $razorpaySecret = config('payments.razorpay_secret');

        if (empty($razorpayKey) || empty($razorpaySecret)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Razorpay configuration is missing. Please contact administrator.',
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Razorpay configuration is missing. Please contact administrator.');
        }

        $api = new Api($razorpayKey, $razorpaySecret);

        try {
            // Create Razorpay order
            $orderData = [
                'receipt' => 'TXN_'.time().'_'.$company->id,
                'amount' => round($totalAmount * 100), // Amount in paise
                'currency' => 'INR',
                'notes' => [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'payment_type' => $paymentType,
                ],
            ];

            $razorpayOrder = $api->order->create($orderData);

            // Store transaction in database
            $transaction = PaymentLedger::create([
                'company_id' => $company->id,
                'razorpay_order_id' => $razorpayOrder['id'],
                'payment_type' => $paymentType,
                'truck_count' => $request->truck_count,
                'lr_credits' => $request->lr_credits,
                'addon_truck_count' => $request->addon_truck_count,
                'addon_lr_credits' => $request->addon_lr_credits,
                'tenure' => $request->tenure,
                'truck_cost' => $calculationResult['truck_cost'],
                'lr_credits_cost' => $calculationResult['lr_credits_cost'],
                'annual_maintenance_cost' => $calculationResult['annual_maintenance_cost'],
                'subtotal' => $calculationResult['subtotal'],
                'promo_code_id' => $promoCode ? PromoCode::where('code', $promoCode)->first()->id : null,
                'promo_discount' => $promoDiscount,
                'gst_amount' => $gstAmount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'razorpay_response' => $razorpayOrder->toArray(),
            ]);

            // Return JSON response with order details for frontend
            return response()->json([
                'success' => true,
                'order_id' => $razorpayOrder['id'],
                'amount' => round($totalAmount * 100),
                'key' => $razorpayKey,
                'name' => config('app.name'),
                'description' => 'Payment for '.ucfirst($paymentType).' - '.$company->name,
                'prefill' => [
                    'name' => $company->contact_person ?? $company->name,
                    'email' => Auth::user()->email ?? '',
                    'contact' => $company->contact_number ?? '',
                ],
                'theme' => [
                    'color' => '#2FB36F',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed: '.$e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to create payment order. Please try again.',
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create payment order. Please try again.');
        }
    }

    /**
     * Calculate payment amounts based on selected options
     */
    private function calculatePaymentAmounts($request, $company, $paymentType, $isExpired)
    {
        $truckRate = [
            config('payments.truck_count_1') => config('payments.truck_rate_1'),
            config('payments.truck_count_2') => config('payments.truck_rate_2'),
            config('payments.truck_count_3') => config('payments.truck_rate_3'),
        ];

        $lrCreditsRate = [
            config('payments.lr_credits_1') => config('payments.lr_credit_rate_1'),
            config('payments.lr_credits_2') => config('payments.lr_credit_rate_2'),
            config('payments.lr_credits_3') => config('payments.lr_credit_rate_3'),
        ];

        $annualMaintenanceAmount = [
            'Both' => config('payments.annual_maintenance_both'),
            'Broker' => config('payments.annual_maintenance_broker'),
            'Agency' => config('payments.annual_maintenance_agency'),
        ];

        $discount = config('payments.discount');
        $tenure = (int) $request->tenure;

        $truckCost = 0;
        $lrCreditsCost = 0;
        $annualMaintenanceCost = 0;

        if ($paymentType === 'renewal') {
            // Calculate Truck Cost
            if ($company->company_type != 'Broker' && $request->truck_count) {
                $truckCount = (int) $request->truck_count;
                $remainingTrucks = $truckCount;

                // Parse truck rate slabs
                $truckRateSlabs = [];
                foreach ($truckRate as $count => $rate) {
                    if (strpos($count, '+') !== false) {
                        $min = (int) str_replace('+', '', $count) + 1;
                        $truckRateSlabs[] = ['min' => $min, 'max' => PHP_INT_MAX, 'rate' => $rate];
                    } elseif (strpos($count, '-') !== false) {
                        [$min, $max] = explode('-', $count);
                        $truckRateSlabs[] = ['min' => (int) $min, 'max' => (int) $max, 'rate' => $rate];
                    }
                }
                usort($truckRateSlabs, fn ($a, $b) => $a['min'] <=> $b['min']);

                foreach ($truckRateSlabs as $slab) {
                    if ($remainingTrucks <= 0) {
                        break;
                    }

                    $slabSize = $slab['max'] === PHP_INT_MAX ? $remainingTrucks : ($slab['max'] - $slab['min'] + 1);
                    $trucksInThisSlab = min($remainingTrucks, $slabSize);

                    $truckCost += $trucksInThisSlab * $slab['rate'];
                    $remainingTrucks -= $trucksInThisSlab;
                }

                $truckCost = $tenure == 3 ? ($truckCost * $tenure) * (1 - $discount / 100) : $truckCost * $tenure;
            }

            // Calculate LR Credits Cost
            if ($company->company_type != 'Agency' && $request->lr_credits) {
                $lrCredits = (int) $request->lr_credits;
                $lrCreditsCost = $lrCreditsRate[$lrCredits] ?? 0;
            }

            // Calculate Annual Maintenance Cost
            $annualMaintenance = $annualMaintenanceAmount[$company->company_type] ?? 0;
            $annualMaintenanceCost = $tenure == 3 ? ($annualMaintenance * $tenure) * (1 - $discount / 100) : $annualMaintenance * $tenure;
        } else {
            // Addon Plan Calculation
            if ($company->company_type != 'Agency' && $request->addon_lr_credits) {
                $lrCredits = (int) $request->addon_lr_credits;
                $lrCreditsCost = $lrCreditsRate[$lrCredits] ?? 0;
            }

            if ($company->company_type != 'Broker' && $request->addon_truck_count) {
                $addonTruckCount = (int) $request->addon_truck_count;
                $currentTruckCount = $company->truck_credits ?? 0;
                $planEndDate = Carbon::parse($company->plan_end_date)->endOfDay();
                $today = Carbon::today()->startOfDay();
                $remainingDays = max(0, $today->diffInDays($planEndDate));
                $tenureYears = $remainingDays / 365;

                // Parse truck rate slabs
                $truckRateSlabs = [];
                foreach ($truckRate as $count => $rate) {
                    if (strpos($count, '+') !== false) {
                        $min = (int) str_replace('+', '', $count) + 1;
                        $truckRateSlabs[] = ['min' => $min, 'max' => PHP_INT_MAX, 'rate' => $rate];
                    } elseif (strpos($count, '-') !== false) {
                        [$min, $max] = explode('-', $count);
                        $truckRateSlabs[] = ['min' => (int) $min, 'max' => (int) $max, 'rate' => $rate];
                    }
                }

                for ($i = 1; $i <= $addonTruckCount; $i++) {
                    $truckNumber = $currentTruckCount + $i;

                    foreach ($truckRateSlabs as $slab) {
                        if ($truckNumber >= $slab['min'] && $truckNumber <= $slab['max']) {
                            $truckCost += $slab['rate'];
                            break;
                        }
                        if ($slab['max'] === PHP_INT_MAX && $truckNumber >= $slab['min']) {
                            $truckCost += $slab['rate'];
                            break;
                        }
                    }
                }

                $truckCost = $truckCost * $tenureYears;
            }
        }

        $subtotal = $truckCost + $lrCreditsCost + $annualMaintenanceCost;

        return [
            'truck_cost' => $truckCost,
            'lr_credits_cost' => $lrCreditsCost,
            'annual_maintenance_cost' => $annualMaintenanceCost,
            'subtotal' => $subtotal,
        ];
    }

    /**
     * Handle Razorpay payment callback
     */
    public function paymentCallback(Request $request)
    {
        $input = $request->all();

        try {
            $razorpaySecret = config('payments.razorpay_secret');
            $api = new Api(config('payments.razorpay_key'), $razorpaySecret);

            // Verify payment signature
            $attributes = [
                'razorpay_order_id' => $input['razorpay_order_id'],
                'razorpay_payment_id' => $input['razorpay_payment_id'],
                'razorpay_signature' => $input['razorpay_signature'],
            ];

            $api->utility->verifyPaymentSignature($attributes);

            // Find transaction
            $transaction = PaymentLedger::where('razorpay_order_id', $input['razorpay_order_id'])->first();

            if (! $transaction) {
                Log::error('Payment transaction not found: '.$input['razorpay_order_id']);

                return redirect()->route('payment.index')
                    ->with('error', 'Payment transaction not found.');
            }

            // Update transaction
            $transaction->razorpay_payment_id = $input['razorpay_payment_id'];
            $transaction->razorpay_signature = $input['razorpay_signature'];
            $transaction->status = 'completed';
            $transaction->save();

            // Update company plan details
            $company = $transaction->company;

            DB::beginTransaction();
            try {
                // Always add LR credits directly to company table
                if ($transaction->payment_type === 'renewal') {
                    if ($transaction->lr_credits) {
                        $company->lr_credits = ($company->lr_credits ?? 0) + $transaction->lr_credits;
                    }
                } else {
                    // Addon plan - add LR credits
                    if ($transaction->addon_lr_credits) {
                        $company->lr_credits = ($company->lr_credits ?? 0) + $transaction->addon_lr_credits;
                    }
                }

                // Check if company has an existing active plan
                $hasExistingPlan = false;
                $currentEndDate = null;
                if ($company->plan_end_date) {
                    $currentEndDate = Carbon::parse($company->plan_end_date);
                    $hasExistingPlan = $currentEndDate->isFuture();
                }

                if ($transaction->payment_type === 'renewal') {
                    // Calculate plan dates
                    $startDate = Carbon::today();
                    if ($hasExistingPlan) {
                        // If existing plan is active, new plan starts after current plan ends
                        $startDate = $currentEndDate->copy()->addDay();
                    }
                    $endDate = $startDate->copy()->addYears($transaction->tenure)->subDay();

                    if ($hasExistingPlan) {
                        // Store in upcoming_plan table if company has existing plan
                        UpcomingPlan::create([
                            'company_id' => $company->id,
                            'payment_ledger_id' => $transaction->id,
                            'plan_start_date' => $startDate->toDateString(),
                            'plan_end_date' => $endDate->toDateString(),
                            'truck_count' => $transaction->truck_count,
                            'tenure' => $transaction->tenure,
                            'status' => 'pending',
                        ]);
                    } else {
                        // No existing plan, update company directly
                        $company->plan_end_date = $endDate->toDateString();
                        if ($transaction->truck_count) {
                            $company->truck_credits = $transaction->truck_count;
                        }
                    }
                } else {
                    // Addon plan - add truck credits directly to company
                    if ($transaction->addon_truck_count) {
                        $company->truck_credits = ($company->truck_credits ?? 0) + $transaction->addon_truck_count;
                    }
                }

                $company->save();
                DB::commit();

                if ($transaction->payment_type === 'renewal') {
                    $this->companyLifecycleNotificationService->notifySubscriptionRenewed(
                        $company->fresh(),
                        $transaction->fresh(),
                        Auth::user()
                    );
                }

                $message = $hasExistingPlan && $transaction->payment_type === 'renewal'
                    ? 'Payment successful! Plan will be activated after your current plan ends.'
                    : 'Payment successful! Plan has been updated.';

                return redirect()->route('payment.index')
                    ->with('success', $message);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to update company plan: '.$e->getMessage());

                return redirect()->route('payment.index')
                    ->with('error', 'Payment successful but failed to update plan. Please contact support.');
            }
        } catch (\Exception $e) {
            Log::error('Payment verification failed: '.$e->getMessage());

            // Update transaction status to failed
            if (isset($input['razorpay_order_id'])) {
                $transaction = PaymentLedger::where('razorpay_order_id', $input['razorpay_order_id'])->first();
                if ($transaction) {
                    $transaction->status = 'failed';
                    $transaction->save();
                }
            }

            return redirect()->route('payment.index')
                ->with('error', 'Payment verification failed. Please contact support.');
        }
    }

    /**
     * Apply promo code and get discount details.
     */
    public function applyPromoCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $promoCode = PromoCode::where('code', $request->code)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->first();
        if (! $promoCode) {
            return response()->json(['error' => 'Invalid or expired promo code.'], 404);
        }

        return response()->json([
            'code' => $promoCode->code,
            'discount_type' => $promoCode->discount_type,
            'discount_value' => $promoCode->discount_value,
        ]);
    }

    /**
     * Display payment history
     */
    public function history(Request $request)
    {
        // Only superadmin can access
        if (Auth::user()->userRole->name !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }

        if ($request->ajax() || $request->wantsJson()) {
            $companyId = $request->get('company_id') ?? null;
            $query = PaymentLedger::with(['company', 'promoCode', 'upcomingPlan'])
                ->whereHas('company', function ($q) {
                    $q->where('user_id', Auth::id());
                });

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            if ($request->has('order') && is_array($request->order)) {
                foreach ($request->order as $order) {

                    $columnIndex = $order['column'];
                    $direction = $order['dir'];
                    $columns = [
                        1 => 'created_at',
                        2 => 'total_amount',
                    ];

                    if (isset($columns[$columnIndex])) {
                        $columnName = $columns[$columnIndex];
                        if ($columnName === 'company.name') {
                            $query->join('companies', 'payment_ledgers.company_id', '=', 'companies.id')
                                ->orderBy('companies.name', $direction);
                        } else {
                            $query->orderBy($columnName, $direction);
                        }
                    }
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $start = $request->start ?? 0;
            $length = $request->length ?? 10;
            $payments = $query->skip($start)->take($length)->get();
            $totalRecords = PaymentLedger::whereHas('company', function ($q) {
                $q->where('user_id', Auth::id());
            })->count();
            $filteredRecords = $query->count();

            $count = $start;
            $data = $payments->map(function ($payment) use (&$count) {
                return [
                    'sr_no' => ++$count,
                    'company_name' => $payment->company->name,
                    'payment_type' => ucfirst($payment->payment_type),
                    'total_amount' => number_format($payment->total_amount, 2),
                    'status' => ucfirst($payment->status),
                    'created_at' => $payment->created_at->format('d-m-Y'),
                    'actions' => '<a href="'.route('payment.invoice', $payment->id).'" class="btn btn-sm btn-primary" target="_blank"><i class="fas fa-file-pdf"></i></a>',
                ];
            });

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        } else {
            $companyId = $request->get('company_id') ?? null;
            $company = null;
            if ($companyId) {
                $company = Company::where('id', $companyId)
                    ->where('user_id', Auth::id())
                    ->firstOrFail();
            }

            return view('payment.history', compact('company'));
        }
    }

    /**
     * Generate invoice PDF for payment
     */
    public function invoice($id)
    {
        // Only superadmin can access
        if (Auth::user()->userRole->name !== 'super_admin') {
            abort(403, 'Unauthorized access.');
        }

        $payment = PaymentLedger::with(['company', 'promoCode', 'upcomingPlan'])
            ->whereHas('company', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->findOrFail($id);

        // Only generate invoice for completed payments
        if ($payment->status !== 'completed') {
            return redirect()->route('payment.history')
                ->with('error', 'Invoice can only be generated for completed payments.');
        }

        $html = view('payment.invoice-pdf', compact('payment'))->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        $invoiceNumber = 'INV-'.str_pad($payment->id, 6, '0', STR_PAD_LEFT);

        return $pdf->stream("invoice_{$invoiceNumber}.pdf");
    }
}
