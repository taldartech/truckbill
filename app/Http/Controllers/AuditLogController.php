<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\VoucherPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Companies this super admin owns (company list / enter uses the same user_id).
     *
     * @return array{0: ?int, 1: \Illuminate\Database\Eloquent\Collection<int, Company>}
     */
    private function resolveSuperAdminAuditScope(Request $request, array $validated): array
    {
        $ownedCompanies = Company::query()
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        if ($ownedCompanies->isEmpty()) {
            session()->forget('super_admin_company_id');

            return [null, $ownedCompanies];
        }

        $ownedIds = $ownedCompanies->pluck('id')->all();

        if ($ownedCompanies->count() === 1) {
            return [(int) $ownedIds[0], $ownedCompanies];
        }

        if (! empty($validated['company_id'])) {
            return [(int) $validated['company_id'], $ownedCompanies];
        }

        if ($request->has('company_id') && ! $request->filled('company_id')) {
            return [null, $ownedCompanies];
        }

        if (session()->has('super_admin_company_id')) {
            $sid = (int) session('super_admin_company_id');
            if (in_array($sid, $ownedIds, true)) {
                return [$sid, $ownedCompanies];
            }
            session()->forget('super_admin_company_id');
        }

        return [null, $ownedCompanies];
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'company_id' => [
                'nullable',
                'integer',
                Rule::exists('companies', 'id')->where(fn ($query) => $query->where('user_id', Auth::id())),
            ],
            'subject_type' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'lr_number' => 'nullable|string|max:100',
            'q' => 'nullable|string|max:255',
        ]);

        [$companyId, $ownedCompanies] = $this->resolveSuperAdminAuditScope($request, $validated);

        $scopeCompanyIds = $companyId !== null
            ? [(int) $companyId]
            : $ownedCompanies->pluck('id')->all();

        $query = AuditLog::query()
            ->with(['user', 'company', 'fieldChanges']);

        if ($scopeCompanyIds === []) {
            $query->whereRaw('0 = 1');
        } elseif (count($scopeCompanyIds) === 1) {
            $query->where('company_id', $scopeCompanyIds[0]);
        } else {
            $query->whereIn('company_id', $scopeCompanyIds);
        }

        if (! empty($validated['subject_type'])) {
            $query->where('subject_type', $validated['subject_type']);
        }

        if (! empty($validated['user_id']) && $scopeCompanyIds !== []) {
            $sameCompany = User::query()
                ->whereKey($validated['user_id'])
                ->whereIn('company_id', $scopeCompanyIds);

            if ($sameCompany->exists()) {
                $query->where('user_id', $validated['user_id']);
            }
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('occurred_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('occurred_at', '<=', $validated['date_to']);
        }

        if (! empty($validated['lr_number']) && $scopeCompanyIds !== []) {
            $lrNumber = trim((string) $validated['lr_number']);
            $lrIds = LorryReceipt::withTrashed()
                ->whereIn('company_id', $scopeCompanyIds)
                ->where('lr_number', $lrNumber)
                ->pluck('id');
            $freightBookingIds = collect();
            $voucherPaymentIds = collect();
            $invoiceIds = collect();
            $invoicePaymentIds = collect();

            if ($lrIds->isNotEmpty()) {
                $freightBookingIds = FreightBooking::query()
                    ->whereIn('company_id', $scopeCompanyIds)
                    ->whereIn('lr_id', $lrIds)
                    ->pluck('id');

                $invoiceIdsQuery = Invoice::withTrashed()
                    ->whereIn('company_id', $scopeCompanyIds)
                    ->where(function ($invoiceQuery) use ($lrIds): void {
                        foreach ($lrIds as $lrId) {
                            $invoiceQuery->orWhereJsonContains('lr_ids', (int) $lrId)
                                ->orWhereJsonContains('lr_ids', (string) $lrId);
                        }
                    });
                $invoiceIds = $invoiceIdsQuery->pluck('id');

                if ($invoiceIds->isNotEmpty()) {
                    $invoicePaymentIds = InvoicePayment::query()
                        ->whereIn('invoice_id', $invoiceIds)
                        ->pluck('id');
                }
            }

            $freightBookingLabel = 'FB for LR '.$lrNumber;
            $freightBookingIdsFromAuditLogs = AuditLog::query()
                ->whereIn('company_id', $scopeCompanyIds)
                ->where('subject_type', FreightBooking::class)
                ->where('subject_label', $freightBookingLabel)
                ->pluck('subject_id');

            $freightBookingIds = $freightBookingIds
                ->merge($freightBookingIdsFromAuditLogs)
                ->unique()
                ->values();

            if ($freightBookingIds->isNotEmpty()) {
                $voucherPaymentIds = VoucherPayment::withTrashed()
                    ->whereIn('company_id', $scopeCompanyIds)
                    ->whereIn('freight_booking_id', $freightBookingIds)
                    ->pluck('id');
            }

            $hasRelatedIds = $lrIds->isNotEmpty()
                || $freightBookingIds->isNotEmpty()
                || $voucherPaymentIds->isNotEmpty()
                || $invoiceIds->isNotEmpty()
                || $invoicePaymentIds->isNotEmpty();

            if (! $hasRelatedIds) {
                $query->whereRaw('0 = 1');
            } else {
                $query->where(function ($q) use ($lrIds, $freightBookingIds, $voucherPaymentIds, $invoiceIds, $invoicePaymentIds): void {
                if ($lrIds->isNotEmpty()) {
                    $q->where(function ($q2) use ($lrIds): void {
                        $q2->where('subject_type', LorryReceipt::class)
                            ->whereIn('subject_id', $lrIds);
                    });
                }

                if ($freightBookingIds->isNotEmpty()) {
                    $q->orWhere(function ($q2) use ($freightBookingIds): void {
                        $q2->where('subject_type', FreightBooking::class)
                            ->whereIn('subject_id', $freightBookingIds);
                    });
                }

                if ($voucherPaymentIds->isNotEmpty()) {
                    $q->orWhere(function ($q2) use ($voucherPaymentIds): void {
                        $q2->where('subject_type', VoucherPayment::class)
                            ->whereIn('subject_id', $voucherPaymentIds);
                    });
                }

                if ($invoiceIds->isNotEmpty()) {
                    $q->orWhere(function ($q2) use ($invoiceIds): void {
                        $q2->where('subject_type', Invoice::class)
                            ->whereIn('subject_id', $invoiceIds);
                    });
                }

                if ($invoicePaymentIds->isNotEmpty()) {
                    $q->orWhere(function ($q2) use ($invoicePaymentIds): void {
                        $q2->where('subject_type', InvoicePayment::class)
                            ->whereIn('subject_id', $invoicePaymentIds);
                    });
                }
                });
            }
        }

        if (! empty($validated['q'])) {
            $term = '%'.$validated['q'].'%';
            $query->where(function ($q) use ($term): void {
                $q->where('summary', 'like', $term)
                    ->orWhere('subject_label', 'like', $term)
                    ->orWhere('subject_type_name', 'like', $term);
            });
        }

        $hasActiveFilters = collect($validated)
            ->except(['company_id'])
            ->filter(fn ($value) => ! empty($value))
            ->isNotEmpty();

        if ($hasActiveFilters) {
            $statsQuery = clone $query;
            $totalEvents = (clone $statsQuery)->count();
            $last7Days = (clone $statsQuery)->where('occurred_at', '>=', Carbon::now()->subDays(7))->count();
            $prev7Days = (clone $statsQuery)
                ->whereBetween('occurred_at', [Carbon::now()->subDays(14), Carbon::now()->subDays(7)])
                ->count();
            $trendPercent = $prev7Days > 0 ? (int) round((($last7Days - $prev7Days) / $prev7Days) * 100) : ($last7Days > 0 ? 100 : 0);
            $actionStats = (clone $statsQuery)
                ->selectRaw('action, COUNT(*) as total')
                ->groupBy('action')
                ->pluck('total', 'action');

            $logs = $query
                ->orderByDesc('occurred_at')
                ->paginate(25)
                ->withQueryString();
        } else {
            $totalEvents = 0;
            $last7Days = 0;
            $prev7Days = 0;
            $trendPercent = 0;
            $actionStats = collect();
            $logs = new LengthAwarePaginator([], 0, 25, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }

        $users = $scopeCompanyIds !== []
            ? User::query()
                ->whereIn('company_id', $scopeCompanyIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
            : collect();

        $subjectTypeOptions = [
            Invoice::class => 'Invoice',
            InvoicePayment::class => 'Invoice payment',
            LorryReceipt::class => 'Good Receipts (Bilty)',
            FreightBooking::class => 'Good Hires (Lorry Hire)',
            VoucherPayment::class => 'Voucher payment',
        ];

        $auditScopeAllCompanies = $companyId === null && $ownedCompanies->count() > 1;

        return view('audit_logs.index', [
            'logs' => $logs,
            'auditScopeCompanyId' => $companyId,
            'auditScopeAllCompanies' => $auditScopeAllCompanies,
            'ownedCompanies' => $ownedCompanies,
            'showCompanyPicker' => $ownedCompanies->count() > 1,
            'currentCompany' => $companyId !== null ? Company::query()->find($companyId) : null,
            'users' => $users,
            'subjectTypeOptions' => $subjectTypeOptions,
            'filters' => $validated,
            'hasActiveFilters' => $hasActiveFilters,
            'insights' => [
                'total_events' => $totalEvents,
                'last_7_days' => $last7Days,
                'prev_7_days' => $prev7Days,
                'trend_percent' => $trendPercent,
                'action_stats' => $actionStats,
            ],
        ]);
    }
}
