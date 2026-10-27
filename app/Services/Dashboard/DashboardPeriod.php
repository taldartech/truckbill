<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;

/**
 * A dashboard reporting window plus the equal-length window right before it,
 * used for "vs previous period" comparisons.
 */
final class DashboardPeriod
{
    public const DEFAULT_KEY = 'month';

    /**
     * @var array<string, string>
     */
    public const LABELS = [
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'month' => 'This month',
        'quarter' => 'This quarter',
    ];

    public function __construct(
        public readonly string $key,
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly Carbon $previousStart,
        public readonly Carbon $previousEnd,
        public readonly string $label,
        public readonly string $comparisonLabel,
    ) {}

    public static function fromKey(?string $key, ?Carbon $now = null): self
    {
        $key = array_key_exists((string) $key, self::LABELS) ? (string) $key : self::DEFAULT_KEY;
        $now = ($now ?? Carbon::now())->copy();
        $end = $now->copy()->endOfDay();

        return match ($key) {
            '7d', '30d' => self::rolling($key, $end, $key === '7d' ? 7 : 30),
            'quarter' => self::toDate($key, $now->copy()->startOfQuarter(), $end, 3, 'vs same days last quarter'),
            default => self::toDate($key, $now->copy()->startOfMonth(), $end, 1, 'vs same days last month'),
        };
    }

    public function days(): int
    {
        return (int) $this->start->copy()->startOfDay()->diffInDays($this->end->copy()->startOfDay()) + 1;
    }

    private static function rolling(string $key, Carbon $end, int $days): self
    {
        $start = $end->copy()->subDays($days - 1)->startOfDay();
        $previousEnd = $start->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return new self($key, $start, $end, $previousStart, $previousEnd, self::LABELS[$key], "vs previous {$days} days");
    }

    private static function toDate(string $key, Carbon $start, Carbon $end, int $months, string $comparisonLabel): self
    {
        $previousStart = $start->copy()->subMonthsNoOverflow($months);
        $elapsedDays = (int) $start->diffInDays($end->copy()->startOfDay());
        $previousEnd = $previousStart->copy()->addDays($elapsedDays)->endOfDay();
        $previousPeriodLastDay = $start->copy()->subDay()->endOfDay();

        if ($previousEnd->gt($previousPeriodLastDay)) {
            $previousEnd = $previousPeriodLastDay;
        }

        return new self($key, $start->startOfDay(), $end, $previousStart->startOfDay(), $previousEnd, self::LABELS[$key], $comparisonLabel);
    }
}
