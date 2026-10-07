<?php

namespace App\Services\Dashboard;

use App\Models\Company;
use App\Models\EwayBill;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\VoucherPayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Business metrics for one or more companies: what is owed to us, what we owe,
 * what is not billed yet, how profitable trips are, and what needs attention.
 */
class BusinessInsightsService
{
    public const AUDIENCE_OWNER = 'owner';

    public const AUDIENCE_OPERATIONS = 'operations';

    public const AUDIENCE_FINANCE = 'finance';

    /** Owner view across several companies, rendered outside any company context. */
    public const AUDIENCE_PORTFOLIO = 'portfolio';

    public const LONG_RUNNING_TRIP_DAYS = 7;

    public const LOW_MARGIN_PERCENT = 5.0;

    public const DUE_SOON_DAYS = 7;

    public const EWAY_WARNING_DAYS = 2;

    public const CUSTOMER_CONCENTRATION_PERCENT = 40.0;

    public const SIGNIFICANT_CHANGE_PERCENT = 10.0;

    public const TREND_MONTHS = 6;

    public const MIN_DAYS_FOR_TREND_ALERTS = 7;

    public const MIN_TRIPS_FOR_TREND_ALERTS = 3;

    public const LIST_SIZE = 5;

    /**
     * Mirrors FreightBooking::effectivePayableAmount() so SQL aggregates agree with per-trip figures.
     */
    private const FREIGHT_COST_SQL = 'COALESCE(freight_bookings.tr_amount, 0)'
        .' - COALESCE(freight_bookings.tds_amount, 0)'
        .' - COALESCE(freight_bookings.damage_amount, 0)'
        .' - COALESCE(freight_bookings.shortage_amount, 0)'
        .' + COALESCE(freight_bookings.detention, 0)'
        .' + COALESCE(freight_bookings.deviation, 0)'
        .' - COALESCE(freight_bookings.misc_expense, 0)'
        .' + COALESCE(freight_bookings.negative_adjusted_amount, 0)'
        .' + COALESCE((SELECT SUM(ca.amount) FROM freight_booking_custom_amounts ca WHERE ca.freight_booking_id = freight_bookings.id), 0)';

    private const INVOICE_SETTLED_SQL = '(SELECT COALESCE(SUM(ip.amount + COALESCE(ip.tds_amount, 0) + COALESCE(ip.deduction_amount, 0)), 0)'
        .' FROM invoice_payments ip WHERE ip.invoice_id = invoices.id)';

    /**
     * @param  array<int, int>  $companyIds
     * @return array<string, mixed>
     */
    public function build(array $companyIds, DashboardPeriod $period): array
    {
        $today = Carbon::today();
        $margin = $this->margin($companyIds, $period);

        return [
            'period' => $period,
            'receivables' => $this->receivables($companyIds, $period, $today),
            'unbilled' => $this->unbilled($companyIds, $today),
            'payables' => $this->payables($companyIds, $period),
            'margin' => $margin,
            'exceptions' => $this->exceptions($companyIds, $period, $today),
            'growth' => $this->growth($companyIds, $period, $margin),
            'trend' => $this->monthlyTrend($companyIds),
        ];
    }

    /**
     * @param  array<int, int>  $companyIds
     * @return array<string, mixed>
     */
    public function receivables(array $companyIds, DashboardPeriod $period, Carbon $today): array
    {
        $rows = $this->openInvoiceRows($companyIds, $today);

        $buckets = ['not_due' => 0.0, '1_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, '90_plus' => 0.0];
        foreach ($rows as $row) {
            $buckets[$this->agingBucket($row['days_overdue'])] += $row['balance'];
        }

        $overdueRows = $rows->filter(fn (array $row): bool => $row['days_overdue'] > 0);
        $dueSoonRows = $rows->filter(fn (array $row): bool => $row['days_overdue'] <= 0 && $row['days_overdue'] > -self::DUE_SOON_DAYS);
        $outstanding = round($rows->sum('balance'), 2);
        $overdue = round($overdueRows->sum('balance'), 2);

        $collected = $this->collectedBetween($companyIds, $period->start, $period->end);
        $collectedPrevious = $this->collectedBetween($companyIds, $period->previousStart, $period->previousEnd);

        $debtors = $rows->groupBy('customer_id')
            ->map(fn (Collection $customerRows): array => [
                'customer_id' => $customerRows->first()['customer_id'],
                'customer' => $customerRows->first()['customer'],
                'company_id' => $customerRows->first()['company_id'],
                'invoices' => $customerRows->count(),
                'outstanding' => round($customerRows->sum('balance'), 2),
                'overdue' => round($customerRows->where('days_overdue', '>', 0)->sum('balance'), 2),
                'max_days_overdue' => (int) $customerRows->max('days_overdue'),
            ])
            ->sortBy([['overdue', 'desc'], ['outstanding', 'desc']])
            ->values();

        return [
            'outstanding' => $outstanding,
            'overdue' => $overdue,
            'overdue_count' => $overdueRows->count(),
            'overdue_customers' => $overdueRows->pluck('customer_id')->unique()->count(),
            'overdue_share' => $outstanding > 0 ? round($overdue / $outstanding * 100, 1) : 0.0,
            'due_soon' => round($dueSoonRows->sum('balance'), 2),
            'due_soon_count' => $dueSoonRows->count(),
            'open_count' => $rows->count(),
            'buckets' => array_map(fn (float $amount): float => round($amount, 2), $buckets),
            'debtors' => $debtors->take(self::LIST_SIZE + 1)->all(),
            'overdue_invoices' => $overdueRows->sortByDesc('days_overdue')->take(10)->values()->all(),
            'collected' => $collected,
            'collected_previous' => $collectedPrevious,
            'collected_change' => $this->percentChange($collected, $collectedPrevious),
            'by_company' => $rows->groupBy('company_id')->map(fn (Collection $companyRows): array => [
                'outstanding' => round($companyRows->sum('balance'), 2),
                'overdue' => round($companyRows->where('days_overdue', '>', 0)->sum('balance'), 2),
            ])->all(),
        ];
    }

    /**
     * @param  array<int, int>  $companyIds
     * @return array<string, mixed>
     */
    public function unbilled(array $companyIds, Carbon $today): array
    {
        $delivered = LorryReceipt::query()
            ->whereIn('company_id', $companyIds)
            ->where('delivery_status', 'Delivered')
            ->where('invoice_generated', 0)
            ->with('customer:id,name')
            ->get(['id', 'company_id', 'customer_id', 'gr_date', 'memo_id', 'amount', 'actual_amount'])
            ->map(fn (LorryReceipt $lr): array => [
                'company_id' => (int) $lr->company_id,
                'customer_id' => $lr->customer_id,
                'customer' => $lr->customer?->name ?? '—',
                'value' => $this->bookedValue($lr),
                'has_memo' => filled($lr->memo_id),
                'age_days' => $lr->gr_date ? (int) Carbon::parse($lr->gr_date)->startOfDay()->diffInDays($today) : 0,
            ]);

        $readyToBill = $delivered->where('has_memo', true);
        $podPending = $delivered->where('has_memo', false);

        $inTransit = LorryReceipt::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('delivery_status', ['Pending', 'In-Progress'])
            ->selectRaw('COUNT(*) as trips, COALESCE(SUM(COALESCE(actual_amount, amount, 0)), 0) as value')
            ->first();

        return [
            'ready_count' => $readyToBill->count(),
            'ready_value' => round($readyToBill->sum('value'), 2),
            'ready_oldest_days' => (int) $readyToBill->max('age_days'),
            'pod_pending_count' => $podPending->count(),
            'pod_pending_value' => round($podPending->sum('value'), 2),
            'pod_pending_oldest_days' => (int) $podPending->max('age_days'),
            'in_transit_count' => (int) ($inTransit->trips ?? 0),
            'in_transit_value' => round((float) ($inTransit->value ?? 0), 2),
            'ready_by_customer' => $readyToBill->groupBy('customer_id')
                ->map(fn (Collection $rows): array => [
                    'customer' => $rows->first()['customer'],
                    'trips' => $rows->count(),
                    'value' => round($rows->sum('value'), 2),
                    'oldest_days' => (int) $rows->max('age_days'),
                ])
                ->sortByDesc('value')
                ->take(self::LIST_SIZE)
                ->values()
                ->all(),
            'by_company' => $delivered->groupBy('company_id')->map(fn (Collection $rows): array => [
                'ready_value' => round($rows->where('has_memo', true)->sum('value'), 2),
                'pod_pending_value' => round($rows->where('has_memo', false)->sum('value'), 2),
            ])->all(),
        ];
    }

    /**
     * @param  array<int, int>  $companyIds
     * @return array<string, mixed>
     */
    public function payables(array $companyIds, DashboardPeriod $period): array
    {
        $rows = FreightBooking::query()
            ->whereIn('company_id', $companyIds)
            ->where('is_cancelled', false)
            ->where('balance_amount', '>', 0.01)
            ->with('transporter.transporterMaster')
            ->get(['id', 'company_id', 'transporter_id', 'transporter_name', 'balance_amount', 'is_delivered'])
            ->map(fn (FreightBooking $booking): array => [
                'company_id' => (int) $booking->company_id,
                'transporter_id' => $booking->transporter_id,
                'transporter' => $this->transporterLabel($booking->transporter, $booking->transporter_name),
                'balance' => (float) $booking->balance_amount,
                'is_delivered' => (bool) $booking->is_delivered,
            ]);

        $dueNow = $rows->where('is_delivered', true);
        $paidOut = $this->paidOutBetween($companyIds, $period->start, $period->end);
        $paidOutPrevious = $this->paidOutBetween($companyIds, $period->previousStart, $period->previousEnd);

        return [
            'total' => round($rows->sum('balance'), 2),
            'due_now' => round($dueNow->sum('balance'), 2),
            'due_now_trips' => $dueNow->count(),
            'due_now_transporters' => $dueNow->pluck('transporter_id')->unique()->count(),
            'running' => round($rows->where('is_delivered', false)->sum('balance'), 2),
            'by_transporter' => $rows->groupBy('transporter_id')
                ->map(fn (Collection $transporterRows): array => [
                    'transporter' => $transporterRows->first()['transporter'],
                    'trips' => $transporterRows->count(),
                    'due_now' => round($transporterRows->where('is_delivered', true)->sum('balance'), 2),
                    'total' => round($transporterRows->sum('balance'), 2),
                ])
                ->sortBy([['total', 'desc'], ['due_now', 'desc']])
                ->take(self::LIST_SIZE)
                ->values()
                ->all(),
            'paid_out' => $paidOut,
            'paid_out_previous' => $paidOutPrevious,
            'paid_out_change' => $this->percentChange($paidOut, $paidOutPrevious),
            'by_company' => $rows->groupBy('company_id')->map(fn (Collection $companyRows): array => [
                'due_now' => round($companyRows->where('is_delivered', true)->sum('balance'), 2),
                'total' => round($companyRows->sum('balance'), 2),
            ])->all(),
        ];
    }

    /**
     * @param  array<int, int>  $companyIds
     * @return array<string, mixed>
     */
    public function margin(array $companyIds, DashboardPeriod $period): array
    {
        $rows = $this->tripRows($companyIds, $period->start, $period->end);
        $previousRows = $this->tripRows($companyIds, $period->previousStart, $period->previousEnd);

        $customers = $this->summarise($rows, 'customer_id', 'customer');
        $lossRows = $rows->filter(fn (array $row): bool => $row['profit'] < 0);

        return array_merge($this->totals($rows), [
            'previous' => $this->totals($previousRows),
            'loss_trips' => $lossRows->count(),
            'loss_amount' => round(abs($lossRows->sum('profit')), 2),
            'worst_trips' => $lossRows->sortBy('profit')->take(self::LIST_SIZE)->values()->all(),
            'low_margin_trips' => $rows->filter(fn (array $row): bool => $row['profit'] >= 0 && $row['margin'] < self::LOW_MARGIN_PERCENT)->count(),
            'best_customers' => $customers->sortByDesc('profit')->take(self::LIST_SIZE)->values()->all(),
            'weak_customers' => $customers->filter(fn (array $customer): bool => $customer['revenue'] > 0)
                ->sortBy('margin')->take(self::LIST_SIZE)->values()->all(),
            'transporters' => $this->summarise($rows, 'transporter_id', 'transporter')
                ->sortByDesc('trips')->take(self::LIST_SIZE)->values()->all(),
            'routes' => $this->summarise($rows, 'route', 'route')
                ->sortByDesc('profit')->take(self::LIST_SIZE)->values()->all(),
            'customers_current' => $customers,
            'customers_previous' => $this->summarise($previousRows, 'customer_id', 'customer'),
            'daily' => $this->profitTrend($rows, $period),
            'by_company' => $rows->groupBy('company_id')->map(fn (Collection $companyRows): array => $this->totals($companyRows))->all(),
            'last_trip_date' => $rows->isEmpty() ? $this->lastTripDateBefore($companyIds, $period->end) : null,
        ]);
    }

    /**
     * Latest bilty date with an active hire on or before the given day, used to explain an empty period.
     *
     * @param  array<int, int>  $companyIds
     */
    public function lastTripDateBefore(array $companyIds, Carbon $end): ?Carbon
    {
        $lastDate = LorryReceipt::query()
            ->whereIn('company_id', $companyIds)
            ->where('gr_date', '<=', $end->toDateString())
            ->where(fn ($query) => $query->whereNull('delivery_status')->orWhere('delivery_status', '!=', 'Cancelled'))
            ->whereHas('freightBookings', fn ($query) => $query->where('is_cancelled', false))
            ->max('gr_date');

        return $lastDate ? Carbon::parse($lastDate)->startOfDay() : null;
    }

    /**
     * @param  array<int, int>  $companyIds
     * @return array<string, mixed>
     */
    public function exceptions(array $companyIds, DashboardPeriod $period, Carbon $today): array
    {
        $runningEwayBills = fn () => EwayBill::query()->whereHas('freightBooking', function ($query) use ($companyIds): void {
            $query->whereIn('company_id', $companyIds)
                ->where('is_delivered', false)
                ->where('is_cancelled', false);
        });
        $warningUntil = $today->copy()->addDays(self::EWAY_WARNING_DAYS)->toDateString();

        $longRunningCutoff = $today->copy()->subDays(self::LONG_RUNNING_TRIP_DAYS)->toDateString();
        $longRunning = fn () => LorryReceipt::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('delivery_status', ['Pending', 'In-Progress'])
            ->where('gr_date', '<=', $longRunningCutoff)
            ->whereHas('freightBookings', fn ($query) => $query->where('is_cancelled', false)->where('is_delivered', false));

        $cancelledInPeriod = FreightBooking::query()
            ->whereIn('company_id', $companyIds)
            ->where('is_cancelled', true)
            ->whereBetween('updated_at', [$period->start, $period->end])
            ->count();
        $createdInPeriod = FreightBooking::query()
            ->whereIn('company_id', $companyIds)
            ->whereBetween('created_at', [$period->start, $period->end])
            ->count();

        return [
            'eway_expired' => $runningEwayBills()->whereDate('eway_bill_expiry', '<', $today->toDateString())->count(),
            'eway_expiring' => $runningEwayBills()
                ->whereDate('eway_bill_expiry', '>=', $today->toDateString())
                ->whereDate('eway_bill_expiry', '<=', $warningUntil)
                ->count(),
            'eway_alerts' => $runningEwayBills()
                ->with('freightBooking.lr.truck.driver')
                ->whereDate('eway_bill_expiry', '<=', $warningUntil)
                ->orderBy('eway_bill_expiry')
                ->limit(10)
                ->get(),
            'long_running' => $longRunning()->count(),
            'long_running_trips' => $longRunning()
                ->with(['truck:id,truck_number', 'transporter.transporterMaster', 'freightBookings:id,lr_id'])
                ->orderBy('gr_date')
                ->limit(8)
                ->get(['id', 'lr_number', 'gr_date', 'source_city', 'destination_city', 'truck_id', 'transporter_id', 'delivery_status'])
                ->map(fn (LorryReceipt $lr): array => [
                    'lr_number' => $lr->lr_number,
                    'route' => $this->routeLabel($lr),
                    'truck' => $lr->truck?->truck_number ?? '—',
                    'transporter' => $this->transporterLabel($lr->transporter),
                    'days' => (int) Carbon::parse($lr->gr_date)->startOfDay()->diffInDays($today),
                    'status' => $lr->delivery_status,
                    'freight_booking_id' => $lr->freightBookings->first()?->id,
                ])
                ->all(),
            'without_hire' => LorryReceipt::query()
                ->whereIn('company_id', $companyIds)
                ->where(fn ($query) => $query->whereNull('delivery_status')->orWhere('delivery_status', '!=', 'Cancelled'))
                ->where('gr_date', '>=', $today->copy()->subDays(90)->toDateString())
                ->whereDoesntHave('freightBookings', fn ($query) => $query->where('is_cancelled', false))
                ->count(),
            'cancelled' => $cancelledInPeriod,
            'cancellation_rate' => $createdInPeriod > 0 ? round($cancelledInPeriod / $createdInPeriod * 100, 1) : 0.0,
        ];
    }

    /**
     * @param  array<int, int>  $companyIds
     * @param  array<string, mixed>  $margin
     * @return array<string, mixed>
     */
    public function growth(array $companyIds, DashboardPeriod $period, array $margin): array
    {
        /** @var Collection<int|string, array<string, mixed>> $currentCustomers */
        $currentCustomers = $margin['customers_current'];
        /** @var Collection<int|string, array<string, mixed>> $previousCustomers */
        $previousCustomers = $margin['customers_previous'];

        $topCustomers = $currentCustomers->sortByDesc('revenue')
            ->take(self::LIST_SIZE)
            ->map(function (array $customer, int|string $customerId) use ($previousCustomers): array {
                $previousRevenue = (float) ($previousCustomers->get($customerId)['revenue'] ?? 0);

                return $customer + [
                    'previous_revenue' => $previousRevenue,
                    'change' => $this->percentChange($customer['revenue'], $previousRevenue),
                ];
            })
            ->values();

        $declining = $previousCustomers->sortByDesc('revenue')
            ->take(10)
            ->map(function (array $customer, int|string $customerId) use ($currentCustomers): array {
                $currentRevenue = (float) ($currentCustomers->get($customerId)['revenue'] ?? 0);

                return $customer + [
                    'current_revenue' => $currentRevenue,
                    'change' => $this->percentChange($currentRevenue, $customer['revenue']),
                ];
            })
            ->filter(fn (array $customer): bool => $customer['change'] !== null && $customer['change'] <= -30)
            ->sortBy('change')
            ->values();

        $topShare = $margin['revenue'] > 0 && $topCustomers->isNotEmpty()
            ? round($topCustomers->first()['revenue'] / $margin['revenue'] * 100, 1)
            : 0.0;

        $invoiced = $this->invoicedBetween($companyIds, $period->start, $period->end);
        $invoicedPrevious = $this->invoicedBetween($companyIds, $period->previousStart, $period->previousEnd);

        return [
            'revenue_change' => $this->percentChange($margin['revenue'], $margin['previous']['revenue']),
            'profit_change' => $this->percentChange($margin['profit'], $margin['previous']['profit']),
            'trips_change' => $this->percentChange($margin['trips'], $margin['previous']['trips']),
            'margin_change_points' => round($margin['margin'] - $margin['previous']['margin'], 1),
            'invoiced' => $invoiced,
            'invoiced_previous' => $invoicedPrevious,
            'invoiced_change' => $this->percentChange($invoiced, $invoicedPrevious),
            'top_customers' => $topCustomers->all(),
            'declining_customers' => $declining->take(self::LIST_SIZE)->all(),
            'top_customer_share' => $topShare,
            'new_customers' => LorryReceipt::query()
                ->whereIn('company_id', $companyIds)
                ->whereNotNull('customer_id')
                ->groupBy('customer_id')
                ->havingRaw('MIN(gr_date) >= ?', [$period->start->toDateString()])
                ->havingRaw('MIN(gr_date) <= ?', [$period->end->toDateString()])
                ->pluck('customer_id')
                ->count(),
        ];
    }

    /**
     * Booked revenue, trip cost and cash collected per month for the last six months.
     *
     * @param  array<int, int>  $companyIds
     * @return array{labels: list<string>, revenue: list<float>, cost: list<float>, profit: list<float>, collected: list<float>}
     */
    public function monthlyTrend(array $companyIds): array
    {
        $trend = ['labels' => [], 'revenue' => [], 'cost' => [], 'profit' => [], 'collected' => []];
        $currentMonth = Carbon::now()->startOfMonth();

        for ($offset = self::TREND_MONTHS - 1; $offset >= 0; $offset--) {
            $monthStart = $currentMonth->copy()->subMonthsNoOverflow($offset);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $revenue = (float) $this->activeTripsQuery($companyIds, $monthStart, $monthEnd)
                ->selectRaw('COALESCE(SUM(COALESCE(actual_amount, amount, 0)), 0) as total')
                ->value('total');

            $cost = (float) FreightBooking::query()
                ->join('lorry_receipts', 'lorry_receipts.id', '=', 'freight_bookings.lr_id')
                ->whereIn('freight_bookings.company_id', $companyIds)
                ->where('freight_bookings.is_cancelled', false)
                ->whereNull('lorry_receipts.deleted_at')
                ->where(fn ($query) => $query->whereNull('lorry_receipts.delivery_status')
                    ->orWhere('lorry_receipts.delivery_status', '!=', 'Cancelled'))
                ->whereBetween('lorry_receipts.gr_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->whereRaw('freight_bookings.id = (SELECT MIN(fb2.id) FROM freight_bookings fb2 WHERE fb2.lr_id = freight_bookings.lr_id AND fb2.is_cancelled = 0 AND fb2.deleted_at IS NULL)')
                ->selectRaw('COALESCE(SUM('.self::FREIGHT_COST_SQL.'), 0) as total')
                ->value('total');

            $trend['labels'][] = $monthStart->format('M Y');
            $trend['revenue'][] = round($revenue, 2);
            $trend['cost'][] = round($cost, 2);
            $trend['profit'][] = round($revenue - $cost, 2);
            $trend['collected'][] = $this->collectedBetween($companyIds, $monthStart, $monthEnd);
        }

        return $trend;
    }

    /**
     * Ranked "what to do now" list for a dashboard audience.
     *
     * @param  array<string, mixed>  $insights
     * @return list<array{tone: string, icon: string, title: string, detail: string, url: ?string, cta: string, priority: int}>
     */
    public function actionItems(array $insights, string $audience): array
    {
        $receivables = $insights['receivables'];
        $unbilled = $insights['unbilled'];
        $payables = $insights['payables'];
        $margin = $insights['margin'];
        $exceptions = $insights['exceptions'];
        $growth = $insights['growth'];
        /** @var DashboardPeriod $period */
        $period = $insights['period'];

        $isFinance = $audience === self::AUDIENCE_FINANCE;
        $isOperations = $audience === self::AUDIENCE_OPERATIONS;
        $isOwner = in_array($audience, [self::AUDIENCE_OWNER, self::AUDIENCE_PORTFOLIO], true);
        $reportRange = ['start_date' => $period->start->toDateString(), 'end_date' => $period->end->toDateString()];
        $backlogRange = ['start_date' => Carbon::today()->subMonths(3)->toDateString(), 'end_date' => Carbon::today()->toDateString()];

        $items = [];

        if ($exceptions['eway_expired'] > 0) {
            $items[] = $this->actionItem(10, 'danger', 'bi bi-x-octagon',
                "{$exceptions['eway_expired']} running ".str('trip')->plural($exceptions['eway_expired']).' with an expired e-way bill',
                'Extend the e-way bill before the truck is stopped on the road.',
                route('reports.eway-bill'), 'Review e-way bills');
        }

        if ($receivables['overdue'] > 0 && ! $isOperations) {
            $topDebtor = collect($receivables['debtors'])->firstWhere('overdue', '>', 0);
            $items[] = $this->actionItem(20, 'danger', 'bi bi-telephone-outbound',
                'Collect '.$this->money($receivables['overdue']).' overdue from '.$receivables['overdue_customers'].' '.str('customer')->plural($receivables['overdue_customers']),
                $topDebtor
                    ? "Start with {$topDebtor['customer']}: ".$this->money($topDebtor['overdue'])." overdue, oldest {$topDebtor['max_days_overdue']} days."
                    : "{$receivables['overdue_share']}% of receivables are past due.",
                $isFinance ? route('accounting.debtors-aging') : route('invoices.index'), 'Follow up');
        }

        if ($margin['loss_trips'] > 0 && ! $isFinance) {
            $worst = $margin['worst_trips'][0] ?? null;
            $items[] = $this->actionItem(30, 'danger', 'bi bi-graph-down-arrow',
                "{$margin['loss_trips']} ".str('trip')->plural($margin['loss_trips']).' ran at a loss ('.$this->money($margin['loss_amount']).')',
                $worst
                    ? "Worst: {$worst['lr_number']} for {$worst['customer']} lost ".$this->money(abs($worst['profit'])).'. Check the customer rate and hire cost.'
                    : 'Check customer rates against hire costs.',
                route('reports.profitability', $reportRange + ['profit_loss' => 'loss']), 'See loss trips');
        }

        if ($unbilled['ready_count'] > 0) {
            $items[] = $this->actionItem(40, 'warning', 'bi bi-receipt',
                "Invoice {$unbilled['ready_count']} delivered ".str('bilty')->plural($unbilled['ready_count']).' worth '.$this->money($unbilled['ready_value']),
                "Memo received but not billed; oldest is {$unbilled['ready_oldest_days']} days old. Billing now starts the credit clock.",
                $isFinance ? route('reports.pending-invoice', $backlogRange) : route('invoices.create'), 'Create invoice');
        }

        if ($unbilled['pod_pending_count'] > 0 && ! $isFinance) {
            $items[] = $this->actionItem(50, 'warning', 'bi bi-clipboard-check',
                "Collect memo / POD for {$unbilled['pod_pending_count']} delivered ".str('bilty')->plural($unbilled['pod_pending_count']),
                $this->money($unbilled['pod_pending_value'])." can't be invoiced until the memo / POD is in (oldest {$unbilled['pod_pending_oldest_days']} days).",
                route('freight_bookings.index', ['pending_memo' => 1]), 'Update memo / POD');
        }

        if ($exceptions['eway_expiring'] > 0) {
            $items[] = $this->actionItem(55, 'warning', 'bi bi-hourglass-split',
                "{$exceptions['eway_expiring']} e-way ".str('bill')->plural($exceptions['eway_expiring']).' expire within '.self::EWAY_WARNING_DAYS.' days',
                'Extend them now if the trucks will not reach on time.',
                route('reports.eway-bill'), 'Review e-way bills');
        }

        if ($exceptions['long_running'] > 0 && ! $isFinance) {
            $items[] = $this->actionItem(60, 'warning', 'bi bi-signpost-split',
                "{$exceptions['long_running']} ".str('trip')->plural($exceptions['long_running']).' running for more than '.self::LONG_RUNNING_TRIP_DAYS.' days',
                'Confirm delivery with the driver or transporter and update the status.',
                route('freight_bookings.index'), 'Check trips');
        }

        if ($exceptions['without_hire'] > 0 && ! $isFinance) {
            $items[] = $this->actionItem(65, 'warning', 'bi bi-truck',
                "{$exceptions['without_hire']} ".str('bilty')->plural($exceptions['without_hire']).' without a lorry hire',
                'Book the hire so trip cost and transporter payment are tracked.',
                route('freight_bookings.create'), 'Create hire');
        }

        $weakCustomer = collect($margin['weak_customers'])->first(fn (array $customer): bool => $customer['margin'] < self::LOW_MARGIN_PERCENT && $customer['trips'] >= 2);
        if ($weakCustomer && ! $isFinance) {
            $items[] = $this->actionItem(70, 'warning', 'bi bi-percent',
                "Review rates for {$weakCustomer['customer']}: {$weakCustomer['margin']}% margin",
                "{$weakCustomer['trips']} trips, ".$this->money($weakCustomer['revenue']).' revenue, '.$this->money($weakCustomer['profit']).' profit this period.',
                route('reports.profitability', $reportRange), 'Open profitability');
        }

        if ($payables['due_now'] > 0 && ! $isOperations) {
            $items[] = $this->actionItem(75, 'info', 'bi bi-wallet2',
                'Plan '.$this->money($payables['due_now']).' due to '.$payables['due_now_transporters'].' '.str('transporter')->plural($payables['due_now_transporters']),
                'Balance on delivered trips. Collections due in the next '.self::DUE_SOON_DAYS.' days: '.$this->money($receivables['due_soon']).'.',
                route('reports.transporter-report'), 'View transporter dues');
        }

        if ($receivables['due_soon'] > 0 && ! $isOperations) {
            $items[] = $this->actionItem(80, 'info', 'bi bi-bell',
                $this->money($receivables['due_soon']).' falls due in the next '.self::DUE_SOON_DAYS.' days',
                "{$receivables['due_soon_count']} ".str('invoice')->plural($receivables['due_soon_count']).'. Send payment reminders before the due date.',
                $isFinance ? route('reports.party-pending-invoice') : route('invoices.index'), 'Send reminders');
        }

        $hasComparableHistory = $period->days() >= self::MIN_DAYS_FOR_TREND_ALERTS
            && $margin['previous']['trips'] >= self::MIN_TRIPS_FOR_TREND_ALERTS;

        if ($isOwner && $hasComparableHistory) {
            $revenueChange = $growth['revenue_change'];
            if ($revenueChange !== null && $revenueChange <= -self::SIGNIFICANT_CHANGE_PERCENT) {
                $items[] = $this->actionItem(35, 'warning', 'bi bi-arrow-down-right',
                    'Booked revenue is down '.abs($revenueChange).'% '.$period->comparisonLabel,
                    $this->money($margin['revenue']).' vs '.$this->money($margin['previous']['revenue']).'. Check which customers slowed down below.',
                    null, '');
            }

            foreach (array_slice($growth['declining_customers'], 0, 1) as $customer) {
                $items[] = $this->actionItem(45, 'warning', 'bi bi-person-dash',
                    "Business from {$customer['customer']} is down ".abs($customer['change']).'%',
                    $this->money($customer['current_revenue']).' vs '.$this->money($customer['revenue']).' in the previous period. Worth a call.',
                    null, '');
            }

            if ($growth['top_customer_share'] >= self::CUSTOMER_CONCENTRATION_PERCENT && $margin['trips'] >= 5) {
                $topCustomer = $growth['top_customers'][0]['customer'] ?? '';
                $items[] = $this->actionItem(85, 'info', 'bi bi-diagram-3',
                    "{$topCustomer} is {$growth['top_customer_share']}% of revenue",
                    'High dependence on one customer. Grow other accounts to reduce risk.',
                    null, '');
            }

            if ($revenueChange !== null && $revenueChange >= self::SIGNIFICANT_CHANGE_PERCENT) {
                $items[] = $this->actionItem(90, 'success', 'bi bi-arrow-up-right',
                    "Booked revenue is up {$revenueChange}% {$period->comparisonLabel}",
                    'Margin is '.$margin['margin'].'% ('.($growth['margin_change_points'] >= 0 ? '+' : '').$growth['margin_change_points'].' pts).',
                    null, '');
            }
        }

        usort($items, fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        if ($audience === self::AUDIENCE_PORTFOLIO) {
            $items = array_map(fn (array $item): array => array_merge($item, ['url' => null, 'cta' => '']), $items);
        }

        return $items;
    }

    /**
     * One row per owned company with its key numbers and the reasons it needs attention.
     *
     * @param  Collection<int, Company>  $companies  loaded with `subscription` and `pending_upcoming_renewals_count`
     * @param  array<string, mixed>  $insights
     * @return list<array<string, mixed>>
     */
    public function companyScorecards(Collection $companies, array $insights): array
    {
        $today = Carbon::today();

        return $companies->map(function (Company $company) use ($insights, $today): array {
            $receivables = $insights['receivables']['by_company'][$company->id] ?? ['outstanding' => 0.0, 'overdue' => 0.0];
            $unbilled = $insights['unbilled']['by_company'][$company->id] ?? ['ready_value' => 0.0, 'pod_pending_value' => 0.0];
            $payables = $insights['payables']['by_company'][$company->id] ?? ['due_now' => 0.0, 'total' => 0.0];
            $margin = $insights['margin']['by_company'][$company->id] ?? ['revenue' => 0.0, 'profit' => 0.0, 'margin' => 0.0, 'trips' => 0];

            $subscription = $company->subscription;
            $daysToExpiry = $subscription?->ends_on ? (int) $today->diffInDays($subscription->ends_on->copy()->startOfDay(), false) : null;
            $hasRenewal = ($company->pending_upcoming_renewals_count ?? 0) > 0;
            $creditPool = (int) ($subscription?->included_lr_credits ?? 0) + (int) ($subscription?->addon_lr_credits ?? 0);
            $creditsRemaining = (int) ($subscription?->lr_credits_remaining ?? 0);

            $flags = [];
            if ($company->is_blocked) {
                $flags[] = ['tone' => 'danger', 'label' => 'Blocked'];
            }
            if ($daysToExpiry !== null && $daysToExpiry < 0 && ! $hasRenewal) {
                $flags[] = ['tone' => 'danger', 'label' => 'Plan expired'];
            } elseif ($daysToExpiry !== null && $daysToExpiry <= self::DUE_SOON_DAYS && ! $hasRenewal) {
                $flags[] = ['tone' => 'warning', 'label' => $daysToExpiry === 0 ? 'Plan ends today' : "Plan ends in {$daysToExpiry}d"];
            }
            if ($subscription && $creditsRemaining <= max(10, (int) ceil($creditPool * 0.1))) {
                $flags[] = ['tone' => 'warning', 'label' => "{$creditsRemaining} LR credits left"];
            }
            if ($receivables['outstanding'] > 0 && $receivables['overdue'] / $receivables['outstanding'] > 0.5) {
                $flags[] = ['tone' => 'danger', 'label' => 'Over half of dues overdue'];
            }
            if ($margin['trips'] > 0 && $margin['margin'] < self::LOW_MARGIN_PERCENT) {
                $flags[] = ['tone' => 'warning', 'label' => "Margin {$margin['margin']}%"];
            }

            return [
                'id' => $company->id,
                'name' => $company->name,
                'is_blocked' => (bool) $company->is_blocked,
                'outstanding' => $receivables['outstanding'],
                'overdue' => $receivables['overdue'],
                'unbilled' => round($unbilled['ready_value'] + $unbilled['pod_pending_value'], 2),
                'payable_due' => $payables['due_now'],
                'revenue' => $margin['revenue'],
                'profit' => $margin['profit'],
                'margin' => $margin['margin'],
                'trips' => $margin['trips'],
                'days_to_expiry' => $daysToExpiry,
                'flags' => $flags,
            ];
        })
            ->sortBy([
                fn (array $a, array $b): int => count($b['flags']) <=> count($a['flags']),
                fn (array $a, array $b): int => $b['overdue'] <=> $a['overdue'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $companyIds
     */
    public function collectedBetween(array $companyIds, Carbon $start, Carbon $end): float
    {
        return round((float) InvoicePayment::query()
            ->whereHas('invoice', fn ($query) => $query->whereIn('company_id', $companyIds))
            ->where(function ($query) use ($start, $end): void {
                $query->where(fn ($dated) => $dated->whereNotNull('payment_date')
                    ->whereDate('payment_date', '>=', $start->toDateString())
                    ->whereDate('payment_date', '<=', $end->toDateString()))
                    ->orWhere(fn ($undated) => $undated->whereNull('payment_date')
                        ->whereBetween('created_at', [$start, $end]));
            })
            ->sum('amount'), 2);
    }

    /**
     * @param  array<int, int>  $companyIds
     */
    public function paidOutBetween(array $companyIds, Carbon $start, Carbon $end): float
    {
        return round((float) VoucherPayment::query()
            ->whereIn('company_id', $companyIds)
            ->where('payment_status', 'paid')
            ->where('payment_mode', '!=', 'adjust in advance')
            ->where(function ($query) use ($start, $end): void {
                $query->where(fn ($dated) => $dated->whereNotNull('date')
                    ->whereDate('date', '>=', $start->toDateString())
                    ->whereDate('date', '<=', $end->toDateString()))
                    ->orWhere(fn ($undated) => $undated->whereNull('date')
                        ->whereBetween('created_at', [$start, $end]));
            })
            ->sum('amount'), 2);
    }

    /**
     * @param  array<int, int>  $companyIds
     */
    public function invoicedBetween(array $companyIds, Carbon $start, Carbon $end): float
    {
        return round((float) Invoice::query()
            ->whereIn('company_id', $companyIds)
            ->whereBetween('generated_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_amount'), 2);
    }

    public function percentChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }

    /**
     * @param  array<int, int>  $companyIds
     * @return Collection<int, array<string, mixed>>
     */
    private function openInvoiceRows(array $companyIds, Carbon $today): Collection
    {
        return Invoice::query()
            ->whereIn('company_id', $companyIds)
            ->select(['id', 'company_id', 'customer_id', 'invoice_number', 'manual_invoice_number', 'generated_date', 'due_date', 'created_at', 'net_amount', 'total_amount'])
            ->selectRaw(self::INVOICE_SETTLED_SQL.' as settled_amount')
            ->whereRaw('COALESCE(net_amount, total_amount) - '.self::INVOICE_SETTLED_SQL.' > 0.01')
            ->with('consignee:id,name')
            ->get()
            ->map(function (Invoice $invoice) use ($today): array {
                $dueDate = $invoice->effectiveDueDate() ?? $today->copy();

                return [
                    'id' => $invoice->id,
                    'company_id' => (int) $invoice->company_id,
                    'customer_id' => $invoice->customer_id,
                    'customer' => $invoice->consignee?->name ?? '—',
                    'number' => $invoice->invoice_number
                        ? $invoice->invoice_number.($invoice->manual_invoice_number ? ' ('.$invoice->manual_invoice_number.')' : '')
                        : ($invoice->manual_invoice_number ?? '#'.$invoice->id),
                    'invoice_date' => $invoice->generated_date,
                    'due_date' => $dueDate,
                    'has_due_date' => filled($invoice->due_date),
                    'balance' => round((float) ($invoice->net_amount ?? $invoice->total_amount) - (float) $invoice->settled_amount, 2),
                    'days_overdue' => (int) $dueDate->diffInDays($today, false),
                ];
            });
    }

    /**
     * @param  array<int, int>  $companyIds
     */
    private function activeTripsQuery(array $companyIds, Carbon $start, Carbon $end): Builder
    {
        return LorryReceipt::query()
            ->whereIn('company_id', $companyIds)
            ->whereBetween('gr_date', [$start->toDateString(), $end->toDateString()])
            ->where(fn ($query) => $query->whereNull('delivery_status')->orWhere('delivery_status', '!=', 'Cancelled'))
            ->whereHas('freightBookings', fn ($query) => $query->where('is_cancelled', false));
    }

    /**
     * @param  array<int, int>  $companyIds
     * @return Collection<int, array<string, mixed>>
     */
    private function tripRows(array $companyIds, Carbon $start, Carbon $end): Collection
    {
        return $this->activeTripsQuery($companyIds, $start, $end)
            ->with([
                'freightBookings' => fn ($query) => $query->where('is_cancelled', false)->with('customAmounts'),
                'customer:id,name',
                'transporter.transporterMaster',
            ])
            ->get(['id', 'company_id', 'lr_number', 'gr_date', 'customer_id', 'transporter_id', 'source_city', 'destination_city', 'amount', 'actual_amount'])
            ->map(function (LorryReceipt $lr): array {
                $revenue = $this->bookedValue($lr);
                $cost = (float) ($lr->freightBookings->first()?->effectivePayableAmount() ?? 0);
                $profit = $revenue - $cost;

                return [
                    'company_id' => (int) $lr->company_id,
                    'lr_number' => $lr->lr_number,
                    'gr_date' => $lr->gr_date,
                    'customer_id' => $lr->customer_id,
                    'customer' => $lr->customer?->name ?? '—',
                    'transporter_id' => $lr->transporter_id,
                    'transporter' => $this->transporterLabel($lr->transporter),
                    'route' => $this->routeLabel($lr),
                    'revenue' => round($revenue, 2),
                    'cost' => round($cost, 2),
                    'profit' => round($profit, 2),
                    'margin' => $revenue > 0 ? round($profit / $revenue * 100, 1) : 0.0,
                ];
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{revenue: float, cost: float, profit: float, margin: float, trips: int, profit_per_trip: float}
     */
    private function totals(Collection $rows): array
    {
        $revenue = round($rows->sum('revenue'), 2);
        $cost = round($rows->sum('cost'), 2);
        $profit = round($revenue - $cost, 2);
        $trips = $rows->count();

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $profit,
            'margin' => $revenue > 0 ? round($profit / $revenue * 100, 1) : 0.0,
            'trips' => $trips,
            'profit_per_trip' => $trips > 0 ? round($profit / $trips, 2) : 0.0,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int|string, array<string, mixed>>
     */
    private function summarise(Collection $rows, string $groupKey, string $labelKey): Collection
    {
        return $rows->groupBy($groupKey)->map(function (Collection $groupRows) use ($labelKey): array {
            $totals = $this->totals($groupRows);

            return [
                $labelKey => $groupRows->first()[$labelKey],
                'trips' => $totals['trips'],
                'revenue' => $totals['revenue'],
                'profit' => $totals['profit'],
                'margin' => $totals['margin'],
            ];
        });
    }

    /**
     * Daily buckets for short periods, weekly buckets for longer ones.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{labels: list<string>, revenue: list<float>, cost: list<float>, profit: list<float>}
     */
    private function profitTrend(Collection $rows, DashboardPeriod $period): array
    {
        $weekly = $period->days() > 31;
        $bucketKey = fn (Carbon $date): string => $weekly ? $date->copy()->startOfWeek()->toDateString() : $date->toDateString();

        $byBucket = $rows->groupBy(fn (array $row): string => $bucketKey(Carbon::parse($row['gr_date'])));
        $trend = ['labels' => [], 'revenue' => [], 'cost' => [], 'profit' => []];

        $firstBucket = $weekly ? $period->start->copy()->startOfWeek() : $period->start->copy()->startOfDay();

        for ($date = $firstBucket; $date->lte($period->end); $date->addDays($weekly ? 7 : 1)) {
            $key = $bucketKey($date);
            $bucketRows = $byBucket->get($key, collect());
            $totals = $this->totals($bucketRows);

            $trend['labels'][] = $weekly ? 'Wk '.Carbon::parse($key)->format('d M') : $date->format('d M');
            $trend['revenue'][] = $totals['revenue'];
            $trend['cost'][] = $totals['cost'];
            $trend['profit'][] = $totals['profit'];
        }

        return $trend;
    }

    private function agingBucket(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue <= 0 => 'not_due',
            $daysOverdue <= 30 => '1_30',
            $daysOverdue <= 60 => '31_60',
            $daysOverdue <= 90 => '61_90',
            default => '90_plus',
        };
    }

    private function bookedValue(LorryReceipt $lr): float
    {
        return (float) ($lr->actual_amount ?? $lr->amount ?? 0);
    }

    private function routeLabel(LorryReceipt $lr): string
    {
        $from = trim((string) $lr->source_city);
        $to = trim((string) $lr->destination_city);

        return ($from !== '' || $to !== '') ? ($from ?: '—').' → '.($to ?: '—') : '—';
    }

    private function transporterLabel(?Transporter $transporter, ?string $fallback = null): string
    {
        $name = trim((string) $transporter?->name);

        if ($name === '') {
            $name = trim((string) $fallback);
        }

        return $name !== '' ? $name : '—';
    }

    private function money(float $amount): string
    {
        return formatRupeesCompact($amount);
    }

    /**
     * @return array{tone: string, icon: string, title: string, detail: string, url: ?string, cta: string, priority: int}
     */
    private function actionItem(int $priority, string $tone, string $icon, string $title, string $detail, ?string $url, string $cta): array
    {
        return compact('tone', 'icon', 'title', 'detail', 'url', 'cta', 'priority');
    }
}
