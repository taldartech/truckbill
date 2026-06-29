<?php

namespace App\Http\Controllers;

use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\FreightBooking;
use App\Models\FreightBooking as Booking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\User;
use App\Models\VoucherPayment;
use App\Models\UpcomingSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

            case 'customer':
                return $this->customerDashboard();

            default:
                return $this->defaultDashboard();
        }
    }

    private function masterAdminDashboard()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $pendingRenewalCompanyIds = UpcomingSubscription::query()
            ->where('status', 'pending')
            ->pluck('company_id');

        // System-wide statistics
        $totalRevenue = $this->sumInvoiceTotalRevenue(null, $startOfMonth, $endOfMonth);

        $systemStats = [
            'monthly_revenue' => $totalRevenue,
            'total_companies' => Company::count(),
            'active_companies' => Company::where('is_blocked', 0)->count(),
            'expired_plans' => CompanySubscription::where('ends_on', '<', now()->toDateString())
                ->whereNotIn('company_id', $pendingRenewalCompanyIds)
                ->count(),
            'upcoming_renewals' => CompanySubscription::whereBetween('ends_on', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->whereNotIn('company_id', $pendingRenewalCompanyIds)
                ->count(),
            'total_users' => User::count(),
        ];

        // Companies with expiring/expired plans
        $expiringCompanies = Company::with('subscription')
            ->whereDoesntHave('pendingUpcomingRenewals')
            ->whereHas('subscription', fn ($q) => $q->where('ends_on', '<=', now()->addDays(30)->toDateString()))
            ->get()
            ->sortBy(fn ($c) => $c->subscription?->ends_on)
            ->take(10)
            ->values();

        // Recent companies
        $recentCompanies = Company::latest()->take(5)->get();

        // Revenue by company (top 10) — sum of invoice totals
        $topCompaniesByRevenue = Company::select('companies.*')
            ->selectSub(
                Invoice::selectRaw('COALESCE(SUM(total_amount), 0)')
                    ->whereColumn('invoices.company_id', 'companies.id')
                    ->whereBetween('invoices.created_at', [$startOfMonth, $endOfMonth]),
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

        $totalRevenue = $this->sumInvoiceTotalRevenue($companyIds->all(), $startOfMonth, $endOfMonth);

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

        $pendingRenewalCompanyIds = UpcomingSubscription::query()
            ->where('status', 'pending')
            ->whereIn('company_id', $companyIds)
            ->pluck('company_id');

        $adminStats = [
            'monthly_revenue' => $totalRevenue,
            'total_companies' => Company::where('user_id', auth()->user()->id)->count(),
            'active_companies' => Company::where('user_id', auth()->user()->id)
                ->where('is_blocked', 0)
                ->count(),
            'expired_plans' => CompanySubscription::whereIn('company_id', $companyIds)
                ->where('ends_on', '<', now()->toDateString())
                ->whereNotIn('company_id', $pendingRenewalCompanyIds)
                ->count(),
            'upcoming_renewals' => CompanySubscription::whereIn('company_id', $companyIds)
                ->whereBetween('ends_on', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->whereNotIn('company_id', $pendingRenewalCompanyIds)
                ->count(),
            'total_users' => User::whereIn('company_id', $companyIds)->count(),
            'outstanding_amount' => $outstandingAmount,
        ];

        // Companies with expiring/expired plans (today or past)
        $expiringCompanies = Company::where('user_id', auth()->user()->id)
            ->with('subscription')
            ->whereDoesntHave('pendingUpcomingRenewals')
            ->whereHas('subscription', fn ($q) => $q->where('ends_on', '<=', now()->toDateString()))
            ->get()
            ->sortBy(fn ($c) => $c->subscription?->ends_on);

        $upcomingPlanExpiry = Company::where('user_id', auth()->user()->id)
            ->with('subscription')
            ->whereDoesntHave('pendingUpcomingRenewals')
            ->whereHas('subscription', fn ($q) => $q
                ->where('ends_on', '>', now()->toDateString())
                ->where('ends_on', '<=', now()->addDays(30)->toDateString()))
            ->get()
            ->sortBy(fn ($c) => $c->subscription?->ends_on);

        // Revenue by company — sum of invoice totals
        $revenueByCompany = Company::where('user_id', auth()->user()->id)
            ->select('companies.*')
            ->selectSub(
                Invoice::selectRaw('COALESCE(SUM(total_amount), 0)')
                    ->whereColumn('invoices.company_id', 'companies.id')
                    ->whereBetween('invoices.created_at', [$startOfMonth, $endOfMonth]),
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

        $companyInvoices = Invoice::where('company_id', $companyId)
            ->with(['payments', 'consignee'])
            ->get();

        $outstandingAmount = 0;
        $invoicePaymentStatuses = [
            'pending' => 0,
            'partial' => 0,
            'paid' => 0,
        ];

        foreach ($companyInvoices as $invoice) {
            $paidAmount = $invoice->paid_amount;
            $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
            $remainingAmount = $amountToCollect - $paidAmount;

            if ($paidAmount < $amountToCollect) {
                $outstandingAmount += $remainingAmount;
            }

            if ($paidAmount >= $amountToCollect) {
                $invoicePaymentStatuses['paid']++;
            } elseif ($paidAmount > 0) {
                $invoicePaymentStatuses['partial']++;
            } else {
                $invoicePaymentStatuses['pending']++;
            }
        }

        $pendingInvoices = $companyInvoices
            ->filter(function ($invoice) {
                $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;

                return $invoice->paid_amount < $amountToCollect;
            })
            ->sortByDesc('generated_date')
            ->take(5)
            ->values();

        // Calculate Admin KPIs
        $today = now()->startOfDay();

        $monthlyStats = [
            'total_bookings' => Booking::whereBetween('freight_bookings.created_at', [$startOfMonth, $endOfMonth])
                ->where('freight_bookings.company_id', $companyId)
                ->count(),
            'active_trips' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->count(),
            'pending_lrs' => LorryReceipt::where('delivery_status', 'Pending')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->count(),
            'delivered_today' => LorryReceipt::where('delivery_status', 'Delivered')
                ->where('company_id', $companyId)
                ->whereDate('updated_at', $today)
                ->whereNull('deleted_at')
                ->count(),
            'lrs_pending_invoice' => LorryReceipt::where('invoice_generated', 0)
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

        // Expired Eway bills
        $expiredEwayBills = FreightBooking::with('lr.truck.driver')
            ->where('company_id', $companyId)
            ->where('is_delivered', 0)
            ->where('is_cancelled', 0)
            ->where('eway_bill_expiry', '<=', now()->addDays(2)->endOfDay())
            ->orderBy('eway_bill_expiry', 'asc')
            ->get();

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
            'pending_lrs' => LorryReceipt::where('delivery_status', 'Pending')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->count(),
            'delivered_today' => LorryReceipt::where('delivery_status', 'Delivered')
                ->where('company_id', $companyId)
                ->whereDate('updated_at', $today)
                ->whereNull('deleted_at')
                ->count(),
            'lrs_pending_invoice' => LorryReceipt::where('invoice_generated', 0)
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
            'incoming_bookings' => LorryReceipt::where('delivery_status', 'Pending')
                ->where('company_id', $companyId)
                ->whereNull('deleted_at')
                ->count(),
            'loading_in_progress' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->whereHas('lr', function ($q) {
                    $q->where('delivery_status', 'Pending');
                })
                ->count(),
            'en_route' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->whereHas('lr', function ($q) {
                    $q->where('delivery_status', 'In-Progress');
                })
                ->count(),
            'delayed_trips' => Booking::where('is_delivered', false)
                ->where('is_cancelled', false)
                ->where('company_id', $companyId)
                ->whereHas('lr', function ($q) {
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
                ->count(),
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
        $expiredEwayBills = FreightBooking::with('lr.truck.driver')
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

        $totalRevenue = $this->sumInvoiceTotalRevenue([$companyId], $startOfMonth, $endOfMonth);

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

                $invoiceDate = $invoice->generated_date ? Carbon::parse($invoice->generated_date) : $invoice->created_at;
                if ($invoiceDate->copy()->addDays(30)->isPast()) {
                    $overdueInvoicesCount++;
                }
            }
        }

        $totalInvoiced = $totalRevenue;

        // Calculate total paid amount (all time)
        $totalPaid = InvoicePayment::whereHas('invoice', function ($q) use ($companyId) {
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
                ->whereHas('invoice', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->sum('amount'),
            'outstanding_amount' => $outstandingAmount,
            'overdue_invoices' => $overdueInvoicesCount,
        ];

        // Revenue chart for the last 6 months — sum of invoice totals
        $revenueData = Invoice::select(
            DB::raw('DATE_FORMAT(created_at, "%b %Y") as month'),
            DB::raw('SUM(total_amount) as total')
        )
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->where('company_id', $companyId)
            ->groupBy('month')
            ->orderBy('created_at')
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
            ->whereHas('invoice', function ($q) use ($companyId) {
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
            'pending_lrs' => LorryReceipt::whereIn('transporter_id', $transporterIds)
                ->where('delivery_status', 'Pending')
                ->whereNull('deleted_at')
                ->count(),
            'delivered_today' => LorryReceipt::whereIn('transporter_id', $transporterIds)
                ->where('delivery_status', 'Delivered')
                ->whereDate('updated_at', $today)
                ->whereNull('deleted_at')
                ->count(),
            'payments_received' => VoucherPayment::whereHas('freight_booking', fn ($q) => $q->whereIn('transporter_id', $transporterIds))
                ->where('payment_status', 'paid')
                ->sum('amount'),
            'advance_received' => AdvancePayment::whereIn('transporter_id', $transporterIds)->sum('advance_amount'),
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

        $recentPayments = VoucherPayment::with('freight_booking.lr')
            ->whereHas('freight_booking', fn ($q) => $q->whereIn('transporter_id', $transporterIds))
            ->latest()
            ->take(10)
            ->get();

        $expiredEwayBills = FreightBooking::with('lr.truck.driver')
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

    private function customerDashboard()
    {
        $user = auth()->user();
        $consigneeOptions = $user->consignees()->with('company')->orderBy('name')->get();
        $activeConsigneeIds = $consigneeOptions
            ->filter(fn ($consignee) => ! ($consignee->company?->is_blocked ?? false))
            ->pluck('id')
            ->all();

        if ($activeConsigneeIds === []) {
            return view('dashboard');
        }

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $recentLorryReceipts = LorryReceipt::query()
            ->with(['transporter', 'truck', 'consignee'])
            ->whereIn('customer_id', $activeConsigneeIds)
            ->orderByDesc('gr_date')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $recentInvoicePayments = InvoicePayment::query()
            ->with(['invoice'])
            ->whereHas('invoice', function ($query) use ($activeConsigneeIds) {
                $query->whereIn('customer_id', $activeConsigneeIds);
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $allInvoices = Invoice::query()
            ->with('payments')
            ->whereIn('customer_id', $activeConsigneeIds)
            ->get();

        $outstandingAmount = 0.0;
        $pendingInvoicesCount = 0;
        foreach ($allInvoices as $invoice) {
            $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
            if ($invoice->paid_amount < $amountToCollect) {
                $pendingInvoicesCount++;
                $outstandingAmount += max(0, $amountToCollect - $invoice->paid_amount);
            }
        }

        $customerStats = [
            'total_lorry_receipts' => LorryReceipt::query()
                ->whereIn('customer_id', $activeConsigneeIds)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count(),
            'pending_lorry_receipts' => LorryReceipt::query()
                ->whereIn('customer_id', $activeConsigneeIds)
                ->where('delivery_status', 'Pending')
                ->count(),
            'delivered_lorry_receipts' => LorryReceipt::query()
                ->whereIn('customer_id', $activeConsigneeIds)
                ->where('delivery_status', 'Delivered')
                ->count(),
            'pending_invoices' => $pendingInvoicesCount,
            'outstanding_amount' => $outstandingAmount,
        ];

        return view('dashboard', compact(
            'customerStats',
            'recentLorryReceipts',
            'recentInvoicePayments',
            'consigneeOptions'
        ));
    }

    private function defaultDashboard()
    {
        // Fallback for unknown roles
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $companyId = auth()->user()->company_id;

        $totalRevenue = $this->sumInvoiceTotalRevenue([$companyId], $startOfMonth, $endOfMonth);

        $monthlyStats = [
            'total_bookings' => Booking::whereBetween('freight_bookings.created_at', [$startOfMonth, $endOfMonth])
                ->where('freight_bookings.company_id', $companyId)
                ->count(),
            'total_revenue' => $totalRevenue,
        ];

        return view('dashboard', compact('monthlyStats'));
    }

    /**
     * @param  array<int, int>|null  $companyIds
     */
    private function sumInvoiceTotalRevenue(?array $companyIds, Carbon $start, Carbon $end): float
    {
        $query = Invoice::query()->whereBetween('created_at', [$start, $end]);

        if ($companyIds !== null) {
            if ($companyIds === []) {
                return 0.0;
            }

            $query->whereIn('company_id', $companyIds);
        }

        return (float) ($query->sum('total_amount') ?? 0);
    }
}
