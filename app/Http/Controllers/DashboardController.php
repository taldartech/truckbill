<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FreightBooking as Booking;
use App\Models\Invoice;
use App\Services\Dashboard\BusinessInsightsService;
use App\Services\Dashboard\DashboardPeriod;
use App\Services\Dashboard\PlatformInsightsService;
use Carbon\Carbon;
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
