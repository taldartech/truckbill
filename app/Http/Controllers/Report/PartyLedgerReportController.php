<?php

namespace App\Http\Controllers\Report;

use App\Exports\PartyLedgerExport;
use App\Http\Controllers\Controller;
use App\Models\Consignee;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PartyLedgerReportController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request)
    {
        return view('reports.party-ledger', $this->reportPayload($request, paginate: true));
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new PartyLedgerExport($this->reportPayload($request, paginate: false)),
            'party_ledger_report.xlsx'
        );
    }

    /**
     * @return array{
     *     startDate: string|null,
     *     endDate: string,
     *     customerId: string|null,
     *     presetCustomer: Consignee|null,
     *     customer: Consignee|null,
     *     lrs: Collection|LengthAwarePaginator,
     *     ledgerRows: Collection
     * }
     */
    private function reportPayload(Request $request, bool $paginate): array
    {
        $companyId = (int) Auth::user()->company_id;
        $customerId = $request->input('customer_id');
        $customerIdInt = ($customerId !== null && $customerId !== '') ? (int) $customerId : null;
        [$startDate, $endDate, $hasRange] = $this->normalizeDateRange($request);
        $presetCustomer = $this->resolveCustomer($customerId, $companyId);

        $lrs = collect();
        $ledgerRows = collect();
        $customer = null;

        if ($customerIdInt && $hasRange) {
            $query = $this->buildLrQuery($companyId, $customerIdInt, $startDate, $endDate);

            if ($paginate) {
                $lrs = $query->paginate(self::PER_PAGE)->appends($request->query());
                $lrItems = collect($lrs->items());
            } else {
                $lrs = $query->get();
                $lrItems = $lrs;
            }

            $invoiceByLrId = $this->buildInvoiceByLrId($lrItems, $companyId, $customerIdInt);
            $ledgerRows = $this->mapLedgerRows($lrItems, $invoiceByLrId);
            $customer = Consignee::query()
                ->with(['city', 'state'])
                ->where('company_id', $companyId)
                ->find($customerIdInt);
        }

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'customerId' => $customerId,
            'presetCustomer' => $presetCustomer,
            'customer' => $customer,
            'lrs' => $lrs,
            'ledgerRows' => $ledgerRows,
        ];
    }

    /**
     * @return array{0: ?string, 1: string, 2: bool}
     */
    private function normalizeDateRange(Request $request, int $monthsLimit = 3): array
    {
        $endInput = $request->input('end_date');
        $startInput = $request->input('start_date');
        $endDateForView = $endInput ?? date('Y-m-d');

        if (! $startInput || ! $endInput) {
            return [null, $endDateForView, false];
        }

        $start = Carbon::parse($startInput);
        $end = Carbon::parse($endInput);

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

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

    /**
     * @return Builder<LorryReceipt>
     */
    private function buildLrQuery(int $companyId, int $customerId, string $startDate, string $endDate): Builder
    {
        return LorryReceipt::query()
            ->with('truck')
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('invoice_generated', 1)
            ->whereBetween('gr_date', [$startDate, $endDate])
            ->orderBy('gr_date', 'asc');
    }

    private function resolveCustomer(?string $customerId, int $companyId): ?Consignee
    {
        if ($customerId === null || $customerId === '') {
            return null;
        }

        return Consignee::query()
            ->where('company_id', $companyId)
            ->whereKey((int) $customerId)
            ->first(['id', 'name']);
    }

    /**
     * @param  Collection<int, LorryReceipt>  $lrs
     * @return Collection<int, Invoice>
     */
    private function buildInvoiceByLrId(Collection $lrs, int $companyId, int $customerId): Collection
    {
        if ($lrs->isEmpty()) {
            return collect();
        }

        $invoiceByLrId = collect();

        $invoices = Invoice::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->get([
                'id',
                'invoice_number',
                'manual_invoice_number',
                'generated_date',
                'payment_status',
                'invoice_type',
                'lr_ids',
                'memo_ids',
            ]);

        foreach ($invoices as $invoice) {
            foreach ((array) ($invoice->lr_ids ?? []) as $lrId) {
                $invoiceByLrId[(int) $lrId] = $invoice;
            }
        }

        foreach ($invoices as $invoice) {
            foreach ((array) ($invoice->memo_ids ?? []) as $memoId) {
                foreach ($lrs->where('memo_id', $memoId) as $lr) {
                    if (! $invoiceByLrId->has($lr->id)) {
                        $invoiceByLrId[(int) $lr->id] = $invoice;
                    }
                }
            }
        }

        return $invoiceByLrId;
    }

    /**
     * @param  Collection<int, LorryReceipt>  $lrs
     * @param  Collection<int, Invoice>  $invoiceByLrId
     * @return Collection<int, array{
     *     lr: LorryReceipt,
     *     bill_number: string,
     *     bill_date: string|null,
     *     bill_amount: float,
     *     payment_status: string
     * }>
     */
    private function mapLedgerRows(Collection $lrs, Collection $invoiceByLrId): Collection
    {
        return $lrs->map(function (LorryReceipt $lr) use ($invoiceByLrId): array {
            $invoice = $invoiceByLrId->get($lr->id);
            $gst = (($invoice->invoice_type ?? '') === 'Forward') ? 5 : 0;
            $billAmount = (float) $lr->amount + (((float) $lr->amount * $gst) / 100);

            return [
                'lr' => $lr,
                'bill_number' => $invoice ? $this->displayInvoiceNumber($invoice) : 'N/A',
                'bill_date' => $invoice?->generated_date,
                'bill_amount' => $billAmount,
                'payment_status' => $invoice ? ucfirst($invoice->payment_status ?? 'pending') : 'N/A',
            ];
        });
    }

    private function displayInvoiceNumber(Invoice $invoice): string
    {
        if ($invoice->invoice_number) {
            $label = $invoice->invoice_number;

            if ($invoice->manual_invoice_number) {
                $label .= ' ('.$invoice->manual_invoice_number.')';
            }

            return $label;
        }

        return (string) ($invoice->manual_invoice_number ?? $invoice->id);
    }
}
