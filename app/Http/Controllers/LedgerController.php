<?php

namespace App\Http\Controllers;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\Ledger;
use App\Services\ChartOfAccountsProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public function __construct(
        private readonly ChartOfAccountsProvisioner $provisioner
    ) {}

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
        $this->provisioner->provisionForCompany($company);

        $groups = AccountGroup::with(['ledgers' => function ($q): void {
            $q->orderBy('name');
        }])
            ->where('company_id', $company->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $allGroups = AccountGroup::where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        return view('accounting.ledgers.index', compact('company', 'groups', 'allGroups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->getActiveCompany();

        $validated = $request->validate([
            'account_group_id' => 'required|exists:account_groups,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'opening_balance' => 'nullable|numeric|min:0',
            'opening_balance_type' => 'required|in:debit,credit',
            'gstin' => 'nullable|string|max:20',
            'pan_number' => 'nullable|string|max:15',
        ]);

        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $validated['account_group_id'],
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'opening_balance' => $validated['opening_balance'] ?? 0.00,
            'opening_balance_type' => $validated['opening_balance_type'],
            'opening_balance_date' => now()->startOfYear()->toDateString(),
            'gstin' => $validated['gstin'] ?? null,
            'pan_number' => $validated['pan_number'] ?? null,
            'is_builtin' => false,
            'is_active' => true,
        ]);

        return redirect()->route('accounting.ledgers.index')->with('success', "Ledger '{$validated['name']}' created successfully.");
    }
}
