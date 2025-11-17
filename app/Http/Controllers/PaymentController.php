<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PromoCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
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
        if (!$companyId) {
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
     * Store payment/plan update
     */
    public function store(Request $request)
    {
        // Implementation for storing payment/plan update goes here
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
        if (!$promoCode) {
            return response()->json(['error' => 'Invalid or expired promo code.'], 404);
        }

        return response()->json([
            'code' => $promoCode->code,
            'discount_type' => $promoCode->discount_type,
            'discount_value' => $promoCode->discount_value,
        ]);
    }
}
