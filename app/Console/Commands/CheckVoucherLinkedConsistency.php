<?php

namespace App\Console\Commands;

use App\Models\FreightBooking;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CheckVoucherLinkedConsistency extends Command
{
    private const float AMOUNT_TOLERANCE = 0.02;

    /**
     * @var string
     */
    protected $signature = 'voucher-payments:check-linked-consistency
                            {--company-id= : Limit checks to a single company}
                            {--id= : Check only this voucher_linkeds id}
                            {--include-trashed : Include soft-deleted rows in the audit}
                            {--apply : Create or update voucher payments by ratio and recalculate freight booking amounts}
                            {--reset-negative-adjusted : When applying fixes, reset negative_adjusted_amount on linked bookings}';

    /**
     * @var string
     */
    protected $description = 'Audit voucher_payments and voucher_linkeds for combined-payment linkage and amount consistency';

    public function handle(): int
    {
        $companyId = $this->option('company-id');
        $voucherLinkedId = $this->option('id');
        $includeTrashed = (bool) $this->option('include-trashed');
        $apply = (bool) $this->option('apply');
        $resetNegativeAdjusted = (bool) $this->option('reset-negative-adjusted');

        $parsedCompanyId = $companyId !== null && $companyId !== '' ? (int) $companyId : null;
        $parsedLinkedId = $voucherLinkedId !== null && $voucherLinkedId !== '' ? (int) $voucherLinkedId : null;

        $result = $this->runCheck($parsedCompanyId, $parsedLinkedId, $includeTrashed);
        $summary = $result['summary'];
        $issues = $result['issues'];

        $this->info('Checked '.$summary['voucher_linkeds_checked'].' voucher_linkeds and '.
            $summary['linked_voucher_payments_checked'].' linked voucher_payments.');

        if ($issues !== []) {
            $this->error('Found '.$summary['issue_count'].' issue(s):');
            $this->table(
                ['Code', 'VoucherLinked ID', 'VoucherPayment ID', 'Message'],
                array_map(static fn (array $issue): array => [
                    $issue['code'],
                    $issue['voucher_linked_id'] ?? '-',
                    $issue['voucher_payment_id'] ?? '-',
                    $issue['message'],
                ], $issues)
            );
        }

        $fixableIds = $this->extractFixableLinkedIds($issues);
        $repairPlans = [];

        foreach ($this->groupLinkedIdsByBookingGroup($fixableIds) as $groupLinkedIds) {
            $firstLinkedVoucher = VoucherLinked::query()->find($groupLinkedIds[0]);

            if ($firstLinkedVoucher === null) {
                continue;
            }

            $bookings = $this->resolveCombinedFreightBookings($firstLinkedVoucher);

            if ($bookings->isEmpty()) {
                continue;
            }

            $bookings->loadMissing('lr');

            $this->newLine();
            $this->comment('Combined booking group (linked_lr_id '.($bookings->first()?->linked_lr_id ?? 'N/A').'):');
            $this->table(
                ['FB', 'LR', 'Voucher #', 'Current balance', 'Effective payable'],
                $bookings->map(fn (FreightBooking $booking): array => [
                    $booking->id,
                    $booking->lr?->lr_number ?? 'N/A',
                    (string) $booking->voucher_number,
                    number_format((float) $booking->balance_amount, 2),
                    number_format($this->effectivePayableAmount($booking), 2),
                ])->all()
            );

            $this->comment('Checking linked vouchers: '.implode(', ', array_map(
                static fn (int $id): string => '#'.$id,
                $groupLinkedIds
            )));

            $groupPlans = $this->buildGroupRepairPlansSequentially($bookings, $groupLinkedIds, $resetNegativeAdjusted);
            array_push($repairPlans, ...$groupPlans);
        }

        if ($issues === [] && $repairPlans === []) {
            $this->info('No linkage inconsistencies found.');

            return self::SUCCESS;
        }

        if ($repairPlans === []) {
            return self::FAILURE;
        }

        if (! $apply) {
            $this->comment('Dry run only. Re-run with --apply to create or update voucher payments by ratio.');

            return self::FAILURE;
        }

        if (! $this->option('no-interaction') && ! $this->confirm(
            'Apply the planned voucher payment creates/updates and freight booking recalculations above?',
            true
        )) {
            $this->warn('Apply cancelled.');

            return self::FAILURE;
        }

        foreach ($this->groupRepairPlans($repairPlans) as $groupPlans) {
            $this->applyGroupRepairPlans($groupPlans, $resetNegativeAdjusted);
        }

        $result = $this->runCheck($parsedCompanyId, $parsedLinkedId, $includeTrashed);

        if ($result['issues'] === []) {
            $this->info('All linkage inconsistencies resolved.');

            return self::SUCCESS;
        }

        $this->error('Some issues remain after apply:');
        $this->table(
            ['Code', 'VoucherLinked ID', 'VoucherPayment ID', 'Message'],
            array_map(static fn (array $issue): array => [
                $issue['code'],
                $issue['voucher_linked_id'] ?? '-',
                $issue['voucher_payment_id'] ?? '-',
                $issue['message'],
            ], $result['issues'])
        );

        return self::FAILURE;
    }

    /**
     * @param  list<array{code: string, voucher_linked_id: int|null, voucher_payment_id: int|null, message: string}>  $issues
     * @return list<int>
     */
    private function extractFixableLinkedIds(array $issues): array
    {
        $seedIds = collect($issues)
            ->filter(fn (array $issue): bool => in_array($issue['code'], [
                'amount_sum_mismatch',
                'empty_voucher_ids_with_children',
            ], true))
            ->pluck('voucher_linked_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique();

        $expandedIds = collect();

        foreach ($seedIds as $linkedId) {
            $linkedVoucher = VoucherLinked::query()->find($linkedId);

            if ($linkedVoucher === null) {
                continue;
            }

            $bookingIds = $this->resolveCombinedFreightBookings($linkedVoucher)->pluck('id')->all();

            if ($bookingIds === []) {
                $expandedIds->push($linkedId);

                continue;
            }

            foreach ($this->findAllLinkedVoucherIdsForBookings((int) $linkedVoucher->company_id, $bookingIds) as $groupLinkedId) {
                $expandedIds->push($groupLinkedId);
            }
        }

        return $expandedIds->unique()->values()->all();
    }

    /**
     * @param  list<int>  $linkedIds
     * @return list<list<int>>
     */
    private function groupLinkedIdsByBookingGroup(array $linkedIds): array
    {
        $groupsByKey = [];

        foreach ($linkedIds as $linkedId) {
            $linkedVoucher = VoucherLinked::query()->find($linkedId);

            if ($linkedVoucher === null) {
                continue;
            }

            $bookingIds = $this->resolveCombinedFreightBookings($linkedVoucher)->pluck('id')->sort()->values()->all();
            $groupKey = implode(',', $bookingIds);

            $groupIds = $this->findAllLinkedVoucherIdsForBookings((int) $linkedVoucher->company_id, $bookingIds);

            if (isset($groupsByKey[$groupKey])) {
                $groupsByKey[$groupKey] = array_values(array_unique(array_merge($groupsByKey[$groupKey], $groupIds)));

                continue;
            }

            $groupsByKey[$groupKey] = $groupIds;
        }

        return array_values($groupsByKey);
    }

    /**
     * @param  list<int>  $bookingIds
     * @return list<int>
     */
    private function findAllLinkedVoucherIdsForBookings(int $companyId, array $bookingIds): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $linkedIdsFromPrimary = VoucherLinked::query()
            ->where('company_id', $companyId)
            ->whereIn('freight_booking_id', $bookingIds)
            ->pluck('id');

        $linkedIdsFromPayments = VoucherPayment::query()
            ->whereIn('freight_booking_id', $bookingIds)
            ->whereNotNull('linked_voucher_id')
            ->whereNull('deleted_at')
            ->pluck('linked_voucher_id');

        return $linkedIdsFromPrimary
            ->merge($linkedIdsFromPayments)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, FreightBooking>  $bookings
     * @param  list<int>  $groupLinkedIds
     * @return list<array<string, mixed>>
     */
    private function buildGroupRepairPlansSequentially(
        Collection $bookings,
        array $groupLinkedIds,
        bool $resetNegativeAdjusted
    ): array {
        $bookingIds = $bookings->pluck('id')->all();
        $simulation = $this->initializeBookingSimulation($bookings);
        $plans = [];

        $this->line('  Replaying payments chronologically (standalone first, then linked vouchers by date):');

        $standalonePayments = VoucherPayment::query()
            ->whereIn('freight_booking_id', $bookingIds)
            ->whereNull('linked_voucher_id')
            ->whereNull('deleted_at')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $linkedVouchers = VoucherLinked::query()
            ->whereIn('id', $groupLinkedIds)
            ->orderBy('date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $events = collect();

        foreach ($standalonePayments as $payment) {
            $events->push([
                'type' => 'standalone',
                'sort_key' => ($payment->date ?? '1970-01-01').'-P-'.str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT),
                'payment' => $payment,
            ]);
        }

        foreach ($linkedVouchers as $linkedVoucher) {
            $events->push([
                'type' => 'linked',
                'sort_key' => ($linkedVoucher->date ?? '1970-01-01').'-LV-'.str_pad((string) $linkedVoucher->id, 10, '0', STR_PAD_LEFT),
                'linked_voucher' => $linkedVoucher,
            ]);
        }

        foreach ($events->sortBy('sort_key')->values() as $event) {
            if ($event['type'] === 'standalone') {
                /** @var VoucherPayment $payment */
                $payment = $event['payment'];
                $this->applyAmountToSimulation($simulation, (int) $payment->freight_booking_id, (float) $payment->amount);
                $this->line('    Standalone VoucherPayment #'.$payment->id.' on FB #'.$payment->freight_booking_id.
                    ': '.number_format((float) $payment->amount, 2));

                continue;
            }

            /** @var VoucherLinked $linkedVoucher */
            $linkedVoucher = $event['linked_voucher'];
            $balanceSnapshot = collect($simulation)->mapWithKeys(
                fn (array $state, int $bookingId): array => [$bookingId => $state['balance']]
            );

            $this->line('    VoucherLinked #'.$linkedVoucher->id.' ('.($linkedVoucher->date ?? 'no date').') — balances before split: '.
                $balanceSnapshot->map(fn (float $balance, int $bookingId): string => "FB#{$bookingId}=".number_format($balance, 2))
                    ->implode(', '));

            $plan = $this->buildRepairPlan($linkedVoucher, $resetNegativeAdjusted, $balanceSnapshot, $simulation);
            $targets = collect($this->calculateTargetAllocations(
                $bookings,
                (float) $linkedVoucher->amount,
                $balanceSnapshot
            ));

            if ($plan !== null) {
                $plans[] = $plan;
                $this->displayRepairPlan($plan);
            } else {
                $this->line('  VoucherLinked #'.$linkedVoucher->id.': allocation already matches signed balance ratio.');
            }

            foreach ($targets as $bookingId => $amount) {
                if (abs((float) $amount) > self::AMOUNT_TOLERANCE) {
                    $this->applyAmountToSimulation($simulation, (int) $bookingId, (float) $amount);
                }
            }
        }

        return $plans;
    }

    /**
     * @param  Collection<int, FreightBooking>  $bookings
     * @return array<int, array{effective_payable: float, advance: float, balance: float}>
     */
    private function initializeBookingSimulation(Collection $bookings): array
    {
        $simulation = [];

        foreach ($bookings as $booking) {
            $effectivePayable = $this->effectivePayableAmount($booking);
            $simulation[$booking->id] = [
                'effective_payable' => $effectivePayable,
                'advance' => 0.0,
                'balance' => $effectivePayable,
            ];
        }

        return $simulation;
    }

    /**
     * @param  array<int, array{effective_payable: float, advance: float, balance: float}>  $simulation
     */
    private function applyAmountToSimulation(array &$simulation, int $bookingId, float $amount): void
    {
        if (! isset($simulation[$bookingId])) {
            return;
        }

        $simulation[$bookingId]['advance'] = round($simulation[$bookingId]['advance'] + $amount, 2);
        $simulation[$bookingId]['balance'] = round(
            $simulation[$bookingId]['effective_payable'] - $simulation[$bookingId]['advance'],
            2
        );
    }

    /**
     * @param  Collection<int, float>|null  $balanceSnapshot
     * @param  array<int, array{effective_payable: float, advance: float, balance: float}>|null  $simulation
     * @return array<string, mixed>|null
     */
    private function buildRepairPlan(
        VoucherLinked $linkedVoucher,
        bool $resetNegativeAdjusted,
        ?Collection $balanceSnapshot = null,
        ?array $simulation = null
    ): ?array {
        $bookings = $this->resolveCombinedFreightBookings($linkedVoucher);

        if ($bookings->isEmpty()) {
            return null;
        }

        $bookings->loadMissing('lr');

        $existingPayments = VoucherPayment::query()
            ->where('linked_voucher_id', $linkedVoucher->id)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('freight_booking_id');

        $templatePayment = $existingPayments->first();
        $paymentMode = (string) ($templatePayment?->payment_mode ?? $linkedVoucher->payment_mode);
        $linkedAmount = (float) $linkedVoucher->amount;
        $currentPaymentSum = (float) $existingPayments->sum('amount');
        $targetAllocations = $this->calculateTargetAllocations($bookings, $linkedAmount, $balanceSnapshot);
        $paymentChanges = [];
        $allocationPreview = [];

        foreach ($bookings as $booking) {
            $targetAmount = (float) ($targetAllocations[$booking->id] ?? 0);
            $existingPayment = $existingPayments->get($booking->id);
            $lrNumber = $booking->lr?->lr_number ?? 'N/A';
            $currentBalance = (float) ($balanceSnapshot?->get($booking->id) ?? $booking->balance_amount);
            $ratioPercent = $linkedAmount > self::AMOUNT_TOLERANCE
                ? round(($targetAmount / $linkedAmount) * 100, 2)
                : 0.0;

            $allocationPreview[] = [
                'freight_booking_id' => (int) $booking->id,
                'lr_number' => $lrNumber,
                'voucher_number' => (string) $booking->voucher_number,
                'current_balance' => $currentBalance,
                'target_amount' => $targetAmount,
                'ratio_percent' => $ratioPercent,
                'existing_amount' => $existingPayment ? (float) $existingPayment->amount : null,
            ];

            if ($existingPayment) {
                if (abs((float) $existingPayment->amount - $targetAmount) > self::AMOUNT_TOLERANCE) {
                    $paymentChanges[] = [
                        'action' => 'update',
                        'voucher_payment_id' => (int) $existingPayment->id,
                        'freight_booking_id' => (int) $booking->id,
                        'lr_number' => $lrNumber,
                        'voucher_number' => (string) $booking->voucher_number,
                        'current_amount' => (float) $existingPayment->amount,
                        'new_amount' => $targetAmount,
                        'ratio_percent' => $ratioPercent,
                    ];
                }

                continue;
            }

            if (abs($targetAmount) <= self::AMOUNT_TOLERANCE) {
                continue;
            }

            $paymentChanges[] = [
                'action' => 'create',
                'voucher_payment_id' => null,
                'freight_booking_id' => (int) $booking->id,
                'lr_number' => $lrNumber,
                'voucher_number' => (string) $booking->voucher_number,
                'current_amount' => null,
                'new_amount' => $targetAmount,
                'ratio_percent' => $ratioPercent,
            ];
        }

        $bookingChanges = [];

        foreach ($bookings as $booking) {
            $targetAmount = (float) ($targetAllocations[$booking->id] ?? 0);
            $projected = $simulation !== null
                ? $this->projectBookingAmountsFromSimulation($simulation, (int) $booking->id, $targetAmount, $resetNegativeAdjusted)
                : $this->projectBookingAmounts(
                    $booking,
                    (int) $linkedVoucher->id,
                    $targetAmount,
                    $paymentMode,
                    $resetNegativeAdjusted
                );

            $bookingChanged = abs($projected['current_advance'] - $projected['new_advance']) > self::AMOUNT_TOLERANCE
                || abs($projected['current_balance'] - $projected['new_balance']) > self::AMOUNT_TOLERANCE
                || abs($projected['current_negative_adjusted'] - $projected['new_negative_adjusted']) > self::AMOUNT_TOLERANCE;

            if (! $bookingChanged) {
                continue;
            }

            $bookingChanges[] = array_merge($projected, [
                'freight_booking_id' => (int) $booking->id,
                'lr_number' => $booking->lr?->lr_number ?? 'N/A',
                'voucher_number' => (string) $booking->voucher_number,
            ]);
        }

        $projectedPaymentSum = round(array_sum($targetAllocations), 2);
        $voucherIdsBefore = $this->parseVoucherIds($linkedVoucher->voucher_ids);
        $existingPaymentIds = $existingPayments->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
        sort($voucherIdsBefore);
        sort($existingPaymentIds);
        $needsVoucherIdsSync = $voucherIdsBefore !== $existingPaymentIds
            && $existingPayments->isNotEmpty();

        if ($paymentChanges === [] && ! $needsVoucherIdsSync) {
            return null;
        }

        return [
            'voucher_linked_id' => (int) $linkedVoucher->id,
            'linked_amount' => $linkedAmount,
            'linked_date' => $linkedVoucher->date,
            'current_payment_sum' => $currentPaymentSum,
            'projected_payment_sum' => $projectedPaymentSum,
            'voucher_ids_before' => $this->parseVoucherIds($linkedVoucher->voucher_ids),
            'existing_linked_payments' => $existingPayments->map(fn (VoucherPayment $payment): array => [
                'voucher_payment_id' => (int) $payment->id,
                'freight_booking_id' => (int) $payment->freight_booking_id,
                'amount' => (float) $payment->amount,
            ])->values()->all(),
            'target_allocations' => collect($targetAllocations)->map(
                fn (float $amount, int $bookingId): array => ['freight_booking_id' => $bookingId, 'amount' => $amount]
            )->values()->all(),
            'allocation_preview' => $allocationPreview,
            'payment_changes' => $paymentChanges,
            'booking_changes' => $bookingChanges,
            'payment_mode' => $paymentMode,
            'sync_voucher_ids_only' => $paymentChanges === [] && $needsVoucherIdsSync,
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function displayRepairPlan(array $plan): void
    {
        $this->newLine();
        $this->info('Repair plan for VoucherLinked #'.$plan['voucher_linked_id'].' ('.($plan['linked_date'] ?? 'no date').')');
        $this->line('  Linked amount: '.number_format((float) $plan['linked_amount'], 2));
        $this->line('  Current payment sum: '.number_format((float) $plan['current_payment_sum'], 2));
        $this->line('  Projected payment sum: '.number_format((float) $plan['projected_payment_sum'], 2));
        $this->line('  voucher_ids before: '.json_encode($plan['voucher_ids_before']));

        if ($plan['existing_linked_payments'] !== []) {
            $this->line('  Existing linked voucher_payments:');
            $this->table(
                ['VoucherPayment', 'FB', 'Amount'],
                collect($plan['existing_linked_payments'])->map(fn (array $payment): array => [
                    $payment['voucher_payment_id'],
                    $payment['freight_booking_id'],
                    number_format((float) $payment['amount'], 2),
                ])->all()
            );
        }

        if (! empty($plan['sync_voucher_ids_only'])) {
            $this->line('  Will sync voucher_ids from existing linked payments only (no voucher_payment amount changes).');
        }

        $this->line('  Target split by balance ratio at time of this payment:');
        $this->table(
            ['FB', 'LR', 'Voucher #', 'Balance', 'Existing', 'Target', 'Ratio %'],
            collect($plan['allocation_preview'])->map(fn (array $row): array => [
                $row['freight_booking_id'],
                $row['lr_number'],
                $row['voucher_number'],
                number_format((float) $row['current_balance'], 2),
                $row['existing_amount'] === null ? '-' : number_format((float) $row['existing_amount'], 2),
                number_format((float) $row['target_amount'], 2),
                number_format((float) $row['ratio_percent'], 2),
            ])->all()
        );

        if ($plan['payment_changes'] === []) {
            $this->line('  No voucher_payment rows need to be created or updated.');
        } else {
            $this->line('  Planned voucher_payment changes (ratio of linked amount):');
            $this->table(
                ['Action', 'VoucherPayment', 'FB', 'LR', 'Voucher #', 'Ratio %', 'Current', 'New'],
                collect($plan['payment_changes'])->map(fn (array $change): array => [
                    strtoupper($change['action']),
                    $change['voucher_payment_id'] ?? 'NEW',
                    $change['freight_booking_id'],
                    $change['lr_number'],
                    $change['voucher_number'],
                    number_format((float) $change['ratio_percent'], 2),
                    $change['current_amount'] === null ? '-' : number_format((float) $change['current_amount'], 2),
                    number_format((float) $change['new_amount'], 2),
                ])->all()
            );
        }

        if ($plan['booking_changes'] === []) {
            $this->line('  No freight_booking amounts need to change.');
        } else {
            $this->table(
                ['FB', 'LR', 'Voucher #', 'Advance (now → new)', 'Balance (now → new)', 'Neg. adj. (now → new)'],
                collect($plan['booking_changes'])->map(fn (array $change): array => [
                    $change['freight_booking_id'],
                    $change['lr_number'],
                    $change['voucher_number'],
                    number_format((float) $change['current_advance'], 2).' → '.number_format((float) $change['new_advance'], 2),
                    number_format((float) $change['current_balance'], 2).' → '.number_format((float) $change['new_balance'], 2),
                    number_format((float) $change['current_negative_adjusted'], 2).' → '.number_format((float) $change['new_negative_adjusted'], 2),
                ])->all()
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $repairPlans
     * @return list<list<array<string, mixed>>>
     */
    private function groupRepairPlans(array $repairPlans): array
    {
        $groupsByKey = [];

        foreach ($repairPlans as $plan) {
            $linkedVoucher = VoucherLinked::query()->find($plan['voucher_linked_id']);

            if ($linkedVoucher === null) {
                continue;
            }

            $bookingIds = $this->resolveCombinedFreightBookings($linkedVoucher)->pluck('id')->sort()->values()->all();
            $groupKey = implode(',', $bookingIds);

            $groupsByKey[$groupKey] ??= [];
            $groupsByKey[$groupKey][] = $plan;
        }

        return array_values($groupsByKey);
    }

    /**
     * @param  list<array<string, mixed>>  $plans
     */
    private function applyGroupRepairPlans(array $plans, bool $resetNegativeAdjusted): void
    {
        if ($plans === []) {
            return;
        }

        DB::transaction(function () use ($plans, $resetNegativeAdjusted): void {
            $firstPlan = $plans[0];
            $linkedVoucher = VoucherLinked::query()->lockForUpdate()->findOrFail($firstPlan['voucher_linked_id']);
            $bookings = $this->resolveCombinedFreightBookings($linkedVoucher);

            foreach ($plans as $plan) {
                $this->applyRepairPlanChanges($plan);
            }

            foreach ($bookings as $booking) {
                $this->recalculateFreightBookingFromVoucherPayments(
                    $booking,
                    (string) ($plans[0]['payment_mode'] ?? 'cash'),
                    $resetNegativeAdjusted
                );
                $this->line('  Recalculated FB #'.$booking->id.': advance '.
                    number_format((float) $booking->advance_amount, 2).', balance '.
                    number_format((float) $booking->balance_amount, 2));
            }
        });
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    private function applyRepairPlanChanges(array $plan): void
    {
        $linkedVoucher = VoucherLinked::query()->lockForUpdate()->findOrFail($plan['voucher_linked_id']);
        $bookings = $this->resolveCombinedFreightBookings($linkedVoucher);

        if ($bookings->isEmpty()) {
            $this->warn("VoucherLinked #{$plan['voucher_linked_id']}: no freight bookings found to repair.");

            return;
        }

        $existingPayments = VoucherPayment::query()
            ->where('linked_voucher_id', $linkedVoucher->id)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('freight_booking_id');

        $templatePayment = $existingPayments->first();

        foreach ($plan['payment_changes'] as $change) {
            if ($change['action'] === 'update') {
                $payment = VoucherPayment::query()->lockForUpdate()->findOrFail($change['voucher_payment_id']);
                $payment->amount = $change['new_amount'];
                $payment->save();
                $this->line('  Updated VoucherPayment #'.$payment->id.' for FB #'.$change['freight_booking_id'].' to '.
                    number_format((float) $change['new_amount'], 2));

                continue;
            }

            $booking = $bookings->firstWhere('id', $change['freight_booking_id']);

            if ($booking === null) {
                continue;
            }

            $payment = VoucherPayment::create([
                'freight_booking_id' => $booking->id,
                'voucher_number' => (string) $booking->voucher_number,
                'payment_type' => $templatePayment?->payment_type ?? $linkedVoucher->payment_type,
                'payment_mode' => $templatePayment?->payment_mode ?? $linkedVoucher->payment_mode,
                'payment_type_id' => $templatePayment?->payment_type_id ?? $linkedVoucher->payment_type_id,
                'payment_mode_id' => $templatePayment?->payment_mode_id ?? $linkedVoucher->payment_mode_id,
                'pump_id' => $templatePayment?->pump_id ?? $linkedVoucher->pump_id,
                'amount' => $change['new_amount'],
                'remark' => $linkedVoucher->remark,
                'payment_status' => $templatePayment?->payment_status ?? $linkedVoucher->payment_status,
                'company_id' => $linkedVoucher->company_id,
                'date' => $linkedVoucher->date,
                'linked_voucher_id' => $linkedVoucher->id,
            ]);
            $this->line('  Created VoucherPayment #'.$payment->id.' for FB #'.$booking->id.' with '.
                number_format((float) $change['new_amount'], 2));
        }

        $paymentIds = VoucherPayment::query()
            ->where('linked_voucher_id', $linkedVoucher->id)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $linkedVoucher->voucher_ids = json_encode($paymentIds);
        $linkedVoucher->save();
        $this->line('  voucher_ids after for VoucherLinked #'.$linkedVoucher->id.': '.json_encode($paymentIds));
        $this->info('Applied repair for VoucherLinked #'.$plan['voucher_linked_id'].'.');
    }

    /**
     * @param  array<int, array{effective_payable: float, advance: float, balance: float}>  $simulation
     * @return array{
     *     current_advance: float,
     *     current_balance: float,
     *     current_negative_adjusted: float,
     *     new_advance: float,
     *     new_balance: float,
     *     new_negative_adjusted: float
     * }
     */
    private function projectBookingAmountsFromSimulation(
        array $simulation,
        int $bookingId,
        float $targetLinkedPaymentAmount,
        bool $resetNegativeAdjusted
    ): array {
        $state = $simulation[$bookingId] ?? [
            'effective_payable' => 0.0,
            'advance' => 0.0,
            'balance' => 0.0,
        ];

        $newAdvance = round($state['advance'] + $targetLinkedPaymentAmount, 2);
        $newBalance = round($state['effective_payable'] - $newAdvance, 2);

        return [
            'current_advance' => $state['advance'],
            'current_balance' => $state['balance'],
            'current_negative_adjusted' => 0.0,
            'new_advance' => $newAdvance,
            'new_balance' => $newBalance,
            'new_negative_adjusted' => $resetNegativeAdjusted ? 0.0 : 0.0,
        ];
    }

    /**
     * @return array{
     *     current_advance: float,
     *     current_balance: float,
     *     current_negative_adjusted: float,
     *     new_advance: float,
     *     new_balance: float,
     *     new_negative_adjusted: float
     * }
     */
    private function projectBookingAmounts(
        FreightBooking $booking,
        int $linkedVoucherId,
        float $targetLinkedPaymentAmount,
        string $paymentMode,
        bool $resetNegativeAdjusted
    ): array {
        $otherPaymentsTotal = (float) VoucherPayment::query()
            ->where('freight_booking_id', $booking->id)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($linkedVoucherId): void {
                $query->whereNull('linked_voucher_id')
                    ->orWhere('linked_voucher_id', '!=', $linkedVoucherId);
            })
            ->sum('amount');

        $totalVoucherPayments = $otherPaymentsTotal + $targetLinkedPaymentAmount;
        $effectivePayableAmount = $this->effectivePayableAmount($booking);

        if (strtolower($paymentMode) === 'adjust in advance') {
            $newAdvance = (float) $booking->advance_amount;
            $newBalance = $effectivePayableAmount - $newAdvance;
        } else {
            $newAdvance = $totalVoucherPayments;
            $newBalance = $effectivePayableAmount - $totalVoucherPayments;
        }

        return [
            'current_advance' => (float) $booking->advance_amount,
            'current_balance' => (float) $booking->balance_amount,
            'current_negative_adjusted' => (float) ($booking->negative_adjusted_amount ?? 0),
            'new_advance' => $newAdvance,
            'new_balance' => $newBalance,
            'new_negative_adjusted' => $resetNegativeAdjusted ? 0.0 : (float) ($booking->negative_adjusted_amount ?? 0),
        ];
    }

    /**
     * @param  Collection<int, FreightBooking>  $bookings
     * @param  Collection<int, float>|null  $balanceSnapshot
     * @return array<int, float>
     */
    private function calculateTargetAllocations(
        Collection $bookings,
        float $totalAmount,
        ?Collection $balanceSnapshot = null
    ): array {
        $balances = $bookings->mapWithKeys(function (FreightBooking $booking) use ($balanceSnapshot): array {
            $balance = $balanceSnapshot !== null
                ? (float) ($balanceSnapshot[$booking->id] ?? 0)
                : (float) $booking->balance_amount;

            return [$booking->id => $balance];
        });
        $totalBalance = (float) $balances->sum();

        if (abs($totalBalance) > self::AMOUNT_TOLERANCE) {
            return $this->allocateBySignedBalanceRatio($bookings, $totalAmount, $balances, $totalBalance);
        }

        $effectiveAmounts = $bookings->mapWithKeys(
            fn (FreightBooking $booking): array => [$booking->id => $this->effectivePayableAmount($booking)]
        );

        $effectiveSum = (float) $effectiveAmounts->sum();

        if (abs($effectiveSum - $totalAmount) <= self::AMOUNT_TOLERANCE) {
            return $effectiveAmounts->map(fn (float $amount): float => round($amount, 2))->all();
        }

        $weights = $bookings->mapWithKeys(function (FreightBooking $booking) use ($effectiveAmounts, $balances): array {
            $balance = (float) ($balances[$booking->id] ?? 0);
            $weight = abs($balance) > self::AMOUNT_TOLERANCE
                ? abs($balance)
                : abs((float) ($effectiveAmounts[$booking->id] ?? 0));

            return [$booking->id => max($weight, 0.01)];
        });

        return $this->allocateProportionally($bookings, $totalAmount, $weights);
    }

    /**
     * @param  Collection<int, float>  $balances
     * @return array<int, float>
     */
    private function allocateBySignedBalanceRatio(
        Collection $bookings,
        float $totalAmount,
        Collection $balances,
        float $totalBalance
    ): array {
        $targets = [];
        $allocated = 0.0;
        $items = [];

        foreach ($bookings as $booking) {
            $balance = (float) ($balances[$booking->id] ?? 0);

            if (abs($balance) <= self::AMOUNT_TOLERANCE) {
                $targets[$booking->id] = 0.0;

                continue;
            }

            $share = $totalAmount * ($balance / $totalBalance);

            if ($balance > 0) {
                $share = min($share, $balance);
            } else {
                $share = max($share, $balance);
            }

            $targets[$booking->id] = round($share, 2);
            $allocated += $targets[$booking->id];
            $items[] = [
                'booking_id' => $booking->id,
                'balance' => $balance,
            ];
        }

        $diff = round($totalAmount - $allocated, 2);

        if ($diff > self::AMOUNT_TOLERANCE) {
            foreach ($items as $item) {
                if ($item['balance'] <= self::AMOUNT_TOLERANCE) {
                    continue;
                }

                $bookingId = $item['booking_id'];
                $room = round($item['balance'] - ($targets[$bookingId] ?? 0), 2);

                if ($room <= self::AMOUNT_TOLERANCE) {
                    continue;
                }

                $add = min($room, $diff);
                $targets[$bookingId] = round(($targets[$bookingId] ?? 0) + $add, 2);
                $diff = round($diff - $add, 2);

                if ($diff <= self::AMOUNT_TOLERANCE) {
                    break;
                }
            }
        }

        return $targets;
    }

    /**
     * @param  Collection<int, float>  $weights
     * @return array<int, float>
     */
    private function allocateProportionally(Collection $bookings, float $totalAmount, Collection $weights): array
    {
        $weightSum = (float) $weights->sum();

        if ($weightSum <= self::AMOUNT_TOLERANCE) {
            return [];
        }

        $targets = [];
        $allocated = 0.0;
        $lastBookingId = null;

        foreach ($bookings as $booking) {
            $share = round($totalAmount * ((float) $weights[$booking->id] / $weightSum), 2);
            $targets[$booking->id] = $share;
            $allocated += $share;
            $lastBookingId = $booking->id;
        }

        if ($lastBookingId !== null && abs($allocated - $totalAmount) > self::AMOUNT_TOLERANCE) {
            $targets[$lastBookingId] = round($targets[$lastBookingId] + ($totalAmount - $allocated), 2);
        }

        return $targets;
    }

    private function recalculateFreightBookingFromVoucherPayments(
        FreightBooking $booking,
        string $paymentMode,
        bool $resetNegativeAdjusted
    ): void {
        $booking = FreightBooking::query()->lockForUpdate()->findOrFail($booking->id);

        if ($resetNegativeAdjusted) {
            $booking->negative_adjusted_amount = 0;
        }

        $effectivePayableAmount = $this->effectivePayableAmount($booking);
        $totalVoucherPayments = (float) VoucherPayment::query()
            ->where('freight_booking_id', $booking->id)
            ->whereNull('deleted_at')
            ->sum('amount');

        if (strtolower($paymentMode) === 'adjust in advance') {
            $booking->balance_amount = $effectivePayableAmount - (float) $booking->advance_amount;
        } else {
            $booking->advance_amount = $totalVoucherPayments;
            $booking->balance_amount = $effectivePayableAmount - $totalVoucherPayments;
        }

        $booking->save();
    }

    private function effectivePayableAmount(FreightBooking $booking): float
    {
        return (float) $booking->tr_amount
            - (float) ($booking->tds_amount ?? 0)
            - (float) ($booking->damage_amount ?? 0)
            - (float) ($booking->shortage_amount ?? 0)
            + (float) ($booking->detention ?? 0)
            + (float) ($booking->deviation ?? 0)
            - (float) ($booking->misc_expense ?? 0);
    }

    /**
     * @return Collection<int, FreightBooking>
     */
    private function resolveCombinedFreightBookings(VoucherLinked $linkedVoucher): Collection
    {
        $primaryBooking = FreightBooking::query()->find($linkedVoucher->freight_booking_id);

        if ($primaryBooking === null) {
            return collect();
        }

        if ($primaryBooking->linked_lr_id) {
            return FreightBooking::query()
                ->where('company_id', $linkedVoucher->company_id)
                ->where('linked_lr_id', $primaryBooking->linked_lr_id)
                ->orderBy('id')
                ->get();
        }

        return collect([$primaryBooking]);
    }

    /**
     * @return array{
     *     issues: list<array{code: string, voucher_linked_id: int|null, voucher_payment_id: int|null, message: string}>,
     *     summary: array{voucher_linkeds_checked: int, linked_voucher_payments_checked: int, issue_count: int}
     * }
     */
    private function runCheck(?int $companyId, ?int $voucherLinkedId, bool $includeTrashed): array
    {
        $issues = [];

        $linkedQuery = VoucherLinked::query();
        if ($companyId !== null) {
            $linkedQuery->where('company_id', $companyId);
        }
        if ($voucherLinkedId !== null) {
            $linkedQuery->whereKey($voucherLinkedId);
        }
        if ($includeTrashed) {
            $linkedQuery->withTrashed();
        }

        /** @var Collection<int, VoucherLinked> $linkedVouchers */
        $linkedVouchers = $linkedQuery->get();

        $paymentsByLinkedId = $this->loadPaymentsByLinkedId($companyId, $voucherLinkedId, $includeTrashed);
        $paymentsById = $this->loadPaymentsById($companyId, $includeTrashed);

        foreach ($linkedVouchers as $linkedVoucher) {
            if ($linkedVoucher->trashed()) {
                continue;
            }

            $issues = array_merge(
                $issues,
                $this->checkLinkedVoucher($linkedVoucher, $paymentsByLinkedId, $paymentsById)
            );
        }

        $issues = array_merge(
            $issues,
            $this->checkOrphanLinkedPayments($paymentsByLinkedId, $linkedVouchers, $includeTrashed)
        );

        return [
            'issues' => $issues,
            'summary' => [
                'voucher_linkeds_checked' => $linkedVouchers->whereNull('deleted_at')->count(),
                'linked_voucher_payments_checked' => $paymentsByLinkedId->flatten(1)->count(),
                'issue_count' => count($issues),
            ],
        ];
    }

    /**
     * @return array{code: string, voucher_linked_id: int|null, voucher_payment_id: int|null, message: string}
     */
    private function issue(
        string $code,
        ?int $voucherLinkedId,
        ?int $voucherPaymentId,
        string $message
    ): array {
        return [
            'code' => $code,
            'voucher_linked_id' => $voucherLinkedId,
            'voucher_payment_id' => $voucherPaymentId,
            'message' => $message,
        ];
    }

    /**
     * @param  Collection<int, Collection<int, VoucherPayment>>  $paymentsByLinkedId
     * @param  Collection<int, VoucherPayment>  $paymentsById
     * @return list<array{code: string, voucher_linked_id: int|null, voucher_payment_id: int|null, message: string}>
     */
    private function checkLinkedVoucher(
        VoucherLinked $linkedVoucher,
        Collection $paymentsByLinkedId,
        Collection $paymentsById
    ): array {
        $issues = [];
        $linkedId = (int) $linkedVoucher->id;
        $voucherIds = $this->parseVoucherIds($linkedVoucher->voucher_ids);
        $linkedPayments = $paymentsByLinkedId->get($linkedId, collect());

        if ($voucherIds !== [] && count($voucherIds) !== count(array_unique($voucherIds))) {
            $issues[] = $this->issue(
                'duplicate_voucher_ids',
                $linkedId,
                null,
                'voucher_ids contains duplicate payment IDs.'
            );
        }

        foreach ($voucherIds as $paymentId) {
            $payment = $paymentsById->get($paymentId);

            if ($payment === null) {
                $issues[] = $this->issue(
                    'missing_payment_record',
                    $linkedId,
                    $paymentId,
                    "voucher_ids references payment #{$paymentId}, but no active voucher_payment row exists."
                );

                continue;
            }

            if ((int) $payment->linked_voucher_id !== $linkedId) {
                $issues[] = $this->issue(
                    'back_link_mismatch',
                    $linkedId,
                    $paymentId,
                    "Payment #{$paymentId} is listed in voucher_ids but linked_voucher_id is ".
                    ($payment->linked_voucher_id ?? 'null').", expected {$linkedId}."
                );
            }

            if ((int) $payment->company_id !== (int) $linkedVoucher->company_id) {
                $issues[] = $this->issue(
                    'company_mismatch',
                    $linkedId,
                    $paymentId,
                    "Payment #{$paymentId} company_id {$payment->company_id} does not match VoucherLinked company_id {$linkedVoucher->company_id}."
                );
            }
        }

        foreach ($linkedPayments as $payment) {
            $paymentId = (int) $payment->id;

            if (! in_array($paymentId, $voucherIds, true)) {
                $issues[] = $this->issue(
                    'missing_from_voucher_ids',
                    $linkedId,
                    $paymentId,
                    "Payment #{$paymentId} has linked_voucher_id {$linkedId} but is missing from voucher_ids."
                );
            }
        }

        if ($voucherIds === [] && $linkedPayments->isNotEmpty()) {
            $issues[] = $this->issue(
                'empty_voucher_ids_with_children',
                $linkedId,
                null,
                'voucher_ids is empty but '.$linkedPayments->count().' voucher_payment row(s) reference this linked voucher.'
            );
        }

        if ($voucherIds !== [] || $linkedPayments->isNotEmpty()) {
            $paymentSum = $linkedPayments->sum(fn (VoucherPayment $payment): float => (float) $payment->amount);
            $linkedAmount = (float) $linkedVoucher->amount;

            if (abs($paymentSum - $linkedAmount) > self::AMOUNT_TOLERANCE) {
                $issues[] = $this->issue(
                    'amount_sum_mismatch',
                    $linkedId,
                    null,
                    'Sum of linked voucher_payments ('.number_format($paymentSum, 2).') does not match VoucherLinked amount ('.number_format($linkedAmount, 2).').'
                );
            }
        }

        return $issues;
    }

    /**
     * @param  Collection<int, Collection<int, VoucherPayment>>  $paymentsByLinkedId
     * @param  Collection<int, VoucherLinked>  $linkedVouchers
     * @return list<array{code: string, voucher_linked_id: int|null, voucher_payment_id: int|null, message: string}>
     */
    private function checkOrphanLinkedPayments(
        Collection $paymentsByLinkedId,
        Collection $linkedVouchers,
        bool $includeTrashed
    ): array {
        $issues = [];
        $activeLinkedIds = $linkedVouchers
            ->filter(fn (VoucherLinked $linked): bool => $linked->deleted_at === null)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        foreach ($paymentsByLinkedId as $linkedId => $payments) {
            if (in_array((int) $linkedId, $activeLinkedIds, true)) {
                continue;
            }

            foreach ($payments as $payment) {
                $linkedVoucher = $includeTrashed
                    ? VoucherLinked::withTrashed()->find($linkedId)
                    : null;

                if ($linkedVoucher === null) {
                    $issues[] = $this->issue(
                        'orphan_linked_voucher_id',
                        (int) $linkedId,
                        (int) $payment->id,
                        "Payment #{$payment->id} references missing VoucherLinked #{$linkedId}."
                    );

                    continue;
                }

                if ($linkedVoucher->trashed()) {
                    $issues[] = $this->issue(
                        'orphan_linked_voucher_id',
                        (int) $linkedId,
                        (int) $payment->id,
                        "Payment #{$payment->id} references soft-deleted VoucherLinked #{$linkedId}."
                    );
                }
            }
        }

        return $issues;
    }

    /**
     * @return Collection<int, Collection<int, VoucherPayment>>
     */
    private function loadPaymentsByLinkedId(?int $companyId, ?int $voucherLinkedId, bool $includeTrashed): Collection
    {
        $query = VoucherPayment::query()->whereNotNull('linked_voucher_id');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }
        if ($voucherLinkedId !== null) {
            $query->where('linked_voucher_id', $voucherLinkedId);
        }
        if (! $includeTrashed) {
            $query->whereNull('deleted_at');
        } else {
            $query->withTrashed();
        }

        return $query->get()->groupBy('linked_voucher_id');
    }

    /**
     * @return Collection<int, VoucherPayment>
     */
    private function loadPaymentsById(?int $companyId, bool $includeTrashed): Collection
    {
        $query = VoucherPayment::query();

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }
        if (! $includeTrashed) {
            $query->whereNull('deleted_at');
        } else {
            $query->withTrashed();
        }

        return $query->get()->keyBy('id');
    }

    /**
     * @return list<int>
     */
    private function parseVoucherIds(mixed $voucherIds): array
    {
        if (is_array($voucherIds)) {
            return array_values(array_unique(array_filter(array_map('intval', $voucherIds))));
        }

        if (! is_string($voucherIds) || trim($voucherIds) === '') {
            return [];
        }

        $decoded = json_decode($voucherIds, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $decoded))));
    }
}
