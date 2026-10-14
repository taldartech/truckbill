<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Services\SubscriptionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BranchController extends Controller
{
    public function __construct(
        private readonly SubscriptionGuard $subscriptionGuard,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        if ($request->ajax()) {
            return $this->getBranchesData($request);
        }

        return view('branches.index');
    }

    /**
     * Get branches data for DataTables.
     */
    private function getBranchesData(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $companyIds = Company::where('user_id', $user->id)->pluck('id')->toArray();

        $query = Branch::with('company')
            ->whereIn('company_id', $companyIds);

        // Apply search filters
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('branch_name', 'like', "%{$searchValue}%")
                    ->orWhere('branch_contact_number', 'like', "%{$searchValue}%")
                    ->orWhere('branch_address', 'like', "%{$searchValue}%")
                    ->orWhereHas('company', function ($companyQuery) use ($searchValue) {
                        $companyQuery->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Apply sorting
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDirection = $request->order[0]['dir'];

            match ($orderColumn) {
                1 => $query->orderBy('branch_name', $orderDirection),
                2 => $query->join('companies', 'branches.company_id', '=', 'companies.id')
                    ->orderBy('companies.name', $orderDirection)
                    ->select('branches.*'),
                3 => $query->orderBy('branch_contact_number', $orderDirection),
                4 => $query->orderBy('branch_address', $orderDirection),
                default => $query->orderBy('created_at', 'desc')
            };
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $branches = $query->get();

        $data = $branches->map(function ($branch, $index) {
            return [
                'id' => $index + 1,
                'branch_name' => $branch->branch_name,
                'company' => [
                    'name' => $branch->company?->name ?? 'N/A',
                ],
                'branch_contact_number' => $branch->branch_contact_number ?? 'N/A',
                'branch_address' => $branch->branch_address ?? 'N/A',
                'action' => $this->getActionButtons($branch),
            ];
        });

        return response()->json([
            'draw' => intval($request->draw ?? 1),
            'recordsTotal' => $branches->count(),
            'recordsFiltered' => $branches->count(),
            'data' => $data,
        ]);
    }

    /**
     * Generate action buttons for DataTables.
     */
   private function getActionButtons($branch)
{
    $buttons = '<div class="app-index-table-actions">';

    // Edit Button
    $editUrl = route('branches.edit', $branch->id);

    $buttons .= '<a href="'.$editUrl.'"
        class="btn btn-sm btn-icon btn-action-edit"
        title="Edit Branch"
        data-bs-toggle="tooltip">
        <i class="bi bi-pencil"></i>
    </a>';

    // Delete Button
    $deleteUrl = route('branches.destroy', $branch->id);

    $buttons .= '<form action="'.$deleteUrl.'" method="POST" class="branch-delete-form">';
    $buttons .= csrf_field().method_field('DELETE');

    $buttons .= '<button type="button"
        class="btn btn-sm btn-icon btn-action-delete branch-delete-btn"
        title="Delete Branch"
        data-bs-toggle="modal"
        data-bs-target="#branchDeleteModal"
        data-branch-id="'.$branch->id.'"
        data-branch-name="'.e($branch->branch_name ?? '-').'"
        data-branch-company="'.e($branch->company?->name ?? '-').'"
        data-branch-head-office="'.($branch->head_office ? 'Yes' : 'No').'">
        <i class="bi bi-trash"></i>
    </button>';

    $buttons .= '</form>';
    $buttons .= '</div>';

    return $buttons;
}
    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $user = Auth::user();
        $companies = Company::where('user_id', $user->id)->get();

        return view('branches.create', compact('companies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBranchRequest $request): RedirectResponse
    {
        // Form Request validation already passed, now add controller-level validation
        $validated = $request->validated();

        $company = Company::with('subscription')->findOrFail($validated['company_id']);
        $this->subscriptionGuard->assertCanCreateBranch($company);

        // Additional controller-level validation for branch constraints
        $this->validateBranchConstraints($validated);

        DB::transaction(function () use ($validated) {
            Branch::create($validated);
        });

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Branch $branch): View
    {
        $user = Auth::user();

        // Check authorization - user can only edit branches from their companies
        if (!$this->canManageBranch($user, $branch)) {
            abort(403, 'Unauthorized access.');
        }

        $companies = Company::where('user_id', $user->id)->get();

        return view('branches.edit', compact('branch', 'companies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        // Form Request authorization and validation already passed, now add controller-level validation
        $validated = $request->validated();

        // Additional controller-level validation for branch constraints
        $this->validateBranchConstraints($validated, $branch);

        DB::transaction(function () use ($validated, $branch) {
            $branch->update($validated);
        });

        return redirect()
            ->route('branches.index')
            ->with('success', 'Branch updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Branch $branch)
{
    $request->validate([
        'delete_remark' => ['required', 'string', 'max:1000'],
    ]);

    $branch->deleted_by = Auth::id();
    $branch->deleted_remark = $request->input('delete_remark');
    $branch->save();

    $branch->delete();

    return redirect()
        ->route('branches.index')
        ->with('success', 'Branch deleted successfully!');
}

    /**
     * Check if user can manage (edit/delete) the branch.
     * Super admin can only manage branches from their own companies.
     */
    private function canManageBranch($user, Branch $branch): bool
    {
        $companyIds = Company::where('user_id', $user->id)->pluck('id')->toArray();
        return in_array($branch->company_id, $companyIds);
    }

    /**
     * Validate branch constraints for creation/update.
     * Controller-level validation for branch name uniqueness and head office rules.
     */
    private function validateBranchConstraints(array $validated, ?Branch $branch = null): void
    {
        $companyId = $validated['company_id'];
        $branchName = $validated['branch_name'];
        $isHeadOffice = (bool) $validated['head_office'];

        // Validate branch name uniqueness within the same company
        $query = Branch::where('company_id', $companyId)
            ->where('branch_name', $branchName);

        if ($branch) {
            $query->where('id', '!=', $branch->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'branch_name' => 'A branch with this name already exists for the selected company.',
            ]);
        }

        // Validate head office constraint: only one head office per company
        if ($isHeadOffice) {
            $headOfficeQuery = Branch::where('company_id', $companyId)
                ->where('head_office', true);

            if ($branch) {
                $headOfficeQuery->where('id', '!=', $branch->id);
            }

            if ($headOfficeQuery->exists()) {
                throw ValidationException::withMessages([
                    'head_office' => 'This company already has a head office. Please unset the existing head office first.',
                ]);
            }
        }
    }
}
