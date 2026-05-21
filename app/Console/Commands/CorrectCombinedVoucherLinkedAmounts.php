<?php

namespace App\Console\Commands;

use App\Models\FreightBooking;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CorrectCombinedVoucherLinkedAmounts extends Command
{
    protected $signature = 'freight-bookings:correct-combined-voucher-amounts
                            {--apply : Apply changes to the database (default is dry run)}
                            {--id= : Process only this voucher_linkeds id}
                            {--company-id= : Process only records for this company id}
                            {--reset-negative-adjusted : Zero negative_adjusted_amount on affected freight bookings before recalculating balance}';

    protected $description = 'Correct combined voucher payment splits and freight booking advance/balance using voucher_linkeds.amount as source of truth';

    public function handle(): int
    {
        $dryRun = ! $this->option('apply');
        $resetNegativeAdjusted = (bool) $this->option('reset-negative-adjusted');

        $this->info('========================================');
        $this->info('Correct Combined Voucher Linked Amounts');
        $this->info('========================================');
        $this->info('Mode: '.($dryRun ? 'DRY RUN (no changes will be made)' : 'LIVE (changes will be saved)'));
        $this->newLine();

        if (! $dryRun && $this->input->isInteractive()) {
            if (! $this->confirm('This will update voucher_payments and freight_bookings. Continue?')) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $query = VoucherLinked::query()->orderBy('id');

        if ($this->option('id')) {
            $query->where('id', (int) $this->option('id'));
        }

        if ($this->option('company-id')) {
            $query->where('company_id', (int) $this->option('company-id'));
        }

        $linkedVouchers = $query->get();

        if ($linkedVouchers->isEmpty()) {
            $this->warn('No voucher_linkeds records found for the given filters.');

            return self::SUCCESS;
        }

        $processed = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($linkedVouchers as $linkedVoucher) {
            try {
                $result = $this->processLinkedVoucher($linkedVoucher, $dryRun, $resetNegativeAdjusted);

                if ($result === null) {
                    $skipped++;

                    continue;
                }

                $processed++;
                $this->line(sprintf(
                    'VoucherLinked #%d: payments %s -> %s | bookings updated: %s',
                    $linkedVoucher->id,
                    number_format($result['old_payment_total'], 2),
                    number_format($result['new_payment_total'], 2),
                    implode(', ', $result['booking_ids'])
                ));

                foreach ($result['allocations'] as $allocation) {
                    $this->line(sprintf(
                        '  FR #%d (LR %s): voucher #%s amount %s -> %s',
                        $allocation['freight_booking_id'],
                        $allocation['lr_number'],
                        $allocation['voucher_payment_id'] ?? 'new',
                        number_format($allocation['old_amount'], 2),
                        number_format($allocation['new_amount'], 2)
                    ));
                }
            } catch (Throwable $exception) {
                $errors++;
                Log::error('Failed correcting combined voucher linked amounts', [
                    'voucher_linked_id' => $linkedVoucher->id,
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);
                $this->error("VoucherLinked #{$linkedVoucher->id}: {$exception->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('Summary');
        $this->info('========================================');
        $this->info("Processed: {$processed}");
        $this->info("Skipped (already aligned): {$skipped}");
        $this->info("Errors: {$errors}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('DRY RUN complete. Re-run with --apply to persist changes.');
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{
     *     old_payment_total: float,
     *     new_payment_total: float,
     *     booking_ids: list<int>,
     *     allocations: list<array{
     *         freight_booking_id: int,
     *         lr_number: string,
     *         voucher_payment_id: int|null,
     *         old_amount: float,
     *         new_amount: float
     *     }>
     * }|null
     */
    private function processLinkedVoucher(VoucherLinked $linkedVoucher, bool $dryRun, bool $resetNegativeAdjusted): ?array
    {
        $payments = VoucherPayment::query()
            ->where('linked_voucher_id', $linkedVoucher->id)
            ->whereNull('deleted_at')
            ->get();

        $groupBookings = $this->resolveGroupBookings($linkedVoucher, $payments);

        if ($groupBookings->isEmpty()) {
            throw new \RuntimeException('Could not resolve freight bookings for this combined voucher.');
        }

        $targetAmount = round((float) $linkedVoucher->amount, 2);
        $currentPaymentTotal = round((float) $payments->sum('amount'), 2);
        $allocationsByBookingId = $this->allocateAmount($targetAmount, $groupBookings);

        if ($this->isAlreadyAligned($payments, $groupBookings, $allocationsByBookingId, $targetAmount, $currentPaymentTotal)) {
            return null;
        }
        $allocationRows = [];
        $paymentIds = [];

        $templatePayment = $payments->first();

        $applyChanges = function () use (
            $linkedVoucher,
            $payments,
            $groupBookings,
            $allocationsByBookingId,
            &$allocationRows,
            &$paymentIds,
            $templatePayment,
            $resetNegativeAdjusted,
        ): void {
            DB::transaction(function () use (
                $linkedVoucher,
                $payments,
                $groupBookings,
                $allocationsByBookingId,
                &$allocationRows,
                &$paymentIds,
                $templatePayment,
                $resetNegativeAdjusted,
            ): void {
                foreach ($groupBookings as $booking) {
                    $newAmount = round((float) ($allocationsByBookingId[$booking->id] ?? 0), 2);
                    $payment = $payments->firstWhere('freight_booking_id', $booking->id);

                    $oldAmount = $payment ? (float) $payment->amount : 0.0;

                    if ($payment) {
                        $payment->amount = $newAmount;
                        $payment->save();
                        $paymentIds[] = $payment->id;
                    } elseif ($newAmount > 0.01 && $templatePayment) {
                        $payment = VoucherPayment::create([
                            'freight_booking_id' => $booking->id,
                            'voucher_number' => $booking->voucher_number,
                            'payment_type' => $templatePayment->payment_type,
                            'payment_mode' => $templatePayment->payment_mode,
                            'payment_type_id' => $templatePayment->payment_type_id,
                            'payment_mode_id' => $templatePayment->payment_mode_id,
                            'pump_id' => $templatePayment->pump_id,
                            'amount' => $newAmount,
                            'remark' => $linkedVoucher->remark,
                            'payment_status' => $templatePayment->payment_status,
                            'company_id' => $linkedVoucher->company_id,
                            'date' => $linkedVoucher->date,
                            'linked_voucher_id' => $linkedVoucher->id,
                        ]);
                        $paymentIds[] = $payment->id;
                    }

                    $allocationRows[] = [
                        'freight_booking_id' => $booking->id,
                        'lr_number' => (string) ($booking->lr?->lr_number ?? 'N/A'),
                        'voucher_payment_id' => $payment?->id,
                        'old_amount' => $oldAmount,
                        'new_amount' => $newAmount,
                    ];
                }

                $linkedVoucher->voucher_ids = json_encode(array_values(array_unique($paymentIds)));
                $linkedVoucher->save();

                foreach ($groupBookings as $booking) {
                    $this->recalculateFreightBooking($booking, $resetNegativeAdjusted);
                }
            });
        };

        if (! $dryRun) {
            $applyChanges();
        } else {
            foreach ($groupBookings as $booking) {
                $newAmount = round((float) ($allocationsByBookingId[$booking->id] ?? 0), 2);
                $payment = $payments->firstWhere('freight_booking_id', $booking->id);

                $allocationRows[] = [
                    'freight_booking_id' => $booking->id,
                    'lr_number' => (string) ($booking->lr?->lr_number ?? 'N/A'),
                    'voucher_payment_id' => $payment?->id,
                    'old_amount' => $payment ? (float) $payment->amount : 0.0,
                    'new_amount' => $newAmount,
                ];
            }
        }

        return [
            'old_payment_total' => $currentPaymentTotal,
            'new_payment_total' => $targetAmount,
            'booking_ids' => $groupBookings->pluck('id')->map(static fn ($id) => (int) $id)->all(),
            'allocations' => $allocationRows,
        ];
    }

    /**
     * @return Collection<int, FreightBooking>
     */
    private function resolveGroupBookings(VoucherLinked $linkedVoucher, Collection $payments): Collection
    {
        $seedBooking = FreightBooking::query()
            ->with('lr')
            ->find($linkedVoucher->freight_booking_id);

        if (! $seedBooking && $payments->isNotEmpty()) {
            $seedBooking = FreightBooking::query()
                ->with('lr')
                ->find($payments->first()->freight_booking_id);
        }

        if ($seedBooking && ! empty($seedBooking->linked_lr_id)) {
            return FreightBooking::query()
                ->with('lr')
                ->where('linked_lr_id', $seedBooking->linked_lr_id)
                ->where('company_id', $linkedVoucher->company_id)
                ->where(function ($query): void {
                    $query->whereNull('is_cancelled')->orWhere('is_cancelled', '!=', 1);
                })
                ->orderBy('id')
                ->get();
        }

        $bookingIds = $payments->pluck('freight_booking_id')->filter()->unique()->values();

        if ($bookingIds->isEmpty() && $seedBooking) {
            $bookingIds = collect([$seedBooking->id]);
        }

        return FreightBooking::query()
            ->with('lr')
            ->whereIn('id', $bookingIds)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, FreightBooking>  $bookings
     * @return array<int, float>
     */
    private function allocateAmount(float $totalAmount, Collection $bookings): array
    {
        $weights = [];

        foreach ($bookings as $booking) {
            $weights[$booking->id] = max(0.0, $this->effectivePayableAmount($booking));
        }

        $weightSum = array_sum($weights);

        if ($weightSum <= 0.01) {
            foreach ($bookings as $booking) {
                $weights[$booking->id] = max(0.0, (float) $booking->tr_amount);
            }
            $weightSum = array_sum($weights);
        }

        if ($weightSum <= 0.01) {
            $equalShare = round($totalAmount / max($bookings->count(), 1), 2);
            $allocations = [];

            foreach ($bookings as $index => $booking) {
                if ($index === $bookings->count() - 1) {
                    $allocations[$booking->id] = round($totalAmount - array_sum($allocations), 2);
                } else {
                    $allocations[$booking->id] = $equalShare;
                }
            }

            return $allocations;
        }

        $allocations = [];
        $allocated = 0.0;
        $bookingIds = array_keys($weights);
        $lastIndex = count($bookingIds) - 1;

        foreach ($bookingIds as $index => $bookingId) {
            if ($index === $lastIndex) {
                $allocations[$bookingId] = round($totalAmount - $allocated, 2);

                continue;
            }

            $share = round(($weights[$bookingId] / $weightSum) * $totalAmount, 2);
            $allocations[$bookingId] = $share;
            $allocated += $share;
        }

        return $allocations;
    }

    private function recalculateFreightBooking(FreightBooking $booking, bool $resetNegativeAdjusted): void
    {
        $booking->refresh();

        if ($resetNegativeAdjusted) {
            $booking->negative_adjusted_amount = 0;
        }

        $advanceAmount = (float) VoucherPayment::query()
            ->where('freight_booking_id', $booking->id)
            ->whereNull('deleted_at')
            ->sum('amount');

        $effectivePayableAmount = $this->effectivePayableAmount($booking);

        $booking->advance_amount = round($advanceAmount, 2);
        $booking->balance_amount = round(
            $effectivePayableAmount
            - $booking->advance_amount
            + (float) ($booking->negative_adjusted_amount ?? 0),
            2
        );
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
     * @param  Collection<int, VoucherPayment>  $payments
     * @param  Collection<int, FreightBooking>  $bookings
     * @param  array<int, float>  $allocationsByBookingId
     */
    private function isAlreadyAligned(
        Collection $payments,
        Collection $bookings,
        array $allocationsByBookingId,
        float $targetAmount,
        float $currentPaymentTotal,
    ): bool {
        if (abs($currentPaymentTotal - $targetAmount) > 0.01) {
            return false;
        }

        foreach ($bookings as $booking) {
            $expectedAmount = round((float) ($allocationsByBookingId[$booking->id] ?? 0), 2);
            $payment = $payments->firstWhere('freight_booking_id', $booking->id);

            if ($expectedAmount > 0.01 && ! $payment) {
                return false;
            }

            if ($payment && abs((float) $payment->amount - $expectedAmount) > 0.01) {
                return false;
            }
        }

        return true;
    }
}
