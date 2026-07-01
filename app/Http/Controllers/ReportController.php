<?php

namespace App\Http\Controllers;

use App\Exports\BankExport;
use App\Exports\CashExport;
use App\Exports\ConsignorConsigneeExport;
use App\Exports\DispatchExport;
use App\Exports\EwayBillExport;
use App\Exports\InvoiceExport;
use App\Exports\MemoCopyExport;
use App\Exports\PartyBillExport;
use App\Exports\PartyLedgerExport;
use App\Exports\PendingInvoiceExport;
use App\Exports\PendingMemoExport;
use App\Exports\PumpExport;
use App\Exports\ShortageDamageExport;
use App\Exports\TDSExport;
use App\Exports\TransporterMasterExport;
use App\Exports\TransporterReportExport;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\PaymentMode;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use App\Support\FreightChallanTemplate;
use App\Support\LrCopyPdfTemplate;
use App\Support\MpdfDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private const REPORTS_PER_PAGE = 50;

    public function __construct()
    {
        // Company context is enforced by `company.context` route middleware.
    }

    /**
     * Normalize and limit a date range from the request.
     *
     * - Does NOT default a start date when it is missing.
     * - If either start_date or end_date is missing, no range is applied (flag = false).
     * - Ensures start_date <= end_date.
     * - Clamps the range so it does not exceed the given month limit (default: 3 months).
     *
     * @return array [startDate|null, endDate, hasValidRange(bool)]
     */
    private function normalizeDateRange(Request $request, int $monthsLimit = 3): array
    {
        $endInput = $request->input('end_date');
        $startInput = $request->input('start_date');

        // Always have an end date value for the view (defaults to today if not provided)
        $endDateForView = $endInput ?? date('Y-m-d');

        // Require both dates for an actionable range
        if (! $startInput || ! $endInput) {
            return [null, $endDateForView, false];
        }

        $start = Carbon::parse($startInput);
        $end = Carbon::parse($endInput);

        // Ensure start is not after end
        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        // Enforce maximum range length
        $clamped = false;
        if ($monthsLimit > 0) {
            $minStart = $end->copy()->subMonths($monthsLimit);
            if ($start->lt($minStart)) {
                $start = $minStart;
                $clamped = true;
            }
        }

        if ($clamped) {
            session()->flash('error', "Maximum allowed date range is {$monthsLimit} months. Your selection was limited automatically.");
        }

        return [$start->toDateString(), $end->toDateString(), true];
    }

    private function paginateCollection($items, Request $request, int $perPage = self::REPORTS_PER_PAGE, string $pageName = 'page'): LengthAwarePaginator
    {
        $collection = collect($items)->values();
        $currentPage = (int) $request->input($pageName, Paginator::resolveCurrentPage($pageName));
        $currentPage = $currentPage > 0 ? $currentPage : 1;
        $offset = ($currentPage - 1) * $perPage;
        $sliced = $collection->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $sliced,
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
                'query' => $request->query(),
            ]
        );
    }

    public function consignor_consignee(Request $request)
    {
        $companyId = Auth::user()->company_id;
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

        $consignorId = $request->input('consignor_id') ?? null;
        $consigneeId = $request->input('consignee_id') ?? null;

        $presetIds = array_values(array_unique(array_filter([
            $consignorId !== null && $consignorId !== '' ? (int) $consignorId : null,
            $consigneeId !== null && $consigneeId !== '' ? (int) $consigneeId : null,
        ])));

        $presetParties = collect();
        if ($presetIds !== [] && $companyId) {
            $presetParties = Consignee::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $presetIds)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->keyBy('id');
        }

        $presetConsignor = ($consignorId !== null && $consignorId !== '')
            ? $presetParties->get((int) $consignorId)
            : null;
        $presetConsignee = ($consigneeId !== null && $consigneeId !== '')
            ? $presetParties->get((int) $consigneeId)
            : null;

        if (($consignorId || $consigneeId) && $hasRange) {
            $query = LorryReceipt::query()
                ->with(['truck', 'consignor', 'consignee', 'transporter'])
                ->whereBetween('gr_date', [$startDate, $endDate]);

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            if ($consigneeId) {
                $query->where('consignee_id', $consigneeId);
            }

            if ($consignorId) {
                $query->where('consignor_id', $consignorId);
            }
            $query->orderBy('gr_date', 'asc');
            $lrs = $query->paginate(self::REPORTS_PER_PAGE)->appends($request->query());
        } else {
            $lrs = collect();
        }

        return view('reports.consignor-consignee', compact(
            'lrs',
            'startDate',
            'endDate',
            'consignorId',
            'consigneeId',
            'presetConsignor',
            'presetConsignee'
        ));
    }

    public function consignor_consignee_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $consignorId = $request->input('consignor_id');
        $consigneeId = $request->input('consignee_id');

        $export = Excel::download(new ConsignorConsigneeExport($startDate, $endDate, $consignorId, $consigneeId), 'consignor_consignee_report.xlsx');

        return $export;
    }

    public function tds(Request $request)
    {
        [$startDate, $endDate, $generated] = $this->normalizeDateRange($request);

        $user = Auth::user()->company_id;
        if ($generated) {
            $query = FreightBooking::whereHas('lr', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('gr_date', [$startDate, $endDate]);
            });
            if ($user) {
                $query->where('company_id', $user);
            }
            $query->where('tds_amount', '>', 0);
            $query->orderBy(function ($q) {
                $q->select('gr_date')
                    ->from('lorry_receipts')
                    ->whereColumn('lorry_receipts.id', 'freight_bookings.lr_id')
                    ->limit(1);
            }, 'asc');
            $lrs = $query->paginate(self::REPORTS_PER_PAGE)->appends($request->query());
            $generated = true;
        } else {
            $lrs = collect();
        }

        return view('reports.tds', compact('lrs', 'startDate', 'endDate', 'generated'));
    }

    public function tds_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $export = Excel::download(new TDSExport($startDate, $endDate), 'tds_report.xlsx');

        return $export;
    }

    public function dispatch(Request $request)
    {
        [$startDate, $endDate, $generated] = $this->normalizeDateRange($request);

        $user = Auth::user()->company_id;

        if ($generated) {
            // First, get the LorryReceipts with their freight bookings and voucher payments
            $query = LorryReceipt::with([
                'freightBookings' => function ($q) {
                    $q->with(['voucherPayments']);
                },
                'consignor',
                'consignee',
                'customer',
                'truck',
            ])->whereBetween('gr_date', [$startDate, $endDate]);

            if ($user) {
                $query->where('company_id', $user);
            }
            $query->orderBy('gr_date', 'asc');
            $lrs = $query->paginate(self::REPORTS_PER_PAGE)->appends($request->query());

        } else {
            $lrs = collect();
        }

        return view('reports.dispatch', compact('lrs', 'startDate', 'endDate', 'generated'));
    }

    public function dispatch_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $export = Excel::download(new DispatchExport($startDate, $endDate), 'dispatch_report.xlsx');

        return $export;
    }

    public function party_ledger(Request $request)
    {
        $companyId = Auth::user()->company_id;
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

        $customerId = $request->input('customer_id') ?? null;

        $presetCustomer = null;
        if ($customerId !== null && $customerId !== '' && $companyId) {
            $presetCustomer = Consignee::query()
                ->where('company_id', $companyId)
                ->whereKey((int) $customerId)
                ->first(['id', 'name']);
        }

        $invoiceByLrId = collect();

        if ($customerId && $hasRange) {
            $query = LorryReceipt::query()
                ->with('truck')
                ->whereBetween('gr_date', [$startDate, $endDate]);
            $query->where('invoice_generated', 1);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            $query->orderBy('gr_date', 'asc');
            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            $lrs = $query->paginate(self::REPORTS_PER_PAGE)->appends($request->query());

            $currentPageLrIds = collect($lrs->items())->pluck('id')->all();
            if (! empty($currentPageLrIds)) {
                $invoices = Invoice::query()
                    ->where('company_id', $companyId)
                    ->where(function ($invoiceQuery) use ($currentPageLrIds) {
                        foreach ($currentPageLrIds as $lrId) {
                            $invoiceQuery->orWhereJsonContains('lr_ids', (int) $lrId);
                        }
                    })
                    ->get([
                        'id',
                        'invoice_number',
                        'manual_invoice_number',
                        'generated_date',
                        'payment_status',
                        'invoice_type',
                        'lr_ids',
                    ]);

                $invoiceByLrId = $invoices
                    ->flatMap(function ($invoice) {
                        return collect($invoice->lr_ids ?? [])->mapWithKeys(function ($lrId) use ($invoice) {
                            return [(int) $lrId => $invoice];
                        });
                    });
            }
        } else {
            $lrs = collect();
        }

        return view('reports.party-ledger', compact('lrs', 'startDate', 'endDate', 'customerId', 'invoiceByLrId', 'presetCustomer'));
    }

    public function party_ledger_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $customerId = $request->input('customer_id');

        $export = Excel::download(new PartyLedgerExport($startDate, $endDate, $customerId), 'party_ledger_report.xlsx');

        return $export;
    }

    public function lr_copy(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $customerIdInput = $request->input('customer_id');

        $selectedCustomer = null;
        if ($customerIdInput !== null && $customerIdInput !== '') {
            $selectedCustomer = Consignee::query()
                ->where('company_id', $companyId)
                ->whereKey((int) $customerIdInput)
                ->first(['id', 'name']);
        }

        $customerId = $selectedCustomer?->id;
        $lr = null;
        $lr_id = $request->input('lr_id');
        if ($lr_id) {
            $lr = LorryReceipt::query()
                ->where('company_id', $companyId)
                ->find((int) $lr_id);
        }
        $companyDefaultTemplate = LrCopyPdfTemplate::normalizeKey(
            Auth::user()->company?->lr_copy_template ?? 'classic'
        );

        return view('reports.lr-copy', compact('lr', 'selectedCustomer', 'customerId', 'lr_id', 'companyDefaultTemplate'));
    }

    public function lr_copy_export(Request $request)
    {
        if (! $request->input('lr_id')) {
            return redirect()->back()->with('error', 'Please select a Good Receipt (Bilty) to export.');
        }

        $companyId = Auth::user()->company_id;
        $lr = LorryReceipt::query()
            ->with(['truck.transporter', 'consignor', 'consignee', 'company.city', 'company.state', 'company.country'])
            ->where('company_id', $companyId)
            ->find($request->input('lr_id'));

        if (! $lr) {
            return redirect()->back()->with('error', 'Good Receipt (Bilty) not found or access denied.');
        }

        $companyTemplate = LrCopyPdfTemplate::normalizeKey(
            Auth::user()->company?->lr_copy_template ?? 'classic'
        );
        $requested = $request->filled('template')
            ? LrCopyPdfTemplate::normalizeKey($request->input('template'))
            : $companyTemplate;

        $view = LrCopyPdfTemplate::viewName($requested);
        $company = $lr->company ?? Auth::user()->company;
        $company?->loadMissing(['city', 'state', 'country']);

        $html = view($view, ['lr' => $lr, 'company' => $company])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        return $pdf->stream('lr_copy.pdf');
    }

    public function invoice(Request $request)
    {
        [$startDate, $endDate, $generated] = $this->normalizeDateRange($request);

        $user = Auth::user()->company_id;
        $invoices = collect();
        if ($generated) {
            $query = Invoice::with(['consignee', 'consignee.state', 'company'])
                ->whereBetween('generated_date', [$startDate, $endDate]);

            if ($user) {
                $query->where('company_id', $user);
            }
            $query->orderBy('generated_date', 'asc');
            $invoices = $query->paginate(self::REPORTS_PER_PAGE)->appends($request->query());
        }

        return view('reports.invoice', compact('invoices', 'startDate', 'endDate', 'generated'));
    }

    public function invoice_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $export = Excel::download(new InvoiceExport($startDate, $endDate), 'GSTR1.xlsx');

        return $export;
    }

    public function shortage_damage(Request $request)
    {
        [$startDate, $endDate, $generated] = $this->normalizeDateRange($request);

        $user = Auth::user()->company_id;
        $invoices = [];

        if ($generated) {
            // First, get the LorryReceipts with their freight bookings and voucher payments
            $query = LorryReceipt::with([
                'freightBookings' => function ($q) {
                    $q->with(['voucherPayments'])
                        ->where(function ($sub) {
                            $sub->where('shortage_amount', '>', 0)
                                ->orWhere('damage_amount', '>', 0);
                        });
                },
                'consignor',
                'consignee',
                'customer',
                'truck',
            ])
                ->whereBetween('gr_date', [$startDate, $endDate])
                ->whereHas('freightBookings', function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('shortage_amount', '>', 0)
                            ->orWhere('damage_amount', '>', 0);
                    });
                });

            if ($user) {
                $query->where('company_id', $user);
            }
            $query->orderBy('gr_date', 'asc');
            $lrs = $query->paginate(self::REPORTS_PER_PAGE)->appends($request->query());

        } else {
            $lrs = collect();
        }

        return view('reports.shortage-damage', compact('lrs', 'startDate', 'endDate', 'generated'));
    }

    public function shortage_damage_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $export = Excel::download(new ShortageDamageExport($startDate, $endDate), 'shortage_damage_report.xlsx');

        return $export;
    }

    public function eway_bill(Request $request)
    {
        [$startDate, $endDate, $generated] = $this->normalizeDateRange($request);

        $user = Auth::user()->company_id;
        $invoices = [];

        if ($generated) {
            // First, get the LorryReceipts with their freight bookings and voucher payments
            $query = FreightBooking::whereBetween('eway_bill_expiry', [$startDate, $endDate]);
            if ($user) {
                $query->where('company_id', $user);
            }
            $query->orderBy('eway_bill_expiry', 'asc');
            $frs = $query->paginate(self::REPORTS_PER_PAGE)->appends($request->query());
        } else {
            $frs = collect();
        }

        return view('reports.eway-bill', compact('frs', 'startDate', 'endDate', 'generated'));
    }

    public function eway_bill_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $export = Excel::download(new EwayBillExport($startDate, $endDate), 'eway_bill_report.xlsx');

        return $export;
    }

    public function pump(Request $request)
    {
        $pumps = Pump::where('user_id', Auth::user()->company->user_id)->get();
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

        $pumpId = $request->input('pump_id');
        $paymentStatus = $request->input('payment_status');
        $companyId = Auth::user()->company_id;

        $pumpData = collect(); // default empty collection

        $grandTotalAmount = 0;
        if ($pumpId && $hasRange) {
            // --- Step 1: Fetch records from voucher_payments where linked_voucher_id is null ---
            $voucherPaymentsQuery = VoucherPayment::with(['freight_booking.lr'])
                ->where('pump_id', $pumpId)
                ->where('company_id', $companyId)
                ->whereNull('linked_voucher_id')
                ->whereBetween('date', [$startDate, $endDate]);

            if ($paymentStatus) {
                $voucherPaymentsQuery->where('payment_status', $paymentStatus);
            }

            $voucherPayments = $voucherPaymentsQuery->get();

            // --- Step 2: Fetch all records from voucher_linked table ---
            $linkedPaymentsQuery = VoucherLinked::with(['freight_booking.lr'])
                ->where('pump_id', $pumpId)
                ->where('company_id', $companyId)
                ->whereBetween('date', [$startDate, $endDate]);

            if ($paymentStatus) {
                $linkedPaymentsQuery->where('payment_status', $paymentStatus);
            }

            $linkedPayments = $linkedPaymentsQuery->get();

            // --- Step 3: Prepare merged data from unlinked payments ---
            $unlinkedData = $voucherPayments->map(function ($payment) {
                $lrDetails = LorryReceipt::where('freight_booking_id', $payment->freight_booking_id)
                    ->with(['truck:id,truck_number'])
                    ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id', 'destination_city')
                    ->get();

                return [
                    'freight_booking_id' => $payment->freight_booking_id,
                    'amount' => $payment->amount,
                    'lrs' => $lrDetails,
                    'date' => Carbon::parse($payment->date),
                    'type' => 'Unlinked',
                    'payment_status' => $payment->payment_status ?? 'N/A',
                ];
            });

            // --- Step 4: Prepare merged data from linked payments ---
            $linkedData = $linkedPayments->map(function ($linked) {
                $lrDetails = LorryReceipt::where('freight_booking_id', $linked->freight_booking_id)
                    ->with(['truck:id,truck_number'])
                    ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id', 'destination_city')
                    ->get();

                return [
                    'freight_booking_id' => $linked->freight_booking_id,
                    'amount' => $linked->amount,
                    'lrs' => $lrDetails,
                    'date' => Carbon::parse($linked->date),
                    'type' => 'Linked',
                    'payment_status' => $linked->payment_status ?? 'N/A',
                ];
            });

            // --- Step 5: Merge and sort by date ---
            $pumpData = $unlinkedData->merge($linkedData)
                ->sortBy('date')
                ->map(function ($item) {
                    $item['date'] = $item['date']->format('d-m-Y');

                    return $item;
                })
                ->values();
            $grandTotalAmount = (float) $pumpData->sum('amount');
        }

        $pumpData = $this->paginateCollection($pumpData, $request);

        // --- Step 3: Return view ---
        return view('reports.pump', compact('pumpData', 'startDate', 'endDate', 'pumpId', 'pumps', 'paymentStatus', 'grandTotalAmount'));
    }

    public function pump_export(Request $request)
    {
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);
        $pumpId = $request->input('pump_id');
        $paymentStatus = $request->input('payment_status');
        $companyId = Auth::user()->company_id;

        if (! $pumpId || ! $hasRange) {
            return redirect()->route('reports.pump')
                ->with('error', 'Please select a pump and date range to export.');
        }

        $voucherPaymentsQuery = VoucherPayment::with(['freight_booking.lr'])
            ->where('pump_id', $pumpId)
            ->where('company_id', $companyId)
            ->whereNull('linked_voucher_id')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($paymentStatus) {
            $voucherPaymentsQuery->where('payment_status', $paymentStatus);
        }

        $voucherPayments = $voucherPaymentsQuery->get();

        $linkedPaymentsQuery = VoucherLinked::with(['freight_booking.lr'])
            ->where('pump_id', $pumpId)
            ->where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($paymentStatus) {
            $linkedPaymentsQuery->where('payment_status', $paymentStatus);
        }

        $linkedPayments = $linkedPaymentsQuery->get();

        $unlinkedData = $voucherPayments->map(function ($payment) {
            $lrDetails = LorryReceipt::where('freight_booking_id', $payment->freight_booking_id)
                ->with(['truck:id,truck_number'])
                ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id', 'destination_city')
                ->get();

            return [
                'freight_booking_id' => $payment->freight_booking_id,
                'amount' => $payment->amount,
                'lrs' => $lrDetails,
                'date' => Carbon::parse($payment->date),
                'type' => 'Unlinked',
                'payment_status' => $payment->payment_status ?? 'N/A',
            ];
        });

        $linkedData = $linkedPayments->map(function ($linked) {
            $lrDetails = LorryReceipt::where('freight_booking_id', $linked->freight_booking_id)
                ->with(['truck:id,truck_number'])
                ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id', 'destination_city')
                ->get();

            return [
                'freight_booking_id' => $linked->freight_booking_id,
                'amount' => $linked->amount,
                'lrs' => $lrDetails,
                'date' => Carbon::parse($linked->date),
                'type' => 'Linked',
                'payment_status' => $linked->payment_status ?? 'N/A',
            ];
        });

        $rawPumpData = $unlinkedData->merge($linkedData)->sortBy('date')->values();

        return Excel::download(new PumpExport($rawPumpData, $startDate, $endDate, $pumpId), 'pump_report.xlsx');
    }

    /**
     * Memo IDs for the memo-copy report customer filter (same data as invoices all-memos, but allowed for report roles including accountant).
     */
    public function customer_memos_json(int $customerId): JsonResponse
    {
        $memos = LorryReceipt::query()
            ->where('company_id', Auth::user()->company_id)
            ->where('customer_id', $customerId)
            ->whereNotNull('memo_id')
            ->distinct()
            ->pluck('memo_id');

        return response()->json($memos);
    }

    public function memo_copy(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $customerId = $request->input('customer_id') ?? null;
        $memo_id = $request->input('memo_id') ?? null;

        $presetCustomer = null;
        if ($customerId !== null && $customerId !== '' && $companyId) {
            $presetCustomer = Consignee::query()
                ->where('company_id', $companyId)
                ->whereKey((int) $customerId)
                ->first(['id', 'name']);
        }

        $memo_ids = [];
        $lrs = [];
        if ($customerId) {
            $memo_ids = LorryReceipt::where('customer_id', $customerId)
                ->where('company_id', $companyId)
                ->whereNotNull('memo_id')
                ->pluck('memo_id')->unique();
            $lrs = LorryReceipt::where('company_id', $companyId)->where('memo_id', $memo_id)->get();
        }

        return view('reports.memo-copy', compact('presetCustomer', 'customerId', 'memo_id', 'memo_ids', 'lrs'));
    }

    public function memo_copy_export(Request $request)
    {
        if (! $request->input('memo_id')) {
            return redirect()->back()->with('error', 'Please select a Memo to export.');
        }

        $memo_id = $request->input('memo_id');
        $exportType = $request->input('type', 'excel'); // Default to excel for backward compatibility

        try {
            if ($exportType === 'pdf') {
                $lrs = LorryReceipt::where('memo_id', $memo_id)->get();

                if ($lrs->isEmpty()) {
                    return redirect()->back()->with('error', 'No data found for the selected memo.');
                }

                // Generate PDF
                $html = view('reports.exports.memo-copy-pdf', compact('lrs', 'memo_id'))->render();
                $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

                return $pdf->stream("memo_copy_{$memo_id}.pdf");
            } else {
                // Existing Excel export
                return Excel::download(new MemoCopyExport($memo_id), 'memo_copy.xlsx');
            }
        } catch (\Exception $e) {
            Log::error('Error exporting memo copy: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error generating export: '.$e->getMessage());
        }
    }

    public function pending_memo(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $customerId = $request->input('customer_id');

            $presetCustomer = null;
            if ($customerId !== null && $customerId !== '' && $companyId) {
                $presetCustomer = Consignee::query()
                    ->where('company_id', $companyId)
                    ->whereKey((int) $customerId)
                    ->first(['id', 'name']);
            }
            $status = $request->input('status', 'delivered'); // in-progress, delivered, both
            [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

            $lrsQuery = LorryReceipt::with(['truck:id,truck_number', 'customer:id,name'])
                ->where('company_id', $companyId)
                ->whereNull('memo_id');

            if ($customerId) {
                $lrsQuery->where('customer_id', $customerId);
            }

            if ($hasRange) {
                $lrsQuery->whereBetween('gr_date', [$startDate, $endDate]);
            }

            $normalizedStatus = strtolower($status);
            if ($normalizedStatus === 'in-progress') {
                $lrsQuery->where('delivery_status', 'In-Progress');
            } elseif ($normalizedStatus === 'delivered') {
                $lrsQuery->where('delivery_status', 'Delivered');
            } elseif ($normalizedStatus === 'both') {
                $lrsQuery->whereIn('delivery_status', ['In-Progress', 'Delivered']);
            }

            $lrs = $lrsQuery->orderBy('gr_date', 'desc')
                ->paginate(self::REPORTS_PER_PAGE)
                ->appends($request->query());

            return view('reports.pending-memo', [
                'customerId' => $customerId,
                'presetCustomer' => $presetCustomer,
                'lrs' => $lrs,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in pending_memo: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing your request.');
        }
    }

    public function pendingMemoExport(Request $request)
    {

        // Optionally get customer_id if you want to filter by customer as well
        $customerId = $request->input('customer_id');
        $status = $request->input('status', 'delivered');
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

        return Excel::download(
            new PendingMemoExport(
                $customerId,
                $hasRange ? $startDate : null,
                $hasRange ? $endDate : null,
                $status
            ),
            'pending-memo.xlsx'
        );
    }

    public function pending_invoice(Request $request)
    {
        try {
            $companyId = Auth::user()->company_id;
            $customerId = $request->input('customer_id');

            $presetCustomer = null;
            if ($customerId !== null && $customerId !== '' && $companyId) {
                $presetCustomer = Consignee::query()
                    ->where('company_id', $companyId)
                    ->whereKey((int) $customerId)
                    ->first(['id', 'name']);
            }
            [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

            $lrsQuery = LorryReceipt::with(['truck:id,truck_number', 'customer:id,name'])
                ->where('company_id', $companyId)
                ->whereNotNull('memo_id')
                ->where('invoice_generated', 0)
                ->where('delivery_status', 'Delivered');

            if ($customerId) {
                $lrsQuery->where('customer_id', $customerId);
            }

            if ($hasRange) {
                $lrsQuery->whereBetween('gr_date', [$startDate, $endDate]);
            }

            $lrs = $lrsQuery->orderBy('gr_date', 'desc')
                ->paginate(self::REPORTS_PER_PAGE)
                ->appends($request->query());

            return view('reports.pending-invoice', [
                'customerId' => $customerId,
                'presetCustomer' => $presetCustomer,
                'lrs' => $lrs,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in pending_invoice: '.$e->getMessage());

            return redirect()->back()->with('error', 'An error occurred while processing your request.');
        }
    }

    public function pendingInvoiceExport(Request $request)
    {

        // Optionally get customer_id if you want to filter by customer as well
        $customerId = $request->input('customer_id');
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

        return Excel::download(
            new PendingInvoiceExport(
                $customerId,
                $hasRange ? $startDate : null,
                $hasRange ? $endDate : null
            ),
            'pending-invoice.xlsx'
        );
    }

    public function bank(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $paymentStatus = $request->input('payment_status');
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

        if ($hasRange) {
            $query = VoucherPayment::with(['freight_booking.lr.truck', 'freight_booking.lr.transporter.transporterMaster'])
                ->whereBetween('date', [$startDate, $endDate]);
            $query->where('company_id', $companyId);
            $query->whereNull('linked_voucher_id');
            $query->where('payment_mode', 'bank transfer');

            if ($paymentStatus) {
                $query->where('payment_status', $paymentStatus);
            }

            $payments = $query->get();

            $query1 = VoucherLinked::with(['freight_booking.lr.truck', 'freight_booking.lr.transporter.transporterMaster'])
                ->whereBetween('date', [$startDate, $endDate]);
            $query1->where('company_id', $companyId);
            $query1->where('payment_mode', 'bank transfer');

            if ($paymentStatus) {
                $query1->where('payment_status', $paymentStatus);
            }

            $linked_payments = $query1->get();
        }
        $allPayments = [];
        if (isset($payments)) {
            foreach ($payments as $payment) {
                $freightBooking = $payment->freight_booking;
                $lr = $freightBooking?->lr;
                $transporter = $lr?->transporter;
                $truck = $lr?->truck;
                $tds_deducted = ($freightBooking?->tds_amount ?? 0) == 0 ? 'No' : 'Yes';
                $allPayments[] = [
                    'date' => $payment->date,
                    'lr_number' => $lr?->lr_number ?? 'N/A',
                    'gr_number' => $lr?->gr_number ?? 'N/A',
                    'truck_number' => $truck?->truck_number ?? 'N/A',
                    'transporter_name' => $transporter?->name ?? 'N/A',
                    'name' => $transporter?->short_name ?? 'N/A',
                    'beneficiary_id' => $transporter?->beneficiary_id ?? 'N/A',
                    'amount' => $payment['amount'],
                    'tds_deducted' => $tds_deducted,
                    'payment_status' => $payment->payment_status ?? 'N/A',
                ];
            }
        }
        if (isset($linked_payments)) {
            foreach ($linked_payments as $linked_payment) {
                $freightBooking = $linked_payment->freight_booking;
                $lr = $freightBooking?->lr;
                $transporter = $lr?->transporter;
                $truck = $lr?->truck;
                $tds_deducted = ($freightBooking?->tds_amount ?? 0) == 0 ? 'No' : 'Yes';
                $allPayments[] = [
                    'date' => $linked_payment->date,
                    'lr_number' => $lr?->lr_number ?? 'N/A',
                    'gr_number' => $lr?->gr_number ?? 'N/A',
                    'truck_number' => $truck?->truck_number ?? 'N/A',
                    'transporter_name' => $transporter?->name ?? 'N/A',
                    'name' => $transporter?->short_name ?? 'N/A',
                    'beneficiary_id' => $transporter?->beneficiary_id ?? 'N/A',
                    'amount' => $linked_payment['amount'],
                    'tds_deducted' => $tds_deducted,
                    'payment_status' => $linked_payment->payment_status ?? 'N/A',
                ];
            }
        }
        $allPayments = collect($allPayments)->sortBy('date')->values();
        $grandTotalAmount = (float) $allPayments->sum('amount');
        $allPayments = $this->paginateCollection($allPayments, $request);

        return view('reports.bank', compact('startDate', 'endDate', 'allPayments', 'paymentStatus', 'grandTotalAmount'));
    }

    public function bank_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $paymentStatus = $request->input('payment_status');

        $export = Excel::download(new BankExport($startDate, $endDate, $paymentStatus), 'bank_report.xlsx');

        return $export;
    }

    public function cash(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $paymentStatus = $request->input('payment_status');
        $branchId = $request->input('branch');
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

        $branchesQuery = Branch::orderBy('branch_name');
        if ($companyId) {
            $branchesQuery->where('company_id', $companyId);
        }
        $branches = $branchesQuery->get();

        if ($hasRange) {
            $query = VoucherPayment::with(['freight_booking.lr.truck', 'freight_booking.lr.transporter.transporterMaster'])
                ->whereBetween('date', [$startDate, $endDate]);
            $query->where('company_id', $companyId);
            $query->whereNull('linked_voucher_id');
            $query->where('payment_mode', 'cash');

            $query1 = VoucherLinked::with(['freight_booking.lr.truck', 'freight_booking.lr.transporter.transporterMaster'])
                ->whereBetween('date', [$startDate, $endDate]);
            $query1->where('company_id', $companyId);
            $query1->where('payment_mode', 'cash');

            if ($paymentStatus) {
                $query->where('payment_status', $paymentStatus);
                $query1->where('payment_status', $paymentStatus);
            }

            if ($branchId) {
                $query->whereHas('creator', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
                $query1->whereHas('creator', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            $payments = $query->get();
            $linked_payments = $query1->get();
        }
        $allPayments = [];
        if (isset($payments)) {
            foreach ($payments as $payment) {
                $freightBooking = $payment->freight_booking;
                $lr = $freightBooking?->lr;
                $transporter = $lr?->transporter;
                $truck = $lr?->truck;
                $tds_deducted = ($freightBooking?->tds_amount ?? 0) == 0 ? 'No' : 'Yes';
                $allPayments[] = [
                    'date' => $payment->date,
                    'lr_number' => $lr?->lr_number ?? 'N/A',
                    'gr_number' => $lr?->gr_number ?? 'N/A',
                    'truck_number' => $truck?->truck_number ?? 'N/A',
                    'transporter_name' => $transporter?->name ?? 'N/A',
                    'name' => $transporter?->short_name ?? 'N/A',
                    'beneficiary_id' => $transporter?->beneficiary_id ?? 'N/A',
                    'amount' => $payment['amount'],
                    'tds_deducted' => $tds_deducted,
                    'payment_status' => $payment->payment_status ?? 'N/A',
                ];
            }
        }
        if (isset($linked_payments)) {
            foreach ($linked_payments as $linked_payment) {
                $freightBooking = $linked_payment->freight_booking;
                $lr = $freightBooking?->lr;
                $transporter = $lr?->transporter;
                $truck = $lr?->truck;
                $tds_deducted = ($freightBooking?->tds_amount ?? 0) == 0 ? 'No' : 'Yes';
                $allPayments[] = [
                    'date' => $linked_payment->date,
                    'lr_number' => $lr?->lr_number ?? 'N/A',
                    'gr_number' => $lr?->gr_number ?? 'N/A',
                    'truck_number' => $truck?->truck_number ?? 'N/A',
                    'transporter_name' => $transporter?->name ?? 'N/A',
                    'name' => $transporter?->short_name ?? 'N/A',
                    'beneficiary_id' => $transporter?->beneficiary_id ?? 'N/A',
                    'amount' => $linked_payment['amount'],
                    'tds_deducted' => $tds_deducted,
                    'payment_status' => $linked_payment->payment_status ?? 'N/A',
                ];
            }
        }
        $allPayments = collect($allPayments)->sortBy('date')->values();
        $grandTotalAmount = (float) $allPayments->sum('amount');
        $allPayments = $this->paginateCollection($allPayments, $request);

        return view('reports.cash', compact('startDate', 'endDate', 'allPayments', 'paymentStatus', 'grandTotalAmount', 'branches'));
    }

    public function cash_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $paymentStatus = $request->input('payment_status');
        $branchId = $request->input('branch');

        $export = Excel::download(new CashExport($startDate, $endDate, $paymentStatus, $branchId), 'cash_report.xlsx');

        return $export;
    }

    public function voucher_receipt_export(Request $request)
    {
        if (! $request->input('lr_id')) {
            return redirect()->back()->with('error', 'Please select a Good Receipt (Bilty) to export.');
        }

        $companyId = Auth::user()->company_id;
        $lrRelations = [
            'truck.driver',
            'transporter.transporterMaster',
            'consignor',
            'consignee',
            'freightBookings',
        ];

        $lr = LorryReceipt::with($lrRelations)
            ->where('company_id', $companyId)
            ->find($request->input('lr_id'));

        if (! $lr) {
            return redirect()->back()->with('error', 'Good Receipt (Bilty) not found or access denied.');
        }

        if ($lr->linked_lr_id) {
            $lrs = LorryReceipt::with($lrRelations)
                ->where('linked_lr_id', $lr->linked_lr_id)
                ->where('company_id', $companyId)
                ->get();
            $allHaveFreightBooking = $lrs->every(function ($item) {
                return ! is_null($item->freight_booking_id);
            });

            if (! $allHaveFreightBooking) {
                return redirect()->back()->with('error', 'Create all Good Hires (Lorry Hire) for linked Good Receipts (Bilty) before exporting the Voucher Receipt.');
            }
        } else {
            $lrs = collect([$lr]);
        }

        $company = Company::find($companyId);
        // Export should not hard-fail if voucher template fields are empty.
        // Use fallback values to keep PDF generation + UI flow working.
        if (! $company->template_name) {
            $company->template_name = $company->name;
        }

        if (! $company->template_address) {
            $company->template_address = $company->address ?? 'Sample registered office address';
        }

        $templateKey = FreightChallanTemplate::normalizeKey(
            $request->input('template') ?? ($company->freight_challan_template ?? 'classic')
        );
        $view = FreightChallanTemplate::viewName($templateKey);

        $html = view($view, compact('lrs', 'company'))->render();

        $mpdf = MpdfDocument::make();
        $mpdf->WriteHTML($html);

        return $mpdf->Output('voucher_receipt.pdf', 'I');
    }

    public function markVoucherReceiptPrinted(Request $request)
    {
        $validated = $request->validate([
            'freight_booking_id' => ['required', 'integer', 'exists:freight_bookings,id'],
        ]);

        $freightBooking = FreightBooking::find($validated['freight_booking_id']);
        if (! $freightBooking) {
            return response()->json(['message' => 'Freight Booking not found.'], 404);
        }

        if (Auth::user()->company_id && $freightBooking->company_id !== Auth::user()->company_id) {
            return response()->json(['message' => 'Unauthorized access to this freight booking.'], 403);
        }

        $printTime = now();
        $query = FreightBooking::query();

        if (Auth::user()->company_id) {
            $query->where('company_id', Auth::user()->company_id);
        }

        if ($freightBooking->linked_lr_id) {
            $query->where('linked_lr_id', $freightBooking->linked_lr_id);
        } else {
            $query->where('id', $freightBooking->id);
        }

        $query->update([
            'voucher_printed' => true,
            'voucher_last_printed_at' => $printTime,
            'updated_at' => $printTime,
        ]);

        return response()->json([
            'message' => 'Voucher print status updated.',
            'voucher_printed' => true,
            'voucher_last_printed_at' => $printTime->format('d-m-Y h:i A'),
        ]);
    }

    public function party_bill(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $requestCustomerId = $request->input('customer_id');
        $requestCustomerId = ($requestCustomerId !== null && $requestCustomerId !== '') ? $requestCustomerId : null;

        $id = $request->input('invoice_number') ?? null;
        $invoice = null;
        if ($id !== null && $id !== '' && $companyId) {
            $invoice = Invoice::query()
                ->where('company_id', $companyId)
                ->whereKey($id)
                ->first();
        }

        $customerId = $requestCustomerId;
        if ($customerId === null && $invoice?->customer_id) {
            $customerId = (string) $invoice->customer_id;
        }

        $presetCustomer = null;
        if ($customerId !== null && $customerId !== '' && $companyId) {
            $presetCustomer = Consignee::query()
                ->where('company_id', $companyId)
                ->whereKey((int) $customerId)
                ->first(['id', 'name']);
        }

        $selectedCompany = Company::find($companyId);
        $selectedInvoiceTemplate = (string) ($selectedCompany->invoice_template ?? 'classic');
        $selectedInvoiceTemplateLabel = match ($selectedInvoiceTemplate) {
            'modern' => 'B (Modern)',
            'compact' => 'C (Compact)',
            default => 'A (Classic)',
        };

        $invoices = collect();
        if ($customerId !== null && $customerId !== '' && $companyId) {
            $invoices = Invoice::query()
                ->where('company_id', $companyId)
                ->where('customer_id', (int) $customerId)
                ->orderByDesc('id')
                ->limit(5000)
                ->get(['id', 'manual_invoice_number', 'invoice_number']);
        }
        $customDetails = collect();
        if ($invoice && $invoice->custom_invoice) {
            $invoice->load(['customInvoiceDetails.lorryReceipt.truck']);
            $customDetails = $invoice->customInvoiceDetails->sortBy('sort_order')->values();
        }

        return view('reports.party-bill', compact(
            'presetCustomer',
            'customerId',
            'id',
            'invoices',
            'invoice',
            'customDetails',
            'selectedInvoiceTemplateLabel'
        ));
    }

    public function party_bill_export(Request $request)
    {
        if (! $request->input('invoice_number')) {
            return redirect()->back()->with('error', 'Please select an Invoice to export.');
        }

        $id = $request->input('invoice_number');
        $exportType = $request->input('type', 'excel');
        $resolvedTemplateKey = 'classic';

        if ($exportType === 'pdf') {
            $selectedCompany = Company::find(Auth::user()->company_id);
            $companyTemplate = (string) ($selectedCompany->invoice_template ?? 'classic');

            $resolvedTemplateKey = match ($companyTemplate) {
                'modern' => 'modern',
                'compact' => 'compact',
                'classic' => 'classic',
                default => 'classic',
            };
        }

        $resolvedTemplateKey = match ($resolvedTemplateKey) {
            'modern' => 'modern',
            'compact' => 'compact',
            'classic' => 'classic',
            default => 'classic',
        };

        try {
            if ($exportType === 'pdf') {
                $invoice = Invoice::with([
                    'consignee.city',
                    'consignee.state',
                    'company.city',
                    'company.state',
                ])->findOrFail($id);

                $customDetails = collect();
                $lrs = collect();

                if ($invoice->custom_invoice) {
                    $invoice->load(['customInvoiceDetails.lorryReceipt.truck']);
                    $customDetails = $invoice->customInvoiceDetails->sortBy('sort_order')->values();
                } else {
                    $lrIds = $invoice->lr_ids ?? [];
                    if (! empty($lrIds)) {
                        $lrs = LorryReceipt::whereIn('id', $lrIds)
                            ->with(['consignor', 'consignee', 'truck'])
                            ->get();
                    }
                    if ($lrs->isEmpty()) {
                        return redirect()->back()->with('error', 'No Good Receipts (Bilty) found for this invoice.');
                    }
                }

                $baseAmount = $invoice->base_amount ?? $invoice->total_amount ?? 0;
                if ($baseAmount == 0 && ! $invoice->custom_invoice && $lrs->isNotEmpty()) {
                    $baseAmount = $lrs->sum('actual_amount');
                }
                $cgst = $invoice->cgst ?? 0;
                $sgst = $invoice->sgst ?? 0;
                $igst = $invoice->igst ?? 0;
                if ($cgst == 0 && $sgst == 0 && $igst == 0 && $baseAmount > 0) {
                    $company = $invoice->company;
                    $gstSlab = (int) ($company->gst_slab ?? 5);
                    $gstRate = $gstSlab / 100;
                    $cgst = $baseAmount * ($gstRate / 2);
                    $sgst = $baseAmount * ($gstRate / 2);
                    $igst = 0;
                }
                $totalAmount = $baseAmount;

                $templateView = match ($resolvedTemplateKey) {
                    'modern' => 'reports.exports.party-bill-pdf-modern',
                    'compact' => 'reports.exports.party-bill-pdf-compact',
                    default => 'reports.exports.party-bill-pdf',
                };

                $html = view($templateView, array_merge(compact(
                    'invoice',
                    'lrs',
                    'customDetails',
                    'totalAmount',
                    'cgst',
                    'sgst',
                    'igst'
                ), [
                    'partyBillInvoiceUsePdfLogoPath' => true,
                ]))->render();

                $pdf = Pdf::loadHTML($html)
                    ->setPaper('a4', 'portrait')
                    ->setOption('isHtml5ParserEnabled', true)
                    ->setOption('isRemoteEnabled', true);

                $invoiceIdentifier = $invoice->invoice_number
                    ? $invoice->invoice_number.($invoice->manual_invoice_number ? ' ('.$invoice->manual_invoice_number.')' : '')
                    : ($invoice->manual_invoice_number ?? $invoice->id);

                $safeInvoiceIdentifier = preg_replace('/[\\\\\/]+/', '-', (string) $invoiceIdentifier) ?? (string) $invoice->id;
                $safeInvoiceIdentifier = trim($safeInvoiceIdentifier);

                if ($safeInvoiceIdentifier === '') {
                    $safeInvoiceIdentifier = (string) $invoice->id;
                }

                return $pdf->stream('party_bill_'.$safeInvoiceIdentifier.'.pdf');
            } else {
                $invoice = Invoice::findOrFail($id);
                $invoiceIdentifier = $invoice->invoice_number
                    ? $invoice->invoice_number.($invoice->manual_invoice_number ? ' ('.$invoice->manual_invoice_number.')' : '')
                    : ($invoice->manual_invoice_number ?? $invoice->id);
                $safeInvoiceIdentifier = preg_replace('/[\\\\\/]+/', '-', (string) $invoiceIdentifier) ?? (string) $invoice->id;
                $safeInvoiceIdentifier = trim($safeInvoiceIdentifier);
                if ($safeInvoiceIdentifier === '') {
                    $safeInvoiceIdentifier = (string) $invoice->id;
                }

                return Excel::download(new PartyBillExport($invoice), $safeInvoiceIdentifier.'.xlsx');
            }
        } catch (\Exception $e) {
            Log::error('Error in party_bill_export:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invoice_id' => $id,
                'type' => $exportType ?? 'unknown',
            ]);

            return redirect()->back()
                ->with('error', 'Error generating export: '.$e->getMessage());
        }
    }

    public function transporter_report(Request $request)
    {
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

        $transporterId = $request->filled('transporter_id') ? (int) $request->input('transporter_id') : null;
        $truckId = $request->filled('truck_id') ? (int) $request->input('truck_id') : null;
        $referenceId = $request->filled('reference_by') ? (int) $request->input('reference_by') : null;

        $transporters = collect();
        $trucks = collect();
        $references = collect();

        if ($hasRange) {
            $options = $this->getTransporterReportFilterOptions($startDate, $endDate, $transporterId, $truckId, $referenceId);
            $transporters = $options['transporters'];
            $trucks = $options['trucks'];
            $references = $options['references'];

            if ($transporterId && ! $transporters->pluck('id')->contains($transporterId)) {
                $transporterId = null;
            }
            if ($truckId && ! $trucks->pluck('id')->contains($truckId)) {
                $truckId = null;
            }
            if ($referenceId && ! $references->pluck('id')->contains($referenceId)) {
                $referenceId = null;
            }

            $lrs = $this->buildTransporterReportBaseQuery($startDate, $endDate)
                ->with([
                    'truck:id,truck_number',
                    'consignor:id,name',
                    'consignee:id,name',
                    'freightBooking.voucherPayments:id,freight_booking_id,payment_mode,amount',
                ])
                ->when($transporterId, function ($query) use ($transporterId) {
                    $query->where('transporter_id', $transporterId);
                })
                ->when($truckId, function ($query) use ($truckId) {
                    $query->where('truck_id', $truckId);
                })
                ->when($referenceId, function ($query) use ($referenceId) {
                    $query->where('reference_by', $referenceId);
                })
                ->orderBy('gr_date', 'asc')
                ->paginate(self::REPORTS_PER_PAGE)
                ->appends($request->query());
        } else {
            $lrs = collect();
        }

        $paymentNames = PaymentMode::query()->orderBy('id')->pluck('name')->all();

        return view('reports.transporter-report', compact(
            'transporters',
            'transporterId',
            'startDate',
            'endDate',
            'trucks',
            'truckId',
            'lrs',
            'references',
            'referenceId',
            'paymentNames'
        ));
    }

    private function buildTransporterReportBaseQuery(?string $startDate, ?string $endDate)
    {
        $query = LorryReceipt::query()
            ->whereNotNull('freight_booking_id');

        if (Auth::user()->company_id) {
            $query->where('company_id', Auth::user()->company_id);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('gr_date', [$startDate, $endDate]);
        }

        return $query;
    }

    private function getTransporterReportFilterOptions(
        string $startDate,
        string $endDate,
        ?int $transporterId,
        ?int $truckId,
        ?int $referenceId
    ): array {
        $baseQuery = $this->buildTransporterReportBaseQuery($startDate, $endDate);

        $transporterIdSubquery = (clone $baseQuery)
            ->when($truckId, function ($query) use ($truckId) {
                $query->where('truck_id', $truckId);
            })
            ->when($referenceId, function ($query) use ($referenceId) {
                $query->where('reference_by', $referenceId);
            })
            ->whereNotNull('transporter_id')
            ->select('transporter_id')
            ->distinct();

        $truckIdSubquery = (clone $baseQuery)
            ->when($transporterId, function ($query) use ($transporterId) {
                $query->where('transporter_id', $transporterId);
            })
            ->when($referenceId, function ($query) use ($referenceId) {
                $query->where('reference_by', $referenceId);
            })
            ->whereNotNull('truck_id')
            ->select('truck_id')
            ->distinct();

        $referenceIdSubquery = (clone $baseQuery)
            ->when($transporterId, function ($query) use ($transporterId) {
                $query->where('transporter_id', $transporterId);
            })
            ->when($truckId, function ($query) use ($truckId) {
                $query->where('truck_id', $truckId);
            })
            ->whereNotNull('reference_by')
            ->select('reference_by')
            ->distinct();

        $transporters = Transporter::query()
            ->with('transporterMaster')
            ->whereIn('transporters.id', $transporterIdSubquery)
            ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
            ->orderBy('transporter_masters.name')
            ->select('transporters.*')
            ->get();

        $trucks = Truck::query()
            ->whereIn('id', $truckIdSubquery)
            ->orderBy('truck_number')
            ->get(['id', 'truck_number']);

        $references = Transporter::query()
            ->with('transporterMaster')
            ->whereIn('transporters.id', $referenceIdSubquery)
            ->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
            ->orderBy('transporter_masters.name')
            ->select('transporters.*')
            ->get();

        return [
            'transporters' => $transporters,
            'trucks' => $trucks,
            'references' => $references,
        ];
    }

    public function transporter_report_filter_options(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'transporter_id' => ['nullable', 'integer'],
            'truck_id' => ['nullable', 'integer'],
            'reference_by' => ['nullable', 'integer'],
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $maxStart = $endDate->copy()->subMonths(3);

        if ($startDate->lt($maxStart)) {
            return response()->json([
                'message' => 'The selected date range cannot exceed 3 months.',
                'errors' => [
                    'start_date' => ['Maximum allowed date range is 3 months.'],
                ],
            ], 422);
        }

        $transporterId = isset($validated['transporter_id']) ? (int) $validated['transporter_id'] : null;
        $truckId = isset($validated['truck_id']) ? (int) $validated['truck_id'] : null;
        $referenceId = isset($validated['reference_by']) ? (int) $validated['reference_by'] : null;

        $options = $this->getTransporterReportFilterOptions(
            $startDate->toDateString(),
            $endDate->toDateString(),
            $transporterId,
            $truckId,
            $referenceId
        );

        return response()->json([
            'transporters' => $options['transporters'],
            'trucks' => $options['trucks'],
            'references' => $options['references'],
        ]);
    }

    public function transporter_report_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $transporterId = $request->input('transporter_id') ?? null;
        $truckId = $request->input('truck_id') ?? null;
        $referenceId = $request->input('reference_by') ?? null;

        $export = Excel::download(new TransporterReportExport($startDate, $endDate, $transporterId, $truckId, $referenceId), 'transporter_report.xlsx');

        return $export;
    }

    /**
     * Transporter / Truck Owner master report (no date range, filter by type).
     */
    public function transporter_master(Request $request)
    {
        $type = $request->input('type', 'transporter'); // transporter, owner, both

        $query = Transporter::with(['transporterMaster.city', 'transporterMaster.state', 'transporterMaster.country'])
            ->where('company_id', Auth::user()->company_id);

        $normalizedType = strtolower($type);
        if ($normalizedType === 'transporter') {
            $query->where('is_owner', 0);
        } elseif ($normalizedType === 'owner' || $normalizedType === 'truck_owner') {
            $query->where('is_owner', 1);
        } // both → no extra filter

        $transporters = $query->join('transporter_masters', 'transporters.transporter_master_id', '=', 'transporter_masters.id')
            ->orderBy('transporter_masters.name')
            ->select('transporters.*')
            ->paginate(self::REPORTS_PER_PAGE)
            ->appends($request->query());

        return view('reports.transporter-master', [
            'transporters' => $transporters,
            'type' => $type,
        ]);
    }

    public function transporter_master_export(Request $request)
    {
        $type = $request->input('type', 'transporter');

        return Excel::download(new TransporterMasterExport($type), 'transporter_truck_owner_report.xlsx');
    }
}
