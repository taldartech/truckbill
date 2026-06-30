<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyContextMiddleware
{
    /**
     * Ensure super_admin has an active company selected in session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user?->userRole?->name === 'super_admin') {
            $companyId = session('super_admin_company_id');

            if (! $companyId) {
                return redirect()->route('company.index')->with('error', 'Please enter a company first.');
            }

            $company = Company::query()->find($companyId);

            if (! $company || $company->is_blocked) {
                session()->forget('super_admin_company_id');

                return redirect()->route('dashboard')->with('error', 'This company is blocked and cannot be accessed.');
            }
        }

        return $next($request);
    }
}
