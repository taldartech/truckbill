<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\VoucherPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MasterPanelSummaryService
{
    /**
     * @return array{
     *     from_date: string,
     *     to_date: string,
     *     company_id: int|null,
     *     super_admin_user_id: int|null,
     *     range_start: Carbon,
     *     range_end: Carbon
     * }
     */
    public function resolveFilters(array $input): array
    {
        $now = Carbon::now();
        $fromDate = $input['from_date'] ?? $now->copy()->startOfMonth()->toDateString();
        $toDate = $input['to_date'] ?? $now->copy()->endOfMonth()->toDateString();

        $rangeStart = Carbon::parse($fromDate)->startOfDay();
        $rangeEnd = Carbon::parse($toDate)->endOfDay();

        if ($rangeStart->gt($rangeEnd)) {
            [$rangeStart, $rangeEnd] = [$rangeEnd->copy()->startOfDay(), $rangeStart->copy()->endOfDay()];
            $fromDate = $rangeStart->toDateString();
            $toDate = $rangeEnd->toDateString();
        }

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'company_id' => isset($input['company_id']) ? (int) $input['company_id'] : null,
            'super_admin_user_id' => isset($input['super_admin_user_id']) ? (int) $input['super_admin_user_id'] : null,
            'range_start' => $rangeStart,
            'range_end' => $rangeEnd,
        ];
    }

    /**
     * @param  array{company_id: int|null, super_admin_user_id: int|null}  $filters
     * @return Collection<int, int>
     */
    public function scopedCompanyIds(User $viewer, array $filters): Collection
    {
        $query = Company::query()->select('id');

        if ($viewer->userRole?->name === 'super_admin') {
            $query->where('user_id', $viewer->id);
        }

        if ($filters['super_admin_user_id']) {
            $query->where('user_id', $filters['super_admin_user_id']);
        }

        if ($filters['company_id']) {
            $query->whereKey($filters['company_id']);
        }

        return $query->pluck('id');
    }

    /**
     * @param  Collection<int, int>  $companyIds
     * @return array<string, float|int>
     */
    public function buildKpis(Collection $companyIds, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        if ($companyIds->isEmpty()) {
            return $this->emptyKpis();
        }

        $ids = $companyIds->all();

        $revenue = (float) (Invoice::query()
            ->whereIn('company_id', $ids)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->sum('total_amount') ?? 0);

        $lrBase = LorryReceipt::query()
            ->whereIn('company_id', $ids)
            ->whereBetween('gr_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()]);

        $totalLrs = (clone $lrBase)->count();
        $pendingLrs = (clone $lrBase)->where('delivery_status', 'Pending')->count();
        $inProgressLrs = (clone $lrBase)->where('delivery_status', 'In-Progress')->count();
        $deliveredLrs = (clone $lrBase)->where('delivery_status', 'Delivered')->count();
        $lrsPendingInvoice = (clone $lrBase)->where('invoice_generated', 0)->count();

        $freightBookings = FreightBooking::query()
            ->whereIn('company_id', $ids)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->count();

        $activeTrips = FreightBooking::query()
            ->whereIn('company_id', $ids)
            ->where('is_delivered', false)
            ->where('is_cancelled', false)
            ->count();

        $invoicesInRange = Invoice::query()
            ->whereIn('company_id', $ids)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->with('payments')
            ->get();

        $totalInvoiced = $invoicesInRange->sum(fn (Invoice $invoice) => (float) ($invoice->net_amount ?? $invoice->total_amount));
        $invoicePaidInRange = $invoicesInRange->sum(fn (Invoice $invoice) => (float) $invoice->paid_amount);

        $allInvoices = Invoice::query()
            ->whereIn('company_id', $ids)
            ->with('payments')
            ->get();

        $outstandingAmount = 0.0;
        foreach ($allInvoices as $invoice) {
            $amountToCollect = (float) ($invoice->net_amount ?? $invoice->total_amount);
            $paid = (float) $invoice->paid_amount;
            if ($paid < $amountToCollect) {
                $outstandingAmount += $amountToCollect - $paid;
            }
        }

        $voucherTotal = (float) VoucherPayment::query()
            ->whereIn('company_id', $ids)
            ->whereBetween('date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->sum('amount');

        $voucherPaid = (float) VoucherPayment::query()
            ->whereIn('company_id', $ids)
            ->whereBetween('date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->where('payment_status', 'paid')
            ->sum('amount');

        return [
            'revenue' => $revenue,
            'total_invoiced' => $totalInvoiced,
            'invoice_paid_in_range' => $invoicePaidInRange,
            'outstanding_amount' => $outstandingAmount,
            'voucher_total' => $voucherTotal,
            'voucher_paid' => $voucherPaid,
            'total_lrs' => $totalLrs,
            'pending_lrs' => $pendingLrs,
            'in_progress_lrs' => $inProgressLrs,
            'delivered_lrs' => $deliveredLrs,
            'lrs_pending_invoice' => $lrsPendingInvoice,
            'freight_bookings' => $freightBookings,
            'active_trips' => $activeTrips,
            'total_companies' => $companyIds->count(),
        ];
    }

    /**
     * @param  Collection<int, int>  $companyIds
     * @return Collection<int, array<string, mixed>>
     */
    public function buildCompanyBreakdown(Collection $companyIds, Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        if ($companyIds->isEmpty()) {
            return collect();
        }

        $companies = Company::query()
            ->with('user:id,name,email')
            ->whereIn('id', $companyIds->all())
            ->orderBy('name')
            ->get();

        return $companies->map(function (Company $company) use ($rangeStart, $rangeEnd) {
            $kpis = $this->buildKpis(collect([$company->id]), $rangeStart, $rangeEnd);

            return [
                'id' => $company->id,
                'name' => $company->name,
                'owner_name' => $company->user?->name,
                'is_blocked' => (bool) $company->is_blocked,
                'plan_end_date' => $company->subscription?->ends_on?->toDateString(),
                ...$kpis,
            ];
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function buildSuperAdminBreakdown(
        Collection $scopedCompanyIds,
        Carbon $rangeStart,
        Carbon $rangeEnd,
        ?int $onlySuperAdminId = null
    ): Collection {
        $superAdminQuery = User::query()
            ->whereHas('userRole', fn ($q) => $q->where('name', 'super_admin'));

        if ($onlySuperAdminId) {
            $superAdminQuery->whereKey($onlySuperAdminId);
        } elseif ($scopedCompanyIds->isNotEmpty()) {
            $superAdminQuery->whereHas('ownedCompanies', fn ($q) => $q->whereIn('id', $scopedCompanyIds->all()));
        }

        $superAdmins = $superAdminQuery->orderBy('name')->get();

        return $superAdmins->map(function (User $superAdmin) use ($scopedCompanyIds, $rangeStart, $rangeEnd) {
            $ownedIds = Company::query()
                ->where('user_id', $superAdmin->id)
                ->when($scopedCompanyIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $scopedCompanyIds->all()))
                ->pluck('id');

            $kpis = $this->buildKpis($ownedIds, $rangeStart, $rangeEnd);

            return [
                'id' => $superAdmin->id,
                'name' => $superAdmin->name,
                'email' => $superAdmin->email,
                'company_count' => $ownedIds->count(),
                ...$kpis,
            ];
        });
    }

    /**
     * @return array<string, float|int>
     */
    private function emptyKpis(): array
    {
        return [
            'revenue' => 0,
            'total_invoiced' => 0,
            'invoice_paid_in_range' => 0,
            'outstanding_amount' => 0,
            'voucher_total' => 0,
            'voucher_paid' => 0,
            'total_lrs' => 0,
            'pending_lrs' => 0,
            'in_progress_lrs' => 0,
            'delivered_lrs' => 0,
            'lrs_pending_invoice' => 0,
            'freight_bookings' => 0,
            'active_trips' => 0,
            'total_companies' => 0,
        ];
    }
}
