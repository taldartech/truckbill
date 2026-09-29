<?php

namespace App\Http\Controllers;

use App\Models\AccountGroup;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\CostCentre;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Ledger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountingVoucherController extends Controller
{
    private function getActiveCompany(): Company
    {
        $companyId = Auth::user()->userRole?->name === 'super_admin' && session('super_admin_company_id')
            ? session('super_admin_company_id')
            : Auth::user()->company_id;

        return Company::findOrFail($companyId);
    }

    public function index(Request $request): View
    {
        $company = $this->getActiveCompany();
        $query = AccountingVoucher::with(['items.ledger', 'items.costCentre', 'user'])
            ->where('company_id', $company->id)
            ->where('is_posted', true);

        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        if ($request->filled('from_date')) {
            $query->where('voucher_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('voucher_date', '<=', $request->to_date);
        }

        $vouchers = $query->orderBy('voucher_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        return view('accounting.vouchers.index', compact('company', 'vouchers'));
    }

    public function create(): View
    {
        $company = $this->getActiveCompany();
        $ledgers = Ledger::with('accountGroup')
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $costCentres = CostCentre::where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        $allGroups = AccountGroup::where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        return view('accounting.vouchers.create', compact('company', 'ledgers', 'costCentres', 'allGroups'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $company = $this->getActiveCompany();

        $validated = $request->validate([
            'voucher_type' => 'required|in:contra,payment,receipt,journal,sales,purchase,debit_note,credit_note',
            'voucher_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'narration' => 'nullable|string|max:1000',
            'challan_bsr_code' => 'nullable|string|max:20',
            'challan_serial_no' => 'nullable|string|max:30',
            'challan_tender_date' => 'nullable|date',
            'challan_head' => 'nullable|string|max:50',
            'items' => 'required|array|min:2',
            'items.*.ledger_id' => 'required|exists:ledgers,id',
            'items.*.type' => 'required|in:debit,credit',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.cost_centre_id' => 'nullable|exists:cost_centres,id',
            'items.*.invoice_id' => 'nullable|exists:invoices,id',
            'items.*.particulars_note' => 'nullable|string|max:255',
        ]);

        $items = collect($validated['items']);
        $totalDebit = round($items->where('type', 'debit')->sum('amount'), 2);
        $totalCredit = round($items->where('type', 'credit')->sum('amount'), 2);

        if (abs($totalDebit - $totalCredit) > 0.01) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "Voucher is unbalanced. Total Debits (₹{$totalDebit}) must equal Total Credits (₹{$totalCredit}).",
                ], 422);
            }

            return back()->withInput()->withErrors([
                'items' => "Voucher is unbalanced. Total Debits (₹{$totalDebit}) must equal Total Credits (₹{$totalCredit}).",
            ]);
        }

        // Circular Entry Check: in a 2-line voucher, Debit and Credit ledgers cannot be identical
        if ($items->count() === 2 && $items->first()['ledger_id'] == $items->last()['ledger_id']) {
            $msg = 'Debit and Credit ledgers cannot be identical in a 2-line voucher.';
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 422);
            }

            return back()->withInput()->withErrors([
                'items' => $msg,
            ]);
        }

        $voucher = DB::transaction(function () use ($validated, $company, $items, $totalDebit) {
            $prefix = match ($validated['voucher_type']) {
                'credit_note' => 'CRN',
                'debit_note' => 'DBN',
                default => strtoupper(substr($validated['voucher_type'], 0, 3)),
            };
            $voucherNumber = $prefix.'-'.now()->format('ymd').'-'.rand(1000, 9999);

            $voucher = AccountingVoucher::create([
                'company_id' => $company->id,
                'voucher_type' => $validated['voucher_type'],
                'voucher_number' => $voucherNumber,
                'voucher_date' => $validated['voucher_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'challan_bsr_code' => $validated['challan_bsr_code'] ?? null,
                'challan_serial_no' => $validated['challan_serial_no'] ?? null,
                'challan_tender_date' => $validated['challan_tender_date'] ?? null,
                'challan_head' => $validated['challan_head'] ?? null,
                'narration' => $validated['narration'] ?? null,
                'total_amount' => $totalDebit,
                'is_posted' => true,
                'user_id' => Auth::id(),
            ]);

            foreach ($items as $itemData) {
                $voucherItem = AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $itemData['ledger_id'],
                    'type' => $itemData['type'],
                    'amount' => $itemData['amount'],
                    'cost_centre_id' => $itemData['cost_centre_id'] ?? null,
                    'invoice_id' => ! empty($itemData['invoice_id']) ? $itemData['invoice_id'] : null,
                    'particulars_note' => $itemData['particulars_note'] ?? null,
                ]);

                // If this item is adjusted against an invoice:
                if (! empty($itemData['invoice_id'])) {
                    $inv = Invoice::find($itemData['invoice_id']);
                    if ($inv) {
                        // Check if the debit items in this voucher represent TDS or deductions
                        $isTds = false;
                        $isDeduction = false;
                        foreach ($items as $otherItem) {
                            if ($otherItem['type'] === 'debit') {
                                $otherLedger = Ledger::find($otherItem['ledger_id']);
                                $lName = strtolower($otherLedger?->name ?? '');
                                if (str_contains($lName, 'tds')) {
                                    $isTds = true;
                                } elseif (str_contains($lName, 'discount') || str_contains($lName, 'claim') || str_contains($lName, 'shortage')) {
                                    $isDeduction = true;
                                }
                            }
                        }

                        InvoicePayment::withoutEvents(function () use ($inv, $itemData, $voucher, $validated, $isTds, $isDeduction): void {
                            InvoicePayment::create([
                                'invoice_id' => $inv->id,
                                'mode' => $isTds ? 'tds' : ($isDeduction ? 'deduction' : ($validated['voucher_type'] === 'receipt' ? 'bank' : 'voucher')),
                                'bank_ledger_id' => null,
                                'amount' => (! $isTds && ! $isDeduction) ? (float) $itemData['amount'] : 0.00,
                                'tds_amount' => $isTds ? (float) $itemData['amount'] : 0.00,
                                'deduction_amount' => $isDeduction ? (float) $itemData['amount'] : 0.00,
                                'deduction_reason' => $isDeduction ? ('Shortage/Deduction via Voucher #'.$voucher->voucher_number) : null,
                                'remark' => ($isTds ? 'TDS' : ($isDeduction ? 'Deduction' : 'Settlement')).' via Voucher #'.$voucher->voucher_number.($validated['narration'] ? ' - '.$validated['narration'] : ''),
                                'payment_date' => $validated['voucher_date'],
                            ]);
                        });
                    }
                }
            }

            return $voucher;
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Voucher #{$voucher->voucher_number} posted successfully.",
                'voucher_id' => $voucher->id,
            ]);
        }

        return redirect()->route('accounting.vouchers.index')->with('success', "Voucher #{$voucher->voucher_number} posted successfully.");
    }

    public function getPendingInvoices(Ledger $ledger): JsonResponse
    {
        $company = $this->getActiveCompany();
        if ($ledger->company_id !== $company->id) {
            abort(403);
        }

        $customer = null;
        if ($ledger->entity_type === Consignee::class && $ledger->entity_id) {
            $customer = Consignee::find($ledger->entity_id);
        } else {
            $cleanName = preg_replace('/\s*\(customer\)$/i', '', trim($ledger->name));
            $customer = Consignee::where('company_id', $company->id)
                ->where(function ($q) use ($cleanName) {
                    $q->where('name', $cleanName)
                        ->orWhere('name', 'like', "%{$cleanName}%");
                })->first();
        }

        if (! $customer) {
            return response()->json([
                'is_customer' => false,
                'invoices' => [],
            ]);
        }

        $invoices = Invoice::with('payments')
            ->where('customer_id', $customer->id)
            ->where('company_id', $company->id)
            ->orderBy('generated_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $pendingInvoices = $invoices->map(function ($inv) {
            $billAmount = (float) ($inv->net_amount ?? $inv->total_amount);
            $paidAmount = (float) $inv->paid_amount;
            $remaining = (float) $inv->remaining_amount;

            return [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number ?: ($inv->manual_invoice_number ?: 'INV-'.$inv->id),
                'date' => $inv->generated_date ? Carbon::parse($inv->generated_date)->format('d-m-Y') : '',
                'total_amount' => $billAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remaining,
                'label' => ($inv->invoice_number ?: 'INV-'.$inv->id).' - Bal Due: ₹'.number_format($remaining, 2).' (Total: ₹'.number_format($billAmount, 2).')',
            ];
        })
        ->filter(fn ($inv) => $inv['remaining_amount'] > 0.01)
        ->values();

        return response()->json([
            'is_customer' => true,
            'customer_name' => $customer->name,
            'invoices' => $pendingInvoices,
        ]);
    }

    public function show(AccountingVoucher $voucher): View
    {
        $company = $this->getActiveCompany();
        if ($voucher->company_id !== $company->id) {
            abort(403);
        }

        $voucher->load(['items.ledger.accountGroup', 'items.costCentre', 'user']);

        return view('accounting.vouchers.show', compact('company', 'voucher'));
    }
}
