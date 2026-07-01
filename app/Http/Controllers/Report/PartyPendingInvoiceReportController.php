<?php

namespace App\Http\Controllers\Report;

use App\Exports\PartyPendingInvoiceExport;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PartyPendingInvoiceReportController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request)
    {
        return view('reports.party-pending-invoice', $this->reportPayload($request, paginate: true));
    }

    public function export(Request $request): BinaryFileResponse
    {
        return Excel::download(
            new PartyPendingInvoiceExport($this->reportPayload($request, paginate: false)),
            'party-pending-invoice.xlsx'
        );
    }

    /**
     * @return array{
     *     partyRows: Collection|LengthAwarePaginator,
     *     grandTotalCollect: float,
     *     grandTotalPaid: float,
     *     grandTotalPending: float
     * }
     */
    private function reportPayload(Request $request, bool $paginate): array
    {
        $companyId = (int) Auth::user()->company_id;

        $invoices = $this->buildQuery($companyId)->get();
        $partyRows = $this->mapPartyRows($invoices);

        $grandTotalCollect = (float) $partyRows->sum('amount_to_collect');
        $grandTotalPaid = (float) $partyRows->sum('paid_amount');
        $grandTotalPending = (float) $partyRows->sum('pending_amount');

        if ($paginate) {
            $partyRows = $this->paginateCollection($partyRows, $request);
        }

        return [
            'partyRows' => $partyRows,
            'grandTotalCollect' => $grandTotalCollect,
            'grandTotalPaid' => $grandTotalPaid,
            'grandTotalPending' => $grandTotalPending,
        ];
    }

    /**
     * @return Builder<Invoice>
     */
    private function buildQuery(int $companyId): Builder
    {
        return Invoice::query()
            ->with(['consignee', 'payments'])
            ->where('company_id', $companyId)
            ->whereIn('payment_status', ['pending', 'partial']);
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return Collection<int, array{
     *     customer_id: int|null,
     *     party_name: string,
     *     invoice_count: int,
     *     amount_to_collect: float,
     *     paid_amount: float,
     *     pending_amount: float
     * }>
     */
    private function mapPartyRows(Collection $invoices): Collection
    {
        return $invoices
            ->groupBy('customer_id')
            ->map(function (Collection $partyInvoices, $customerId): array {
                $partyName = $partyInvoices->first()?->consignee?->name ?? 'Unknown Party';

                return [
                    'customer_id' => $customerId !== '' ? (int) $customerId : null,
                    'party_name' => $partyName,
                    'invoice_count' => $partyInvoices->count(),
                    'amount_to_collect' => (float) $partyInvoices->sum(
                        fn (Invoice $invoice): float => $this->amountToCollect($invoice)
                    ),
                    'paid_amount' => (float) $partyInvoices->sum(
                        fn (Invoice $invoice): float => (float) $invoice->paid_amount
                    ),
                    'pending_amount' => (float) $partyInvoices->sum(
                        fn (Invoice $invoice): float => $this->pendingAmount($invoice)
                    ),
                ];
            })
            ->sortBy('party_name')
            ->values();
    }

    private function amountToCollect(Invoice $invoice): float
    {
        return (float) (($invoice->invoice_type ?? '') === 'Reverse'
            ? $invoice->total_amount
            : ($invoice->net_amount ?? $invoice->total_amount));
    }

    private function pendingAmount(Invoice $invoice): float
    {
        return max(0, $this->amountToCollect($invoice) - (float) $invoice->paid_amount);
    }

    private function paginateCollection(Collection $items, Request $request): LengthAwarePaginator
    {
        $collection = $items->values();
        $currentPage = (int) $request->input('page', Paginator::resolveCurrentPage());
        $currentPage = $currentPage > 0 ? $currentPage : 1;
        $offset = ($currentPage - 1) * self::PER_PAGE;

        return new LengthAwarePaginator(
            $collection->slice($offset, self::PER_PAGE)->values(),
            $collection->count(),
            self::PER_PAGE,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
