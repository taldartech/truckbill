<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Company;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    // List all branches
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $branches = Branch::with('company');
            
            return DataTables::of($branches)
                ->addIndexColumn()
                ->addColumn('action', function($row) {
                    $btn = '<div class="btn-group" role="group">';
                    $btn .= '<a href="'.route('branches.edit', $row->id).'" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a>';
                    $btn .= '<form action="'.route('branches.destroy', $row->id).'" method="POST" class="d-inline">';
                    $btn .= csrf_field();
                    $btn .= method_field('DELETE');
                    $btn .= '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this branch?\')"><i class="bi bi-trash"></i></button>';
                    $btn .= '</form>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        
        return view('branches.index');
    }

    // Show create form
    public function create()
    {
        $companies = Company::all(); // select company for branch
        return view('branches.create', compact('companies'));
    }

    // Store new branch
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_name' => 'required|string|max:255',
            'branch_address' => 'nullable|string',
            'branch_contact_number' => 'nullable|unique:branches,branch_contact_number|string|max:20',
            'head_office' => 'required|boolean',
        ]);

        Branch::create($request->all());
        return redirect()->route('branches.index')->with('success', 'Branch created successfully!');
    }

    // Show edit form
    public function edit(Branch $branch)
    {
        $companies = Company::all();
        return view('branches.edit', compact('branch', 'companies'));
    }

    // Update branch
    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_name' => 'required|string|max:255',
            'branch_address' => 'nullable|string',
            'branch_contact_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('branches', 'branch_contact_number')->ignore($branch->id),
            ],
            'head_office' => 'required|boolean',
        ], [
            'branch_contact_number.unique' => 'This contact number is already used by another branch.',
            'branch_contact_number.max' => 'The contact number must not exceed 20 digits.',
        ]);

        $branch->update($request->all());
        return redirect()->route('branches.index')->with('success', 'Branch updated successfully!');
    }

    // Delete branch
    public function destroy(Branch $branch)
    {
        $branch->delete(); // soft delete
        return redirect()->route('branches.index')->with('success', 'Branch deleted successfully!');
    }
    
}
