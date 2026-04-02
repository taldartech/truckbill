<?php

namespace App\Http\Controllers;

use App\Exports\ConsignorConsigneeExport;
use App\Exports\TDSExport;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DispatchExport;
use App\Exports\PartyLedgerExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Invoice;
use App\Exports\InvoiceExport;
use App\Exports\PartyBillExport;
use App\Models\Consignees;
use App\Exports\ShortageDamageExport;
use App\Exports\EwayBillExport;
use App\Exports\MemoCopyExport;
use App\Models\Pump;
use App\Exports\PumpExport;
use App\Models\VoucherPayment;
use App\Exports\BankExport;
use App\Exports\TransporterReportExport;
use App\Exports\TransporterMasterExport;
use App\Exports\UltratechBillExport;
use App\Models\Company;
use App\Models\Transporter;
use App\Models\VoucherLinked;
use App\Exports\PendingMemoExport;
use App\Exports\PendingInvoiceExport;

use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct()
    {
        if (Auth::check() && Auth::user()->userRole->name === 'super_admin' && ! session('super_admin_company_id')) {
            abort(403, 'Please enter a company first.');
        }
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
        if (!$startInput || !$endInput) {
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

    public function consignor_consignee(Request $request)
    {
        $consignees = Consignee::where('company_id', Auth::user()->company_id)->get();
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

        $consignorId = $request->input('consignor_id') ?? null;
        $consigneeId = $request->input('consignee_id') ?? null;

        if (($consignorId || $consigneeId) && $hasRange) {
            $user = Auth::user()->company_id;
            $query = LorryReceipt::whereBetween('gr_date', [$startDate, $endDate]);
            if ($user) {
                $query->where('company_id', $user);
            }

            if ($consigneeId) {
                $query->where('consignee_id', $consigneeId);
            }

            if ($consignorId) {
                $query->where('consignor_id', $consignorId);
            }
            $query->orderBy('gr_date', 'asc');
            $lrs = $query->get();
        } else {
            $lrs = [];
        }

        return view('reports.consignor-consignee', compact('lrs', 'consignees', 'startDate', 'endDate', 'consignorId', 'consigneeId'));
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
            $lrs = $query->get();
            $generated = true;
        } else {
            $lrs = [];
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
    // public function dispatch(Request $request)
    // {
    //   $endDate = $request->input('end_date') ?? date('Y-m-d');
    //   $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

    //   $user = Auth::user()->company_id;
    //   if ($request->input('end_date') || $request->input('start_date')) {


    //     $query = FreightBooking::with('voucherPayments')->whereHas('lr', function($query) use ($startDate, $endDate) {
    //       $query->whereBetween('gr_date', [$startDate, $endDate]);
    //     });
    //     if ($user) {
    //       $query->where('company_id', $user);
    //     }
    //     // $query->where('tds_amount', '>', 0);
    //     $lrs = $query->get();
    //     $generated = true;
    //   } else {
    //     $lrs = [];
    //     $generated = false;
    //   }
    //   return view('reports.dispatch', compact('lrs', 'startDate', 'endDate', 'generated'));
    // }
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
                'truck'
            ])->whereBetween('gr_date', [$startDate, $endDate]);

            if ($user) {
                $query->where('company_id', $user);
            }
            $query->orderBy('gr_date', 'asc');
            $lrs = $query->get();

            // Debug: Log the first few records to check relationships
            if ($lrs->isNotEmpty()) {
                \Log::info('First LorryReceipt data:', [
                    'lr_id' => $lrs[0]->id,
                    'lr_number' => $lrs[0]->lr_number,
                    'has_freight_bookings' => $lrs[0]->relationLoaded('freightBookings') && $lrs[0]->freightBookings->isNotEmpty(),
                    'freight_booking_count' => $lrs[0]->freightBookings->count(),
                    'voucher_payments' => $lrs[0]->freightBookings->isNotEmpty() ?
                        $lrs[0]->freightBookings[0]->voucherPayments->toArray() : 'No good hires (lorry hire)'
                ]);
            }
        } else {
            $lrs = [];
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
        $consignees = Consignee::where('company_id', Auth::user()->company_id)->get();
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

        $customerId = $request->input('customer_id') ?? null;

        if ($customerId && $hasRange) {
            $user = Auth::user()->company_id;
            $query = LorryReceipt::whereBetween('gr_date', [$startDate, $endDate]);
            $query->where('invoice_generated', 1);
            if ($user) {
                $query->where('company_id', $user);
            }
            $query->orderBy('gr_date', 'asc');
            if ($customerId) {
                $query->where('customer_id', $customerId);
            }

            $lrs = $query->get();
        } else {
            $lrs = [];
        }
        return view('reports.party-ledger', compact('lrs', 'consignees', 'startDate', 'endDate', 'customerId'));
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
        $customers = Consignee::where('company_id', Auth::user()->company_id)->get();
        $lr = null;
        $customerId = $request->input('customer_id') ?? null;
        $lrs = LorryReceipt::where('customer_id', $customerId)->get();
        $lr_id = $request->input('lr_id') ?? null;
        if ($lr_id) {
            $lr = LorryReceipt::find($request->input('lr_id'));
        }
        return view('reports.lr-copy', compact('lr', 'customers', 'customerId', 'lr_id', 'lrs'));
    }

    public function lr_copy_export(Request $request)
    {
        if (!$request->input('lr_id')) {
            return redirect()->back()->with('error', 'Please select a Good Receipt (Bilty) to export.');
        }
        $lr = LorryReceipt::find($request->input('lr_id'));
        $html = view('reports.exports.lr-copy', compact('lr'))->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return $pdf->stream('lr_copy.pdf');
    }
    public function invoice(Request $request)
    {
        [$startDate, $endDate, $generated] = $this->normalizeDateRange($request);

        $user = Auth::user()->company_id;
        $invoices = [];
        if ($generated) {
            $query = Invoice::with(['consignee', 'consignee.state', 'company'])
                ->whereBetween('generated_date', [$startDate, $endDate]);

            if ($user) {
                $query->where('company_id', $user);
            }
            $query->orderBy('generated_date', 'asc');
            $invoices = $query->get();
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
                'truck'
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
            $lrs = $query->get();

            // Debug: Log the first few records to check relationships
            if ($lrs->isNotEmpty()) {
                \Log::info('First LorryReceipt data:', [
                    'lr_id' => $lrs[0]->id,
                    'lr_number' => $lrs[0]->lr_number,
                    'has_freight_bookings' => $lrs[0]->relationLoaded('freightBookings') && $lrs[0]->freightBookings->isNotEmpty(),
                    'freight_booking_count' => $lrs[0]->freightBookings->count(),
                    'voucher_payments' => $lrs[0]->freightBookings->isNotEmpty() ?
                        $lrs[0]->freightBookings[0]->voucherPayments->toArray() : 'No good hires (lorry hire)'
                ]);
            }
        } else {
            $lrs = [];
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
            $frs = $query->get();
        } else {
            $frs = [];
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

        if ($pumpId && $hasRange) {
            // --- Step 1: Fetch records from voucher_payments where linked_voucher_id is null ---
            $voucherPaymentsQuery = \App\Models\VoucherPayment::with(['freight_booking.lr'])
                ->where('pump_id', $pumpId)
                ->where('company_id', $companyId)
                ->whereNull('linked_voucher_id')
                ->whereBetween('date', [$startDate, $endDate]);

            if ($paymentStatus) {
                $voucherPaymentsQuery->where('payment_status', $paymentStatus);
            }

            $voucherPayments = $voucherPaymentsQuery->get();

            // --- Step 2: Fetch all records from voucher_linked table ---
            $linkedPaymentsQuery = \App\Models\VoucherLinked::with(['freight_booking.lr'])
                ->where('pump_id', $pumpId)
                ->where('company_id', $companyId)
                ->whereBetween('date', [$startDate, $endDate]);

            if ($paymentStatus) {
                $linkedPaymentsQuery->where('payment_status', $paymentStatus);
            }

            $linkedPayments = $linkedPaymentsQuery->get();

            // --- Step 3: Prepare merged data from unlinked payments ---
            $unlinkedData = $voucherPayments->map(function ($payment) {
                $lrDetails = \App\Models\LorryReceipt::where('freight_booking_id', $payment->freight_booking_id)
                    ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id', 'destination_city')
                    ->get();

                return [
                    'freight_booking_id' => $payment->freight_booking_id,
                    'amount'             => $payment->amount,
                    'lrs'                => $lrDetails,
                    'date'               => \Carbon\Carbon::parse($payment->date),
                    'type'               => 'Unlinked',
                    'payment_status'     => $payment->payment_status ?? 'N/A',
                ];
            });

            // --- Step 4: Prepare merged data from linked payments ---
            $linkedData = $linkedPayments->map(function ($linked) {
                $lrDetails = \App\Models\LorryReceipt::where('freight_booking_id', $linked->freight_booking_id)
                    ->select('freight_booking_id', 'lr_number', 'destination_state', 'truck_id', 'destination_city')
                    ->get();

                return [
                    'freight_booking_id' => $linked->freight_booking_id,
                    'amount'             => $linked->amount,
                    'lrs'                => $lrDetails,
                    'date'               => \Carbon\Carbon::parse($linked->date),
                    'type'               => 'Linked',
                    'payment_status'     => $linked->payment_status ?? 'N/A',
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
        }

        // --- Step 3: Return view ---
        return view('reports.pump', compact('pumpData', 'startDate', 'endDate', 'pumpId', 'pumps', 'paymentStatus'));
    }

    public function pump_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $pumpId = $request->input('pump_id');
        $paymentStatus = $request->input('payment_status');

        $export = Excel::download(new PumpExport($startDate, $endDate, $pumpId, $paymentStatus), 'pump_report.xlsx');
        return $export;
    }

    public function memo_copy(Request $request)
    {
        $customers = Consignee::where('company_id', Auth::user()->company_id)->get();
        $customerId = $request->input('customer_id') ?? null;
        $memo_id = $request->input('memo_id') ?? null;

        $memo_ids = [];
        $lrs = [];
        if ($customerId) {
            $memo_ids = LorryReceipt::where('customer_id', $customerId)
                ->whereNotNull('memo_id')
                ->pluck('memo_id')->unique();
            $lrs = LorryReceipt::where('memo_id', $memo_id)->get();
        }
        return view('reports.memo-copy', compact('customers', 'customerId', 'memo_id', 'memo_ids', 'lrs'));
    }



    // public function memo_copy_export(Request $request)
    // {
    //   if (!$request->input('memo_id')) {
    //     return redirect()->back()->with('error', 'Please select a Memo to export.');
    //   }
    //   $memo_id = $request->input('memo_id');

    //   try {
    //     $export = Excel::download(new MemoCopyExport($memo_id), 'memo_copy.xlsx');
    //   } catch (\Exception $e) {
    //     dd($e);
    //   }

    //   return $export;
    // }

    public function memo_copy_export(Request $request)
    {
        if (!$request->input('memo_id')) {
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
                $pdf = PDF::loadHTML($html)->setPaper('a4', 'portrait');
                return $pdf->stream("memo_copy_{$memo_id}.pdf");
            } else {
                // Existing Excel export
                return Excel::download(new MemoCopyExport($memo_id), 'memo_copy.xlsx');
            }
        } catch (\Exception $e) {
            \Log::error('Error exporting memo copy: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generating export: ' . $e->getMessage());
        }
    }

    public function pending_memo(Request $request)
    {
        try {
            // Get all customers for the dropdown
            $customers = Consignee::where('company_id', Auth::user()->company_id)
                ->orderBy('name')
                ->get();

            $customerId = $request->input('customer_id');
            $status = $request->input('status', 'delivered'); // in-progress, delivered, both
            [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

            $lrs = collect();

            if ($customerId) {
                // Only pending memos for selected customer
                $idsQuery = LorryReceipt::where('customer_id', $customerId)
                    ->where('company_id', Auth::user()->company_id)
                    ->whereNull('memo_id');

                if ($hasRange) {
                    $idsQuery->whereBetween('gr_date', [$startDate, $endDate]);
                }

                $ids = $idsQuery->pluck('id')->unique()->values();

                if ($ids->count() > 0) {
                    $lrsQuery = LorryReceipt::with(['truck:id,truck_number', 'customer:id,name'])
                        ->whereIn('id', $ids)
                        ->where('company_id', Auth::user()->company_id);

                    $normalizedStatus = strtolower($status);
                    if ($normalizedStatus === 'in-progress') {
                        $lrsQuery->where('delivery_status', 'In-Progress');
                    } elseif ($normalizedStatus === 'delivered') {
                        $lrsQuery->where('delivery_status', 'Delivered');
                    } elseif ($normalizedStatus === 'both') {
                        $lrsQuery->whereIn('delivery_status', ['In-Progress', 'Delivered']);
                    }

                    $lrs = $lrsQuery->orderBy('gr_date', 'desc')->get();
                }
            } else {
                // Show pending memos for **all customers**
                $idsQuery = LorryReceipt::where('company_id', Auth::user()->company_id)
                    ->whereNull('memo_id');

                if ($hasRange) {
                    $idsQuery->whereBetween('gr_date', [$startDate, $endDate]);
                }

                $ids = $idsQuery->pluck('id')->unique()->values();

                if ($ids->count() > 0) {
                    $lrsQuery = LorryReceipt::with(['truck:id,truck_number', 'customer:id,name'])
                        ->whereIn('id', $ids)
                        ->where('company_id', Auth::user()->company_id);

                    $normalizedStatus = strtolower($status);
                    if ($normalizedStatus === 'in-progress') {
                        $lrsQuery->where('delivery_status', 'In-Progress');
                    } elseif ($normalizedStatus === 'delivered') {
                        $lrsQuery->where('delivery_status', 'Delivered');
                    } elseif ($normalizedStatus === 'both') {
                        $lrsQuery->whereIn('delivery_status', ['In-Progress', 'Delivered']);
                    }

                    $lrs = $lrsQuery->orderBy('gr_date', 'desc')->get();
                }
            }

            return view('reports.pending-memo', [
                'customers' => $customers,
                'customerId' => $customerId,
                'lrs' => $lrs,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in pending_memo: ' . $e->getMessage());
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
            // Get all customers for the dropdown
            $customers = Consignee::where('company_id', Auth::user()->company_id)
                ->orderBy('name')
                ->get();

            $customerId = $request->input('customer_id');
            [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);

            $lrs = collect();

            if ($customerId) {
                // Only pending memos for selected customer
                $idsQuery = LorryReceipt::where('customer_id', $customerId)
                    ->where('company_id', Auth::user()->company_id)
                    ->whereNotNull('memo_id')
                    ->where('invoice_generated', 0);

                if ($hasRange) {
                    $idsQuery->whereBetween('gr_date', [$startDate, $endDate]);
                }

                $ids = $idsQuery->pluck('id')->unique()->values();

                if ($ids->count() > 0) {
                    $lrs = LorryReceipt::with(['truck:id,truck_number', 'customer:id,name'])
                        ->whereIn('id', $ids)
                        ->where('delivery_status', 'Delivered')
                        ->where('company_id', Auth::user()->company_id)
                        ->orderBy('gr_date', 'desc')
                        ->get();
                }
            } else {
                // Show pending memos for **all customers**
                $idsQuery = LorryReceipt::where('company_id', Auth::user()->company_id)
                    ->whereNotNull('memo_id')
                    ->where('invoice_generated', 0);

                if ($hasRange) {
                    $idsQuery->whereBetween('gr_date', [$startDate, $endDate]);
                }

                $ids = $idsQuery->pluck('id')->unique()->values();

                if ($ids->count() > 0) {
                    $lrs = LorryReceipt::with(['truck:id,truck_number', 'customer:id,name'])
                        ->whereIn('id', $ids)
                        ->where('delivery_status', 'Delivered')
                        ->where('company_id', Auth::user()->company_id)
                        ->orderBy('gr_date', 'desc')
                        ->get();
                }
            }

            return view('reports.pending-invoice', [
                'customers' => $customers,
                'customerId' => $customerId,
                'lrs' => $lrs,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in pending_invoice: ' . $e->getMessage());
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
                $tds_deducted = $payment->freight_booking->tds_amount == 0 ? 'No' : 'Yes';
                $allPayments[] = [
                    'date' => $payment->date,
                    'lr_number' => $payment->freight_booking->lr->lr_number ?? 'N/A',
                    'gr_number' => $payment->freight_booking->lr->gr_number ?? 'N/A',
                    'truck_number' => $payment->freight_booking->lr->truck->truck_number ?? 'N/A',
                    'transporter_name' => $payment->freight_booking->lr->transporter->name ?? 'N/A',
                    'name' => $payment->freight_booking->lr->transporter->short_name ?? 'N/A',
                    'beneficiary_id' => $payment->freight_booking->lr->transporter->beneficiary_id ?? 'N/A',
                    'amount' => $payment['amount'],
                    'tds_deducted' => $tds_deducted,
                    'payment_status' => $payment->payment_status ?? 'N/A',
                ];
            }
        }
        if (isset($linked_payments)) {
            foreach ($linked_payments as $linked_payment) {
                $tds_deducted = $linked_payment->freight_booking->tds_amount == 0 ? 'No' : 'Yes';
                $allPayments[] = [
                    'date' => $linked_payment->date,
                    'lr_number' => $linked_payment->freight_booking->lr->lr_number ?? 'N/A',
                    'gr_number' => $linked_payment->freight_booking->lr->gr_number ?? 'N/A',
                    'truck_number' => $linked_payment->freight_booking->lr->truck->truck_number ?? 'N/A',
                    'transporter_name' => $linked_payment->freight_booking->lr->transporter->name ?? 'N/A',
                    'name' => $linked_payment->freight_booking->lr->transporter->short_name ?? 'N/A',
                    'beneficiary_id' => $linked_payment->freight_booking->lr->transporter->beneficiary_id ?? 'N/A',
                    'amount' => $linked_payment['amount'],
                    'tds_deducted' => $tds_deducted,
                    'payment_status' => $linked_payment->payment_status ?? 'N/A',
                ];
            }
            $allPayments = collect($allPayments)->sortBy('date')->values();
        }

        return view('reports.bank', compact('startDate', 'endDate', 'allPayments', 'paymentStatus'));
    }

    public function bank_export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $paymentStatus = $request->input('payment_status');

        $export = Excel::download(new BankExport($startDate, $endDate, $paymentStatus), 'bank_report.xlsx');
        return $export;
    }

    public function voucher_receipt_export(Request $request)
    {
        if (!$request->input('lr_id')) {
            return redirect()->back()->with('error', 'Please select a Good Receipt (Bilty) to export.');
        }
        $lr = LorryReceipt::find($request->input('lr_id'));
        if ($lr->linked_lr_id) {
            $lrs = LorryReceipt::where('linked_lr_id', $lr->linked_lr_id)
                ->where('company_id', Auth::user()->company_id)
                ->get();
            // check if any lr does not have null freight_booking_id
            $allHaveFreightBooking = $lrs->every(function ($item) {
                return !is_null($item->freight_booking_id);
            });

            if (!$allHaveFreightBooking) {
                return redirect()->back()->with('error', 'Create all Good Hires (Lorry Hire) for linked Good Receipts (Bilty) before exporting the Voucher Receipt.');
            }
        } else {
            $lrs = collect([$lr]);
        }

        $company = Company::find(Auth::user()->company_id);
        if (!$company->template_name || !$company->template_address) {
            return redirect()->back()->with('error', 'Please set the Voucher template before exporting the Voucher Receipt.');
        }

        // Set the font configuration for the PDF
        $html = view('reports.exports.voucher-receipt', compact('lrs', 'company'))->render();

        // Render with mPDF for proper Devanagari shaping
        $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'fontDir' => array_merge($fontDirs, [storage_path('fonts')]),
            'fontdata' => $fontData + [
                'notosansdevanagari' => [
                    'R' => 'NotoSansDevanagari-Regular.ttf',
                    'B' => 'NotoSansDevanagari-Bold.ttf',
                ],
            ],
            'default_font' => 'notosansdevanagari',
            'useOTL' => 0xFF, // Enable OpenType layout (Indic shaping)
            'useKerning' => true,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $mpdf->WriteHTML($html);
        return $mpdf->Output('voucher_receipt.pdf', 'I');
    }

    public function party_bill(Request $request)
    {
        $customers = Consignee::where('company_id', Auth::user()->company_id)->get();
        $selectedCompany = Company::find(Auth::user()->company_id);
        $selectedInvoiceTemplate = (string) ($selectedCompany->invoice_template ?? 'classic');
        $selectedInvoiceTemplateLabel = match ($selectedInvoiceTemplate) {
            'modern' => 'B (Modern)',
            'compact' => 'C (Compact)',
            default => 'A (Classic)',
        };
        $customerId = $request->input('customer_id') ?? null;
        $id = $request->input('invoice_number') ?? null;
        // Load all invoices initially, filter by customer if selected
        $invoices = Invoice::where('company_id', Auth::user()->company_id)
            ->when($customerId, function ($query) use ($customerId) {
                return $query->where('customer_id', $customerId);
            })
            ->orderByDesc('id')
            ->get();
        $invoice = Invoice::find($id);
        $customDetails = collect();
        if ($invoice && $invoice->custom_invoice) {
            $invoice->load(['customInvoiceDetails.lorryReceipt.truck']);
            $customDetails = $invoice->customInvoiceDetails->sortBy('sort_order')->values();
        }
        return view('reports.party-bill', compact(
            'customers',
            'customerId',
            'id',
            'invoices',
            'invoice',
            'customDetails',
            'selectedInvoiceTemplateLabel'
        ));
    }

    // public function party_bill_export(Request $request)
    // {
    //   if (!$request->input('invoice_number')) {
    //     return redirect()->back()->with('error', 'Please select an Invoice to export.');
    //   }
    //   $id = $request->input('invoice_number');
    //   $export = Excel::download(new PartyBillExport($id), 'party_bill.xlsx');
    //   return $export;
    // }
    public function party_bill_export(Request $request)
    {
        if (!$request->input('invoice_number')) {
            return redirect()->back()->with('error', 'Please select an Invoice to export.');
        }

        $id = $request->input('invoice_number');
        $exportType = $request->input('type', 'excel');
        $resolvedTemplateKey = 'classic';

        if ($exportType === 'pdf') {
            $selectedCompany = \App\Models\Company::find(Auth::user()->company_id);
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
                    'company.state'
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

                $pdf = \PDF::loadHTML($html)
                    ->setPaper('a4', 'portrait')
                    ->setOption('isHtml5ParserEnabled', true)
                    ->setOption('isRemoteEnabled', true);

                $invoiceIdentifier = $invoice->invoice_number
                    ? $invoice->invoice_number . ($invoice->manual_invoice_number ? ' (' . $invoice->manual_invoice_number . ')' : '')
                    : ($invoice->manual_invoice_number ?? $invoice->id);

                $safeInvoiceIdentifier = preg_replace('/[\\\\\/]+/', '-', (string) $invoiceIdentifier) ?? (string) $invoice->id;
                $safeInvoiceIdentifier = trim($safeInvoiceIdentifier);

                if ($safeInvoiceIdentifier === '') {
                    $safeInvoiceIdentifier = (string) $invoice->id;
                }

                return $pdf->stream('party_bill_' . $safeInvoiceIdentifier . '.pdf');
            } else {
                // Existing Excel export
                return Excel::download(new PartyBillExport($id), 'party_bill.xlsx');
            }
        } catch (\Exception $e) {
            \Log::error('Error in party_bill_export:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invoice_id' => $id,
                'type' => $exportType ?? 'unknown'
            ]);

            return redirect()->back()
                ->with('error', 'Error generating export: ' . $e->getMessage());
        }
    }

    public function ultratech_bill(Request $request)
    {
        $customers = Consignee::where('company_id', Auth::user()->company_id)->get();
        $customerId = $request->input('customer_id') ?? null;
        $id = $request->input('invoice_number') ?? null;
        // Load all invoices initially, filter by customer if selected
        $invoices = Invoice::where('company_id', Auth::user()->company_id)
            ->when($customerId, function ($query) use ($customerId) {
                return $query->where('customer_id', $customerId);
            })
            ->orderByDesc('id')
            ->get();
        $invoice = Invoice::find($id);
        $customDetails = collect();
        if ($invoice && $invoice->custom_invoice) {
            $invoice->load(['customInvoiceDetails.lorryReceipt.truck']);
            $customDetails = $invoice->customInvoiceDetails->sortBy('sort_order')->values();
        }
        return view('reports.ultratech-bill', compact('customers', 'customerId', 'id', 'invoices', 'invoice', 'customDetails'));
    }

    public function ultratech_bill_export(Request $request)
    {
        if (!$request->input('invoice_number')) {
            return redirect()->back()->with('error', 'Please select an Invoice to export.');
        }
        $id = $request->input('invoice_number');
        $export = Excel::download(new UltratechBillExport($id), 'ultratech_bill.xlsx');
        return $export;
    }

    public function transporter_report(Request $request)
    {
        $endDate = $request->input('end_date') ?? date('Y-m-d');
        $startDate = max($request->input('start_date') ?? date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('-3 months', strtotime($endDate))));

        $transporters = Transporter::with('transporterMaster')->where('company_id', Auth::user()->company_id)->get();
        $transporterId = $request->input('transporter_id') ?? null;
        $trucks = [];
        $truckId = $request->input('truck_id') ?? null;
        $references = [];
        $referenceId = $request->input('reference_by') ?? null;
        if ($request->input('end_date') || $request->input('start_date')) {
            $query = LorryReceipt::whereBetween('gr_date', [$startDate, $endDate]);
            $query->whereNotNull('freight_booking_id');
            if (Auth::user()->company_id) {
                $query->where('company_id', Auth::user()->company_id);
            }
            if ($transporterId) {
                $trucks = LorryReceipt::where('transporter_id', $transporterId)
                    ->get(['truck_id']);
                $trucks = Truck::whereIn('id', $trucks->pluck('truck_id'))->get();
                $query->where('transporter_id', $transporterId);
            }
            if ($truckId) {
                $query->where('truck_id', $truckId);
                $references = LorryReceipt::where('truck_id', $truckId)
                    ->get(['reference_by']);
                $references = Transporter::with('transporterMaster')->whereIn('id', $references->pluck('reference_by'))->get();
            }
            if ($referenceId) {
                $query->where('reference_by', $referenceId);
            }
            $query->orderBy('gr_date', 'asc');
            $lrs = $query->get();
        } else {
            $lrs = [];
        }

        return view('reports.transporter-report', compact('transporters', 'transporterId', 'startDate', 'endDate', 'trucks', 'truckId', 'lrs', 'references', 'referenceId'));
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
            ->get();

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
