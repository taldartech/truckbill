<?php

namespace App\Http\Controllers;

use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\FreightBooking;
use App\Models\FreightBooking as Booking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\VoucherPayment;
use App\Services\Dashboard\BusinessInsightsService;
use App\Services\Dashboard\DashboardPeriod;
use App\Services\Dashboard\PlatformInsightsService;
use Carbon\Carbon;
use App\Models\EwayBill;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BusinessInsightsService $businessInsights,
        private readonly PlatformInsightsService $platformInsights,
    ) {}

    public function index()
    {
        $user = auth()->user();
        // Super admin in company context: show admin dashboard for that company
        if ($user->userRole->name === 'super_admin' && session('super_admin_company_id')) {
            return $this->adminDashboard();
        }
        $userRole = $user->userRole->name;

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

    private function masterAdminDashboard(): View
    {
        $platform = $this->platformInsights->build();

        return view('dashboard', [
            'dashboardView' => 'master_admin',
            'platform' => $platform,
            'chartPayload' => ['platformTrend' => $platform['trend']],
        ]);
    }

    private function superAdminDashboard(): View
    {
        $period = DashboardPeriod::fromKey(request('period'));

        $companies = Company::query()
            ->where('user_id', auth()->id())
            ->with('subscription')
            ->withCount('pendingUpcomingRenewals')
            ->orderBy('name')
            ->get();

        $insights = $this->businessInsights->build($companies->pluck('id')->all(), $period);

        return view('dashboard', [
            'dashboardView' => 'super_admin',
            'insights' => $insights,
            'actions' => $this->businessInsights->actionItems($insights, BusinessInsightsService::AUDIENCE_PORTFOLIO),
            'scorecards' => $this->businessInsights->companyScorecards($companies, $insights),
            'ownedCompanies' => $companies,
            'dashboardPeriod' => $period,
            'chartPayload' => $this->businessChartPayload($insights),
        ]);
    }

    private function adminDashboard(): View
    {
        return $this->companyDashboard('admin', BusinessInsightsService::AUDIENCE_OWNER);
    }

    private function moderatorDashboard(): View
    {
        return $this->companyDashboard('moderator', BusinessInsightsService::AUDIENCE_OPERATIONS);
    }

    private function accountantDashboard(): View
    {
        return $this->companyDashboard('accountant', BusinessInsightsService::AUDIENCE_FINANCE);
    }

    private function companyDashboard(string $dashboardView, string $audience): View
    {
        $period = DashboardPeriod::fromKey(request('period'));
        $insights = $this->businessInsights->build([(int) auth()->user()->company_id], $period);

        return view('dashboard', [
            'dashboardView' => $dashboardView,
            'insights' => $insights,
            'actions' => $this->businessInsights->actionItems($insights, $audience),
            'dashboardPeriod' => $period,
            'chartPayload' => $this->businessChartPayload($insights),
        ]);
    }

    /**
     * @param  array<string, mixed>  $insights
     * @return array<string, mixed>
     */
    private function businessChartPayload(array $insights): array
    {
        return [
            'monthlyTrend' => $insights['trend'],
            'profitTrend' => $insights['margin']['daily'],
        ];
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
            'expired_eway_bills' => EwayBill::whereHas('freightBooking', function ($query) use ($transporterIds) {
            $query->whereIn('transporter_id', $transporterIds)
            ->where('is_delivered', 0)
            ->where('is_cancelled', 0);
          })
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

       $expiredEwayBills = EwayBill::with('freightBooking.lr.truck.driver')
      ->whereHas('freightBooking', function ($query) use ($transporterIds) {
        $query->whereIn('transporter_id', $transporterIds)
            ->where('is_delivered', 0)
            ->where('is_cancelled', 0);
    })
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
