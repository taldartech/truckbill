<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\GoodType;
use App\Models\LorryReceipt;
use App\Models\PaymentMode;
use App\Models\PaymentType;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use App\Services\FreightBookingAmountVerifier;
use App\Services\FreightBookingCalculationService;
use App\Services\FreightBookingCustomAmountService;
use App\Services\Notifications\FreightBookingNotificationService;
use App\Services\Notifications\LorryReceiptNotificationService;
use App\Services\SubscriptionGuard;
use App\Services\TransporterAdvanceLedgerService;
use App\Support\ListingQueryString;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;
use App\Models\EwayBill;

class FreightBookingController extends Controller
{
    public function __construct(
        private readonly LorryReceiptNotificationService $lrNotificationService,
        private readonly FreightBookingNotificationService $frNotificationService,
        private readonly FreightBookingCalculationService $freightBookingCalculationService,
        private readonly FreightBookingCustomAmountService $freightBookingCustomAmountService,
        private readonly SubscriptionGuard $subscriptionGuard,
    ) {}

    private function redirectToFreightBookingsIndex(?Request $request = null, ?string $flashKey = null, ?string $flashMessage = null): RedirectResponse
    {
        $request ??= request();

        $redirect = redirect()->route(
            'freight_bookings.index',
            ListingQueryString::params($request, ListingQueryString::FREIGHT_BOOKING_KEYS)
        );

        if ($flashKey !== null && $flashMessage !== null) {
            return $redirect->with($flashKey, $flashMessage);
        }

        return $redirect;
    }

    private function getActiveCompanyId(): ?int
    {
        $user = Auth::user();

        if ($user->userRole->name === 'super_admin') {
            $companyId = session('super_admin_company_id');

            return (int) $companyId;
        }

        return $user->company_id ? (int) $user->company_id : null;
    }

    /**
     * When LR and FB point at the same transporter row, reuse the eager-loaded LR relation
     * so we do not run a second `transporters` / `transporter_masters` batch for the same IDs.
     */
    private function freightBookingTransporterForDisplay(FreightBooking $booking): ?Transporter
    {
        $lr = $booking->lr;
        $lrTransporter = $lr?->transporter;
        if ($lrTransporter && (int) ($booking->transporter_id ?? 0) === (int) ($lr->transporter_id ?? 0)) {
            return $lrTransporter;
        }

        return $booking->transporter;
    }

    private function paymentRequestLogContext(Request $request): array
    {
        return [
            'payment_type_id' => $request->input('payment_type_id'),
            'payment_mode_id' => $request->input('payment_mode_id'),
            'payment_type' => $request->input('payment_type'),
            'payment_mode' => $request->input('payment_mode'),
            'pump_id' => $request->input('pump_id'),
            'date' => $request->input('date'),
            'confirm_duplicate' => $request->input('confirm_duplicate'),
            'booking_ids_count' => count($request->input('booking_ids', [])),
            'payment_amounts_count' => count($request->input('payment_amounts', [])),
            'has_reference_number' => $request->filled('reference_number'),
            'has_notes' => $request->filled('notes'),
        ];
    }

    private function freightBookingUpdateLogContext(Request $request): array
    {
        return [
            'lr_id' => $request->input('lr_id'),
            'linked_freight_booking_id' => $request->input('linked_freight_booking_id'),
            'eway_bill_number_present' => $request->filled('eway_bill_number'),
            'is_fixed_amount' => $request->boolean('is_fixed_amount'),
            'is_delivered' => $request->boolean('is_delivered'),
            'is_cancelled' => $request->boolean('is_cancelled'),
        ];
    }

    /**
     * @return array{
     *     is_fixed_amount: bool,
     *     tr_fr_rate: float,
     *     tr_amount: float,
     *     loading_weight: float,
     *     unloading_weight: float,
     *     tds_amount: float,
     *     advance_amount: float,
     *     balance_amount: float,
     *     negative_adjusted_amount: float,
     *     shortage_amount: float,
     *     damage_amount: float,
     *     detention: float,
     *     deviation: float,
     *     misc_expense: float,
     *     custom_amounts_total: float,
     *     voucher_payments_total: float,
     * }
     */
    private function freightBookingAmountsForVerification(
        FreightBooking $freightBooking,
        float $voucherPaymentsTotal,
        float $customAmountsTotal,
        float $loadingWeight,
        float $unloadingWeight,
    ): array {
        return [
            'is_fixed_amount' => (bool) $freightBooking->is_fixed_amount,
            'tr_fr_rate' => (float) $freightBooking->tr_fr_rate,
            'tr_amount' => (float) $freightBooking->tr_amount,
            'loading_weight' => $loadingWeight,
            'unloading_weight' => $unloadingWeight,
            'tds_amount' => (float) ($freightBooking->tds_amount ?? 0),
            'advance_amount' => (float) ($freightBooking->advance_amount ?? 0),
            'balance_amount' => (float) ($freightBooking->balance_amount ?? 0),
            'negative_adjusted_amount' => (float) ($freightBooking->negative_adjusted_amount ?? 0),
            'shortage_amount' => (float) ($freightBooking->shortage_amount ?? 0),
            'damage_amount' => (float) ($freightBooking->damage_amount ?? 0),
            'detention' => (float) ($freightBooking->detention ?? 0),
            'deviation' => (float) ($freightBooking->deviation ?? 0),
            'misc_expense' => (float) ($freightBooking->misc_expense ?? 0),
            'custom_amounts_total' => $customAmountsTotal,
            'voucher_payments_total' => $voucherPaymentsTotal,
        ];
    }

    private function verifyFreightBookingAmountsBeforeSave(
        FreightBooking $freightBooking,
        float $voucherPaymentsTotal,
        float $customAmountsTotal,
        float $loadingWeight,
        float $unloadingWeight,
    ): void {
        FreightBookingAmountVerifier::verify(
            $this->freightBookingAmountsForVerification(
                $freightBooking,
                $voucherPaymentsTotal,
                $customAmountsTotal,
                $loadingWeight,
                $unloadingWeight,
            )
        );
    }

    public function index(Request $request)
    {
        $pumpOwnerUserId = Auth::user()->company?->user_id;
        $pumps = $pumpOwnerUserId
            ? Pump::query()
                ->where('user_id', $pumpOwnerUserId)
                ->select(['id', 'pump_name'])
                ->orderBy('pump_name')
                ->get()
            : collect();

        $endDate = $request->input('end_date', date('Y-m-d', strtotime('+0 days')));
        $startDate = $request->input('start_date', date('Y-m-d', strtotime('-15 days')));

        if ($request->filled('start_date')) {
            $startDate = $request->start_date;
        }
        if ($request->filled('end_date')) {
            $endDate = $request->end_date;
        }

        $maxEndDate = (new \DateTime($startDate))->modify('+3 months')->format('Y-m-d');
        if ($endDate > $maxEndDate) {
            $endDate = $maxEndDate;
        }

        $paymentTypes = Cache::remember(
            'freight_bookings.index.payment_types',
            now()->addHour(),
            fn () => PaymentType::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
        );
        $paymentModes = Cache::remember(
            'freight_bookings.index.payment_modes',
            now()->addHour(),
            fn () => PaymentMode::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get()
        );

        $companyId = auth()->user()->userRole?->name === 'super_admin'
            ? session('super_admin_company_id')
            : auth()->user()->company_id;

        $selectedCustomer = $request->filled('customer_id')
            ? Consignee::where('company_id', $companyId)->find($request->customer_id)
            : null;

        $selectedTransporter = $request->filled('transporter_id')
            ? Transporter::where('company_id', $companyId)->with('transporterMaster')->find($request->transporter_id)
            : null;

        $selectedTruck = $request->filled('truck_id')
            ? Truck::where(function ($query) use ($companyId) {
                if ($companyId) {
                    $query->whereHas('transporter', function ($transporterQuery) use ($companyId) {
                        $transporterQuery->where('company_id', $companyId);
                    })->orWhere(function ($orphanQuery) use ($companyId) {
                        $orphanQuery->whereNull('transporter_id')
                            ->where('company_id', $companyId);
                    });
                }
            })->find($request->truck_id)
            : null;

        return view('freight_bookings.index', compact(
            'paymentTypes',
            'paymentModes',
            'startDate',
            'endDate',
            'pumps',
            'selectedCustomer',
            'selectedTransporter',
            'selectedTruck'
        ));
    }

    public function getFreightBookingsData(Request $request)
    {
        try {
            $endDate = $request->input('end_date', date('Y-m-d', strtotime('+7 days')));
            $startDate = $request->input('start_date', date('Y-m-d', strtotime('-7 days')));

            if ($request->filled('start_date')) {
                $startDate = $request->start_date;
            }
            if ($request->filled('end_date')) {
                $endDate = $request->end_date;
            }

            $maxEndDate = (new \DateTime($startDate))->modify('+3 months')->format('Y-m-d');
            if ($endDate > $maxEndDate) {
                $endDate = $maxEndDate;
            }

            $query = FreightBooking::query()
                ->with([
                    'lr.truck',
                    'lr.customer',
                    'lr.transporter.transporterMaster',
                ])
                ->select('freight_bookings.*')
                ->join('lorry_receipts as list_lr', function ($join) {
                    $join->on('freight_bookings.lr_id', '=', 'list_lr.id')
                        ->whereNull('list_lr.deleted_at');
                });

            if (auth()->user()->company_id) {
                $query->where('freight_bookings.company_id', auth()->user()->company_id);
            }

            $grStart = Carbon::parse($startDate)->startOfDay();
            $grEnd = Carbon::parse($endDate)->endOfDay();

            $query->whereBetween('list_lr.gr_date', [$grStart, $grEnd]);

            if ($request->filled('lr_number')) {
                $query->where('list_lr.lr_number', 'LIKE', '%'.trim($request->lr_number).'%');
            }

            if ($request->filled('customer_id')) {
                $query->where('list_lr.customer_id', $request->customer_id);
            }

            if ($request->filled('transporter_id')) {
                $query->where(function ($q) use ($request) {
                    $q->where('freight_bookings.transporter_id', $request->transporter_id)
                        ->orWhere('list_lr.transporter_id', $request->transporter_id)
                        ->orWhere('freight_bookings.transporter_name', 'LIKE', "%{$request->transporter_id}%");
                });
            }

            if ($request->filled('truck_id')) {
                $query->where('list_lr.truck_id', $request->truck_id);
            }

            if ($request->filled('truck_owner_id')) {
                $query->whereHas('lr.truck', function ($q) use ($request) {
                    $q->where('transporter_id', $request->truck_owner_id);
                });
            }

            if ($request->filled('pending_memo')) {
                $query->whereNull('list_lr.memo_id')
                    ->where('freight_bookings.is_cancelled', 0);
            }

            $listSearch = trim((string) $request->input('filter_search', $request->input('search', '')));
            if ($listSearch !== '') {
                $search = $listSearch;
                $searchQuery = clone $query;
                $searchQuery->where(function ($q) use ($search) {
                    $q->where('freight_bookings.voucher_number', 'LIKE', "%{$search}%")
                        ->orWhereHas('lr', function ($lrQuery) use ($search) {
                            $lrQuery->where('lr_number', 'LIKE', "%{$search}%")
                                ->orWhere('source_city', 'LIKE', "%{$search}%")
                                ->orWhere('destination_city', 'LIKE', "%{$search}%")
                                ->orWhereHas('customer', function ($customerQuery) use ($search) {
                                    $customerQuery->where('name', 'LIKE', "%{$search}%");
                                })
                                ->orWhereHas('truck', function ($truckQuery) use ($search) {
                                    $truckQuery->where('truck_number', 'LIKE', "%{$search}%");
                                })
                                ->orWhereHas('transporter.transporterMaster', function ($transporterQuery) use ($search) {
                                    $transporterQuery->where('name', 'LIKE', "%{$search}%");
                                })
                                ->orWhereHas('reference.transporterMaster', function ($referenceQuery) use ($search) {
                                    $referenceQuery->where('name', 'LIKE', "%{$search}%");
                                });
                        });
                });

                $searchResultIds = (clone $searchQuery)->pluck('freight_bookings.id')->toArray();

                $linkedIds = (clone $searchQuery)
                    ->whereNotNull('freight_bookings.linked_lr_id')
                    ->pluck('freight_bookings.linked_lr_id')
                    ->unique()
                    ->toArray();

                if (empty($searchResultIds) && empty($linkedIds)) {
                    $query->whereRaw('0 = 1');
                } else {
                    $query->where(function ($q) use ($searchResultIds, $linkedIds) {
                        if (! empty($searchResultIds)) {
                            $q->whereIn('freight_bookings.id', $searchResultIds);
                        }

                        if (! empty($linkedIds)) {
                            $q->orWhereIn('freight_bookings.linked_lr_id', $linkedIds);
                        }
                    });
                }
            }

            $dataTable = DataTables::of($query)
                ->setRowId('id')
                ->setRowClass(function (FreightBooking $row) {
                    if ($row->is_cancelled) {
                        return 'table-danger';
                    }
                    if ($row->is_delivered) {
                        return 'table-success';
                    }

                    return '';
                })
                ->addColumn('checkbox', function (FreightBooking $booking) {
                    $lr = $booking->lr;
                    if ($lr && $lr->memo_id && (float) ($booking->balance_amount ?? 0) <= 0) {
                        return '<span class="text-muted"><i class="bi bi-dash-circle"></i></span>';
                    }
                    if ($booking->is_cancelled) {
                        return '<span class="text-muted"><i class="bi bi-dash-circle"></i></span>';
                    }

                    $lrTransporter = $lr?->transporter;

                    return sprintf(
                        '<input type="checkbox" class="row-checkbox" value="%s" data-lr="%s" data-amount="%s" data-linked-lr-id="%s" data-memo-id="%s" data-lr-id="%s" data-truck="%s" data-truck-id="%s" data-transporter-id="%s" data-balance="%s" data-voucher_number="%s" data-freight-rate="%s" data-freight-booking-id="%s" data-is-cancelled="%s" data-is-delivered="%s" data-transporter="%s" data-lr-transporter="%s" data-lr-transporter-mobile-number="%s" data-lr-transporter-short-name="%s" data-lr-transporter-beneficiary-id="%s" data-fb-transporter="%s" data-tds-percentage="%s" data-customer-id="%s" data-shortage-amount="%s" data-damage-amount="%s" data-advance-amount="%s">',
                        e((string) $booking->id),
                        e($lr?->lr_number ?? ''),
                        e((string) ($lr?->amount ?? '')),
                        e((string) ($lr?->linked_lr_id ?? '')),
                        e((string) ($lr?->memo_id ?? '')),
                        e((string) ($lr?->id ?? '')),
                        e($lr?->truck?->truck_number ?? ''),
                        e((string) ($lr?->truck?->id ?? '')),
                        e((string) ($lr?->transporter?->id ?? '')),
                        e((string) ($booking->balance_amount ?? 0)),
                        e((string) ($booking->voucher_number ?? '')),
                        e((string) ($booking->tr_fr_rate ?? 0)),
                        e((string) $booking->id),
                        $booking->is_cancelled ? '1' : '0',
                        $booking->is_delivered ? '1' : '0',
                        e($lrTransporter?->name ?? ''),
                        e($lrTransporter?->name ?? ''),
                        e($lrTransporter?->mobile_number ?? ''),
                        e($lrTransporter?->short_name ?? ''),
                        e((string) ($lrTransporter?->beneficiary_id ?? '')),
                        e($booking->transporter_name ?? ($this->freightBookingTransporterForDisplay($booking)?->name ?? '')),
                        e((string) ($booking->transporter_tds_percentage ?? '')),
                        e((string) ($lr?->customer?->id ?? '')),
                        e((string) ($booking->shortage_amount ?? 0)),
                        e((string) ($booking->damage_amount ?? 0)),
                        e((string) ($lrTransporter?->advance_amount ?? 0))
                    );
                })
                ->addColumn('gr_date', function (FreightBooking $row) {
                    return $row->lr && $row->lr->gr_date
                        ? date('d-m-Y', strtotime((string) $row->lr->gr_date))
                        : '-';
                })
                ->addColumn('lr_number', function (FreightBooking $row) {
                    $html = '<span class="fw-bold text-primary">'.e($row->lr->lr_number).'</span>';
                    if ($row->linked_lr_id) {
                        $html .= ' <span class="badge text-bg-success border-secondary">['.e((string) $row->linked_lr_id).']</span>';
                    }

                    return $html;
                })
                ->addColumn('customer', function (FreightBooking $row) {
                    $name = $row->lr->customer?->name ?? '-';
                    $html = e($name);
                    if ($row->lr->memo_id) {
                        $html .= '<br><span class="badge text-bg-success border-secondary ms-1">['.e((string) $row->lr->memo_id).']</span>';
                    }

                    return $html;
                })
                ->addColumn('transporter', function (FreightBooking $row) {
                    $t = $this->freightBookingTransporterForDisplay($row);

                    $transporterName = $row->transporter_name ?? ($t?->name ?? '-');
                    $shortName = $t?->short_name ?? $t?->beneficiary_id ?? '';

                    if ($t && $t->is_owner) {
                        $badge = '<span class="badge text-bg-success border-secondary ms-1" title="Owner"><i class="bi bi-truck"></i></span>';
                    } else {
                        $badge = '<span class="badge bg-info ms-1" title="Agency"><i class="bi bi-building"></i></span>';
                    }

                    return '
                        <div>
                            <div>'.$badge.' '.e($transporterName).'</div>
                            '.($shortName ? '<div class="text-center"><span class="badge text-bg-secondary">'.e($shortName).'</span></div>' : '').'
                        </div>
                    ';
                })
                ->addColumn('truck_number', function (FreightBooking $row) {
                    if (! $row->lr || ! $row->lr->truck) {
                        return '-';
                    }

                    return e($row->lr->truck->truck_number);
                })
                ->addColumn('route', function (FreightBooking $row) {
                    $src = e($row->lr->source_city ?: '-');
                    $dst = e($row->lr->destination_city ?: '-');

                    return '<div class="small"><span class="text-muted">'.$src.'</span>'
                        .'<i class="bi bi-arrow-right mx-1 text-muted"></i>'
                        .'<span class="text-muted">'.$dst.'</span></div>';
                })
                ->addColumn('weight', function (FreightBooking $row) {
                    if (! $row->lr || $row->lr->weight === null || $row->lr->weight === '') {
                        return '-';
                    }

                    return e((string) $row->lr->weight);
                })
                ->addColumn('rate', function (FreightBooking $row) {
                    return $row->lr && $row->tr_fr_rate
                        ? '<span class="text-muted">'.e((string) $row->tr_fr_rate).'</span>'
                        : '-';
                })
                ->addColumn('total', function (FreightBooking $row) {
                    return $row->lr && $row->tr_fr_rate
                        ? '<span class="fw-bold">'.e((string) $row->tr_amount).'</span>'
                        : '-';
                })
                ->addColumn('balance', function (FreightBooking $row) {
                    $amt = (float) ($row->balance_amount ?? 0);

                    return '<span class="text-end fw-bold text-brand-primary d-block">'.number_format($amt, 2).'</span>';
                })
                ->addColumn('actions', function (FreightBooking $booking) use ($request) {
                    $listingQuerySuffix = ListingQueryString::suffix($request, ListingQueryString::FREIGHT_BOOKING_KEYS);
                    $html = '<div class="app-index-table-actions">';
                    $html .= '<a href="'.e(route('freight_bookings.show', $booking->id).$listingQuerySuffix).'" class="btn btn-sm btn-icon btn-action-view" title="View" data-bs-toggle="tooltip"><i class="bi bi-eye"></i></a>';
                    $html .= '<a href="'.e(route('freight_bookings.edit', $booking->id).$listingQuerySuffix).'" class="btn btn-sm btn-icon btn-action-edit" title="Edit" data-bs-toggle="tooltip"><i class="bi bi-pencil"></i></a>';

                    $lr = $booking->lr;
                    if ($lr) {
                        $printTitle = $booking->voucher_last_printed_at
                          ? 'Last printed: '.$booking->voucher_last_printed_at->format('d-m-Y h:i A')
                          : 'Print voucher challan';
                        $printedClass = $booking->voucher_printed ? ' is-printed' : '';
                        $html .= '<a href="'.e(route('reports.voucher-receipt-export', ['lr_id' => $lr->id])).'" ';
                        $html .= 'class="btn btn-sm btn-icon btn-action-print voucher-print-btn'.$printedClass.'" ';
                        $html .= 'data-freight-booking-id="'.e((string) $booking->id).'" ';
                        $html .= 'data-mark-url="'.e(route('reports.voucher-receipt-mark-printed')).'" ';
                        $html .= 'title="'.e($printTitle).'" data-bs-toggle="tooltip"><i class="bi bi-file-earmark-text"></i></a>';
                    }

                    $html .= '</div>';

                    return $html;
                })
                ->orderColumn('gr_date', 'list_lr.gr_date $1')
                ->orderColumn('lr_number', 'list_lr.lr_number $1')
                ->orderColumn('weight', 'list_lr.weight $1')
                ->orderColumn('rate', 'freight_bookings.tr_fr_rate $1')
                ->orderColumn('total', 'freight_bookings.tr_amount $1')
                ->orderColumn('balance', 'freight_bookings.balance_amount $1')
                ->rawColumns(['checkbox', 'lr_number', 'customer', 'transporter', 'route', 'rate', 'total', 'balance', 'actions'])
                ->only([
                    'checkbox',
                    'gr_date',
                    'lr_number',
                    'customer',
                    'transporter',
                    'truck_number',
                    'route',
                    'weight',
                    'rate',
                    'total',
                    'balance',
                    'actions',
                ]);

            return $dataTable->make(true);
        } catch (\Exception $e) {
            \Log::error('DataTables error: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 200);
        }
    }

    public function create(Request $request)
    {
        $company = auth()->user()->company;
        $company?->loadMissing('subscription');
        if (! $company?->subscriptionCanWrite()) {
            return $this->redirectToFreightBookingsIndex($request, 'error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        if ($company->lrCreditsRemaining() <= 0) {
            return $this->redirectToFreightBookingsIndex($request, 'error', 'You do not have enough credits to create a Good Hire (Lorry Hire).');
        }
        $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();
        $lorry_receipt_id = $request->lorry_receipt_id;

        // Get TDS percentages from config
        $tdsPercentages = [
            'default' => config('payments.tds_percentages.default', 20),
            'agency' => config('payments.tds_percentages.agency', 2),
            'owner' => config('payments.tds_percentages.owner', 1),
        ];

        $lorry_receipt = LorryReceipt::with([
            'transporter.transporterMaster',
            'truck:id,truck_number',
            'customer:id,name',
            'consignor:id,name',
            'consignee:id,name',
            'reference.transporterMaster',
        ])
            ->select('id', 'lr_number', 'transporter_id', 'truck_id', 'reference_by', 'customer_id', 'consignor_id', 'consignee_id', 'good_type', 'source_city', 'destination_city', 'source_state', 'destination_state', 'weight', 'no_of_units', 'amount', 'fr_rate')
            ->when($lorry_receipt_id, function ($query) use ($lorry_receipt_id) {
                $query->where('id', $lorry_receipt_id);
            })
            ->get();

        // If lorry_receipt_id is provided, fetch LR number and related data
        if ($request->has('lorry_receipt_id')) {
            $lr = LorryReceipt::find($request->lorry_receipt_id);
            if ($lr) {
                // Fetch transporter and truck details directly from their models
                $transporter = Transporter::find($lr->transporter_id);
                $truck = Truck::find($lr->truck_id);
                $tdsPercentage = $transporter ? (float) $transporter->tds_percentage : 0.0;

                return view('freight_bookings.create', [
                    'goodsTypes' => $goodsTypes,
                    'lorry_receipt' => $lorry_receipt,
                    'weight' => $lr->weight,

                    'transporter' => $transporter ?? null,
                    'tdsPercentages' => $tdsPercentages,
                    'tdsPercentage' => $tdsPercentage, // send to view
                    // 'tdsAmount'       => $tdsAmount,     // send to view
                    'preFilled' => [
                        'lr_number' => $lr->lr_number,
                        'lr_id' => $lr->id,
                        'transporter_id' => $lr->transporter_id,
                        'transporter_name' => $transporter ? $transporter->name : '',
                        'truck_id' => $lr->truck_id,
                        'truck_number' => $truck ? $truck->truck_number : '',
                        'pan_verified' => $transporter ? $transporter->pan_active : 0,
                        'pan_number' => $transporter ? $transporter->pan_number : '',
                        'is_owner' => $transporter ? $transporter->is_owner : 0,
                        'declaration_given' => $transporter ? $transporter->declaration_given : 0,
                        'goods_type' => $lr->good_type,

                    ],
                ]);
            }
        }

        return view('freight_bookings.create', [
            'goodsTypes' => $goodsTypes,
            'lorry_receipt' => $lorry_receipt,
            'transporter' => null,
            'tdsPercentages' => $tdsPercentages,
            'tdsPercentage' => 0,
            'preFilled' => [],
        ]);
    }

    /**
     * Confirm the freight booking details before final submission
     *
     * @return Response
     */
    public function confirm(Request $request)
    {
        $activeCompanyId = auth()->user()->company_id;

        // Validate the form data
        $validator = Validator::make($request->all(), [
            'lr_id' => 'required|exists:lorry_receipts,id',
            'tr_fr_rate' => 'required|numeric|min:1|max:20000',
            'tr_amount' => 'required|numeric',
            'is_fixed_amount' => 'nullable|boolean',
            'advance_amount' => 'nullable|numeric',
            'balance_amount' => 'required|numeric',
            'tds_amount' => 'nullable|numeric',
            'eway_bills' => 'nullable|array',
            'eway_bills.*.eway_bill_number' => 'nullable|string',
            'eway_bills.*.eway_bill_expiry' => 'nullable|date',
            'good_type' => 'nullable|string',
            'pan_verified' => 'required|boolean',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'company_id' => 'required|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Get the validated form data
        $formData = $validator->validated();
        if ((int) $formData['company_id'] !== (int) $activeCompanyId) {
            return redirect()->back()
                ->withErrors(['company_id' => 'Invalid company context for this request.'])
                ->withInput();
        }
        $formData['company_id'] = (int) $activeCompanyId;

        // Get the lorry receipt with its relationships
        $lorry_receipt = LorryReceipt::with(['transporter.transporterMaster', 'truck', 'customer', 'consignor', 'consignee', 'reference.transporterMaster'])
            ->findOrFail($formData['lr_id']);

        // Return the confirmation view with the form data and lorry receipt
        return view('freight_bookings.confirm', compact('formData', 'lorry_receipt'));
    }

    /**
     * Store a newly created freight booking in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $activeCompanyId = auth()->user()->company_id;

        // Check if the form has been confirmed
        if (! $request->has('confirmed')) {
            return redirect()->route('freight_bookings.create')
                ->with('error', 'Please confirm the good hire (lorry hire) details before submission.');
        }

            $data = $request->validate([
            'lr_id' => 'required|exists:lorry_receipts,id',
            'tr_fr_rate' => 'required|numeric',
            'tr_amount' => 'required|numeric',
            'is_fixed_amount' => 'nullable|boolean',
            'advance_amount' => 'nullable|numeric',
            'balance_amount' => 'required|numeric',
            'tds_amount' => 'nullable|numeric',
            'eway_bills' => 'nullable|array',
            'eway_bills.*.eway_bill_number' => 'nullable|string',
            'eway_bills.*.eway_bill_expiry' => 'nullable|date',
            'good_type' => 'nullable|string',
            'pan_verified' => 'required|boolean',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'company_id' => 'required|exists:companies,id',

        ]);
        if ((int) $data['company_id'] !== (int) $activeCompanyId) {
            return redirect()->back()
                ->withErrors(['company_id' => 'Invalid company context for this request.'])
                ->withInput();
        }
        $data['company_id'] = (int) $activeCompanyId;

        $company = Company::query()->with('subscription')->find($activeCompanyId);
        if ($company) {
            $this->subscriptionGuard->assertHasLrCredits($company, 1);
        }

        try {
            DB::beginTransaction();

            $lorryReceipt = LorryReceipt::lockForUpdate()->find($data['lr_id']);
            if (! $lorryReceipt) {
                DB::rollBack();

                return redirect()->back()->withErrors(['lr_id' => 'Invalid good receipt (bilty)']);
            }

            if ($lorryReceipt->freight_booking_id) {
                DB::rollBack();

                return $this->redirectToFreightBookingsIndex($request, 'error', 'This Good Receipt (Bilty) is already linked to a Good Hire (Lorry Hire).');
            }

            $nextVoucherNumber = FreightBooking::allocateNextVoucherNumber();

            $transporterInfo = [];
            if ($lorryReceipt->transporter) {
                $transporterInfo = [
                    'transporter_id' => $lorryReceipt->transporter_id,
                    'transporter_name' => $lorryReceipt->transporter->name,
                    'transporter_tds_percentage' => $lorryReceipt->transporter->tds_percentage,
                ];
            }

            $calculatedAmounts = $this->freightBookingCalculationService->calculateForStore(
                (bool) ($data['is_fixed_amount'] ?? false),
                (float) $data['tr_fr_rate'],
                (float) ($lorryReceipt->weight ?? 0),
                (float) $data['tr_amount'],
                (float) ($transporterInfo['transporter_tds_percentage'] ?? 0)
            );

            $linkedLrId = $lorryReceipt->linked_lr_id ?? null;
            $linked_freight_booking_id = FreightBooking::where('lr_id', $lorryReceipt->id)->orderBy('id', 'desc')->first()->id ?? null;

            $freightBooking = FreightBooking::create(array_merge($data, $calculatedAmounts, [
                'lr_number' => $lorryReceipt->lr_number,
                'linked_freight_booking_id' => $linked_freight_booking_id,
                'voucher_number' => $nextVoucherNumber,
                'pan_verified' => (bool) $data['pan_verified'],
                'company_id' => $data['company_id'],
                'linked_lr_id' => $linkedLrId,
            ], $transporterInfo));

            foreach ($data['eway_bills'] ?? [] as $ewayBillData) {
     if (
        empty($ewayBillData['eway_bill_number'] ?? null) &&
        empty($ewayBillData['eway_bill_expiry'] ?? null)
        ) {
        continue;
       }

      $freightBooking->ewayBills()->create([
        'eway_bill_number' => $ewayBillData['eway_bill_number'] ?? null,
        'eway_bill_expiry' => $ewayBillData['eway_bill_expiry'] ?? null,
      ]);
     }

            $oldDeliveryStatus = $lorryReceipt->delivery_status;
            $deliveryStatus = 'Pending';
            if ($data['is_cancelled'] === '1') {
                $deliveryStatus = 'Cancelled';
            } elseif ($data['is_delivered'] === '1') {
                $deliveryStatus = 'Delivered';
            } else {
                $deliveryStatus = 'In-Progress';
            }
            $lorryReceipt->delivery_status = $deliveryStatus;
            $lorryReceipt->freight_booking_id = $freightBooking->id;
            $lorryReceipt->save();

            $company = Company::lockForUpdate()->with('subscription')->find($data['company_id']);
            if (! $company) {
                DB::rollBack();

                return redirect()->back()->withErrors(['company_id' => 'Invalid company context for this request.']);
            }
            $this->subscriptionGuard->decrementLrCredits($company, 1);

            $notifyCancelled = ! empty($data['is_cancelled']);
            $notifyLrStatusChange = $deliveryStatus !== $oldDeliveryStatus;

            $this->verifyFreightBookingAmountsBeforeSave(
                $freightBooking,
                0,
                0,
                (float) ($lorryReceipt->weight ?? 0),
                (float) ($lorryReceipt->unloading_weight ?? $lorryReceipt->weight ?? 0),
            );

            DB::afterCommit(function () use ($notifyCancelled, $notifyLrStatusChange, $freightBooking, $lorryReceipt, $deliveryStatus): void {
                if ($notifyCancelled) {
                    $freightBooking->loadMissing(['company', 'lr.truck', 'transporter.transporterMaster']);
                    $this->frNotificationService->notifyCancelled($freightBooking, auth()->user());
                }

                if ($notifyLrStatusChange) {
                    $lorryReceipt->loadMissing(['company', 'truck', 'transporter.transporterMaster', 'customer']);
                    $this->lrNotificationService->notifyStatusChange($lorryReceipt, $deliveryStatus, auth()->user());
                }

                try {
                    $freightBooking->loadMissing(['company', 'transporter', 'truck', 'lr']);
                    app(\App\Services\AccountingPostingService::class)->postFreightBooking($freightBooking);
                } catch (\Throwable $e) {
                    \Log::error('Accounting post freight booking failed: '.$e->getMessage());
                }
            });

            DB::commit();
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            \Log::error('Freight booking create failed: amount verification', [
                'error' => $e->getMessage(),
                'request_context' => $this->freightBookingUpdateLogContext($request),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'Unable to create Good Hire (Lorry Hire). Amount verification failed. Please try again.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Freight booking create failed', [
                'error' => $e->getMessage(),
                'request_context' => $this->freightBookingUpdateLogContext($request),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'Unable to create Good Hire (Lorry Hire). Please try again.']);
        }

        if ($request->has('action') && $request->action === 'save_and_pay') {
            // Redirect to voucher payment creation page with freight booking ID
            return redirect()->route('voucher_payments.create', ['freight_booking_id' => $freightBooking->id])
                ->with('success', 'Good Hire (Lorry Hire) created successfully. Ready to make payment.');
        }
        if ($request->has('action') && $request->action === 'save_and_view') {
            $showUrl = route('freight_bookings.show', $freightBooking->id)
                .ListingQueryString::suffix($request, ListingQueryString::FREIGHT_BOOKING_KEYS);

            return redirect()
                ->to($showUrl)
                ->with('success', 'Good Hire (Lorry Hire) created successfully. Ready to make payment.');
        }

        return $this->redirectToFreightBookingsIndex($request, 'success', 'Good Hire (Lorry Hire) created successfully.');
    }

    public function storeCombinedPayment(Request $request)
    {
        try {
            $request->validate([
                'combined_amount' => 'required|numeric|min:1',
                'payment_type' => 'required|exists:payment_types,id',
                'payment_mode' => 'required|exists:payment_modes,id',
                'pump_id' => 'nullable|exists:pumps,id',
                'reference_number' => 'nullable|string|max:255',
                'payment_date' => 'nullable|date',
                'payment_remark' => 'nullable|string|max:1000',
                'booking_ids' => 'required|array',
                'payment_amounts' => 'required|array',
                'notes' => 'nullable|string|max:1000',
            ]);

            $selectedBookings = FreightBooking::query()
                ->whereIn('id', $request->input('booking_ids'))
                ->get();

            if ($selectedBookings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid freight bookings were selected for combined payment.',
                ], 422);
            }

            $linkedLrIds = $selectedBookings->pluck('linked_lr_id')->unique()->filter()->values();
            if ($linkedLrIds->count() !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Combined payment requires bookings from a single linked group.',
                ], 422);
            }

            $linkedLrId = (int) $linkedLrIds->first();
            $fullGroupCount = FreightBooking::query()
                ->linkedGroup($linkedLrId, (int) auth()->user()->company_id)
                ->where('is_cancelled', false)
                ->count();

            if ($fullGroupCount !== $selectedBookings->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Combined payment must include every booking in the linked group.',
                ], 422);
            }

            $lr_ids = $selectedBookings->pluck('lr_id')->all();
            $lrs = LorryReceipt::whereIn('id', $lr_ids)->get()->select('lr_number')->toArray();
            $lrs = array_map(function ($item) {
                return $item['lr_number'];
            }, $lrs);

            $data = [];
            $data['pump_id'] = $request->input('pump_id');
            $data['payment_type_id'] = $request->input('payment_type');
            $data['payment_mode_id'] = $request->input('payment_mode');
            $data['payment_type'] = PaymentType::find($request->input('payment_type'))->name ?? 'N/A';
            $data['payment_mode'] = PaymentMode::find($request->input('payment_mode'))->name ?? 'N/A';
            $data['total_amount'] = $request->input('combined_amount');
            $data['date'] = $request->input('payment_date') ?? date('Y-m-d');
            $data['booking_ids'] = $request->input('booking_ids');
            // Keep signed split values as submitted (e.g. 1586.55 and -586.05 for linked offsets).
            $data['payment_amounts'] = array_map(static fn ($v) => (float) $v, (array) $request->input('payment_amounts', []));
            if (array_sum($data['payment_amounts']) <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Combined payment amount is not allocatable against selected bookings. Please review balances.',
                ], 422);
            }
            $data['reference_number'] = $request->input('reference_number');
            $data['notes'] = $request->input('notes').'   Combined Payment: '.($request->input('combined_amount'))
                .'. [LR IDs: '.implode(', ', $lrs).'], Date: '.$data['date'];
            $data['combined_payment_flow'] = true;
            if (strtolower($data['payment_mode']) === 'cash' || strtolower($data['payment_mode']) === 'adjust in advance') {
                $paymentStatus = 'in-progress';
            } elseif (
                strtolower($data['payment_mode']) === 'bank transfer' ||
                strtolower($data['payment_mode']) === 'fuel' ||
                strpos(strtolower($data['payment_mode']), 'pump') !== false
            ) {
                $paymentStatus = 'pending';
            }

            // Call voucherPayment which handles all validations, transactions, and locking
            $response = $this->VoucherPayment(new Request($data));
            $responseContent = $response->getContent();
            $response = json_decode($responseContent, true);

            // If voucherPayment returned an error or warning, return it as JSON response
            if ($response['success'] !== true) {
                return response()->json($response, 422);
            }

            $voucherPayments = $response['processed_payments'] ?? [];

            // Wrap VoucherLinked creation in a transaction for data consistency
            // Note: voucherPayment() already committed its transaction, so individual payments are safe
            // This transaction ensures VoucherLinked and linking operations are atomic
            \DB::beginTransaction();

            try {
                $VoucherLinked = VoucherLinked::create([
                    'date' => $data['date'],
                    'voucher_ids' => json_encode($voucherPayments),
                    'pump_id' => $data['pump_id'],
                    'company_id' => auth()->user()->company_id,
                    'payment_type' => $data['payment_type'],
                    'payment_type_id' => $data['payment_type_id'],
                    'payment_mode' => $data['payment_mode'],
                    'payment_mode_id' => $data['payment_mode_id'],
                    'amount' => $data['total_amount'],
                    'reference_transaction_id' => $data['reference_number'],
                    'remark' => $data['notes'],
                    'payment_status' => $paymentStatus,
                    'freight_booking_id' => $request->input('booking_ids')[0] ?? null,
                ]);

                // Link voucher payments to VoucherLinked record
                foreach ($voucherPayments as $index => $voucherId) {
                    $voucher = VoucherPayment::find($voucherId);
                    if ($voucher) {
                        $voucher->linked_voucher_id = $VoucherLinked->id;
                        $voucher->save();
                    }
                }

                \DB::commit();

                return response()->json($response);
            } catch (\Exception $e) {
                \DB::rollBack();
                \Log::error('Error creating VoucherLinked for combined payment', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'voucher_payments' => $voucherPayments,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Combined payment was processed but failed to create linked record. Please contact administrator. Error: '.$e->getMessage(),
                ], 500);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error in storeCombinedPayment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_context' => $this->paymentRequestLogContext($request),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing combined payment. Please try again.',
            ], 500);
        }
    }

    public function show($id)
    {

        try {
            $freightBooking = FreightBooking::with(['lr', 'linkedFreightBooking', 'company', 'customAmounts'])->findOrFail($id);

            if (Auth::user()->company_id && $freightBooking->company_id !== Auth::user()->company_id) {
                abort(403, 'Unauthorized access to good hire (lorry hire).');
            }
            $pumps = Pump::where('user_id', Auth::user()->company->user_id)->get();

            // Load payment types and modes for the voucher payment modal
            $paymentTypes = PaymentType::all();
            $paymentModes = PaymentMode::all();

            $linkedFreightBookings = collect();
            $combinedTotalBalance = 0;

            if ($freightBooking->linked_lr_id) {
                $freightBookingIds = FreightBooking::where('linked_lr_id', $freightBooking->linked_lr_id)
                    ->where('company_id', auth()->user()->company_id)
                    ->pluck('id')->toArray();
                $payments = VoucherLinked::with('pump')
                    ->whereIn('freight_booking_id', $freightBookingIds)
                    ->where('company_id', auth()->user()->company_id)
                    ->orderBy('created_at', 'desc')
                    ->get();
                $linkedFreightBookings = FreightBooking::where('linked_lr_id', $freightBooking->linked_lr_id)
                    ->where('company_id', auth()->user()->company_id)
                    ->with(['lr.truck', 'lr.transporter.transporterMaster', 'customAmounts'])
                    ->orderBy('id')
                    ->get();
                $combinedTotalBalance = $linkedFreightBookings->sum('balance_amount');
            } else {
                $payments = VoucherPayment::with('pump')
                    ->where('freight_booking_id', $id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            return view('freight_bookings.show', compact('freightBooking', 'paymentTypes', 'paymentModes', 'pumps', 'payments', 'linkedFreightBookings', 'combinedTotalBalance'));
        } catch (ModelNotFoundException $e) {
            return $this->redirectToFreightBookingsIndex(request(), 'error', 'Freight booking not found.');
        } catch (\Exception $e) {
            return $this->redirectToFreightBookingsIndex(request(), 'error', 'An error occurred while loading the good hire (lorry hire).');
        }
    }

    public function edit($id)
    {
        $company = auth()->user()->company;
        $company?->loadMissing('subscription');
        if (! $company?->subscriptionCanWrite()) {
            return $this->redirectToFreightBookingsIndex(request(), 'error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        try {
            $freightBooking = FreightBooking::query()
                ->with([
                    'lr.transporter.transporterMaster',
                    'lr.consignee',
                    'lr.truck',
                    'customAmounts',
                ])
                ->findOrFail($id);

            $is_admin = Auth::user()->canActAsAdmin();
            if (Auth::user()->company_id && $freightBooking->company_id !== Auth::user()->company_id) {
                abort(403, 'Unauthorized access to good hire (lorry hire).');
            }

            $lorryReceipt = $freightBooking->lr;
            $transporter = $lorryReceipt?->transporter;
            $truck = $lorryReceipt?->truck;

            $tdsPercentages = [
                'default' => config('payments.tds_percentages.default', 20),
                'agency' => config('payments.tds_percentages.agency', 2),
                'owner' => config('payments.tds_percentages.owner', 1),
            ];

            $goodsTypes = GoodType::where('user_id', Auth::user()->company->user_id)->get();
            $lorry_receipt = $lorryReceipt ? collect([$lorryReceipt]) : collect();

            if ($lorryReceipt) {
                $tdsPercentage = $transporter ? (float) $transporter->tds_percentage : 0.0;

                return view('freight_bookings.edit', [
                    'freightBooking' => $freightBooking,
                    'goodsTypes' => $goodsTypes,
                    'lorry_receipt' => $lorry_receipt,
                    'weight' => $lorryReceipt->weight,

                    'transporter' => $transporter,
                    'tdsPercentages' => $tdsPercentages,
                    'tdsPercentage' => $tdsPercentage,
                    'canChangeTds' => $freightBooking->canChangeTdsAmount(),
                    'preFilled' => [
                        'lr_number' => $lorryReceipt->lr_number,
                        'lr_id' => $lorryReceipt->id,
                        'transporter_id' => $lorryReceipt->transporter_id,
                        'transporter_name' => $transporter ? $transporter->name : '',
                        'truck_id' => $lorryReceipt->truck_id,
                        'truck_number' => $truck ? $truck->truck_number : '',
                        'pan_verified' => $transporter ? $transporter->pan_active : 0,
                        'is_owner' => $transporter ? $transporter->is_owner : 0,
                        'goods_type' => $lorryReceipt->good_type,
                        'memo_id' => $lorryReceipt->memo_id,
                    ],
                    'is_admin' => $is_admin,
                ]);
            }

            return view('freight_bookings.edit', [
                'freightBooking' => $freightBooking,
                'lorry_receipt' => $lorry_receipt,
                'goodsTypes' => $goodsTypes,
                'transporter' => $transporter,
                'truck' => $truck,
                'tdsPercentages' => $tdsPercentages,
                'tdsPercentage' => $transporter ? (float) $transporter->tds_percentage : (float) ($freightBooking->transporter_tds_percentage ?? 0),
                'canChangeTds' => $freightBooking->canChangeTdsAmount(),
                'preFilled' => [
                    'lr_number' => '',
                    'lr_id' => $freightBooking->lr_id,
                    'transporter_id' => '',
                    'transporter_name' => $transporter ? $transporter->name : '',
                    'truck_id' => '',
                    'truck_number' => $truck ? $truck->truck_number : '',
                    'pan_verified' => $transporter ? $transporter->pan_active : 0,
                    'is_owner' => $transporter ? $transporter->is_owner : 0,
                ],
                'is_admin' => $is_admin,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->redirectToFreightBookingsIndex(request(), 'error', 'Freight booking not found.');
        } catch (\Exception $e) {
            return $this->redirectToFreightBookingsIndex(request(), 'error', 'An error occurred while loading the good hire (lorry hire) for editing.');
        }
    }

    public function getLorryReceipt($id)
    {
        $lr = LorryReceipt::find($id);
        if (! $lr) {
            return response()->json(['error' => 'LR not found'], 404);
        }

        return response()->json($lr);
    }

    public function getTransporter($id)
    {
        $companyId = $this->getActiveCompanyId();
        $transporter = Transporter::with([
            'transporterMaster.city',
            'transporterMaster.state',
            'transporterMaster.country',
        ])
            ->where('id', $id)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->first();
        if (! $transporter) {
            return response()->json(['error' => 'Transporter not found'], 404);
        }

        return response()->json($transporter);
    }

    public function getTruck($id)
    {
        $companyId = $this->getActiveCompanyId();
        $truck = Truck::with(['transporter.transporterMaster', 'driver'])
            ->where('id', $id)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where(function ($innerQuery) use ($companyId) {
                    $innerQuery->where('company_id', $companyId)
                        ->orWhereHas('transporter', function ($transporterQuery) use ($companyId) {
                            $transporterQuery->where('company_id', $companyId);
                        });
                });
            })
            ->first();
        if (! $truck) {
            return response()->json(['error' => 'Truck not found'], 404);
        }

        if (! $truck->transporter) {
            $truck->setRelation('transporter', null);
        }

        return response()->json($truck);
    }

    public function update(Request $request, FreightBooking $freightBooking)
    {
        $company = auth()->user()->company;
        $company?->loadMissing('subscription');
        if (! $company?->subscriptionCanWrite()) {
            return $this->redirectToFreightBookingsIndex($request, 'error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }

        // Prevent updates to cancelled freight bookings
        if ($freightBooking->is_cancelled) {
            return redirect()->back()->withErrors(['general' => 'Cannot update a cancelled good hire (lorry hire). Please contact administrator if changes are needed.']);
        }

        $data = $request->validate([
            'lr_id' => 'required|exists:lorry_receipts,id',
            'tr_fr_rate' => 'required|numeric|min:0|max:20000',
            'tr_amount' => 'required|numeric',
            'is_fixed_amount' => 'nullable|boolean',
            'advance_amount' => 'required|numeric',
            'balance_amount' => 'required|numeric',
            'tds_amount' => 'nullable|numeric',
            'good_type' => 'nullable|string',
            'detention' => 'nullable|numeric',
            'deviation' => 'nullable|numeric',
            'misc_expense' => 'nullable|numeric',
            'damage_amount' => 'nullable|numeric',
            'shortage_amount' => 'nullable|numeric',
            'unloading_weight' => 'nullable|numeric|min:0',
            'pan_verified' => 'required|boolean',
            'remark' => 'nullable|string',
            'is_delivered' => 'boolean',
            'is_cancelled' => 'boolean',
            'linked_freight_booking_id' => 'nullable|exists:freight_bookings,id',
            'eway_bills' => 'nullable|array',
            'eway_bills.*.id' => [
                'nullable',
                'integer',
                Rule::exists('eway_bills', 'id')->where(
                    fn ($query) => $query->where('freight_booking_id', $freightBooking->id)
                ),
            ],
            'eway_bills.*.eway_bill_number' => 'nullable|string',
            'eway_bills.*.eway_bill_expiry' => 'nullable|date',
            'custom_amounts' => 'nullable|array',
            'custom_amounts.*.id' => [
                'nullable',
                'integer',
                Rule::exists('freight_booking_custom_amounts', 'id')->where(
                    fn ($query) => $query->where('freight_booking_id', $freightBooking->id)
                ),
            ],
            'custom_amounts.*.description' => 'required|string|max:255',
            'custom_amounts.*.amount' => 'required|numeric|not_in:0',
        ]);

        // Get the lorry receipt to get its LR number
        $lorryReceipt = LorryReceipt::find($data['lr_id']);
        if (! $lorryReceipt) {
            return redirect()->back()->withErrors(['lr_id' => 'Invalid good receipt (bilty)']);
        }

        $voucherPaymentsTotal = 0.0;
        $customAmountsTotal = 0.0;
        $unloadingWeight = (float) ($lorryReceipt->unloading_weight ?? $lorryReceipt->weight ?? 0);

        DB::beginTransaction();
        try {
            $isFixedAmount = (bool) ($data['is_fixed_amount'] ?? $freightBooking->is_fixed_amount);
            $newDamageAmount = (float) ($data['damage_amount'] ?? 0);
            $newShortageAmount = (float) ($data['shortage_amount'] ?? 0);
            $unloadingWeight = isset($data['unloading_weight'])
                ? (float) $data['unloading_weight']
                : (float) ($lorryReceipt->unloading_weight ?? $lorryReceipt->weight ?? 0);
            $voucherPaymentsTotal = (float) $freightBooking->voucherPayments()->sum('amount');

            $customAmountsTotal = $this->freightBookingCustomAmountService->sync(
                $freightBooking,
                $data['custom_amounts'] ?? []
            );

            $calculatedAmounts = $this->freightBookingCalculationService->calculateForUpdate(
                $isFixedAmount,
                (float) $data['tr_fr_rate'],
                (float) ($lorryReceipt->weight ?? 0),
                $unloadingWeight,
                (float) $data['tr_amount'],
                $voucherPaymentsTotal,
                (float) ($data['detention'] ?? 0),
                (float) ($data['deviation'] ?? 0),
                (float) ($data['misc_expense'] ?? 0),
                $newDamageAmount,
                $newShortageAmount,
                (float) ($freightBooking->transporter_tds_percentage ?? 0),
                $freightBooking->canChangeTdsAmount(),
                (float) ($freightBooking->tds_amount ?? 0),
                (float) ($freightBooking->negative_adjusted_amount ?? 0),
                $customAmountsTotal,
            );

            $data = array_merge($data, $calculatedAmounts);

            $linkedLrId = $lorryReceipt->linked_lr_id ?? null;

            $wasFreightBookingCancelled = (bool) $freightBooking->is_cancelled;

            // Update freight booking with lorry receipt's LR number
            $freightBooking->update(array_merge($data, [
                'lr_number' => $lorryReceipt->lr_number,
                'linked_lr_id' => $linkedLrId,
            ]));

            $submittedEwayBills = $data['eway_bills'] ?? [];

            $existingEwayBillIds = $freightBooking->ewayBills()->pluck('id')->toArray();

            $submittedEwayBillIds = collect($submittedEwayBills)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->toArray();

            $freightBooking->ewayBills()
                ->whereIn('id', array_diff($existingEwayBillIds, $submittedEwayBillIds))
                ->delete();

            foreach ($submittedEwayBills as $ewayBill) {
                if (! empty($ewayBill['id'])) {
                    $freightBooking->ewayBills()
                        ->where('id', $ewayBill['id'])
                        ->update([
                            'eway_bill_number' => $ewayBill['eway_bill_number'] ?? null,
                            'eway_bill_expiry' => $ewayBill['eway_bill_expiry'] ?? null,
                        ]);
                } elseif (! empty($ewayBill['eway_bill_number'])) {
                    $freightBooking->ewayBills()->create([
                        'eway_bill_number' => $ewayBill['eway_bill_number'],
                        'eway_bill_expiry' => $ewayBill['eway_bill_expiry'] ?? null,
                    ]);
                }
            }

            // Refresh freight booking to get updated values
            $freightBooking->refresh();

            $justCancelledFreightBooking = ! $wasFreightBookingCancelled && (bool) $freightBooking->is_cancelled;

            $lorryReceiptUpdate = null;

            // Update lorry receipt status
            if ($lorryReceipt) {
                $lorryReceiptUpdate = $this->freightBookingCalculationService->updateLorryReceiptForFreightBookingUpdate(
                    $lorryReceipt,
                    $data,
                    $newDamageAmount,
                    $newShortageAmount,
                );

                if ($lorryReceiptUpdate['delivery_status'] !== $lorryReceiptUpdate['old_delivery_status']) {
                    $lorryReceipt->loadMissing(['company', 'truck', 'transporter.transporterMaster', 'customer']);
                    $this->lrNotificationService->notifyStatusChange($lorryReceipt, $lorryReceiptUpdate['delivery_status'], auth()->user());
                }
            }

            if ($justCancelledFreightBooking) {
                $freightBooking->loadMissing(['company', 'lr.truck', 'transporter.transporterMaster']);
                $this->frNotificationService->notifyCancelled($freightBooking, auth()->user());
            }

            $freightBooking->refresh();
            $this->freightBookingCalculationService->applyNegativeAdjustment(
                $freightBooking,
                $lorryReceipt?->lr_number
            );

            $freightBooking->refresh();
            $this->verifyFreightBookingAmountsBeforeSave(
                $freightBooking,
                $voucherPaymentsTotal,
                $customAmountsTotal,
                (float) ($lorryReceipt->weight ?? 0),
                $unloadingWeight,
            );

            DB::commit();

            return $this->redirectToFreightBookingsIndex($request, 'success', 'Good Hire (Lorry Hire) updated successfully.');
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            \Log::error('Freight booking update failed: amount verification', [
                'freight_booking_id' => $freightBooking->id ?? null,
                'error' => $e->getMessage(),
                'amounts' => $this->freightBookingAmountsForVerification(
                    $freightBooking,
                    $voucherPaymentsTotal,
                    $customAmountsTotal,
                    (float) ($lorryReceipt->weight ?? 0),
                    $unloadingWeight,
                ),
            ]);

            return redirect()->back()->withErrors(['general' => 'Unable to update freight booking. Amount verification failed. Please try again.'])->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Freight booking update failed', [
                'freight_booking_id' => $freightBooking->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors(['general' => 'Unable to update freight booking. Please try again.'])->withInput();
        }
    }

    public function destroy(FreightBooking $freight_booking)
    {
        $company = auth()->user()->company;
        $company?->loadMissing('subscription');
        if (! $company?->subscriptionCanWrite()) {
            return $this->redirectToFreightBookingsIndex(request(), 'error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        $freight_booking->delete();

        return $this->redirectToFreightBookingsIndex(request(), 'success', 'Good Hire (Lorry Hire) deleted.');
    }

    /**
     * Process voucher payment for selected freight bookings
     *
     * @return JsonResponse
     */
    public function voucherPayment(Request $request)
    {
        try {
            // dd($request->all());

            // Validate the request
            $validated = $request->validate([
                'payment_type_id' => 'required|exists:payment_types,id',
                'payment_mode_id' => 'required|exists:payment_modes,id',
                'payment_type' => 'required|string',
                'payment_mode' => 'required|string',
                'booking_ids' => 'required|array',
                'booking_ids.*' => 'required|integer|exists:freight_bookings,id',
                'payment_amounts' => 'required|array',
                'payment_amounts.*' => 'required|numeric',
                'total_amount' => 'required|numeric|min:0',
                'reference_number' => 'nullable|string',
                'notes' => 'nullable|string',
                'pump_id' => 'nullable|exists:pumps,id',
                'date' => 'nullable|date',
                'combined_payment_flow' => 'nullable|boolean',
            ]);

            $isCombinedFlow = $request->boolean('combined_payment_flow');
            $requestedBookings = FreightBooking::query()
                ->whereIn('id', $validated['booking_ids'])
                ->get()
                ->keyBy('id');

            if ($isCombinedFlow) {
                $linkedLrIds = $requestedBookings->pluck('linked_lr_id')->unique()->filter()->values();
                if ($linkedLrIds->count() !== 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Combined payment requires bookings from a single linked group.',
                    ], 422);
                }
            } else {
                foreach ($validated['booking_ids'] as $bookingId) {
                    $booking = $requestedBookings->get($bookingId);
                    if ($booking === null) {
                        continue;
                    }

                    try {
                        $this->freightBookingCalculationService->assertIndividualPaymentAllowed($booking, false);
                    } catch (\DomainException $e) {
                        return response()->json([
                            'success' => false,
                            'message' => $e->getMessage(),
                        ], 422);
                    }
                }
            }

            // Begin transaction
            \DB::beginTransaction();

            $processedPayments = [];
            $affectedBookingIds = [];

            // Process each booking payment
            foreach ($validated['booking_ids'] as $index => $bookingId) {
                $amount = $validated['payment_amounts'][$index] ?? 0;

                if (abs((float) $amount) > 0.01) {
                    // Use row-level locking to prevent race conditions
                    // This ensures only one payment can be processed at a time for this booking
                    $freightBooking = FreightBooking::lockForUpdate()->findOrFail($bookingId);

                    $currentBalance = (float) ($freightBooking->balance_amount ?? 0);

                    // Sign-direction validation by balance direction.
                    if ($currentBalance > 0.01 && $amount < -0.01) {
                        \DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => "Booking ID {$bookingId} has positive balance ({$currentBalance}). Negative payment is not allowed for this row.",
                            'errors' => [
                                'payment_amounts.'.$index => ['Positive balance row cannot accept negative payment.'],
                            ],
                        ], 422);
                    }

                    if ($currentBalance < -0.01 && $amount > 0.01) {
                        \DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => "Booking ID {$bookingId} has negative balance ({$currentBalance}). Positive payment is not allowed for this row.",
                            'errors' => [
                                'payment_amounts.'.$index => ['Negative balance row cannot accept positive payment.'],
                            ],
                        ], 422);
                    }

                    if (abs($currentBalance) <= 0.01) {
                        \DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => "Booking ID {$bookingId} has zero balance. Payment must be 0 for this row.",
                            'errors' => [
                                'payment_amounts.'.$index => ['Zero balance row cannot accept non-zero payment.'],
                            ],
                        ], 422);
                    }

                    // Range validation by sign.
                    if ($currentBalance > 0.01 && $amount > $currentBalance) {
                        \DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => "Payment amount {$amount} cannot exceed balance amount {$currentBalance} for booking ID {$bookingId}.",
                            'errors' => [
                                'payment_amounts.'.$index => ["Payment amount cannot exceed balance amount {$currentBalance}"],
                            ],
                        ], 422);
                    }
                    if ($currentBalance < -0.01 && $amount < $currentBalance) {
                        \DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => "Negative payment {$amount} cannot be less than balance {$currentBalance} for booking ID {$bookingId}.",
                            'errors' => [
                                'payment_amounts.'.$index => ["Negative payment magnitude cannot exceed negative balance {$currentBalance}"],
                            ],
                        ], 422);
                    }

                    $effectivePayableAmount = $freightBooking->effectivePayableAmount();

                    // Validate that advance_amount + payment won't exceed effective payable amount
                    // Allow small tolerance (0.01) for floating point precision
                    $newAdvanceAmount = $freightBooking->advance_amount + $amount;
                    if ($newAdvanceAmount > $effectivePayableAmount + 0.01) {
                        \DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => "Payment amount {$amount} would cause advance amount ({$newAdvanceAmount}) to exceed effective payable amount {$effectivePayableAmount} for booking ID {$bookingId}.",
                            'errors' => [
                                'payment_amounts.'.$index => ["Total advance amount cannot exceed effective payable amount {$effectivePayableAmount}"],
                            ],
                        ], 422);
                    }

                    // Check for duplicate payment on the same day
                    $paymentDate = $validated['date'] ?? now()->toDateString();
                    $duplicatePayment = VoucherPayment::where('freight_booking_id', $bookingId)
                        ->where('amount', $amount)
                        ->where('payment_type_id', $validated['payment_type_id'])
                        ->where('payment_mode_id', $validated['payment_mode_id'])
                        ->whereDate('date', $paymentDate)
                        ->where('company_id', auth()->user()->company_id)
                        ->first();

                    if ($duplicatePayment && ! ($request->has('confirm_duplicate') && $request->input('confirm_duplicate') == '1')) {
                        \DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'warning' => true,
                            'message' => 'A similar payment already exists for this booking on '.date('d-m-Y', strtotime($paymentDate)).'.',
                            'duplicate_details' => [
                                'id' => $duplicatePayment->id,
                                'amount' => number_format($duplicatePayment->amount, 2),
                                'payment_type' => $duplicatePayment->payment_type,
                                'payment_mode' => $duplicatePayment->payment_mode,
                                'created_at' => $duplicatePayment->created_at->format('d-m-Y H:i:s'),
                                'date' => date('d-m-Y', strtotime($paymentDate)),
                            ],
                            'booking_index' => $index,
                            'errors' => [
                                'payment_amounts.'.$index => ['Duplicate payment detected. Please confirm if you want to proceed.'],
                            ],
                        ], 422);
                    }

                    // Get payment type and mode from their IDs
                    $paymentType = PaymentType::find($validated['payment_type_id']);
                    $paymentMode = PaymentMode::find($validated['payment_mode_id']);

                    // Determine payment status based on payment mode
                    $paymentStatus = 'pending'; // Default status

                    // Set status based on payment mode
                    if (strtolower($paymentMode->name) === 'cash' || strtolower($paymentMode->name) === 'adjust in advance') {
                        $paymentStatus = 'in-progress';
                    } elseif (
                        strtolower($paymentMode->name) === 'bank transfer' ||
                        strtolower($paymentMode->name) === 'fuel' ||
                        strpos(strtolower($paymentMode->name), 'pump') !== false
                    ) {
                        $paymentStatus = 'pending';
                    }

                    // Create voucher payment record
                    $voucherPayment = VoucherPayment::create([
                        'freight_booking_id' => $bookingId,
                        'voucher_number' => $freightBooking->voucher_number,
                        'payment_type' => $paymentType->name,
                        'payment_mode' => $paymentMode->name,
                        'payment_type_id' => $validated['payment_type_id'],
                        'payment_mode_id' => $validated['payment_mode_id'],
                        'pump_id' => $validated['pump_id'] ?? null,
                        'amount' => $amount,
                        'remark' => $validated['notes'] ?? null,
                        'payment_status' => $paymentStatus,
                        'company_id' => auth()->user()->company_id,
                        'date' => $validated['date'] ?? now()->toDateString(),
                    ]);

                    // Update freight booking balance
                    // $newadvance_amount = $freightBooking->advance_amount + $amount;
                    // $newBalance = max(0, $freightBooking->balance_amount - $amount);
                    // $freightBooking->update([
                    //     'balance_amount' => $newBalance,
                    //     'advance_amount' => $newadvance_amount
                    // ]);
                    // Update freight booking balance / transporter advance
                    if (strtolower($paymentMode->name) === 'fuel(paid to pump)') {
                        if (empty($validated['pump_id'])) {
                            throw ValidationException::withMessages([
                                'pump_id' => ['Please select Pump for fuel payment.'],
                            ]);
                        }

                        // 👉 If pump_id is valid, you can proceed with pump-related logic
                        $pumpId = $validated['pump_id'];
                        // your pump payment logic here
                    }
                    if (strtolower($paymentMode->name) === 'adjust in advance') {
                        $transporter = Transporter::find($freightBooking->transporter_id);

                        if (! $transporter) {
                            throw new \Exception("Transporter not found for booking ID {$bookingId}");
                        }

                        if ($amount > $transporter->advance_amount) {
                            throw ValidationException::withMessages([
                                'payment_amounts' => [
                                    "Allocated amount {$amount} cannot exceed transporter available advance {$transporter->advance_amount}",
                                ],
                            ]);
                        }

                        $this->freightBookingCalculationService->recalculateFromVoucherPayments($freightBooking);

                        $lrNumber = $freightBooking->lr?->lr_number ?? (string) $freightBooking->id;
                        app(TransporterAdvanceLedgerService::class)->recordAdvancePayment(
                            companyId: (int) $freightBooking->company_id,
                            transporterId: (int) $transporter->id,
                            advanceAmount: -(float) $amount,
                            paymentDate: now()->toDateString(),
                            remark: "LR#{$lrNumber}",
                            actor: auth()->user(),
                            notifyCredit: false
                        );

                        $processedPayments[] = $voucherPayment->id;
                        $affectedBookingIds[] = $bookingId;

                        continue;
                    }

                    $this->freightBookingCalculationService->recalculateFromVoucherPayments($freightBooking);

                    $processedPayments[] = $voucherPayment->id;
                    $affectedBookingIds[] = $bookingId;
                }
            }

            \DB::commit();

            $this->freightBookingCalculationService->applyNegativeAdjustmentsForBookings(
                FreightBooking::query()->whereIn('id', array_unique($affectedBookingIds))->get()
            );

            $payload = [
                'success' => true,
                'message' => 'Voucher payment processed successfully',
                'processed_count' => count($processedPayments),
                'processed_payments' => $processedPayments,
            ];

            if (strtolower($validated['payment_mode'] ?? '') === 'adjust in advance' && count($processedPayments) > 0) {
                $firstBookingId = $validated['booking_ids'][0];
                $firstBooking = FreightBooking::find($firstBookingId);
                $transporterId = $firstBooking?->transporter_id ?? $firstBooking?->lr?->transporter_id;
                if ($transporterId) {
                    $transporter = Transporter::find($transporterId);
                    if ($transporter) {
                        $payload['adjust_in_advance'] = true;
                        $payload['remaining_advance_balance'] = (float) $transporter->advance_amount;
                        $payload['transporter_name'] = $transporter->name;
                    }
                }
            }

            return response()->json($payload);
        } catch (ValidationException $e) {
            \DB::rollBack();
            \Log::error('Validation error in voucher payment', [
                'errors' => $e->errors(),
                'request_context' => $this->paymentRequestLogContext($request),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Voucher payment error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_context' => $this->paymentRequestLogContext($request),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment history for selected freight bookings
     *
     * @return JsonResponse
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $validated = $request->validate([
                'freight_booking_ids' => 'required|array',
                'freight_booking_ids.*' => 'required|integer|exists:freight_bookings,id',
            ]);

            $payments = VoucherPayment::with([
                'freight_booking.lr',
                'freight_booking.transporter',
                'pump',
            ])
                ->whereIn('freight_booking_id', $validated['freight_booking_ids'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'payments' => $payments,
            ]);
        } catch (\Exception $e) {
            \Log::error('Payment history fetch error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment history: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk assign memo ID to lorry receipts and update freight bookings
     *
     * @return RedirectResponse
     */
    public function bulkMemoId(Request $request)
    {
        try {
            $validated = $request->validate([
                'memo_id' => 'required|string|max:255',
                'lr_ids' => 'required|array',
                'lr_ids.*' => 'required|integer|exists:lorry_receipts,id',
                'freight_booking_ids' => 'required|array',
                'freight_booking_ids.*' => 'required|integer|exists:freight_bookings,id',
            ]);

            $customers = LorryReceipt::whereIn('id', $validated['lr_ids'])
                ->pluck('customer_id')
                ->unique();

            // ❌ If multiple customers found → return with warning
            if ($customers->count() > 1) {
                return redirect()->back()
                    ->with('warning', 'Selected Good Receipts (Bilty) belong to different customers. Please select LRs of the same customer only.')
                    ->withInput();
            }

            $customerId = $customers->first();

            // ✅ Check globally if this memo_id is already linked to a different customer
            $conflict = LorryReceipt::where('memo_id', $validated['memo_id'])
                ->where('company_id', auth()->user()->company_id)
                ->where('customer_id', '!=', $customerId)
                ->exists();

            if ($conflict) {
                return redirect()->back()
                    ->with('warning', 'This Memo ID is already used by another customer.')
                    ->withInput();
            }
            // ✅ Check if this memo_id is already linked to a different customer
            $conflictInvoiceGenerated = LorryReceipt::where('memo_id', $validated['memo_id'])
                ->where('company_id', auth()->user()->company_id)
                ->where('customer_id', $customerId)
                ->where('invoice_generated', 1)
                ->exists();

            if ($conflictInvoiceGenerated) {
                return redirect()->back()
                    ->with('warning', 'The invoice is generated on this memo id. Please choose a unique one.')
                    ->withInput();
            }

            \DB::transaction(function () use ($validated) {
                // Update memo_id and delivery_status for lorry receipts
                // Update memo_id and delivery_status for lorry receipts individually to trigger auditing
                $lorryReceipts = LorryReceipt::whereIn('id', $validated['lr_ids'])->get();
                foreach ($lorryReceipts as $lr) {
                    $oldDeliveryStatus = $lr->delivery_status;
                    $lr->memo_id = $validated['memo_id'];
                    $lr->delivery_status = 'Delivered';
                    $lr->save();

                    if ($oldDeliveryStatus !== 'Delivered') {
                        $lr->loadMissing(['company', 'truck', 'transporter.transporterMaster', 'customer']);
                        $this->lrNotificationService->notifyStatusChange($lr, 'Delivered', auth()->user());
                    }
                }

                // Update is_delivered for freight bookings individually to trigger auditing
                $freightBookings = FreightBooking::whereIn('id', $validated['freight_booking_ids'])->get();
                foreach ($freightBookings as $fb) {
                    $fb->is_delivered = 1;
                    $fb->save();
                }
            });

            return $this->redirectToFreightBookingsIndex($request, 'success', 'Memo ID assigned successfully to '.count($validated['lr_ids']).' good receipts (bilty) and '.count($validated['freight_booking_ids']).' good hires (lorry hire) updated.');
        } catch (\Exception $e) {
            \Log::error('Bulk memo ID assignment error: '.$e->getMessage());

            return redirect()->back()
                ->withErrors(['general' => 'An error occurred while assigning memo ID: '.$e->getMessage()]);
        }
    }
}
