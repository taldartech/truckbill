<?php

namespace App\Http\Controllers;

use App\Models\Consignee;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CustomerPortalController extends Controller
{
    /**
     * @return array<int>
     */
    private function allowedConsigneeIds(Request $request): array
    {
        return $request->attributes->get('portal_allowed_consignee_ids', []);
    }

    private function portalConsignees(Request $request): Collection
    {
        return $request->attributes->get('portal_consignees', collect());
    }

    private function companyOptions(Request $request): Collection
    {
        return $this->portalConsignees($request)
            ->filter(fn (Consignee $consignee) => $consignee->company !== null)
            ->map(fn (Consignee $consignee) => [
                'id' => $consignee->company->id,
                'name' => $consignee->company->name,
            ])
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    public function lorryReceipts(Request $request)
    {
        $consigneeIds = $this->allowedConsigneeIds($request);
        $companyId = (int) $request->input('company_id', 0);
        $query = LorryReceipt::query()
            ->with(['transporter', 'truck', 'consignor', 'consignee', 'customer.company'])
            ->whereIn('customer_id', $consigneeIds);

        if ($companyId > 0) {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('gr_date', [$request->input('start_date'), $request->input('end_date')]);
        }

        $lorryReceipts = $query
            ->orderByDesc('gr_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('customer.lorry_receipts.index', [
            'lorryReceipts' => $lorryReceipts,
            'consigneeOptions' => $this->portalConsignees($request),
            'companyOptions' => $this->companyOptions($request),
        ]);
    }

    public function showLorryReceipt(Request $request, LorryReceipt $lorryReceipt)
    {
        if (! in_array((int) $lorryReceipt->customer_id, $this->allowedConsigneeIds($request), true)) {
            abort(403, 'Unauthorized access to this lorry receipt.');
        }

        $lorryReceipt->load(['transporter', 'truck', 'customer', 'consignor', 'consignee']);

        return view('customer.lorry_receipts.show', [
            'lorryReceipt' => $lorryReceipt,
            'consigneeOptions' => $this->portalConsignees($request),
        ]);
    }

    public function invoices(Request $request)
    {
        $consigneeIds = $this->allowedConsigneeIds($request);
        $companyId = (int) $request->input('company_id', 0);
        $query = Invoice::query()
            ->whereIn('customer_id', $consigneeIds)
            ->with(['payments', 'company']);

        if ($companyId > 0) {
            $query->where('company_id', $companyId);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        $invoices = $query
            ->orderByDesc('generated_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('customer.invoices.index', [
            'invoices' => $invoices,
            'consigneeOptions' => $this->portalConsignees($request),
            'companyOptions' => $this->companyOptions($request),
        ]);
    }

    public function showInvoice(Request $request, Invoice $invoice)
    {
        if (! in_array((int) $invoice->customer_id, $this->allowedConsigneeIds($request), true)) {
            abort(403, 'Unauthorized access to this invoice.');
        }

        $invoice->load(['payments.creator', 'company', 'consignee']);

        return view('customer.invoices.show', [
            'invoice' => $invoice,
            'consigneeOptions' => $this->portalConsignees($request),
        ]);
    }

    public function payments(Request $request)
    {
        $consigneeIds = $this->allowedConsigneeIds($request);
        $companyId = (int) $request->input('company_id', 0);
        $payments = InvoicePayment::query()
            ->with(['invoice.company'])
            ->whereHas('invoice', function ($query) use ($consigneeIds) {
                $query->whereIn('customer_id', $consigneeIds);
            })
            ->when($companyId > 0, function ($query) use ($companyId) {
                $query->whereHas('invoice', function ($invoiceQuery) use ($companyId) {
                    $invoiceQuery->where('company_id', $companyId);
                });
            })
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('customer.payments.index', [
            'payments' => $payments,
            'consigneeOptions' => $this->portalConsignees($request),
            'companyOptions' => $this->companyOptions($request),
        ]);
    }

    public function transporters(Request $request)
    {
        $consigneeIds = $this->allowedConsigneeIds($request);
        $companyId = (int) $request->input('company_id', 0);

        $summaryRows = LorryReceipt::query()
            ->join('consignees', 'lorry_receipts.customer_id', '=', 'consignees.id')
            ->leftJoin('companies', 'consignees.company_id', '=', 'companies.id')
            ->selectRaw('companies.id as company_id')
            ->selectRaw('companies.name as company_name')
            ->selectRaw('COUNT(*) as total_lrs')
            ->selectRaw("SUM(CASE WHEN delivery_status = 'Delivered' THEN 1 ELSE 0 END) as delivered_lrs")
            ->selectRaw("SUM(CASE WHEN delivery_status = 'Pending' THEN 1 ELSE 0 END) as pending_lrs")
            ->selectRaw("SUM(CASE WHEN delivery_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_lrs")
            ->selectRaw('SUM(amount) as total_amount')
            ->whereIn('customer_id', $consigneeIds)
            ->when($companyId > 0, function ($query) use ($companyId) {
                $query->where('lorry_receipts.company_id', $companyId);
            })
            ->groupBy('companies.id', 'companies.name')
            ->orderByDesc('total_lrs')
            ->get();

        return view('customer.transporters.index', [
            'summaryRows' => $summaryRows,
            'consigneeOptions' => $this->portalConsignees($request),
            'companyOptions' => $this->companyOptions($request),
        ]);
    }
}
