<?php

namespace App\Http\Controllers;

use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\Company;
use App\Models\CostCentre;
use App\Models\Ledger;
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
        $ledgers = Ledger::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $costCentres = CostCentre::where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        return view('accounting.vouchers.create', compact('company', 'ledgers', 'costCentres'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $company = $this->getActiveCompany();

        $validated = $request->validate([
            'voucher_type' => 'required|in:contra,payment,receipt,journal,sales,purchase',
            'voucher_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'narration' => 'nullable|string|max:1000',
            'items' => 'required|array|min:2',
            'items.*.ledger_id' => 'required|exists:ledgers,id',
            'items.*.type' => 'required|in:debit,credit',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.cost_centre_id' => 'nullable|exists:cost_centres,id',
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

        $voucher = DB::transaction(function () use ($validated, $company, $items, $totalDebit) {
            $prefix = strtoupper(substr($validated['voucher_type'], 0, 3));
            $voucherNumber = $prefix.'-'.now()->format('ymd').'-'.rand(1000, 9999);

            $voucher = AccountingVoucher::create([
                'company_id' => $company->id,
                'voucher_type' => $validated['voucher_type'],
                'voucher_number' => $voucherNumber,
                'voucher_date' => $validated['voucher_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'narration' => $validated['narration'] ?? null,
                'total_amount' => $totalDebit,
                'is_posted' => true,
                'user_id' => Auth::id(),
            ]);

            foreach ($items as $itemData) {
                AccountingVoucherItem::create([
                    'company_id' => $company->id,
                    'accounting_voucher_id' => $voucher->id,
                    'ledger_id' => $itemData['ledger_id'],
                    'type' => $itemData['type'],
                    'amount' => $itemData['amount'],
                    'cost_centre_id' => $itemData['cost_centre_id'] ?? null,
                    'particulars_note' => $itemData['particulars_note'] ?? null,
                ]);
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
