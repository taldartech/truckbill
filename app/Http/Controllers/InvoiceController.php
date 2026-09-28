<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Services\InvoiceNumberService;
use App\Services\Notifications\InvoiceNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceNumberService $invoiceNumberService,
        private InvoiceNotificationService $invoiceNotificationService
    ) {}

    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getInvoicesData($request);
        }

        $summaryQuery = Invoice::query();
        $this->applyInvoiceFilters($summaryQuery, $request);

        $stats = (clone $summaryQuery)
            ->selectRaw(
                'COUNT(*) as total_invoices,
                 COALESCE(SUM(total_amount), 0) as total_amount,
                 SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as paid_count,
                 SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as unpaid_count,
                 SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as partial_count',
                ['paid', 'pending', 'partial']
            )
            ->first();

        $totalInvoices = (int) ($stats->total_invoices ?? 0);
        $totalAmount = (float) ($stats->total_amount ?? 0);
        $paidCount = (int) ($stats->paid_count ?? 0);
        $unpaidCount = (int) ($stats->unpaid_count ?? 0);
        $partialCount = (int) ($stats->partial_count ?? 0);

        $companyId = Auth::user()->company_id;
        $bankLedgers = $companyId ? \App\Models\Ledger::where('company_id', $companyId)
            ->whereHas('accountGroup', fn ($q) => $q->whereIn('code', ['BANK_ACCOUNTS', 'CASH_IN_HAND']))
            ->where('is_active', true)
            ->orderBy('name')
            ->get() : collect();

        return view('invoices.index', compact(
            'totalInvoices',
            'totalAmount',
            'paidCount',
            'unpaidCount',
            'partialCount',
            'bankLedgers'
        ));
    }

    public function getInvoicesData(Request $request)
    {
        try {
            $paymentTotalsSub = DB::table('invoice_payments')
                ->select('invoice_id')
                ->selectRaw('COALESCE(SUM(amount + COALESCE(tds_amount, 0) + COALESCE(deduction_amount, 0)), 0) as paid_amount_sum')
                ->groupBy('invoice_id');

            $latestInvoiceIdByCompanySub = Invoice::query()
                ->selectRaw('company_id, MAX(id) as latest_invoice_id')
                ->groupBy('company_id')
                ->toBase();

            $query = Invoice::with(['consignee'])
                ->leftJoin('companies', 'invoices.company_id', '=', 'companies.id')
                ->leftJoin('consignees', 'invoices.customer_id', '=', 'consignees.id')
                ->leftJoinSub($paymentTotalsSub, 'invoice_payment_totals', 'invoices.id', '=', 'invoice_payment_totals.invoice_id')
                ->leftJoinSub($latestInvoiceIdByCompanySub, 'invoice_latest_by_company', 'invoices.company_id', '=', 'invoice_latest_by_company.company_id')
                ->select('invoices.*')
                ->selectRaw('COALESCE(invoice_payment_totals.paid_amount_sum, 0) as paid_amount_sum')
                ->selectRaw('CASE WHEN (COALESCE(invoices.net_amount, invoices.total_amount) - COALESCE(invoice_payment_totals.paid_amount_sum, 0)) > 0 THEN (COALESCE(invoices.net_amount, invoices.total_amount) - COALESCE(invoice_payment_totals.paid_amount_sum, 0)) ELSE 0 END as remaining_amount_value')
                ->selectRaw('(invoices.id = invoice_latest_by_company.latest_invoice_id) as is_latest_for_company');

            $this->applyInvoiceFilters($query, $request, 'invoices');

            return DataTables::of($query)
                ->addColumn('invoice_link', function ($row) {
                    $invoiceLabel = $row->invoice_number
                        ? $row->invoice_number.($row->manual_invoice_number ? ' ('.$row->manual_invoice_number.')' : '')
                        : $row->manual_invoice_number;

                    return '<a href="#" class="text-primary invoice-details-link" data-id="'.$row->id.'">'.$invoiceLabel.'</a>';
                })
                ->addColumn('memo_numbers', function ($row) {
                    // if (is_array($row->memo_ids)) {
                    //     return implode(', ', $row->memo_ids);
                    // }
                    if (empty($row->memo_ids)) {
                  return '-';
                     }
                   $memoIds = is_array($row->memo_ids)
                    ? $row->memo_ids
                  : [$row->memo_ids];

                 $memos = DB::table('lorry_receipts')
                   ->whereIn('memo_id', $memoIds)
                   ->select('memo_id', 'memo_created_at')
                   ->get()
                   ->keyBy('memo_id');

                    // return (string) ($row->memo_ids ?? '-');
                    return collect($memoIds)->map(function ($memoId) use ($memos) {
                    $memo = $memos->get($memoId);

                      $html = '<div>'.e($memoId).'</div>';

                     if ($memo && $memo->memo_created_at) {
                     $html .= '<small class="text-muted">'
                    .date('d-m-Y h:i A', strtotime($memo->memo_created_at))
                    .'</small>';
                   }

                   return $html;
                   })->implode('');
                })
                ->addColumn('consignee_name', function ($row) {
                    return $row->consignee ? $row->consignee->name : '-';
                })
                ->addColumn('generated_date_formatted', function ($row) {
                    return $row->generated_date ? date('d-m-Y', strtotime($row->generated_date)) : '-';
                })
                ->addColumn('total_amount_formatted', function ($row) {
                    return $row->total_amount ? ''.number_format($row->total_amount, 2) : '0.00';
                })
                ->addColumn('paid_amount_formatted', function ($row) {
                    $paidAmount = (float) ($row->paid_amount_sum ?? 0);

                    return ''.number_format($paidAmount, 2);
                })
                ->addColumn('remaining_amount_formatted', function ($row) {
                    $remaining = (float) ($row->remaining_amount_value ?? 0);
                    $class = $remaining > 0 ? 'text-danger' : 'text-brand-primary';

                    return '<span class="'.$class.'">'.number_format($remaining, 2).'</span>';
                })
                ->addColumn('payment_status_badge', function ($row) {
                    $statusClass = match ($row->payment_status) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'partial' => 'info',
                        default => 'secondary'
                    };
                    $statusText = ucfirst($row->payment_status ?? 'Unknown');

                    return '<span class="badge bg-'.$statusClass.'">'.$statusText.'</span>';
                })
                ->addColumn('actions', function ($row) {
                    return $this->getActionButtons($row);
                })
                
                ->filterColumn('consignee_name', function ($query, $keyword) {
                    $query->where('consignees.name', 'like', "%{$keyword}%");
                })
         
                ->filterColumn('invoice_link', function ($query, $keyword) {
                    $query->where(function ($subQuery) use ($keyword) {
                        $subQuery->where('invoices.invoice_number', 'like', "%{$keyword}%")
                            ->orWhere('invoices.manual_invoice_number', 'like', "%{$keyword}%");
                    });
                    
                })
              
                ->filterColumn('paid_amount_formatted', function ($query, $keyword) {
                    $query->whereRaw('COALESCE(invoice_payment_totals.paid_amount_sum, 0) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('remaining_amount_formatted', function ($query, $keyword) {
                    $query->whereRaw('(CASE WHEN (COALESCE(invoices.net_amount, invoices.total_amount) - COALESCE(invoice_payment_totals.paid_amount_sum, 0)) > 0 THEN (COALESCE(invoices.net_amount, invoices.total_amount) - COALESCE(invoice_payment_totals.paid_amount_sum, 0)) ELSE 0 END) LIKE ?', ["%{$keyword}%"]);
                })
                ->orderColumn('consignee_name', 'consignees.name $1')
                ->orderColumn('invoice_link', 'invoices.invoice_number $1')
                ->orderColumn('paid_amount_formatted', 'paid_amount_sum $1')
                ->orderColumn('remaining_amount_formatted', 'remaining_amount_value $1')
               ->rawColumns(['invoice_link', 'payment_status_badge', 'actions', 'remaining_amount_formatted', 'memo_numbers'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('DataTables error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getActionButtons($invoice)
    {
        $actions = '<div class="app-index-table-actions">';

        $actions .= '<a href="'.route('invoices.show', $invoice->id).'" class="btn btn-sm btn-icon btn-action-view" title="View Invoice" data-bs-toggle="tooltip" target="_blank">';
        $actions .= '<i class="bi bi-eye"></i></a>';

        $actions .= '<a href="'.route('reports.party-bill-export', ['invoice_number' => $invoice->id, 'type' => 'pdf']).'" class="btn btn-sm btn-icon btn-action-download" title="View PDF" data-bs-toggle="tooltip" target="_blank">';
        $actions .= '<i class="bi bi-file-earmark-text"></i></a>';

        $paidAmountForRow = isset($invoice->paid_amount_sum)
        ? (float) $invoice->paid_amount_sum
        : (float) $invoice->paid_amount;
        $remainingAmountForRow = isset($invoice->remaining_amount_value)
            ? (float) $invoice->remaining_amount_value
            : (float) $invoice->remaining_amount;

        if (Auth::user()->canActAsAdmin() && $remainingAmountForRow > 0) {
            $invoiceLabel = $invoice->invoice_number
                ? $invoice->invoice_number.($invoice->manual_invoice_number ? ' ('.$invoice->manual_invoice_number.')' : '')
                : $invoice->manual_invoice_number;

            $totalAmountToCollect = (float) ($invoice->net_amount ?? $invoice->total_amount);
            $hasGst = (($invoice->cgst ?? 0) > 0 || ($invoice->sgst ?? 0) > 0 || ($invoice->igst ?? 0) > 0);
            $baseAmountForTds = ($hasGst && ($invoice->base_amount ?? 0) > 0)
                ? (float) $invoice->base_amount
                : $totalAmountToCollect;

            $actions .= '<button type="button" class="btn btn-sm btn-icon btn-action-mark-paid add-payment-btn" title="Add Payment" data-bs-toggle="tooltip"';
            $actions .= ' data-invoice-id="'.$invoice->id.'"';
            $actions .= ' data-invoice-number="'.e($invoiceLabel).'"';
            $actions .= ' data-total-amount="'.$totalAmountToCollect.'"';
            $actions .= ' data-base-amount="'.$baseAmountForTds.'"';
            $actions .= ' data-paid-amount="'.$paidAmountForRow.'"';
            $actions .= ' data-remaining-amount="'.$remainingAmountForRow.'">';
            $actions .= '<i class="bi bi-currency-rupee"></i></button>';
        }

        if (Auth::user()->canActAsAdmin() && $invoice->payment_status === 'pending') {
            $actions .= '<a href="'.route('invoices.edit', $invoice->id).'" class="btn btn-sm btn-icon btn-action-edit" title="Edit Invoice" data-bs-toggle="tooltip">';
            $actions .= '<i class="bi bi-pencil"></i></a>';
        }

    if ($invoice->payment_status === 'pending' && $this->invoiceRowIsLatestForCompany($invoice)) {
    $actions .= '<form method="POST" class="invoice-delete-form" action="'.route('invoices.destroy', $invoice->id).'">';
    $actions .= csrf_field();
    $actions .= method_field('DELETE');

    $actions .= '<button type="button" class="btn btn-sm btn-icon btn-action-delete invoice-delete-btn"';
    $actions .= ' data-bs-toggle="modal" data-bs-target="#invoiceDeleteModal"';
    $actions .= ' data-invoice-id="'.$invoice->id.'"';
    $actions .= ' data-invoice-number="'.e($invoice->invoice_number).'"';
    $actions .= '>';

    $actions .= '<i class="bi bi-trash"></i>';
    $actions .= '</button>';

    $actions .= '</form>';
}

        $actions .= '</div>';

        return $actions;
    }

    private function applyInvoiceFilters(Builder $query, Request $request, string $tablePrefix = ''): void
    {
        $column = static fn (string $name): string => $tablePrefix !== '' ? $tablePrefix.'.'.$name : $name;

        $generatedDateFrom = trim((string) $request->input('generated_date_from', ''));
        $generatedDateTo = trim((string) $request->input('generated_date_to', ''));
        if ($generatedDateFrom !== '' && $generatedDateTo !== '') {
            $query->whereBetween($column('generated_date'), [$generatedDateFrom, $generatedDateTo]);
        } elseif ($generatedDateFrom !== '') {
            $query->whereDate($column('generated_date'), '>=', $generatedDateFrom);
        } elseif ($generatedDateTo !== '') {
            $query->whereDate($column('generated_date'), '<=', $generatedDateTo);
        }

        $manualInvoiceNumber = trim((string) $request->input('manual_invoice_number', ''));
        if ($manualInvoiceNumber !== '') {
            $query->where(function ($subQuery) use ($manualInvoiceNumber, $column) {
                $subQuery->where($column('manual_invoice_number'), 'like', '%'.$manualInvoiceNumber.'%')
                    ->orWhere($column('invoice_number'), 'like', '%'.$manualInvoiceNumber.'%');
            });
        }

        $customer = trim((string) $request->input('customer', ''));
        if ($customer !== '') {
            $query->whereHas('consignee', function ($subQuery) use ($customer) {
                $subQuery->where('name', 'like', '%'.$customer.'%');
            });
        }

        if ($request->filled('payment_status')) {
            $query->where($column('payment_status'), (string) $request->input('payment_status'));
        }

        if (Auth::user()->company_id) {
            $query->where($column('company_id'), Auth::user()->company_id);
        }
    }

    public function create()
    {
        $company = Auth::user()->company;
        $company->loadMissing('subscription');
        if (! $company->subscriptionCanWrite()) {
            return redirect()->route('invoices.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        $user = Auth::user();
        $company = null;

        if ($user && $user->company_id) {
            $company = Company::with('state')->find($user->company_id);
        }

        /** @var array{id: int, text: string}|null $invoiceCustomerSelectPrefill */
        $invoiceCustomerSelectPrefill = null;
        $oldCustomerId = old('customer_id');
        if ($oldCustomerId && $user->company_id) {
            $prefillRow = Consignee::query()
                ->where('company_id', $user->company_id)
                ->whereKey((int) $oldCustomerId)
                ->first(['id', 'name']);
            if ($prefillRow !== null) {
                $invoiceCustomerSelectPrefill = [
                    'id' => $prefillRow->id,
                    'text' => $prefillRow->name ?: 'Not Set',
                ];
            }
        }

        return view('invoices.create', compact('company', 'invoiceCustomerSelectPrefill'));
    }

    public function getCustomerMemos(Request $request, $customerId)
    {
        $companyId = Auth::user()->company_id;
        $currentMemoIds = [];

        if ($request->filled('invoice_id')) {
            $invoice = Invoice::query()
                ->where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->find($request->input('invoice_id'));

            if ($invoice) {
                $currentMemoIds = $this->invoiceMemoIds($invoice);
            }
        }

        $oldMemoIds = $request->old('memo_ids', []);

        $memos = DB::table('lorry_receipts')
            ->select('memo_id')
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->whereNotNull('memo_id')
            ->where(function ($query) use ($currentMemoIds) {
                $query->where('invoice_generated', 0);

                if (! empty($currentMemoIds)) {
                    $query->orWhereIn('memo_id', $currentMemoIds);
                }
            })
            ->distinct()
            ->orderBy('memo_id')
            ->get()
            ->map(function ($memo) use ($currentMemoIds, $oldMemoIds) {
                $memoId = $memo->memo_id;

                return [
                    'id' => $memoId,
                    'memo_number' => 'Memo #'.$memoId,
                    'selected' => (! empty($oldMemoIds) && in_array($memoId, $oldMemoIds, false))
                        || (empty($oldMemoIds) && in_array($memoId, $currentMemoIds, false)),
                ];
            });

        return response()->json($memos);
    }

    public function getAllCustomerMemos($customerId)
    {
        $memos = LorryReceipt::where('company_id', Auth::user()->company_id)
            ->where('customer_id', $customerId)
            ->whereNotNull('memo_id')
            ->distinct()
            ->pluck('memo_id');

        return response()->json($memos);
    }

    public function store(Request $request)
    {
        $companyId = (int) Auth::user()->company_id;
        $data = $request->validate([
            'manual_invoice_number' => [
                'nullable',
                'string',
                Rule::unique('invoices')->where(
                    fn ($query) => $query->where('company_id', $companyId)
                ),
            ],
            'invoice_type' => 'required|in:Reverse,Forward',
            'generated_date' => 'required|date',
            'customer_id' => 'required|exists:consignees,id',
            'total_amount' => 'required|numeric|min:0',

            'memo_ids.*' => 'exists:lorry_receipts,memo_id',
            'net_amount' => 'nullable|numeric|min:0',
            'sgst' => 'nullable|numeric|min:0',
            'cgst' => 'nullable|numeric|min:0',
            'igst' => 'nullable|numeric|min:0',
            'base_amount' => 'nullable|numeric|min:0',
        ]);
        $data['payment_status'] = 'pending';
        $data['company_id'] = $companyId;
        $data['manual_invoice_number'] = $request->filled('manual_invoice_number')
            ? trim((string) $request->input('manual_invoice_number'))
            : null;

        $sgst = (float) ($data['sgst'] ?? 0);
        $cgst = (float) ($data['cgst'] ?? 0);
        $igst = (float) ($data['igst'] ?? 0);
        $totalGst = $sgst + $cgst + $igst;

        if (($data['invoice_type'] ?? '') === 'Reverse') {
            $data['base_amount'] = $data['base_amount'] ?? (float) $data['total_amount'];
            $data['total_amount'] = (float) $data['base_amount'] + $totalGst;
            $data['net_amount'] = (float) $data['base_amount'];
        } else {
            $data['base_amount'] = $data['base_amount'] ?? ((float) $data['total_amount'] - $totalGst);
        }

        // Separate memo_ids from the rest of invoice data
        $memoIds = $request->input('memo_ids', []);
        $company = Company::query()->findOrFail($companyId);
        $createdInvoice = null;
        DB::transaction(function () use ($memoIds, $data, $company, &$createdInvoice): void {
            // Always use auto-generated primary invoice number.
            $data['invoice_number'] = $this->invoiceNumberService->generate(
                $company,
                (string) $data['generated_date']
            );

            // Update lorry_receipts before invoice creation individually to trigger auditing
            if (! empty($memoIds)) {
                $lorryReceipts = LorryReceipt::whereIn('memo_id', $memoIds)->where('company_id', $company->id)->lockForUpdate()->get();
                foreach ($lorryReceipts as $lr) {
                    $lr->invoice_generated = 1;
                    $lr->save();
                }
                $data['lr_ids'] = $lorryReceipts->pluck('id')->toArray();
                $data['memo_ids'] = array_values(array_unique($memoIds));
            }

            $createdInvoice = Invoice::create($data);
        });

        if ($createdInvoice instanceof Invoice) {
            $createdInvoice->loadMissing(['company', 'consignee', 'payments']);
            $this->invoiceNotificationService->notifyForCurrentStatus($createdInvoice, Auth::user());

            try {
                app(\App\Services\AccountingPostingService::class)->postInvoice($createdInvoice);
            } catch (\Throwable $e) {
                Log::error('Accounting post invoice failed: '.$e->getMessage());
            }
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Request $request, Invoice $invoice)
    {
        if (Auth::user()->company_id && $invoice->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this invoice.');
        }
        $invoice->load(['company', 'consignee', 'payments.creator']);
        $customDetails = collect();
        $memos = collect();

        if ($invoice->custom_invoice) {
            $invoice->load(['customInvoiceDetails.lorryReceipt.truck']);
            $customDetails = $invoice->customInvoiceDetails->sortBy('sort_order')->values();
        } else {
            $lrIds = $invoice->lr_ids ?? [];
            if (! empty($lrIds)) {
                $memos = DB::table('lorry_receipts')
                    ->select('memo_id','memo_created_at', 'lr_number', 'source_city as from_location', 'destination_city as to_location', 'fr_rate as rate', 'amount')
                    ->whereIn('id', $lrIds)
                    ->where('company_id', $invoice->company_id)
                    ->whereNotNull('memo_id')
                    ->get();
            }
        }

        $companyLogoUrl = $this->resolveCompanyLogoPublicUrl($invoice->company);

        if ($request->ajax()) {
            $sgst = $invoice->sgst ?? 0;
            $cgst = $invoice->cgst ?? 0;
            $igst = $invoice->igst ?? 0;
            $totalGst = $sgst + $cgst + $igst;
            $baseAmount = $invoice->base_amount ?? (($invoice->invoice_type ?? '') === 'Reverse'
                ? ($invoice->total_amount ?? 0)
                : (($invoice->net_amount ?? 0) - $totalGst));
            $netAmount = $invoice->net_amount ?? 0;
            $sgstRate = 0;
            $cgstRate = 0;
            $igstRate = 0;
            if ($baseAmount > 0) {
                if ($sgst > 0) {
                    $sgstRate = ($sgst / $baseAmount) * 100;
                }
                if ($cgst > 0) {
                    $cgstRate = ($cgst / $baseAmount) * 100;
                }
                if ($igst > 0) {
                    $igstRate = ($igst / $baseAmount) * 100;
                }
            }

            return response()->json([
                'id' => $invoice->id,
                'company_logo_url' => $companyLogoUrl,
                'invoice_number' => $invoice->invoice_number,
                'manual_invoice_number' => $invoice->manual_invoice_number,
                'invoice_type' => $invoice->invoice_type,
                'generated_date' => $invoice->generated_date,
                'payment_status' => $invoice->payment_status,
                'total_amount' => $baseAmount,
                'net_amount' => $invoice->net_amount,
                'base_amount' => $invoice->base_amount ?? $baseAmount,
                'sgst' => $sgst,
                'cgst' => $cgst,
                'igst' => $igst,
                'sgst_rate' => $sgstRate,
                'cgst_rate' => $cgstRate,
                'igst_rate' => $igstRate,
                'total_gst' => $totalGst,
                'paid_amount' => $invoice->paid_amount,
                'remaining_amount' => $invoice->remaining_amount,
                'payments' => $invoice->payments,
                'company' => $invoice->company,
                'consignee' => $invoice->consignee,
                'memos' => $memos,
                'created_by' => $invoice->created_by,
                'updated_by' => $invoice->updated_by,
                'creator' => $invoice->creator,
                'updater' => $invoice->updater,
            ]);
        }

        $companyId = $invoice->company_id ?: Auth::user()->company_id;
        $bankLedgers = $companyId ? \App\Models\Ledger::where('company_id', $companyId)
            ->whereHas('accountGroup', fn ($q) => $q->whereIn('code', ['BANK_ACCOUNTS', 'CASH_IN_HAND']))
            ->where('is_active', true)
            ->orderBy('name')
            ->get() : collect();

        return view('invoices.show', compact('invoice', 'memos', 'customDetails', 'companyLogoUrl', 'bankLedgers'));
    }

    public function edit(Invoice $invoice)
    {
        if (Auth::user()->company_id && $invoice->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this invoice.');
        }
        $company = Auth::user()->company;
        $company->loadMissing('subscription');
        if (! $company->subscriptionCanWrite()) {
            return redirect()->route('invoices.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        $invoice->loadMissing(['consignee.state', 'company.state']);
        $selectedMemoIds = $this->invoiceMemoIds($invoice);

        return view('invoices.edit', compact('invoice', 'selectedMemoIds'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (Auth::user()->company_id && $invoice->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this invoice.');
        }
        $company = Auth::user()->company;
        $company->loadMissing('subscription');
        if (! $company->subscriptionCanWrite()) {
            return redirect()->route('invoices.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        if ($invoice->payment_status === 'paid' || $invoice->payment_status === 'partial') {
            return redirect()->back()->with('error', 'Cannot edit a paid or partial invoice.');
        }
        $rules = [
            'manual_invoice_number' => [
                'nullable',
                'string',
                Rule::unique('invoices')->where(
                    fn ($query) => $query->where('company_id', Auth::user()->company_id)
                )->ignore($invoice->id),
            ],
            'generated_date' => 'required|date',
        ];

        if (! $invoice->custom_invoice) {
            $rules['memo_ids'] = 'required|array|min:1';
            $rules['memo_ids.*'] = 'exists:lorry_receipts,memo_id';
        }

        $data = $request->validate($rules);
        $companyId = (int) Auth::user()->company_id;

        DB::transaction(function () use ($invoice, $data, $request, $company, $companyId): void {
            $invoice->manual_invoice_number = filled($data['manual_invoice_number'] ?? null)
                ? trim((string) $data['manual_invoice_number'])
                : null;
            $invoice->generated_date = $data['generated_date'];

            if (! $invoice->custom_invoice) {
                $memoIds = $request->input('memo_ids', []);
                $this->syncInvoiceMemos($invoice, $memoIds, $companyId);

                $lrs = LorryReceipt::query()
                    ->whereIn('id', $invoice->lr_ids ?? [])
                    ->where('company_id', $companyId)
                    ->get();

                $amounts = $this->calculateInvoiceAmounts(
                    round((float) $lrs->sum('amount'), 2),
                    (string) $invoice->invoice_type,
                    $company->loadMissing('state'),
                    $invoice->consignee()->with('state')->first()
                );

                $invoice->fill($amounts);
            }

            $invoice->save();
        });

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Request $request, Invoice $invoice)
   {
    if (Auth::user()->company_id && $invoice->company_id !== Auth::user()->company_id) {
        abort(403, 'Unauthorized access to this invoice.');
    }

    $company = Auth::user()->company;
    $company->loadMissing('subscription');

    if (! $company->subscriptionCanWrite()) {
        return redirect()->route('invoices.index')
            ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
    }

    // Delete remark is required
    $request->validate([
        'delete_remark' => ['required', 'string', 'max:1000'],
    ]);

    $deleteRemark = $request->input('delete_remark');

    if ($invoice->payment_status !== 'pending') {
        return redirect()->back()->with('error', 'Cannot delete invoice.');
    }

    if (! $this->isLastInvoiceForCompany($invoice)) {
        return redirect()->back()->with('error', 'Only the latest invoice can be deleted.');
    }

    try {
        DB::transaction(function () use ($invoice, $deleteRemark): void {

            // Custom invoices do not flip LR invoice_generated on create,
            // so do not reset it on delete.
            if (! $invoice->custom_invoice) {
                $lrIds = $invoice->lr_ids ?? [];

                if (! empty($lrIds)) {
                    $lorryReceipts = LorryReceipt::query()
                        ->whereIn('id', $lrIds)
                        ->lockForUpdate()
                        ->get();

                    foreach ($lorryReceipts as $lr) {
                        $lr->invoice_generated = 0;
                        $lr->save();
                    }
                }
            }

            // Save delete information
            $invoice->deleted_by = Auth::id();
            $invoice->deleted_remark = $deleteRemark;
            $invoice->save();

            // Soft delete
            $invoice->delete();
        });
    } catch (\Throwable $e) {
        Log::error('Invoice delete failed', [
            'invoice_id' => $invoice->id,
            'error' => $e->getMessage(),
        ]);

        return redirect()->back()
            ->with('error', 'Failed to delete invoice. Please try again.');
    }

    return redirect()->route('invoices.index')
        ->with('success', 'Invoice deleted.');
}

    private function invoiceRowIsLatestForCompany(Invoice $invoice): bool
    {
        if (array_key_exists('is_latest_for_company', $invoice->getAttributes())) {
            return (int) $invoice->getAttribute('is_latest_for_company') === 1;
        }

        return $this->isLastInvoiceForCompany($invoice);
    }

    private function isLastInvoiceForCompany(Invoice $invoice): bool
    {
        $lastInvoiceId = Invoice::query()
            ->where('company_id', $invoice->company_id)
            ->max('id');

        return (int) $lastInvoiceId === (int) $invoice->id;
    }

    public function getLrs(Request $request)
    {
        $memoIds = $request->input('memo_ids', []);

        if (is_string($memoIds)) {
            $memoIds = explode(',', $memoIds);
        }

        if (! is_array($memoIds)) {
            $memoIds = [];
        }

        $memoIds = array_values(array_filter(array_map(
            static fn ($memoId): string => trim((string) $memoId),
            $memoIds
        ), static fn (string $memoId): bool => $memoId !== ''));

        $currentLrIds = [];

        if ($request->filled('invoice_id') && Auth::user()->company_id) {
            $invoice = Invoice::query()
                ->where('company_id', Auth::user()->company_id)
                ->find($request->input('invoice_id'));

            if ($invoice) {
                $currentLrIds = $invoice->lr_ids ?? [];
            }
        }

        if ($memoIds === []) {
            return response()->json([]);
        }

        $lrs = LorryReceipt::whereIn('memo_id', $memoIds)
            ->when(Auth::user()->company_id, fn ($q) => $q->where('company_id', Auth::user()->company_id))
            ->with(['transporter.transporterMaster', 'consignor', 'consignee', 'truck'])
            ->select('id', 'lr_number', 'memo_id', 'source_city', 'destination_city', 'transporter_id', 'consignor_id', 'consignee_id', 'gr_date', 'amount', 'truck_id', 'weight')
            ->where(function ($query) use ($currentLrIds) {
                $query->where('invoice_generated', 0);

                if (! empty($currentLrIds)) {
                    $query->orWhereIn('id', $currentLrIds);
                }
            })
            ->get()
            ->groupBy('memo_id');

        $result = [];
        foreach ($memoIds as $memoId) {
            $result[$memoId] = $lrs->get($memoId)?->map(function ($lr) {
                return [
                    'lr_number' => $lr->lr_number,
                    'source_city' => $lr->source_city,
                    'destination_city' => $lr->destination_city,
                    'transporter' => $lr->transporter?->name ?? '',
                    'consignor' => $lr->consignor?->name ?? '',
                    'consignee' => $lr->consignee?->name ?? '',
                    'gr_date' => $lr->gr_date,
                    'amount' => $lr->amount,
                    'truck_number' => $lr->truck?->truck_number ?? '',
                    'weight' => $lr->weight,
                ];
            })->toArray() ?? [];
        }

        return response()->json($result);
    }

    public function getAllInvoiceNumbers()
    {
        $invoices = Invoice::where('company_id', Auth::user()->company_id)
            ->orderByDesc('id')
            ->get(['id', 'manual_invoice_number', 'invoice_number']);

        return response()->json($invoices);
    }

    public function getInvoiceNumbersForCustomer($customerId): JsonResponse
    {
        $companyId = Auth::user()->company_id;
        if (! $companyId || ! ctype_digit((string) $customerId) || (int) $customerId < 1) {
            return response()->json([]);
        }

        $customerIdInt = (int) $customerId;

        $invoices = Invoice::where('customer_id', $customerIdInt)
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->limit(5000)
            ->get(['id', 'manual_invoice_number', 'invoice_number']);

        return response()->json($invoices);
    }

    public function checkManualInvoiceNumberUniqueness(Request $request)
    {
        $manualNumber = trim((string) $request->input('manual_invoice_number', ''));
        $invoiceNumber = trim((string) $request->input('invoice_number', ''));
        $invoiceId = $request->input('invoice_id'); // Get invoice ID if editing
        $companyId = Auth::user()->company_id;

        if ($manualNumber === '') {
            return response()->json([
                'valid' => true,
                'message' => 'Optional field.',
            ]);
        }

        $query = Invoice::where('manual_invoice_number', $manualNumber)
            ->where('company_id', $companyId);

        // Ignore current invoice if editing
        if ($invoiceId) {
            $query->where('id', '!=', $invoiceId);
        }

        $exists = $query->exists();

        if ($exists) {
            return response()->json([
                'valid' => false,
                'message' => 'This invoice number is already used in your company.',
            ]);
        } else {
            return response()->json([
                'valid' => true,
                'message' => 'Available!',
            ]);
        }
    }

    /**
     * Store a payment for an invoice
     */
    public function storePayment(Request $request, Invoice $invoice)
    {
        if (Auth::user()->company_id && $invoice->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this invoice.');
        }
        $validated = $request->validate([
            'mode' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'tds_amount' => 'nullable|numeric|min:0',
            'deduction_amount' => 'nullable|numeric|min:0',
            'deduction_reason' => 'nullable|string|max:255',
            'remark' => 'nullable|string|max:1000',
            'payment_date' => 'required|date',
            'bank_ledger_id' => 'nullable|exists:ledgers,id',
        ]);

        $validated['tds_amount'] = (float) ($validated['tds_amount'] ?? 0);
        $validated['deduction_amount'] = (float) ($validated['deduction_amount'] ?? 0);
        $totalSettledThisTransaction = round((float) $validated['amount'] + $validated['tds_amount'] + $validated['deduction_amount'], 2);

        if ($totalSettledThisTransaction <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Total payment settlement (Remitted Amount + TDS + Deductions) must be greater than zero.',
            ], 422);
        }

        $amountToCollect = (float) ($invoice->net_amount ?? $invoice->total_amount);
        $currentPaidAmount = (float) $invoice->paid_amount;
        $remainingAmount = max(0.0, round($amountToCollect - $currentPaidAmount, 2));

        if ($totalSettledThisTransaction > ($remainingAmount + 0.01)) {
            return response()->json([
                'success' => false,
                'message' => 'Total settlement amount (₹'.number_format($totalSettledThisTransaction, 2).') cannot exceed the remaining balance of ₹'.number_format($remainingAmount, 2),
            ], 422);
        }

        $payment = $invoice->payments()->create($validated);
        $invoice->refresh();

        $newPaidAmount = (float) $invoice->paid_amount;
        if ($newPaidAmount >= ($amountToCollect - 0.01)) {
            $invoice->payment_status = 'paid';
        } elseif ($newPaidAmount > 0.01) {
            $invoice->payment_status = 'partial';
        } else {
            $invoice->payment_status = 'pending';
        }
        $invoice->save();

        $this->invoiceNotificationService->notifyForCurrentStatus($invoice, Auth::user(), [
            'paymentAmount' => (string) number_format((float) $validated['amount'], 2),
            'paymentDate' => Carbon::parse((string) $validated['payment_date'])->format('d M Y'),
        ]);

        try {
            app(\App\Services\AccountingPostingService::class)->postInvoicePayment($payment);
        } catch (\Throwable $e) {
            Log::error('Accounting post payment failed: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment added successfully',
            'payment' => $payment,
            'paid_amount' => $newPaidAmount,
            'remaining_amount' => max(0.0, round($amountToCollect - $newPaidAmount, 2)),
            'payment_status' => $invoice->payment_status,
        ]);
    }

    /**
     * Get payments for an invoice
     */
    public function getPayments(Invoice $invoice)
    {
        if (Auth::user()->company_id && $invoice->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this invoice.');
        }
        $payments = $invoice->payments()->orderByRaw('COALESCE(payment_date, created_at) DESC')->get();
        $amountToCollect = (float) ($invoice->net_amount ?? $invoice->total_amount);
        $paidAmount = (float) $invoice->paid_amount;
        $remainingAmount = max(0.0, round($amountToCollect - $paidAmount, 2));

        return response()->json([
            'payments' => $payments,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'total_amount' => $invoice->total_amount,
            'net_amount' => $invoice->net_amount,
        ]);
    }

    /**
     * @return list<int|string>
     */
    private function invoiceMemoIds(Invoice $invoice): array
    {
        $memoIds = $invoice->memo_ids ?? [];
        if (! empty($memoIds)) {
            return array_values($memoIds);
        }

        $lrIds = $invoice->lr_ids ?? [];
        if (empty($lrIds)) {
            return [];
        }

        return LorryReceipt::query()
            ->whereIn('id', $lrIds)
            ->where('company_id', $invoice->company_id)
            ->whereNotNull('memo_id')
            ->distinct()
            ->orderBy('memo_id')
            ->pluck('memo_id')
            ->values()
            ->all();
    }

    /**
     * @param  list<int|string>  $memoIds
     */
    private function syncInvoiceMemos(Invoice $invoice, array $memoIds, int $companyId): void
    {
        $memoIds = array_values(array_unique($memoIds));
        $oldLrIds = array_map('intval', $invoice->lr_ids ?? []);

        $newLorryReceipts = LorryReceipt::query()
            ->whereIn('memo_id', $memoIds)
            ->where('company_id', $companyId)
            ->where('customer_id', $invoice->customer_id)
            ->lockForUpdate()
            ->get();

        $foundMemoIds = $newLorryReceipts->pluck('memo_id')->unique()->values()->all();
        if (count($foundMemoIds) !== count($memoIds)) {
            throw ValidationException::withMessages([
                'memo_ids' => ['One or more selected memos are invalid for this customer.'],
            ]);
        }

        $newLrIds = $newLorryReceipts->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        foreach ($newLorryReceipts as $lr) {
            if ($lr->invoice_generated && ! in_array((int) $lr->id, $oldLrIds, true)) {
                throw ValidationException::withMessages([
                    'memo_ids' => ["Memo #{$lr->memo_id} is already invoiced on another bill."],
                ]);
            }
        }

        $removedLrIds = array_diff($oldLrIds, $newLrIds);
        if (! empty($removedLrIds)) {
            foreach (LorryReceipt::query()->whereIn('id', $removedLrIds)->get() as $lr) {
                $lr->invoice_generated = 0;
                $lr->save();
            }
        }

        foreach ($newLorryReceipts as $lr) {
            if (! $lr->invoice_generated) {
                $lr->invoice_generated = 1;
                $lr->save();
            }
        }

        $invoice->memo_ids = $memoIds;
        $invoice->lr_ids = $newLrIds;
    }

    /**
     * @return array{base_amount: float, sgst: float, cgst: float, igst: float, net_amount: float, total_amount: float}
     */
    private function calculateInvoiceAmounts(
        float $baseAmount,
        string $invoiceType,
        Company $company,
        ?Consignee $customer
    ): array {
        $gstRate = ((int) ($company->gst_slab ?? 5)) / 100;
        $companyState = $company->state ? strtolower($company->state->name) : '';
        $customerState = $customer && $customer->state ? strtolower($customer->state->name) : '';

        $sgst = 0.0;
        $cgst = 0.0;
        $igst = 0.0;
        $netAmount = $baseAmount;

        if ($companyState === $customerState && $companyState !== '') {
            if ($invoiceType === 'Forward') {
                $sgst = $baseAmount * ($gstRate / 2);
                $cgst = $baseAmount * ($gstRate / 2);
                $netAmount = $baseAmount + $sgst + $cgst;
            } else {
                $sgst = $baseAmount * ($gstRate / 2);
                $cgst = $baseAmount * ($gstRate / 2);
                $netAmount = $baseAmount;
            }
        } else {
            if ($invoiceType === 'Forward') {
                $igst = $baseAmount * $gstRate;
                $netAmount = $baseAmount + $igst;
            } else {
                $igst = $baseAmount * $gstRate;
                $netAmount = $baseAmount;
            }
        }

        $amounts = [
            'base_amount' => round($baseAmount, 2),
            'sgst' => round($sgst, 2),
            'cgst' => round($cgst, 2),
            'igst' => round($igst, 2),
            'net_amount' => round($netAmount, 2),
        ];

        $amounts['total_amount'] = $invoiceType === 'Forward'
            ? $amounts['net_amount']
            : round($baseAmount + $sgst + $cgst + $igst, 2);

        return $amounts;
    }

    /**
     * Browser URL for the company logo (uses public disk; works even if public/storage symlink is missing for existence checks).
     */
    private function resolveCompanyLogoPublicUrl(?Company $company): ?string
    {
        if ($company === null) {
            return null;
        }

        return $company->logoPublicUrl();
    }
}
