<?php

namespace App\Http\Controllers;

use App\Models\FreightBooking as Booking;
use App\Models\Transporter;
use App\Models\VoucherPayment;
use App\Models\Truck;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\FreightBooking;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userRole = $user->userRole->name;

        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Role-based data fetching
        switch ($userRole) {
            case 'master_admin':
                return $this->masterAdminDashboard();

            case 'super_admin':
                return $this->superAdminDashboard();

            case 'admin':
                return $this->adminDashboard();

            case 'moderator':
                return $this->moderatorDashboard();

            case 'accountant':
                return $this->accountantDashboard();

            default:
                return $this->defaultDashboard();
        }
    }

    private function masterAdminDashboard()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // System-wide statistics
        // Calculate revenue as LR amount - FR amount
        $totalRevenue = Booking::join('lorry_receipts', 'freight_bookings.lr_id', '=', 'lorry_receipts.id')
            ->whereRaw('freight_bookings.created_at BETWEEN ? AND ?', [$startOfMonth, $endOfMonth])
            ->selectRaw('SUM(lorry_receipts.amount - freight_bookings.tr_amount) as revenue')
            ->value('revenue') ?? 0;

        $systemStats = [
            'total_companies' => Company::count(),
            'active_companies' => Company::where('is_blocked', 0)->count(),
            'blocked_companies' => Company::where('is_blocked', 1)->count(),
            'total_users' => User::count(),
            'total_bookings' => Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
            'total_revenue' => $totalRevenue,
            'expiring_plans' => Company::where('plan_end_date', '<=', now()->addDays(7)->endOfDay())
                ->where('plan_end_date', '>=', now()->startOfDay())
                ->count(),
            'expired_plans' => Company::where('plan_end_date', '<', now()->startOfDay())->count(),
        ];

        // Companies with expiring/expired plans
        $expiringCompanies = Company::where('plan_end_date', '<=', now()->addDays(7)->endOfDay())
            ->orderBy('plan_end_date', 'asc')
            ->take(10)
            ->get();

        // Recent companies
        $recentCompanies = Company::latest()->take(5)->get();

        // Revenue by company (top 10) - LR amount - FR amount
        $topCompaniesByRevenue = Company::select('companies.*')
            ->selectSub(
                Booking::selectRaw('SUM(lorry_receipts.amount - freight_bookings.tr_amount)')
                    ->join('lorry_receipts', 'freight_bookings.lr_id', '=', 'lorry_receipts.id')
                    ->whereColumn('freight_bookings.company_id', 'companies.id')
                    ->whereRaw('freight_bookings.created_at BETWEEN ? AND ?', [$startOfMonth, $endOfMonth]),
                'monthly_revenue'
            )
            ->orderByDesc('monthly_revenue')
            ->take(10)
            ->get();

        // Monthly growth data
        $monthlyGrowth = Company::select(
            DB::raw('DATE_FORMAT(created_at, "%b %Y") as month'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('month')
            ->orderBy('created_at')
            ->pluck('count', 'month');

        return view('dashboard', compact(
            'systemStats',
            'expiringCompanies',
            'recentCompanies',
            'topCompaniesByRevenue',
            'monthlyGrowth'
        ));
    }

    private function superAdminDashboard()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Get companies owned by super admin
        $companyIds = Company::where('user_id', auth()->user()->id)->pluck('id');

        // Calculate revenue as LR amount - FR amount
        $totalRevenue = Booking::join('lorry_receipts', 'freight_bookings.lr_id', '=', 'lorry_receipts.id')
            ->whereIn('freight_bookings.company_id', $companyIds)
            ->whereRaw('freight_bookings.created_at BETWEEN ? AND ?', [$startOfMonth, $endOfMonth])
            ->selectRaw('SUM(lorry_receipts.amount - freight_bookings.tr_amount) as revenue')
            ->value('revenue') ?? 0;

        $adminStats = [
            'total_companies' => Company::where('user_id', auth()->user()->id)->count(),
            'total_bookings' => Booking::whereIn('freight_bookings.company_id', $companyIds)
                ->whereBetween('freight_bookings.created_at', [$startOfMonth, $endOfMonth])
                ->count(),
            'total_revenue' => $totalRevenue,
            'total_users' => User::whereIn('company_id', $companyIds)->count(),
            'expiring_plans' => Company::where('user_id', auth()->user()->id)
                ->where('plan_end_date', '<=', now()->addDays(7)->endOfDay())
                ->where('plan_end_date', '>=', now()->startOfDay())
                ->count(),
        ];

        // Companies with expiring plans
        $expiringCompanies = Company::where('user_id', auth()->user()->id)
            ->where('plan_end_date', '<=', now()->endOfDay())
            ->orderBy('plan_end_date', 'asc')
            ->get();

        // Recent bookings across all companies
        $recentBookings = Booking::whereIn('company_id', $companyIds)
            ->with(['lr.consignor', 'lr.consignee'])
            ->latest()
            ->take(5)
            ->get();

        // Revenue by company - LR amount - FR amount
        $revenueByCompany = Company::where('user_id', auth()->user()->id)
            ->select('companies.*')
            ->selectSub(
                Booking::selectRaw('SUM(lorry_receipts.amount - freight_bookings.tr_amount)')
                    ->join('lorry_receipts', 'freight_bookings.lr_id', '=', 'lorry_receipts.id')
                    ->whereColumn('freight_bookings.company_id', 'companies.id')
                    ->whereRaw('freight_bookings.created_at BETWEEN ? AND ?', [$startOfMonth, $endOfMonth]),
                'monthly_revenue'
            )
            ->orderByDesc('monthly_revenue')
            ->get();

        return view('dashboard', compact(
            'adminStats',
            'expiringCompanies',
            'recentBookings',
            'revenueByCompany'
        ));
    }

    private function adminDashboard()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $companyId = auth()->user()->company_id;

        // Calculate revenue as LR amount - FR amount
        $totalRevenue = Booking::join('lorry_receipts', 'freight_bookings.lr_id', '=', 'lorry_receipts.id')
            ->where('freight_bookings.company_id', $companyId)
            ->whereRaw('freight_bookings.created_at BETWEEN ? AND ?', [$startOfMonth, $endOfMonth])
            ->selectRaw('SUM(lorry_receipts.amount - freight_bookings.tr_amount) as revenue')
            ->value('revenue') ?? 0;

        // Comprehensive admin stats
        $monthlyStats = [
            'total_bookings' => Booking::whereBetween('freight_bookings.created_at', [$startOfMonth, $endOfMonth])
                ->where('freight_bookings.company_id', $companyId)
                ->count(),
            'total_revenue' => $totalRevenue,
            'pending_payments' => VoucherPayment::where('payment_status', 'pending')
                ->where('company_id', $companyId)
                ->count(),
            'active_transporters' => Transporter::where('is_owner', 0)
                ->where('company_id', $companyId)
                ->count(),
            'total_invoices' => Invoice::whereBetween('generated_date', [$startOfMonth, $endOfMonth])
                ->where('company_id', $companyId)
                ->count(),
            'pending_invoices' => Invoice::where('payment_status', 'pending')
                ->where('company_id', $companyId)
                ->count(),
        ];

        // Revenue data for the last 6 months - LR amount - FR amount
        $revenueData = Booking::select(
            DB::raw('DATE_FORMAT(freight_bookings.created_at, "%b %Y") as month'),
            DB::raw('SUM(lorry_receipts.amount - freight_bookings.tr_amount) as total')
        )
            ->join('lorry_receipts', 'freight_bookings.lr_id', '=', 'lorry_receipts.id')
            ->where('freight_bookings.created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->where('freight_bookings.company_id', $companyId)
            ->groupBy('month')
            ->orderBy('freight_bookings.created_at')
            ->pluck('total', 'month');

        // Booking status distribution
        $bookingStatuses = [
            'delivered' => Booking::where('is_delivered', true)
                ->where('company_id', $companyId)
                ->count(),
            'cancelled' => Booking::where('is_cancelled', true)
                ->where('company_id', $companyId)
                ->count(),
            'in_progress' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->count()
        ];

        // Recent bookings
        $recentBookings = Booking::with(['lr.consignor', 'lr.consignee'])
            ->where('company_id', $companyId)
            ->latest()
            ->take(5)
            ->get();

        // Top transporters
        $topTransporters = Transporter::where('is_owner', 0)
            ->where('company_id', $companyId)
            ->withCount('lorryReceipts')
            ->orderByDesc('lorry_receipts_count')
            ->take(5)
            ->get(['name', 'lorry_receipts_count']);

        // Top consignees
        $topConsignees = \App\Models\Consignee::where('company_id', $companyId)
            ->withCount('lorryReceipts')
            ->orderByDesc('lorry_receipts_count')
            ->take(5)
            ->get(['name', 'lorry_receipts_count']);

        // Expired Eway bills
        $expiredEwayBills = FreightBooking::with('lr')
            ->where('company_id', $companyId)
            ->where('is_delivered', 0)
            ->where('eway_bill_expiry', '<=', now()->addDays(2)->endOfDay())
            ->orderBy('eway_bill_expiry', 'asc')
            ->get();

        // Recent invoices
        $recentInvoices = Invoice::where('company_id', $companyId)
            ->with('consignee')
            ->latest()
            ->take(5)
            ->get();

        // Payment status breakdown
        $paymentStatuses = [
            'pending' => VoucherPayment::where('payment_status', 'pending')
                ->where('company_id', $companyId)
                ->count(),
            'in_progress' => VoucherPayment::where('payment_status', 'in progress')
                ->where('company_id', $companyId)
                ->count(),
            'paid' => VoucherPayment::where('payment_status', 'paid')
                ->where('company_id', $companyId)
                ->count(),
        ];

        return view('dashboard', compact(
            'monthlyStats',
            'revenueData',
            'bookingStatuses',
            'recentBookings',
            'topTransporters',
            'topConsignees',
            'expiredEwayBills',
            'recentInvoices',
            'paymentStatuses'
        ));
    }

    private function moderatorDashboard()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $companyId = auth()->user()->company_id;

        // Operational stats
        $operationalStats = [
            'total_bookings' => Booking::whereBetween('freight_bookings.created_at', [$startOfMonth, $endOfMonth])
                ->where('freight_bookings.company_id', $companyId)
                ->count(),
            'active_trucks' => Truck::whereHas('transporter', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->count(),
            'active_transporters' => Transporter::where('is_owner', 0)
                ->where('company_id', $companyId)
                ->count(),
            'pending_bookings' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->count(),
        ];

        // Booking status distribution
        $bookingStatuses = [
            'delivered' => Booking::where('is_delivered', true)
                ->where('company_id', $companyId)
                ->count(),
            'cancelled' => Booking::where('is_cancelled', true)
                ->where('company_id', $companyId)
                ->count(),
            'in_progress' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->count()
        ];

        // Recent bookings
        $recentBookings = Booking::with(['lr.consignor', 'lr.consignee', 'lr.truck'])
            ->where('company_id', $companyId)
            ->latest()
            ->take(10)
            ->get();

        // Top trucks
        $topTrucks = Truck::select('trucks.*', DB::raw('(SELECT COUNT(*) FROM lorry_receipts WHERE lorry_receipts.truck_id = trucks.id) as lr_count'))
            ->whereHas('transporter', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->orderBy('lr_count', 'desc')
            ->take(5)
            ->get();

        // Top transporters
        $topTransporters = Transporter::where('is_owner', 0)
            ->where('company_id', $companyId)
            ->withCount('lorryReceipts')
            ->orderByDesc('lorry_receipts_count')
            ->take(5)
            ->get(['name', 'lorry_receipts_count']);

        // Expired Eway bills
        $expiredEwayBills = FreightBooking::with('lr')
            ->where('company_id', $companyId)
            ->where('is_delivered', 0)
            ->where('eway_bill_expiry', '<=', now()->addDays(2)->endOfDay())
            ->orderBy('eway_bill_expiry', 'asc')
            ->get();

        return view('dashboard', compact(
            'operationalStats',
            'bookingStatuses',
            'recentBookings',
            'topTrucks',
            'topTransporters',
            'expiredEwayBills'
        ));
    }

    private function accountantDashboard()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $companyId = auth()->user()->company_id;

        // Calculate revenue as LR amount - FR amount
        $totalRevenue = Booking::join('lorry_receipts', 'freight_bookings.lr_id', '=', 'lorry_receipts.id')
            ->where('freight_bookings.company_id', $companyId)
            ->whereRaw('freight_bookings.created_at BETWEEN ? AND ?', [$startOfMonth, $endOfMonth])
            ->selectRaw('SUM(lorry_receipts.amount - freight_bookings.tr_amount) as revenue')
            ->value('revenue') ?? 0;

        // Financial stats
        $financialStats = [
            'total_revenue' => $totalRevenue,
            'pending_payments' => VoucherPayment::where('payment_status', 'pending')
                ->where('company_id', $companyId)
                ->count(),
            'pending_amount' => VoucherPayment::where('payment_status', 'pending')
                ->where('company_id', $companyId)
                ->sum('amount'),
            'total_invoices' => Invoice::whereBetween('generated_date', [$startOfMonth, $endOfMonth])
                ->where('company_id', $companyId)
                ->count(),
            'pending_invoices' => Invoice::where('payment_status', 'pending')
                ->where('company_id', $companyId)
                ->count(),
            'paid_amount' => VoucherPayment::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->where('company_id', $companyId)
                ->sum('amount'),
        ];

        // Revenue data for the last 6 months - LR amount - FR amount
        $revenueData = Booking::select(
            DB::raw('DATE_FORMAT(freight_bookings.created_at, "%b %Y") as month'),
            DB::raw('SUM(lorry_receipts.amount - freight_bookings.tr_amount) as total')
        )
            ->join('lorry_receipts', 'freight_bookings.lr_id', '=', 'lorry_receipts.id')
            ->where('freight_bookings.created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->where('freight_bookings.company_id', $companyId)
            ->groupBy('month')
            ->orderBy('freight_bookings.created_at')
            ->pluck('total', 'month');

        // Payment status breakdown
        $paymentStatuses = [
            'pending' => VoucherPayment::where('payment_status', 'pending')
                ->where('company_id', $companyId)
                ->count(),
            'in_progress' => VoucherPayment::where('payment_status', 'in progress')
                ->where('company_id', $companyId)
                ->count(),
            'paid' => VoucherPayment::where('payment_status', 'paid')
                ->where('company_id', $companyId)
                ->count(),
        ];

        // Recent payments
        $recentPayments = VoucherPayment::with(['freight_booking.lr'])
            ->where('company_id', $companyId)
            ->latest()
            ->take(10)
            ->get();

        // Recent invoices
        $recentInvoices = Invoice::where('company_id', $companyId)
            ->with('consignee')
            ->latest()
            ->take(10)
            ->get();

        // Pending payments (high priority)
        $pendingPayments = VoucherPayment::with(['freight_booking.lr'])
            ->where('payment_status', 'pending')
            ->where('company_id', $companyId)
            ->orderBy('amount', 'desc')
            ->take(10)
            ->get();

        // Top payers (consignees)
        $topPayers = \App\Models\Consignee::where('company_id', $companyId)
            ->select('consignees.*')
            ->selectSub(
                Invoice::selectRaw('SUM(total_amount)')
                    ->whereColumn('invoices.customer_id', 'consignees.id')
                    ->whereBetween('generated_date', [$startOfMonth, $endOfMonth]),
                'total_paid'
            )
            ->orderByDesc('total_paid')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'financialStats',
            'revenueData',
            'paymentStatuses',
            'recentPayments',
            'recentInvoices',
            'pendingPayments',
            'topPayers'
        ));
    }

    private function defaultDashboard()
    {
        // Fallback for unknown roles
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $companyId = auth()->user()->company_id;

        // Calculate revenue as LR amount - FR amount
        $totalRevenue = Booking::join('lorry_receipts', 'freight_bookings.lr_id', '=', 'lorry_receipts.id')
            ->where('freight_bookings.company_id', $companyId)
            ->whereRaw('freight_bookings.created_at BETWEEN ? AND ?', [$startOfMonth, $endOfMonth])
            ->selectRaw('SUM(lorry_receipts.amount - freight_bookings.tr_amount) as revenue')
            ->value('revenue') ?? 0;

        $monthlyStats = [
            'total_bookings' => Booking::whereBetween('freight_bookings.created_at', [$startOfMonth, $endOfMonth])
                ->where('freight_bookings.company_id', $companyId)
                ->count(),
            'total_revenue' => $totalRevenue,
        ];

        return view('dashboard', compact('monthlyStats'));
    }
}
