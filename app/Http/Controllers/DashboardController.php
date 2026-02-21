<?php

namespace App\Http\Controllers;

use App\Models\FreightBooking as Booking;
use App\Models\Transporter;
use App\Models\InvoicePayment;
use App\Models\Truck;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\AdvancePayment;
use App\Models\FreightBooking;
use App\Models\VoucherPayment;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        // Super admin in company context: show admin dashboard for that company
        if ($user->userRole->name === 'super_admin' && session('super_admin_company_id')) {
            return $this->adminDashboard();
        }
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

            case 'transporter':
                return $this->transporterDashboard();

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
            'monthly_revenue' => $totalRevenue,
            'total_companies' => Company::count(),
            'active_companies' => Company::where('is_blocked', 0)->count(),
            'expired_plans' => Company::where('plan_end_date', '<', now()->startOfDay())->count(),
            'upcoming_renewals' => Company::where('plan_end_date', '<=', now()->addDays(30)->endOfDay())
                ->where('plan_end_date', '>=', now()->startOfDay())
                ->count(),
            'total_users' => User::count(),
        ];

        // Companies with expiring/expired plans
        $expiringCompanies = Company::where('plan_end_date', '<=', now()->addDays(30)->endOfDay())
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

        // Calculate outstanding amount across all companies
        $allInvoicesForStats = Invoice::whereIn('company_id', $companyIds)
            ->with('payments')
            ->get();

        $outstandingAmount = 0;
        foreach ($allInvoicesForStats as $invoice) {
            $paidAmount = $invoice->paid_amount;
            $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
            $remainingAmount = $amountToCollect - $paidAmount;
            if ($paidAmount < $amountToCollect) {
                $outstandingAmount += $remainingAmount;
            }
        }

        $adminStats = [
            'monthly_revenue' => $totalRevenue,
            'total_companies' => Company::where('user_id', auth()->user()->id)->count(),
            'active_companies' => Company::where('user_id', auth()->user()->id)
                ->where('is_blocked', 0)
                ->count(),
            'expired_plans' => Company::where('user_id', auth()->user()->id)
                ->where('plan_end_date', '<', now()->startOfDay())
                ->count(),
            'upcoming_renewals' => Company::where('user_id', auth()->user()->id)
                ->where('plan_end_date', '<=', now()->addDays(30)->endOfDay())
                ->where('plan_end_date', '>=', now()->startOfDay())
                ->count(),
            'total_users' => User::whereIn('company_id', $companyIds)->count(),
            'outstanding_amount' => $outstandingAmount,
        ];

        // Companies with expiring/expired plans (today or past)
        $expiringCompanies = Company::where('user_id', auth()->user()->id)
            ->where('plan_end_date', '<=', now()->endOfDay())
            ->orderBy('plan_end_date', 'asc')
            ->get();

        // Companies with upcoming plan expiry (next 30 days, not expired yet)
        $upcomingPlanExpiry = Company::where('user_id', auth()->user()->id)
            ->where('plan_end_date', '>', now()->endOfDay())
            ->where('plan_end_date', '<=', now()->addDays(30)->endOfDay())
            ->orderBy('plan_end_date', 'asc')
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
            'upcomingPlanExpiry',
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

        // Comprehensive admin stats - Priority-based minimal content
        // Calculate pending/partial invoices based on payment ledger
        $allInvoicesForStats = Invoice::where('company_id', $companyId)
            ->with('payments')
            ->get();

        $pendingInvoicesCount = 0;
        $pendingInvoiceAmount = 0;
        $outstandingAmount = 0;

        foreach ($allInvoicesForStats as $invoice) {
            $paidAmount = $invoice->paid_amount;
            $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
            $remainingAmount = $amountToCollect - $paidAmount;
            if ($paidAmount < $amountToCollect) {
                $pendingInvoicesCount++;
                $pendingInvoiceAmount += $remainingAmount;
                $outstandingAmount += $remainingAmount;
            }
        }

        // Calculate Admin KPIs
        $today = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $monthlyStats = [
            'total_bookings' => Booking::whereBetween('freight_bookings.created_at', [$startOfMonth, $endOfMonth])
                ->where('freight_bookings.company_id', $companyId)
                ->count(),
            'active_trips' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->count(),
            'pending_lrs' => \App\Models\LorryReceipt::where('delivery_status', 'Pending')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->count(),
            'delivered_today' => \App\Models\LorryReceipt::where('delivery_status', 'Delivered')
                ->where('company_id', $companyId)
                ->whereDate('updated_at', $today)
                ->whereNull('deleted_at')
                ->count(),
            'lrs_pending_invoice' => \App\Models\LorryReceipt::where('invoice_generated', 0)
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->count(),
            'eway_bills_expiring_today' => FreightBooking::where('company_id', $companyId)
                ->where('is_delivered', 0)
                ->where('is_cancelled', 0)
                ->whereDate('eway_bill_expiry', $today)
                ->count(),
            'expired_eway_bills' => FreightBooking::where('company_id', $companyId)
                ->where('is_delivered', 0)
                ->where('is_cancelled', 0)
                ->where('eway_bill_expiry', '<', $today)
                ->count(),
            'outstanding_amount' => $outstandingAmount,
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
        $topTransporters = Transporter::with('transporterMaster')
            ->where('is_owner', 0)
            ->where('company_id', $companyId)
            ->withCount('lorryReceipts')
            ->orderByDesc('lorry_receipts_count')
            ->take(5)
            ->get();

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
            ->where('is_cancelled', 0)
            ->where('eway_bill_expiry', '<=', now()->addDays(2)->endOfDay())
            ->orderBy('eway_bill_expiry', 'asc')
            ->get();

        // Recent invoices
        $recentInvoices = Invoice::where('company_id', $companyId)
            ->with('consignee')
            ->latest()
            ->take(5)
            ->get();

        // Priority: Recent invoices with pending/partial payments (highest priority)
        $pendingInvoices = Invoice::where('company_id', $companyId)
            ->with(['consignee', 'payments'])
            ->get()
            ->filter(function ($invoice) {
                $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
                return $invoice->paid_amount < $amountToCollect;
            })
            ->sortByDesc('generated_date')
            ->take(5)
            ->values();

        // Payment ledger of invoices status breakdown - Based on actual payments
        $allInvoices = Invoice::where('company_id', $companyId)
            ->with('payments')
            ->get();

        $invoicePaymentStatuses = [
            'pending' => 0,
            'partial' => 0,
            'paid' => 0,
        ];

        foreach ($allInvoices as $invoice) {
            $paidAmount = $invoice->paid_amount;
            $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
            if ($paidAmount >= $amountToCollect) {
                $invoicePaymentStatuses['paid']++;
            } elseif ($paidAmount > 0) {
                $invoicePaymentStatuses['partial']++;
            } else {
                $invoicePaymentStatuses['pending']++;
            }
        }

        return view('dashboard', compact(
            'monthlyStats',
            'expiredEwayBills',
            'pendingInvoices',
            'invoicePaymentStatuses'
        ));
    }

    private function moderatorDashboard()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $companyId = auth()->user()->company_id;

        // Calculate outstanding amount for invoices
        $allInvoicesForStats = Invoice::where('company_id', $companyId)
            ->with('payments')
            ->get();

        $outstandingAmount = 0;
        foreach ($allInvoicesForStats as $invoice) {
            $paidAmount = $invoice->paid_amount;
            $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
            $remainingAmount = $amountToCollect - $paidAmount;
            if ($paidAmount < $amountToCollect) {
                $outstandingAmount += $remainingAmount;
            }
        }

        // Calculate Moderator KPIs
        $today = now()->startOfDay();

        $operationalStats = [
            'total_bookings' => Booking::whereBetween('freight_bookings.created_at', [$startOfMonth, $endOfMonth])
                ->where('freight_bookings.company_id', $companyId)
                ->count(),
            'active_trips' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->count(),
            'pending_lrs' => \App\Models\LorryReceipt::where('delivery_status', 'Pending')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->count(),
            'delivered_today' => \App\Models\LorryReceipt::where('delivery_status', 'Delivered')
                ->where('company_id', $companyId)
                ->whereDate('updated_at', $today)
                ->whereNull('deleted_at')
                ->count(),
            'lrs_pending_invoice' => \App\Models\LorryReceipt::where('invoice_generated', 0)
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->count(),
            'eway_bills_expiring_today' => FreightBooking::where('company_id', $companyId)
                ->where('is_delivered', 0)
                ->where('is_cancelled', 0)
                ->whereDate('eway_bill_expiry', $today)
                ->count(),
            'expired_eway_bills' => FreightBooking::where('company_id', $companyId)
                ->where('is_delivered', 0)
                ->where('is_cancelled', 0)
                ->where('eway_bill_expiry', '<', $today)
                ->count(),
            'outstanding_amount' => $outstandingAmount,
        ];

        // Moderator-specific stats for new KPI cards
        $moderatorStats = [
            'incoming_bookings' => \App\Models\LorryReceipt::where('delivery_status', 'Pending')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->count(),
            'loading_in_progress' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->whereHas('lr', function($q) {
                    $q->where('delivery_status', 'Pending');
                })
                ->count(),
            'en_route' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->whereHas('lr', function($q) {
                    $q->where('delivery_status', 'In-Progress');
                })
                ->count(),
            'delayed_trips' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->whereHas('lr', function($q) {
                    $q->where('delivery_status', '!=', 'Pending');
                })
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
        $topTransporters = Transporter::with('transporterMaster')
            ->where('is_owner', 0)
            ->where('company_id', $companyId)
            ->withCount('lorryReceipts')
            ->orderByDesc('lorry_receipts_count')
            ->take(5)
            ->get();

        // Expired Eway bills
        $expiredEwayBills = FreightBooking::with('lr')
            ->where('company_id', $companyId)
            ->where('is_delivered', 0)
            ->where('is_cancelled', 0)
            ->where('eway_bill_expiry', '<=', now()->addDays(2)->endOfDay())
            ->orderBy('eway_bill_expiry', 'asc')
            ->get();

        return view('dashboard', compact(
            'operationalStats',
            'moderatorStats',
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

        // Calculate Accountant KPIs
        $today = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        // Calculate pending invoices based on payment ledger
        $allInvoicesForStats = Invoice::where('company_id', $companyId)
            ->with('payments')
            ->get();

        $pendingInvoicesCount = 0;
        $pendingInvoiceAmount = 0;
        $outstandingAmount = 0;
        $overdueInvoicesCount = 0;

        foreach ($allInvoicesForStats as $invoice) {
            $paidAmount = $invoice->paid_amount;
            $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
            $remainingAmount = $amountToCollect - $paidAmount;

            if ($paidAmount < $amountToCollect) {
                $pendingInvoicesCount++;
                $pendingInvoiceAmount += $remainingAmount;
                $outstandingAmount += $remainingAmount;

                $invoiceDate = $invoice->generated_date ? \Carbon\Carbon::parse($invoice->generated_date) : $invoice->created_at;
                if ($invoiceDate->copy()->addDays(30)->isPast()) {
                    $overdueInvoicesCount++;
                }
            }
        }

        // Calculate total invoiced amount (monthly)
        $totalInvoiced = Invoice::where('company_id', $companyId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        // Calculate total paid amount (all time)
        $totalPaid = InvoicePayment::whereHas('invoice', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->sum('amount');

        $financialStats = [
            'total_revenue' => $totalRevenue,
            'total_invoiced' => $totalInvoiced,
            'pending_payments' => $pendingInvoiceAmount, // Same as pending_invoices_amount
            'total_paid' => $totalPaid,
            'invoices_created_today' => Invoice::where('company_id', $companyId)
                ->whereBetween('created_at', [$today, $todayEnd])
                ->count(),
            'pending_invoices_count' => $pendingInvoicesCount,
            'pending_invoices_amount' => $pendingInvoiceAmount,
            'payments_received_today' => InvoicePayment::whereBetween('created_at', [$today, $todayEnd])
                ->whereHas('invoice', function($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->sum('amount'),
            'outstanding_amount' => $outstandingAmount,
            'overdue_invoices' => $overdueInvoicesCount,
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

        // Payment ledger of invoices - Priority: Pending/Partial invoices (based on payment ledger)
        $pendingInvoices = Invoice::where('company_id', $companyId)
            ->with(['consignee', 'payments'])
            ->get()
            ->filter(function ($invoice) {
                $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
                return $invoice->paid_amount < $amountToCollect;
            })
            ->sortByDesc('generated_date')
            ->take(10)
            ->values();

        // Recent invoice payments (payment ledger entries)
        $recentInvoicePayments = InvoicePayment::with('invoice.consignee')
            ->whereHas('invoice', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard', compact(
            'financialStats',
            'pendingInvoices',
            'recentInvoicePayments'
        ));
    }

    private function transporterDashboard()
    {
        $user = auth()->user();
        $transporterMasterId = $user->transporter_master_id;
        if (! $transporterMasterId) {
            return view('dashboard', ['transporterStats' => [], 'recentFreightBookings' => collect(), 'recentPayments' => collect(), 'expiredEwayBills' => collect()]);
        }

        $transporterIds = Transporter::where('transporter_master_id', $transporterMasterId)->pluck('id');
        $today = now()->startOfDay();
        $startOfMonth = now()->copy()->startOfMonth();
        $endOfMonth = now()->copy()->endOfMonth();

        $transporterStats = [
            'total_bookings' => FreightBooking::whereIn('transporter_id', $transporterIds)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count(),
            'active_trips' => FreightBooking::whereIn('transporter_id', $transporterIds)
                ->where('is_delivered', false)
                ->where('is_cancelled', false)
                ->count(),
            'pending_lrs' => \App\Models\LorryReceipt::whereIn('transporter_id', $transporterIds)
                ->where('delivery_status', 'Pending')
                ->whereNull('deleted_at')
                ->count(),
            'delivered_today' => \App\Models\LorryReceipt::whereIn('transporter_id', $transporterIds)
                ->where('delivery_status', 'Delivered')
                ->whereDate('updated_at', $today)
                ->whereNull('deleted_at')
                ->count(),
            'payments_received' => \App\Models\VoucherPayment::whereHas('freight_booking', fn ($q) => $q->whereIn('transporter_id', $transporterIds))
                ->where('payment_status', 'paid')
                ->sum('amount'),
            'advance_received' => \App\Models\AdvancePayment::whereIn('transporter_id', $transporterIds)->sum('advance_amount'),
            'expired_eway_bills' => FreightBooking::whereIn('transporter_id', $transporterIds)
                ->where('is_delivered', 0)
                ->where('is_cancelled', 0)
                ->where('eway_bill_expiry', '<', now()->startOfDay())
                ->count(),
        ];

        $recentFreightBookings = FreightBooking::with(['lr.consignor', 'lr.consignee', 'lr.truck'])
            ->whereIn('transporter_id', $transporterIds)
            ->latest()
            ->take(10)
            ->get();

        $recentPayments = \App\Models\VoucherPayment::with('freight_booking.lr')
            ->whereHas('freight_booking', fn ($q) => $q->whereIn('transporter_id', $transporterIds))
            ->latest()
            ->take(10)
            ->get();

        $expiredEwayBills = FreightBooking::with('lr')
            ->whereIn('transporter_id', $transporterIds)
            ->where('is_delivered', 0)
            ->where('is_cancelled', 0)
            ->where('eway_bill_expiry', '<=', now()->addDays(2)->endOfDay())
            ->orderBy('eway_bill_expiry', 'asc')
            ->get();

        return view('dashboard', compact(
            'transporterStats',
            'recentFreightBookings',
            'recentPayments',
            'expiredEwayBills'
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

