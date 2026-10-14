<?php

namespace App\Services\Dashboard;

use App\Models\Company;
use App\Models\LorryReceipt;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Platform-level metrics for the master admin: renewals at risk, inactive
 * tenants, credit exhaustion, usage and subscription revenue.
 */
class PlatformInsightsService
{
    public const INACTIVE_DAYS = 14;

    public const RENEWAL_URGENT_DAYS = 7;

    public const RENEWAL_WINDOW_DAYS = 30;

    public const LOW_CREDIT_FLOOR = 10;

    public const LOW_CREDIT_PERCENT = 10;

    public const USAGE_WINDOW_DAYS = 30;

    public const TREND_MONTHS = 6;

    public const LIST_SIZE = 8;

    public function __construct(private readonly BusinessInsightsService $businessInsights) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $today = Carbon::today();
        $companies = Company::query()
            ->with(['subscription', 'user:id,name,email'])
            ->withCount('pendingUpcomingRenewals')
            ->orderBy('name')
            ->get();

        $usageStart = $today->copy()->subDays(self::USAGE_WINDOW_DAYS - 1);
        $previousUsageStart = $usageStart->copy()->subDays(self::USAGE_WINDOW_DAYS);
        $previousUsageEnd = $usageStart->copy()->subDay();

        $lastLrDates = LorryReceipt::query()
            ->selectRaw('company_id, MAX(gr_date) as last_gr_date')
            ->groupBy('company_id')
            ->pluck('last_gr_date', 'company_id');

        $usageCurrent = $this->usageByCompany($usageStart, $today);
        $usagePrevious = $this->usageByCompany($previousUsageStart, $previousUsageEnd);

        $rows = $companies->map(function (Company $company) use ($today, $lastLrDates, $usageCurrent, $usagePrevious): array {
            $subscription = $company->subscription;
            $endsOn = $subscription?->ends_on?->copy()->startOfDay();
            $creditPool = (int) ($subscription?->included_lr_credits ?? 0) + (int) ($subscription?->addon_lr_credits ?? 0);
            $creditsRemaining = (int) ($subscription?->lr_credits_remaining ?? 0);
            $lastLrDate = $lastLrDates->get($company->id);

            return [
                'id' => $company->id,
                'name' => $company->name,
                'owner' => $company->user?->name,
                'contact' => $company->contact_person ?? $company->user?->email,
                'is_blocked' => (bool) $company->is_blocked,
                'plan_status' => $subscription?->status,
                'ends_on' => $endsOn,
                'days_to_expiry' => $endsOn ? (int) $today->diffInDays($endsOn, false) : null,
                'has_pending_renewal' => $company->pending_upcoming_renewals_count > 0,
                'is_plan_active' => $subscription?->isActive() ?? false,
                'credits_remaining' => $creditsRemaining,
                'credit_pool' => $creditPool,
                'is_low_credit' => $subscription !== null && $creditsRemaining <= max(self::LOW_CREDIT_FLOOR, (int) ceil($creditPool * self::LOW_CREDIT_PERCENT / 100)),
                'last_lr_date' => $lastLrDate ? Carbon::parse($lastLrDate) : null,
                'days_since_last_lr' => $lastLrDate ? (int) Carbon::parse($lastLrDate)->startOfDay()->diffInDays($today) : null,
                'lrs_30d' => (int) ($usageCurrent->get($company->id)['trips'] ?? 0),
                'gmv_30d' => (float) ($usageCurrent->get($company->id)['value'] ?? 0),
                'lrs_prev_30d' => (int) ($usagePrevious->get($company->id)['trips'] ?? 0),
            ];
        });

        $liveRows = $rows->where('is_blocked', false);
        $notRenewing = $liveRows->where('has_pending_renewal', false);

        $expired = $notRenewing->filter(fn (array $row): bool => $row['days_to_expiry'] !== null && $row['days_to_expiry'] < 0)
            ->sortByDesc('days_to_expiry')->values();
        $expiringSoon = $notRenewing->filter(fn (array $row): bool => $row['days_to_expiry'] !== null
            && $row['days_to_expiry'] >= 0 && $row['days_to_expiry'] <= self::RENEWAL_WINDOW_DAYS)
            ->sortBy('days_to_expiry')->values();
        $lowCredit = $liveRows->filter(fn (array $row): bool => $row['is_plan_active'] && $row['is_low_credit'])
            ->sortBy('credits_remaining')->values();
        $inactive = $liveRows->filter(fn (array $row): bool => $row['is_plan_active']
            && ($row['days_since_last_lr'] === null || $row['days_since_last_lr'] >= self::INACTIVE_DAYS))
            ->sortByDesc(fn (array $row): int => $row['days_since_last_lr'] ?? PHP_INT_MAX)->values();
        $inactiveIds = $inactive->pluck('id')->all();
        $slowingDown = $liveRows->filter(fn (array $row): bool => $row['is_plan_active']
            && ! in_array($row['id'], $inactiveIds, true)
            && $row['lrs_prev_30d'] >= 10 && $row['lrs_30d'] <= $row['lrs_prev_30d'] * 0.5)
            ->sortBy(fn (array $row): float => $row['lrs_30d'] / max(1, $row['lrs_prev_30d']))->values();

        $monthStart = $today->copy()->startOfMonth();
        $previousMonthStart = $monthStart->copy()->subMonthNoOverflow();
        $previousMonthSameDay = $previousMonthStart->copy()->addDays((int) $monthStart->diffInDays($today))->endOfDay();
        if ($previousMonthSameDay->gt($monthStart->copy()->subDay()->endOfDay())) {
            $previousMonthSameDay = $monthStart->copy()->subDay()->endOfDay();
        }

        $subscriptionRevenue = $this->subscriptionRevenueBetween($monthStart, $today->copy()->endOfDay());
        $subscriptionRevenuePrevious = $this->subscriptionRevenueBetween($previousMonthStart, $previousMonthSameDay);
        $lrs30d = (int) $rows->sum('lrs_30d');
        $lrsPrevious30d = (int) $rows->sum('lrs_prev_30d');

        $insights = [
            'companies_total' => $rows->count(),
            'companies_live' => $liveRows->count(),
            'companies_blocked' => $rows->where('is_blocked', true)->count(),
            'companies_paying' => $liveRows->where('is_plan_active', true)->where('plan_status', 'active')->count(),
            'companies_trial' => $liveRows->where('is_plan_active', true)->where('plan_status', 'trial')->count(),
            'companies_active_usage' => $liveRows->where('lrs_30d', '>', 0)->count(),
            'users_total' => User::query()->count(),
            'expired' => $expired->all(),
            'expiring_soon' => $expiringSoon->all(),
            'expiring_urgent_count' => $expiringSoon->where('days_to_expiry', '<=', self::RENEWAL_URGENT_DAYS)->count(),
            'low_credit' => $lowCredit->all(),
            'inactive' => $inactive->all(),
            'slowing_down' => $slowingDown->all(),
            'lrs_30d' => $lrs30d,
            'lrs_prev_30d' => $lrsPrevious30d,
            'lrs_change' => $this->businessInsights->percentChange($lrs30d, $lrsPrevious30d),
            'gmv_30d' => round($rows->sum('gmv_30d'), 2),
            'top_companies' => $liveRows->sortByDesc('lrs_30d')->filter(fn (array $row): bool => $row['lrs_30d'] > 0)
                ->take(self::LIST_SIZE)->values()->all(),
            'subscription_revenue' => $subscriptionRevenue,
            'subscription_revenue_previous' => $subscriptionRevenuePrevious,
            'subscription_revenue_change' => $this->businessInsights->percentChange($subscriptionRevenue, $subscriptionRevenuePrevious),
            'signups_this_month' => Company::query()->where('created_at', '>=', $monthStart)->count(),
            'recent_companies' => $companies->sortByDesc('created_at')->take(5)->values(),
            'trend' => $this->monthlyTrend(),
        ];

        $insights['actions'] = $this->actionItems($insights);

        return $insights;
    }

    /**
     * @param  array<string, mixed>  $insights
     * @return list<array{tone: string, icon: string, title: string, detail: string, url: ?string, cta: string}>
     */
    public function actionItems(array $insights): array
    {
        $items = [];
        $expiredCount = count($insights['expired']);
        $expiringCount = count($insights['expiring_soon']);
        $lowCreditCount = count($insights['low_credit']);
        $inactiveCount = count($insights['inactive']);
        $slowingCount = count($insights['slowing_down']);

        if ($expiredCount > 0) {
            $items[] = [
                'tone' => 'danger',
                'icon' => 'bi bi-x-octagon',
                'title' => "Win back {$expiredCount} expired ".str('company')->plural($expiredCount),
                'detail' => 'Plan ended with no renewal booked. Most recent: '.$insights['expired'][0]['name'].'.',
                'url' => route('company.index'),
                'cta' => 'Open companies',
            ];
        }

        if ($insights['expiring_urgent_count'] > 0) {
            $items[] = [
                'tone' => 'warning',
                'icon' => 'bi bi-alarm',
                'title' => "{$insights['expiring_urgent_count']} ".str('plan')->plural($insights['expiring_urgent_count']).' expire within '.self::RENEWAL_URGENT_DAYS.' days',
                'detail' => "{$expiringCount} in the next ".self::RENEWAL_WINDOW_DAYS.' days without a renewal booked. Call them before access is cut.',
                'url' => route('company.index'),
                'cta' => 'Review renewals',
            ];
        } elseif ($expiringCount > 0) {
            $items[] = [
                'tone' => 'info',
                'icon' => 'bi bi-calendar-event',
                'title' => "{$expiringCount} ".str('plan')->plural($expiringCount).' renew in the next '.self::RENEWAL_WINDOW_DAYS.' days',
                'detail' => 'No renewal booked yet. A reminder now protects recurring revenue.',
                'url' => route('company.index'),
                'cta' => 'Review renewals',
            ];
        }

        if ($lowCreditCount > 0) {
            $items[] = [
                'tone' => 'warning',
                'icon' => 'bi bi-battery-low',
                'title' => "{$lowCreditCount} ".str('company')->plural($lowCreditCount).' running out of LR credits',
                'detail' => 'Upsell an add-on credit pack before they get blocked mid-operation.',
                'url' => route('company.index'),
                'cta' => 'Open companies',
            ];
        }

        if ($inactiveCount > 0) {
            $items[] = [
                'tone' => 'warning',
                'icon' => 'bi bi-moon',
                'title' => "{$inactiveCount} active-plan ".str('company')->plural($inactiveCount).' idle for '.self::INACTIVE_DAYS.'+ days',
                'detail' => 'Active plan but no bilty created recently: a churn risk at the next renewal. Offer onboarding help.',
                'url' => route('master.summary-dashboard'),
                'cta' => 'See usage',
            ];
        }

        if ($slowingCount > 0) {
            $items[] = [
                'tone' => 'info',
                'icon' => 'bi bi-graph-down',
                'title' => "{$slowingCount} ".str('company')->plural($slowingCount).' halved their usage',
                'detail' => 'Bilties in the last '.self::USAGE_WINDOW_DAYS.' days are under 50% of the previous '.self::USAGE_WINDOW_DAYS.'. Check in with them.',
                'url' => route('master.summary-dashboard'),
                'cta' => 'See usage',
            ];
        }

        return $items;
    }

    /**
     * @return Collection<int, array{trips: int, value: float}>
     */
    private function usageByCompany(Carbon $start, Carbon $end): Collection
    {
        return LorryReceipt::query()
            ->whereBetween('gr_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('company_id, COUNT(*) as trips, COALESCE(SUM(COALESCE(actual_amount, amount, 0)), 0) as value')
            ->groupBy('company_id')
            ->get()
            ->mapWithKeys(fn ($row): array => [(int) $row->company_id => ['trips' => (int) $row->trips, 'value' => (float) $row->value]]);
    }

    private function subscriptionRevenueBetween(Carbon $start, Carbon $end): float
    {
        return round((float) SubscriptionPayment::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount'), 2);
    }

    /**
     * @return array{labels: list<string>, subscription_revenue: list<float>, signups: list<int>, lrs: list<int>}
     */
    private function monthlyTrend(): array
    {
        $trend = ['labels' => [], 'subscription_revenue' => [], 'signups' => [], 'lrs' => []];
        $currentMonth = Carbon::now()->startOfMonth();

        for ($offset = self::TREND_MONTHS - 1; $offset >= 0; $offset--) {
            $monthStart = $currentMonth->copy()->subMonthsNoOverflow($offset);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $trend['labels'][] = $monthStart->format('M Y');
            $trend['subscription_revenue'][] = $this->subscriptionRevenueBetween($monthStart, $monthEnd);
            $trend['signups'][] = Company::query()->whereBetween('created_at', [$monthStart, $monthEnd])->count();
            $trend['lrs'][] = LorryReceipt::query()
                ->whereBetween('gr_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->count();
        }

        return $trend;
    }
}
