<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Handle DataTables AJAX request
        if ($request->ajax()) {
            return $this->getUsersData($request);
        }
        
        $roles = ['super_admin', 'admin', 'moderator', 'accountant'];
        $companies = \App\Models\Company::all();
        return view('users.index', compact('roles', 'companies'));
    }
    
    private function getUsersData(Request $request)
    {
        $query = \App\Models\User::with(['company', 'userRole']);
        
        // Handle search
        if ($request->has('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                  ->orWhere('email', 'like', "%{$searchValue}%")
                  ->orWhereHas('userRole', function($roleQuery) use ($searchValue) {
                      $roleQuery->where('name', 'like', "%{$searchValue}%");
                  })
                  ->orWhereHas('company', function($companyQuery) use ($searchValue) {
                      $companyQuery->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }
        
        // Handle column-specific search
        if ($request->has('columns')) {
            foreach ($request->columns as $index => $column) {
                if (!empty($column['search']['value'])) {
                    $searchValue = $column['search']['value'];
                    switch ($index) {
                        case 1: // Name
                            $query->where('name', 'like', "%{$searchValue}%");
                            break;
                        case 2: // Email
                            $query->where('email', 'like', "%{$searchValue}%");
                            break;
                        case 3: // Role
                            $query->whereHas('userRole', function($roleQuery) use ($searchValue) {
                                $roleQuery->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                        case 4: // Company
                            $query->whereHas('company', function($companyQuery) use ($searchValue) {
                                $companyQuery->where('name', 'like', "%{$searchValue}%");
                            });
                            break;
                    }
                }
            }
        }
        
        // Handle sorting
        if ($request->has('order')) {
            $orderColumn = $request->order[0]['column'];
            $orderDirection = $request->order[0]['dir'];
            
            switch ($orderColumn) {
                case 0: // ID
                    $query->orderBy('id', $orderDirection);
                    break;
                case 1: // Name
                    $query->orderBy('name', $orderDirection);
                    break;
                case 2: // Email
                    $query->orderBy('email', $orderDirection);
                    break;
                case 3: // Role
                    $query->join('user_roles', 'users.user_role_id', '=', 'user_roles.id')
                          ->orderBy('user_roles.name', $orderDirection)
                          ->select('users.*');
                    break;
                case 4: // Company
                    $query->join('companies', 'users.company_id', '=', 'companies.id')
                          ->orderBy('companies.name', $orderDirection)
                          ->select('users.*');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Get total count before pagination
        $totalRecords = \App\Models\User::count();
        $filteredRecords = $query->count();
        
        // Handle pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 10;
        $users = $query->skip($start)->take($length)->get();
        
        // Format data for DataTables
        $data = $users->map(function($user, $index) use ($start) {
            return [
                'id' => $user->id,
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
    
    private function getActionButtons($user)
    {
        $buttons = '<div class="d-flex gap-1">';
        
        // Check if user is super_admin - if so, disable edit and delete actions
        $isSuperAdmin = $user->userRole && $user->userRole->name === 'super_admin';
        
        if ($isSuperAdmin) {
            // Disabled buttons for super_admin users
            $buttons .= '<span class="btn btn-sm btn-secondary disabled" title="Super Admin cannot be edited">';
            $buttons .= '<i class="bi bi-pencil"></i></span>';
            
            $buttons .= '<span class="btn btn-sm btn-secondary disabled" title="Super Admin cannot be deleted">';
            $buttons .= '<i class="bi bi-trash"></i></span>';
        } else {
            // Edit button
            $editUrl = route('users.edit', $user->id);
            $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info" title="Edit User">';
            $buttons .= '<i class="bi bi-pencil"></i></a>';
            
            // Delete button
            $deleteUrl = route('users.destroy', $user->id);
            $buttons .= '<form action="' . $deleteUrl . '" method="POST" style="display:inline-block;" onsubmit="return confirm(\'Are you sure you want to delete this user?\')">';
            $buttons .= csrf_field() . method_field('DELETE');
            $buttons .= '<button type="submit" class="btn btn-sm btn-danger" title="Delete User">';
            $buttons .= '<i class="bi bi-trash"></i></button></form>';
        }
        
        $buttons .= '</div>';
        return $buttons;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = \App\Models\Company::all();
        $roles = ['admin', 'moderator', 'accountant'];
        return view('users.create', compact('companies', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:super_admin,admin,moderator,accountant',
            'company_id' => 'required|exists:companies,id',
        ]);
        $validated['password'] = bcrypt($validated['password']);
        $userRole = UserRole::where('name', $validated['role'])->first();
        if (!$userRole) {
            // Create the role if it doesn't exist
            $userRole = UserRole::create(['name' => $validated['role']]);
        }
        $validated['user_role_id'] = $userRole->id;
        unset($validated['role']);
        \App\Models\User::create($validated);
        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $companies = \App\Models\Company::all();
        $roles = ['admin', 'moderator', 'accountant'];
        return view('users.edit', compact('user', 'companies', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:super_admin,admin,moderator,accountant',
            'company_id' => 'required|exists:companies,id',
        ]);
        if ($validated['password']) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }
        $userRole = UserRole::where('name', $validated['role'])->first();
        if (!$userRole) {
            return back()->withErrors(['role' => 'Invalid role selected.']);
        }
        $validated['user_role_id'] = $userRole->id;
        unset($validated['role']);
        $user->update($validated);
        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }
}
