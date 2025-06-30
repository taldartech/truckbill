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
        $query = \App\Models\User::with('company');
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }
        if ($request->filled('role')) {
    $query->whereHas('userRole', function ($q) use ($request) {
        $q->where('name', $request->role);
    });
}
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        $users = $query->orderByDesc('created_at')->get();
        $roles = UserRole::pluck('name')->toArray();
        $companies = \App\Models\Company::all();
        return view('users.index', compact('users', 'roles', 'companies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companies = \App\Models\Company::all();
        $roles = UserRole::pluck('name')->toArray();
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
            'role' => 'required|in:admin,operator,accountant',
            'company_id' => 'required|exists:companies,id',
        ]);
        $validated['password'] = bcrypt($validated['password']);
        $userRole = UserRole::where('name', $validated['role'])->first();
        if (!$userRole) {
            return back()->withErrors(['role' => 'Invalid role selected.']);
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
        $roles = ['super_admin', 'admin', 'moderator', 'accountant'];
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
            'role' => 'required|in:admin,operator,accountant',
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
