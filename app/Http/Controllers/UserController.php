<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use App\Mail\UserBlockedMail;
use App\Mail\UserUnblockedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getUsersData($request);
        }
        return view('users.index');
    }

    /**
     * Get users data for DataTables
     */
    private function getUsersData(Request $request)
    {
        $authUser = Auth::user();
        $isMasterAdmin = $this->isMasterAdmin($authUser);
        $isSuperAdmin = $this->isSuperAdmin($authUser);

        // Build base query with eager loading
        $query = User::with(['company', 'userRole']);

        // Apply role-based filtering
        if ($isMasterAdmin) {
            // Master admin can only see and manage super_admin users (user_role_id = 2)
            $query->where('user_role_id', 2);
        } elseif ($isSuperAdmin) {
            // Super admin sees users from their companies
            $companyIds = Company::where('user_id', $authUser->id)->pluck('id')->toArray();
            $query->whereIn('company_id', $companyIds);
        } else {
            abort(403, 'Unauthorized access.');
        }

        // Apply search filters
        $this->applySearchFilters($query, $request);

        // Apply sorting
        $this->applySorting($query, $request);

        // Get counts for DataTables
        $totalRecords = $this->getTotalRecords($authUser);
        $filteredRecords = $query->count();

        // Apply pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $users = $query->skip($start)->take($length)->get();

        // Format data for DataTables
        $data = $users->map(function ($user, $index) use ($start) {
            return [
                'id' => $index + $start + 1,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->userRole ? ucfirst($user->userRole->name) : '-',
                'company' => $user->company ? $user->company->name : '-',
                'actions' => $this->getActionButtons($user)
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    /**
     * Apply search filters to query
     */
    private function applySearchFilters($query, Request $request): void
    {
        // Global search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhereHas('userRole', function ($roleQuery) use ($searchValue) {
                        $roleQuery->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('company', function ($companyQuery) use ($searchValue) {
                        $companyQuery->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    match ($index) {
                        1 => $query->where('name', 'like', "%{$searchValue}%"),
                        2 => $query->where('email', 'like', "%{$searchValue}%"),
                        3 => $query->whereHas('userRole', function ($roleQuery) use ($searchValue) {
                            $roleQuery->where('name', 'like', "%{$searchValue}%");
                        }),
                        4 => $query->whereHas('company', function ($companyQuery) use ($searchValue) {
                            $companyQuery->where('name', 'like', "%{$searchValue}%");
                        }),
                        default => null
                    };
                }
            }
        }
    }

    /**
     * Apply sorting to query
     */
    private function applySorting($query, Request $request): void
    {
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDirection = $request->order[0]['dir'];

            match ($orderColumn) {
                0 => $query->orderBy('id', $orderDirection),
                1 => $query->orderBy('name', $orderDirection),
                2 => $query->orderBy('email', $orderDirection),
                3 => $query->join('user_roles', 'users.user_role_id', '=', 'user_roles.id')
                    ->orderBy('user_roles.name', $orderDirection)
                    ->select('users.*'),
                4 => $query->join('companies', 'users.company_id', '=', 'companies.id')
                    ->orderBy('companies.name', $orderDirection)
                    ->select('users.*'),
                default => $query->orderBy('created_at', 'desc')
            };
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Get total records count based on user role
     */
    private function getTotalRecords(User $authUser): int
    {
        if ($this->isMasterAdmin($authUser)) {
            // Master admin can only see super_admin users
            return User::where('user_role_id', 2)->count();
        }

        if ($this->isSuperAdmin($authUser)) {
            $companyIds = Company::where('user_id', $authUser->id)->pluck('id')->toArray();
            return User::whereIn('company_id', $companyIds)->count();
        }

        return 0;
    }

    /**
     * Generate action buttons for DataTables
     */
    private function getActionButtons(User $user): string
    {
        $buttons = '<div class="d-flex gap-1">';

        $editUrl = route('users.edit', $user->id);
        $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-edit" title="Edit User">';
        $buttons .= '<i class="bi bi-pencil"></i></a>';

        $deleteUrl = route('users.destroy', $user->id);
        $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this user?\')">';
        $buttons .= csrf_field() . method_field('DELETE');
        $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete User">';
        $buttons .= '<i class="bi bi-trash"></i></button></form>';

        if ($this->isMasterAdmin(Auth::user())) {
            if ($user->is_blocked) {
                $unblockUrl = route('users.unblock', $user->id);
                $buttons .= '<form action="' . $unblockUrl . '" method="POST" style="display:inline-block;" onsubmit="return handleUserStatusAction(this, \'unblock\')">';
                $buttons .= csrf_field();
                $buttons .= '<input type="hidden" name="reason" value="">';
                $buttons .= '<button type="submit" class="btn btn-sm btn-warning" title="Unblock User"><i class="bi bi-unlock"></i></button></form>';
            } else {
                $blockUrl = route('users.block', $user->id);
                $buttons .= '<form action="' . $blockUrl . '" method="POST" style="display:inline-block;" onsubmit="return handleUserStatusAction(this, \'block\')">';
                $buttons .= csrf_field();
                $buttons .= '<input type="hidden" name="reason" value="">';
                $buttons .= '<button type="submit" class="btn btn-sm btn-secondary" title="Block User"><i class="bi bi-lock"></i></button></form>';
            }
        }

        $buttons .= '</div>';
        return $buttons;
    }

    public function block(Request $request, User $user)
    {
        $authUser = Auth::user();
        if (! $this->isMasterAdmin($authUser) || ! $this->canManageUser($authUser, $user)) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $linkedCompanyIds = Company::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->all();

        $linkedUserIds = User::query()
            ->whereIn('company_id', $linkedCompanyIds)
            ->pluck('id')
            ->all();

        $allImpactedUserIds = array_values(array_unique(array_merge([$user->id], $linkedUserIds)));

        Company::query()
            ->whereIn('id', $linkedCompanyIds)
            ->update([
                'is_blocked' => true,
                'updated_at' => now(),
            ]);

        User::query()
            ->whereIn('id', $allImpactedUserIds)
            ->update([
                'is_blocked' => true,
                'block_reason' => $validated['reason'],
                'blocked_by' => $authUser->id,
                'session_token' => \Illuminate\Support\Str::random(60),
                'updated_at' => now(),
            ]);

        DB::table('sessions')->whereIn('user_id', $allImpactedUserIds)->delete();

        if (! empty($user->email)) {
            Mail::to($user->email)->send(new UserBlockedMail(
                userName: $user->name,
                blockedByName: $authUser->name,
                reason: $validated['reason'],
            ));
        }

        return redirect()->route('users.index')->with('success', 'User and all linked company users blocked successfully. All active sessions were logged out.');
    }

    public function unblock(Request $request, User $user)
    {
        $authUser = Auth::user();
        if (! $this->isMasterAdmin($authUser) || ! $this->canManageUser($authUser, $user)) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $linkedCompanyIds = Company::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->all();

        $linkedUserIds = User::query()
            ->whereIn('company_id', $linkedCompanyIds)
            ->pluck('id')
            ->all();

        $allImpactedUserIds = array_values(array_unique(array_merge([$user->id], $linkedUserIds)));

        Company::query()
            ->whereIn('id', $linkedCompanyIds)
            ->update([
                'is_blocked' => false,
                'updated_at' => now(),
            ]);

        User::query()
            ->whereIn('id', $allImpactedUserIds)
            ->update([
                'is_blocked' => false,
                'block_reason' => null,
                'blocked_by' => null,
                'updated_at' => now(),
            ]);

        if (! empty($user->email)) {
            Mail::to($user->email)->send(new UserUnblockedMail(
                userName: $user->name,
                unblockedByName: $authUser->name,
                reason: $validated['reason'],
            ));
        }

        return redirect()->route('users.index')->with('success', 'User and all linked company users unblocked successfully.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $authUser = Auth::user();
        $isMasterAdmin = $this->isMasterAdmin($authUser);
        $isSuperAdmin = $this->isSuperAdmin($authUser);

        // Determine available roles
        if ($isMasterAdmin) {
            // Master admin can only create super_admin users
            $roles = ['super_admin'];
            $companies = collect(); // Master admin doesn't need companies
            $branches = collect(); // Master admin doesn't need branches
        } else {
            // Super admin can create company users
            $roles = ['admin', 'moderator', 'accountant'];
            $companies = Company::where('user_id', $authUser->id)->get();
            $branches = Branch::whereIn('company_id', $companies->pluck('id'))->get();
        }

        return view('users.create', compact('companies', 'roles', 'branches', 'isMasterAdmin', 'isSuperAdmin'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $authUser = Auth::user();
        $isMasterAdmin = $this->isMasterAdmin($authUser);
        $isSuperAdmin = $this->isSuperAdmin($authUser);

        // Determine allowed roles
        if ($isMasterAdmin) {
            $allowedRoles = ['super_admin'];
        } else {
            $allowedRoles = ['admin', 'moderator', 'accountant'];
        }

        // Build validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:' . implode(',', $allowedRoles),
        ];

        // Add company and branch validation for non-super_admin roles
        if ($request->input('role') !== 'super_admin') {
            $rules['company_id'] = 'required|exists:companies,id';
            $rules['branch_id'] = 'required|exists:branches,id';

            // Verify company belongs to super_admin
            if ($isSuperAdmin) {
                $companyIds = Company::where('user_id', $authUser->id)->pluck('id')->toArray();
                $rules['company_id'] .= '|in:' . implode(',', $companyIds);
            }
        } else {
            // For super_admin, nullify company_id and branch_id
            $request->merge(['company_id' => null, 'branch_id' => null]);
        }

        $validated = $request->validate($rules);
        $validated['password'] = bcrypt($validated['password']);

        // Get or create user role
        $userRole = UserRole::firstOrCreate(['name' => $validated['role']]);
        $validated['user_role_id'] = $userRole->id;
        unset($validated['role']);

        // Create user in transaction
        DB::transaction(function () use ($validated) {
            User::create($validated);
        });

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $authUser = Auth::user();
        $user->loadMissing('consignees');

        // Check authorization
        if (!$this->canManageUser($authUser, $user)) {
            abort(403, 'Unauthorized access.');
        }

        // Determine available roles
        if ($this->isMasterAdmin($authUser)) {
            // Master admin can only edit super_admin users
            $roles = ['super_admin'];
            $companies = collect(); // Master admin doesn't need companies
            $branches = collect(); // Master admin doesn't need branches
        } else {
            // Super admin can edit company users
            $roles = ['admin', 'moderator', 'accountant'];
            $companies = Company::where('user_id', $authUser->id)->get();
            $branches = Branch::whereIn('company_id', $companies->pluck('id'))->get();
        }

        return view('users.edit', compact('user', 'companies', 'roles', 'branches'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $authUser = Auth::user();

        // Check authorization
        if (!$this->canManageUser($authUser, $user)) {
            abort(403, 'Unauthorized access.');
        }

        // Determine allowed roles
        if ($this->isMasterAdmin($authUser)) {
            $allowedRoles = ['super_admin'];
        } else {
            $allowedRoles = ['admin', 'moderator', 'accountant'];
        }

        // Build validation rules
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:' . implode(',', $allowedRoles),
        ];

        // Add company and branch validation for non-super_admin roles
        if ($request->input('role') !== 'super_admin') {
            $rules['company_id'] = 'required|exists:companies,id';
            $rules['branch_id'] = 'required|exists:branches,id';

            // Verify company belongs to super_admin
            if ($this->isSuperAdmin($authUser)) {
                $companyIds = Company::where('user_id', $authUser->id)->pluck('id')->toArray();
                $rules['company_id'] .= '|in:' . implode(',', $companyIds);
            }
        } else {
            // For super_admin, nullify company_id and branch_id
            $request->merge(['company_id' => null, 'branch_id' => null]);
        }

        $validated = $request->validate($rules);

        // Handle password
        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Get or create user role
        $userRole = UserRole::firstOrCreate(['name' => $validated['role']]);
        $validated['user_role_id'] = $userRole->id;
        unset($validated['role']);

        // Update user in transaction
        DB::transaction(function () use ($validated, $user) {
            $user->update($validated);
        });

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $authUser = Auth::user();

        // Check authorization
        if (!$this->canManageUser($authUser, $user)) {
            abort(403, 'Unauthorized action.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    /**
     * Check if user is master_admin
     */
    private function isMasterAdmin(User $user): bool
    {
        return $user->userRole->name === 'master_admin';
    }

    /**
     * Check if user is super_admin
     */
    private function isSuperAdmin(User $user): bool
    {
        return $user->userRole->name === 'super_admin';
    }

    /**
     * Check if auth user can manage (edit/delete) target user
     * - master_admin can only manage super_admin users
     * - super_admin can manage users from their companies
     */
    private function canManageUser(User $authUser, User $targetUser): bool
    {
        if ($this->isMasterAdmin($authUser)) {
            // Master admin can only manage super_admin users
            return $this->isSuperAdmin($targetUser);
        }

        if ($this->isSuperAdmin($authUser)) {
            // Super admin can manage users from their companies
            $companyIds = Company::where('user_id', $authUser->id)->pluck('id')->toArray();
            if ($targetUser->company_id !== null) {
                return in_array($targetUser->company_id, $companyIds);
            }

            if ($targetUser->userRole?->name === 'customer') {
                return $targetUser->consignees()->whereIn('company_id', $companyIds)->exists();
            }

            return false;
        }

        return false;
    }
}
