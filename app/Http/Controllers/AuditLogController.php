<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\VoucherPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'subject_type' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'lr_number' => 'nullable|string|max:100',
            'q' => 'nullable|string|max:255',
        ]);

        $companyId = session('super_admin_company_id');

        $query = AuditLog::query()
            ->with(['user', 'fieldChanges']);

        if (! empty($companyId)) {
            $query->where('company_id', $companyId);
        }

        if (! empty($validated['subject_type'])) {
            $query->where('subject_type', $validated['subject_type']);
        }

        if (! empty($validated['user_id'])) {
            $sameCompany = User::query()->whereKey($validated['user_id']);
            if (! empty($companyId)) {
                $sameCompany->where('company_id', $companyId);
            }

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

        if (! empty($validated['lr_number'])) {
            $lrNumber = trim((string) $validated['lr_number']);
            $lrIdsQuery = LorryReceipt::query()
                ->where('lr_number', 'like', '%'.$lrNumber.'%');
            if (! empty($companyId)) {
                $lrIdsQuery->where('company_id', $companyId);
            }
            $lrIds = $lrIdsQuery->pluck('id');
            $freightBookingIds = collect();
            $voucherPaymentIds = collect();
            $invoiceIds = collect();
            $invoicePaymentIds = collect();

            if ($lrIds->isNotEmpty()) {
                $freightBookingIds = FreightBooking::query()
                    ->whereIn('lr_id', $lrIds)
                    ->when(! empty($companyId), function ($q) use ($companyId): void {
                        $q->where('company_id', $companyId);
                    })
                    ->pluck('id');

                if ($freightBookingIds->isNotEmpty()) {
                    $voucherPaymentIds = VoucherPayment::query()
                        ->whereIn('freight_booking_id', $freightBookingIds)
                        ->when(! empty($companyId), function ($q) use ($companyId): void {
                            $q->where('company_id', $companyId);
                        })
                        ->pluck('id');
                }

                $invoiceIdsQuery = Invoice::query();
                if (! empty($companyId)) {
                    $invoiceIdsQuery->where('company_id', $companyId);
                }
                $invoiceIdsQuery->where(function ($invoiceQuery) use ($lrIds): void {
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

            $query->where(function ($q) use ($lrIds, $freightBookingIds, $voucherPaymentIds, $invoiceIds, $invoicePaymentIds, $lrNumber): void {
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

                $q->orWhere(function ($q2) use ($lrNumber): void {
                    $q2->where('subject_type', FreightBooking::class)
                        ->where('subject_label', 'like', '%'.$lrNumber.'%');
                });

                $q->orWhere('summary', 'like', '%'.$lrNumber.'%');
                $q->orWhere('subject_label', 'like', '%'.$lrNumber.'%');
            });
        }

        if (! empty($validated['q'])) {
            $term = '%'.$validated['q'].'%';
            $query->where(function ($q) use ($term): void {
                $q->where('summary', 'like', $term)
                    ->orWhere('subject_label', 'like', $term)
                    ->orWhere('subject_type_name', 'like', $term);
            });
        }

        $hasActiveFilters = collect($validated)->filter(fn ($value) => ! empty($value))->isNotEmpty();

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

        $users = User::query()
            ->when(! empty($companyId), function ($q) use ($companyId): void {
                $q->where('company_id', $companyId);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $subjectTypeOptions = [
            Invoice::class => 'Invoice',
            InvoicePayment::class => 'Invoice payment',
            LorryReceipt::class => 'Lorry receipt',
            FreightBooking::class => 'Freight booking',
            VoucherPayment::class => 'Voucher payment',
        ];

        return view('audit_logs.index', [
            'logs' => $logs,
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
