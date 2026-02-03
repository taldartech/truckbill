<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\LorryReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getInvoicesData($request);
        }

        $invoices = Invoice::with(['company', 'consignee', 'payments'])->orderByDesc('id');

        // Apply filters
        // if ($request->has('generated_date') && !empty($request->generated_date)) {
        //   $invoices->whereDate('generated_date', $request->generated_date);
        // }

        if ($request->has('generated_date_from') && !empty($request->generated_date_from) && $request->has('generated_date_to') && !empty($request->generated_date_to)) {
            $invoices->whereBetween('generated_date', [$request->generated_date_from, $request->generated_date_to]);
        }

        // Add other existing filters here if needed
        if ($request->has('manual_invoice_number') && !empty($request->manual_invoice_number)) {
            $invoices->where('manual_invoice_number', 'like', '%' . $request->manual_invoice_number . '%');
        }

        if ($request->has('customer') && !empty($request->customer)) {
            $invoices->whereHas('consignee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->customer . '%');
            });
        }

        if ($request->has('company') && !empty($request->company)) {
            $invoices->whereHas('company', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->company . '%');
            });
        }

        if ($request->has('payment_status') && !empty($request->payment_status)) {
            $invoices->where('payment_status', $request->payment_status);
        }

        if (Auth::user()->userRole->name !== 'super_admin') {
            $invoices = $invoices->where('company_id', Auth::user()->company_id);
        }
        $invoices = $invoices->orderBy('generated_date', 'desc');
        $invoices = $invoices->paginate(20);
        return view('invoices.index', compact('invoices'));
    }

    public function getInvoicesData(Request $request)
    {
        try {
            $query = Invoice::with(['company', 'consignee', 'payments'])
                ->leftJoin('companies', 'invoices.company_id', '=', 'companies.id')
                ->leftJoin('consignees', 'invoices.consignee_id', '=', 'consignees.id')
                ->select('invoices.*');

            // Only apply company_id filter for non-super_admin users
            if (Auth::user()->userRole->name !== 'super_admin') {
                $query->where('invoices.company_id', Auth::user()->company_id);
            }

            return DataTables::of($query)
                ->addColumn('company_name', function ($row) {
                    return $row->company ? $row->company->name : '-';
                })
                ->addColumn('consignee_name', function ($row) {
                    return $row->consignee ? $row->consignee->name : '-';
                })
                ->addColumn('generated_date_formatted', function ($row) {
                    return $row->generated_date ? date('d-m-Y', strtotime($row->generated_date)) : '-';
                })
                ->addColumn('total_amount_formatted', function ($row) {
                    return $row->total_amount ? '₹' . number_format($row->total_amount, 2) : '₹0.00';
                })
                ->addColumn('paid_amount_formatted', function ($row) {
                    $paidAmount = $row->payments()->sum('amount');
                    return '₹' . number_format($paidAmount, 2);
                })
                ->addColumn('remaining_amount_formatted', function ($row) {
                    $paidAmount = $row->payments()->sum('amount');
                    $remaining = $row->total_amount - $paidAmount;
                    $class = $remaining > 0 ? 'text-danger' : 'text-brand-primary';
                    return '<span class="' . $class . '">₹' . number_format($remaining, 2) . '</span>';
                })
                ->addColumn('payment_status_badge', function ($row) {
                    $statusClass = match ($row->payment_status) {
                        'paid' => 'brand-primary',
                        'pending' => 'warning',
                        'overdue' => 'danger',
                        default => 'secondary'
                    };
                    $statusText = ucfirst($row->payment_status ?? 'Unknown');
                    return '<span class="badge bg-' . $statusClass . '">' . $statusText . '</span>';
                })
                ->addColumn('invoice_type_badge', function ($row) {
                    $typeClass = match ($row->invoice_type) {
                        'manual' => 'primary',
                        'auto' => 'info',
                        default => 'secondary'
                    };
                    $typeText = ucfirst($row->invoice_type ?? 'Unknown');
                    return '<span class="badge bg-' . $typeClass . '">' . $typeText . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    return $this->getActionButtons($row);
                })
                ->filterColumn('company_name', function ($query, $keyword) {
                    $query->whereHas('company', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('consignee_name', function ($query, $keyword) {
                    $query->whereHas('consignee', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('company_name', 'companies.name $1')
                ->orderColumn('consignee_name', 'consignees.name $1')
                ->rawColumns(['payment_status_badge', 'invoice_type_badge', 'actions', 'remaining_amount_formatted'])
                ->make(true);
        } catch (\Exception $e) {
            Log::error('DataTables error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getActionButtons($invoice)
    {
        $actions = '<div class="btn-group" role="group">';

        // View button
        $actions .= '<a href="' . route('invoices.show', $invoice->id) . '" class="btn btn-sm btn-view" title="View">';
        $actions .= '<i class="bi bi-eye"></i>';
        $actions .= '</a>';

        // Edit button
        $actions .= '<a href="' . route('invoices.edit', $invoice->id) . '" class="btn btn-sm btn-edit" title="Edit">';
        $actions .= '<i class="bi bi-pencil"></i>';
        $actions .= '</a>';

        // Print button
        $actions .= '<a href="' . route('invoices.print', $invoice->id) . '" class="btn btn-sm btn-back" title="Print" target="_blank">';
        $actions .= '<i class="bi bi-printer"></i>';
        $actions .= '</a>';

        // Delete button
        $actions .= '<form method="POST" action="' . route('invoices.destroy', $invoice->id) . '" style="display: inline;">';
        $actions .= csrf_field();
        $actions .= method_field('DELETE');
        $actions .= '<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm(\'Are you sure you want to delete this invoice?\')">';
        $actions .= '<i class="bi bi-trash"></i>';
        $actions .= '</button>';
        $actions .= '</form>';

        $actions .= '</div>';

        return $actions;
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
            $company = Company::find($user->company_id);
        }

        $consignees = Consignee::all()->where('company_id', $user->company_id);

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
                    'memo_number' => 'Memo #' . $memo->memo_id,
                    'selected' => request()->old('memo_ids') && in_array($memo->memo_id, request()->old('memo_ids'))
                ];
            });

        Log::info('Found memos for customer:', ['count' => count($memos)]);

        return response()->json($memos);
    }

    function getAllCustomerMemos($customerId)
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
        // dd($request->all());
        $data = $request->validate([
            'manual_invoice_number' => [
                'required',
                'string',
                Rule::unique('invoices')->where(
                    fn($query) =>
                    $query->where('company_id', Auth::user()->company_id)
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
        ]);
        $data['payment_status'] = 'pending';
        $data['company_id'] = Auth::user()->company_id;




        // Separate memo_ids from the rest of invoice data
        $memoIds = $request->input('memo_ids', []);
        // unset($data['memo_ids']);

        // Update lorry_receipts before invoice creation individually to trigger auditing
        if (!empty($memoIds)) {
            $lorryReceipts = \App\Models\LorryReceipt::whereIn('memo_id', $memoIds)->get();
            foreach ($lorryReceipts as $lr) {
                $lr->invoice_generated = 1;
                $lr->save();
            }
            $data['lr_ids'] = $lorryReceipts->pluck('id')->toArray();
        }

        $invoice = Invoice::create($data);


        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function show(Request $request, Invoice $invoice)
    {
        $invoice->load(['company', 'consignee', 'payments.creator']);
        // Get memos directly from invoice table in invoice tabble their is lr_ids in arrays form fetch lr_ids and then use this lr_ids to fetch 'memo_id', 'lr_number', 'source_city as from_location', 'destination_city as to_location', 'fr_rate as rate', 'amount'
        $lrIds = $invoice->lr_ids;
        $memos = DB::table('lorry_receipts')
            ->select('memo_id', 'lr_number', 'source_city as from_location', 'destination_city as to_location', 'fr_rate as rate', 'amount')
            ->whereIn('id', $lrIds)
            ->whereNotNull('memo_id')
            ->get();

        if ($request->ajax()) {
            return response()->json([
                'id' => $invoice->id,
                'manual_invoice_number' => $invoice->manual_invoice_number,
                'invoice_type' => $invoice->invoice_type,
                'generated_date' => $invoice->generated_date,
                'payment_status' => $invoice->payment_status,
                'total_amount' => $invoice->total_amount,
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

        return view('invoices.show', compact('invoice', 'memos'));
    }

    public function edit(Invoice $invoice)
    {
        $company = Auth::user()->company;
        $today = now()->startOfDay();
        $planEndDate = \Carbon\Carbon::parse($company->plan_end_date)->startOfDay();
        if ($company->plan_end_date && $today->gt($planEndDate)) {
            return redirect()->route('invoices.index')
                ->with('error', 'Your subscription plan has expired. Please renew your subscription to continue using the service.');
        }
        $companies = Company::all();
        $consignees = Consignee::all();


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

                return (object)[
                    'memo_id' => $memo->memo_id,
                    'memo_number' => 'Memo #' . $memo->memo_id,
                    'selected' => $hasInvoice
                ];
            });

        return view('invoices.edit', compact('invoice', 'companies', 'consignees', 'memos'));
    }

    public function update(Request $request, Invoice $invoice)
    {
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
                    fn($query) =>
                    $query->where('company_id', Auth::user()->company_id)
                )->ignore($invoice->id),
            ],
            'generated_date' => 'required|date',
        ]);

        $invoice->update($data);

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
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
        $lr_ids = $invoice->lr_ids ?? [];
        if (!empty($lr_ids)) {
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

    public function getLrs($memoIds)
    {
        $memoIds = explode(',', $memoIds);
        Log::info('Fetching LR numbers for memos:', ['memo_ids' => $memoIds]);

        $lrs = LorryReceipt::whereIn('memo_id', $memoIds)
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
                    'weight' => $lr->weight
                ];
            })->toArray() ?? [];
        }

        return response()->json($result);
    }

    public function getAllInvoiceNumbers()
    {
        $invoices = Invoice::where('company_id', Auth::user()->company_id)
            ->orderByDesc('id')
            ->get(['id', 'manual_invoice_number']);

        return response()->json($invoices);
    }

    public function getInvoiceNumbersForCustomer($customerId)
    {
        $invoices = Invoice::where('customer_id', $customerId)
            ->where('company_id', Auth::user()->company_id)
            ->orderByDesc('id')
            ->get(['id', 'manual_invoice_number']);

        return response()->json($invoices);
    }

    public function checkManualInvoiceNumberUniqueness(Request $request)
    {
        $manualNumber = $request->input('manual_invoice_number');
        $invoiceId = $request->input('invoice_id'); // Get invoice ID if editing
        $companyId = Auth::user()->company_id;

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
                'message' => 'This invoice number is already used in your company.'
            ]);
        } else {
            return response()->json([
                'valid' => true,
                'message' => 'Available!'
            ]);
        }
    }

    /**
     * Store a payment for an invoice
     */
    public function storePayment(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'mode' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'remark' => 'nullable|string|max:1000',
            'payment_date' => 'required|date',
        ]);

        // Calculate remaining balance
        $paidAmount = $invoice->payments()->sum('amount');
        $remainingAmount = $invoice->total_amount - $paidAmount;

        // Validate that payment amount doesn't exceed remaining balance
        if ($validated['amount'] > $remainingAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount cannot exceed the remaining balance of ₹' . number_format($remainingAmount, 2)
            ], 422);
        }

        // Create payment
        $payment = $invoice->payments()->create($validated);

        // Update invoice payment status
        $newPaidAmount = $invoice->payments()->sum('amount');
        if ($newPaidAmount >= $invoice->total_amount) {
            $invoice->payment_status = 'paid';
        } elseif ($newPaidAmount > 0) {
            $invoice->payment_status = 'partial';
        } else {
            $invoice->payment_status = 'pending';
        }
        $invoice->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment added successfully',
            'payment' => $payment,
            'paid_amount' => $newPaidAmount,
            'remaining_amount' => $invoice->total_amount - $newPaidAmount,
            'payment_status' => $invoice->payment_status,
        ]);
    }

    /**
     * Get payments for an invoice
     */
    public function getPayments(Invoice $invoice)
    {
        $payments = $invoice->payments()->orderByRaw('COALESCE(payment_date, created_at) DESC')->get();
        $paidAmount = $invoice->payments()->sum('amount');
        $remainingAmount = $invoice->total_amount - $paidAmount;

        return response()->json([
            'payments' => $payments,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'total_amount' => $invoice->total_amount,
        ]);
    }
}
