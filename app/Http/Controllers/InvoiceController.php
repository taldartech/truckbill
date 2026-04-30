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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
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

        $totalInvoices = (clone $summaryQuery)->count();
        $totalAmount = (clone $summaryQuery)->sum('total_amount');
        $paidCount = (clone $summaryQuery)->where('payment_status', 'paid')->count();
        $unpaidCount = (clone $summaryQuery)->where('payment_status', 'pending')->count();

        $consignees = Consignee::query()
            ->when(Auth::user()->company_id, fn ($query) => $query->where('company_id', Auth::user()->company_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('invoices.index', compact('consignees', 'totalInvoices', 'totalAmount', 'paidCount', 'unpaidCount'));
    }

    public function getInvoicesData(Request $request)
    {
        try {
            $query = Invoice::with(['company', 'consignee', 'payments'])
                ->leftJoin('companies', 'invoices.company_id', '=', 'companies.id')
                ->leftJoin('consignees', 'invoices.customer_id', '=', 'consignees.id')
                ->select('invoices.*')
                ->selectSub(function ($subQuery) {
                    $subQuery->from('invoice_payments')
                        ->selectRaw('COALESCE(SUM(amount), 0)')
                        ->whereColumn('invoice_payments.invoice_id', 'invoices.id');
                }, 'paid_amount_sum')
                ->selectRaw('GREATEST(0, COALESCE(invoices.net_amount, invoices.total_amount) - (SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE invoice_payments.invoice_id = invoices.id)) as remaining_amount_value');

            $this->applyInvoiceFilters($query, $request, 'invoices');

            return DataTables::of($query)
                ->addColumn('invoice_link', function ($row) {
                    $invoiceLabel = $row->invoice_number
                        ? $row->invoice_number.($row->manual_invoice_number ? ' ('.$row->manual_invoice_number.')' : '')
                        : $row->manual_invoice_number;

                    return '<a href="#" class="text-primary invoice-details-link" data-id="'.$row->id.'">'.$invoiceLabel.'</a>';
                })
                ->addColumn('memo_numbers', function ($row) {
                    if (is_array($row->memo_ids)) {
                        return implode(', ', $row->memo_ids);
                    }

                    return (string) ($row->memo_ids ?? '-');
                })
                ->addColumn('consignee_name', function ($row) {
                    return $row->consignee ? $row->consignee->name : '-';
                })
                ->addColumn('generated_date_formatted', function ($row) {
                    return $row->generated_date ? date('d-m-Y', strtotime($row->generated_date)) : '-';
                })
                ->addColumn('total_amount_formatted', function ($row) {
                    return $row->total_amount ? '₹'.number_format($row->total_amount, 2) : '₹0.00';
                })
                ->addColumn('paid_amount_formatted', function ($row) {
                    $paidAmount = (float) ($row->paid_amount_sum ?? 0);

                    return '₹'.number_format($paidAmount, 2);
                })
                ->addColumn('remaining_amount_formatted', function ($row) {
                    $remaining = (float) ($row->remaining_amount_value ?? 0);
                    $class = $remaining > 0 ? 'text-danger' : 'text-brand-primary';

                    return '<span class="'.$class.'">₹'.number_format($remaining, 2).'</span>';
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
                    $query->whereHas('consignee', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('invoice_link', function ($query, $keyword) {
                    $query->where(function ($subQuery) use ($keyword) {
                        $subQuery->where('invoices.invoice_number', 'like', "%{$keyword}%")
                            ->orWhere('invoices.manual_invoice_number', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('paid_amount_formatted', function ($query, $keyword) {
                    $query->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE invoice_payments.invoice_id = invoices.id) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('remaining_amount_formatted', function ($query, $keyword) {
                    $query->whereRaw('(GREATEST(0, COALESCE(invoices.net_amount, invoices.total_amount) - (SELECT COALESCE(SUM(amount), 0) FROM invoice_payments WHERE invoice_payments.invoice_id = invoices.id))) LIKE ?', ["%{$keyword}%"]);
                })
                ->orderColumn('consignee_name', 'consignees.name $1')
                ->orderColumn('invoice_link', 'invoices.invoice_number $1')
                ->orderColumn('paid_amount_formatted', 'paid_amount_sum $1')
                ->orderColumn('remaining_amount_formatted', 'remaining_amount_value $1')
                ->rawColumns(['invoice_link', 'payment_status_badge', 'actions', 'remaining_amount_formatted'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('DataTables error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getActionButtons($invoice)
    {
        $actions = '<div class="d-flex gap-1">';

        $actions .= '<a href="'.route('reports.party-bill-export', ['invoice_number' => $invoice->id, 'type' => 'pdf']).'" class="btn btn-sm btn-primary" title="View PDF" data-bs-toggle="tooltip" target="_blank">';
        $actions .= '<i class="bi bi-eye"></i></a>';

        if (Auth::user()->canActAsAdmin() && $invoice->payment_status !== 'paid') {
            $actions .= '<a href="'.route('invoices.edit', $invoice->id).'" class="btn btn-sm btn-edit" title="Edit Invoice" data-bs-toggle="tooltip">';
            $actions .= '<i class="bi bi-pencil"></i></a>';
        }

        if ($invoice->payment_status === 'pending' && $this->isLastInvoiceForCompany($invoice)) {
            $actions .= '<form method="POST" action="' . route('invoices.destroy', $invoice->id) . '" style="display: inline;">';
            $actions .= csrf_field();
            $actions .= method_field('DELETE');
            $actions .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm(\'Are you sure you want to delete this invoice?\')">';
            $actions .= '<i class="bi bi-trash"></i>';
            $actions .= '</button>';
            $actions .= '</form>';
        }

        if (Auth::user()->canActAsAdmin() && (float) $invoice->remaining_amount > 0) {
            $invoiceLabel = $invoice->invoice_number
                ? $invoice->invoice_number.($invoice->manual_invoice_number ? ' ('.$invoice->manual_invoice_number.')' : '')
                : $invoice->manual_invoice_number;

            $actions .= '<button type="button" class="btn btn-sm btn-view add-payment-btn" title="Add Payment" data-bs-toggle="tooltip"';
            $actions .= ' data-invoice-id="'.$invoice->id.'"';
            $actions .= ' data-invoice-number="'.e($invoiceLabel).'"';
            $actions .= ' data-total-amount="'.(float) $invoice->total_amount.'"';
            $actions .= ' data-paid-amount="'.(float) $invoice->paid_amount.'"';
            $actions .= ' data-remaining-amount="'.(float) $invoice->remaining_amount.'">';
            $actions .= '<i class="bi bi-currency-rupee"></i></button>';
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
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('invoices.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        $user = Auth::user();
        $company = null;

        if ($user && $user->company_id) {
            $company = Company::with('state')->find($user->company_id);
        }

        $consignees = Consignee::query()
            ->where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        return view('invoices.create', compact('company', 'consignees'));
    }

    public function getCustomerMemos($customerId)
    {
        $memos = DB::table('lorry_receipts')
            ->select('memo_id')
            ->where('company_id', Auth::user()->company_id)
            ->where('customer_id', $customerId)
            ->where('invoice_generated', 0) // Only those not yet invoiced
            ->whereNotNull('memo_id')
            ->distinct()
            ->get()
            ->map(function ($memo) {
                return [
                    'id' => $memo->memo_id,
                    'memo_number' => 'Memo #'.$memo->memo_id,
                    'selected' => request()->old('memo_ids') && in_array($memo->memo_id, request()->old('memo_ids')),
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
                $lorryReceipts = \App\Models\LorryReceipt::whereIn('memo_id', $memoIds)->lockForUpdate()->get();
                foreach ($lorryReceipts as $lr) {
                    $lr->invoice_generated = 1;
                    $lr->save();
                }
                $data['lr_ids'] = $lorryReceipts->pluck('id')->toArray();
            }

            $createdInvoice = Invoice::create($data);
        });

        if ($createdInvoice instanceof Invoice) {
            $createdInvoice->loadMissing(['company', 'consignee', 'payments']);
            $this->invoiceNotificationService->notifyForCurrentStatus($createdInvoice, Auth::user());
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
                    ->select('memo_id', 'lr_number', 'source_city as from_location', 'destination_city as to_location', 'fr_rate as rate', 'amount')
                    ->whereIn('id', $lrIds)
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

        return view('invoices.show', compact('invoice', 'memos', 'customDetails', 'companyLogoUrl'));
    }

    public function edit(Invoice $invoice)
    {
        if (Auth::user()->company_id && $invoice->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this invoice.');
        }
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('invoices.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        $consignees = Consignee::query()
            ->when(Auth::user()->company_id, fn ($query) => $query->where('company_id', Auth::user()->company_id))
            ->orderBy('name')
            ->get();

        // Get all memos for this customer
        $memos = DB::table('lorry_receipts')
            ->select('memo_id')
            ->whereIn('id', $invoice->lr_ids)
            ->whereNotNull('memo_id')
            ->distinct()
            ->get()
            ->map(function ($memo) {
                // Only show memos that have an invoice_id (indicating they're used)
                $hasInvoice = DB::table('lorry_receipts')
                    ->where('memo_id', $memo->memo_id)
                    ->exists();

                return (object) [
                    'memo_id' => $memo->memo_id,
                    'memo_number' => 'Memo #'.$memo->memo_id,
                    'selected' => $hasInvoice,
                ];
            });

        return view('invoices.edit', compact('invoice', 'consignees', 'memos'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (Auth::user()->company_id && $invoice->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this invoice.');
        }
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('invoices.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        if ($invoice->payment_status === 'paid' || $invoice->payment_status === 'partial') {
            return redirect()->back()->with('error', 'Cannot edit a paid or partial invoice.');
        }
        $data = $request->validate([
            'manual_invoice_number' => [
                'required',
                'string',
                Rule::unique('invoices')->where(
                    fn ($query) => $query->where('company_id', Auth::user()->company_id)
                )->ignore($invoice->id),
            ],
            'generated_date' => 'required|date',
        ]);

        $invoice->update($data);

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        if (Auth::user()->company_id && $invoice->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized access to this invoice.');
        }
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('invoices.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        if ($invoice->payment_status !== 'pending') {
            return redirect()->back()->with('error', 'Cannot delete invoice.');
        }
        if (! $this->isLastInvoiceForCompany($invoice)) {
            return redirect()->back()->with('error', 'Only the latest invoice can be deleted.');
        }
        $lr_ids = $invoice->lr_ids ?? [];
        if (! empty($lr_ids)) {
            // Update individually to trigger auditing
            $lorryReceipts = LorryReceipt::whereIn('id', $lr_ids)->get();
            foreach ($lorryReceipts as $lr) {
                $lr->invoice_generated = 0;
                $lr->save();
            }
        }
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    private function isLastInvoiceForCompany(Invoice $invoice): bool
    {
        $lastInvoiceId = Invoice::query()
            ->where('company_id', $invoice->company_id)
            ->max('id');

        return (int) $lastInvoiceId === (int) $invoice->id;
    }

    public function getLrs($memoIds)
    {
        $memoIds = explode(',', $memoIds);
        $lrs = LorryReceipt::whereIn('memo_id', $memoIds)
            ->when(Auth::user()->company_id, fn ($q) => $q->where('company_id', Auth::user()->company_id))
            ->with(['transporter.transporterMaster', 'consignor', 'consignee', 'truck'])
            ->select('lr_number', 'memo_id', 'source_city', 'destination_city', 'transporter_id', 'consignor_id', 'consignee_id', 'gr_date', 'amount', 'truck_id', 'weight')
            ->where('invoice_generated', 0)
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

    public function getInvoiceNumbersForCustomer($customerId)
    {
        $invoices = Invoice::where('customer_id', $customerId)
            ->where('company_id', Auth::user()->company_id)
            ->orderByDesc('id')
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

        $query = \App\Models\Invoice::where('manual_invoice_number', $manualNumber)
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
            'amount' => 'required|numeric|min:0.01',
            'remark' => 'nullable|string|max:1000',
            'payment_date' => 'required|date',
        ]);

        $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
        $paidAmount = $invoice->payments()->sum('amount');
        $remainingAmount = $amountToCollect - $paidAmount;

        if ($validated['amount'] > $remainingAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot exceed the remaining balance of ₹'.number_format($remainingAmount, 2),
            ], 422);
        }

        $payment = $invoice->payments()->create($validated);

        $newPaidAmount = $invoice->payments()->sum('amount');
        if ($newPaidAmount >= $amountToCollect) {
            $invoice->payment_status = 'paid';
        } elseif ($newPaidAmount > 0) {
            $invoice->payment_status = 'partial';
        } else {
            $invoice->payment_status = 'pending';
        }
        $invoice->save();

        $this->invoiceNotificationService->notifyForCurrentStatus($invoice, Auth::user(), [
            'paymentAmount' => (string) number_format((float) $validated['amount'], 2),
            'paymentDate' => Carbon::parse((string) $validated['payment_date'])->format('d M Y'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment added successfully',
            'payment' => $payment,
            'paid_amount' => $newPaidAmount,
            'remaining_amount' => max(0, $amountToCollect - $newPaidAmount),
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
        $amountToCollect = $invoice->net_amount ?? $invoice->total_amount;
        $paidAmount = $invoice->payments()->sum('amount');
        $remainingAmount = max(0, $amountToCollect - $paidAmount);

        return response()->json([
            'payments' => $payments,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'total_amount' => $invoice->total_amount,
            'net_amount' => $invoice->net_amount,
        ]);
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
