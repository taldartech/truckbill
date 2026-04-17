<?php

namespace App\Http\Middleware;

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

        if ($user?->userRole?->name === 'super_admin' && ! session('super_admin_company_id')) {
            // Redirect to company selection page
            return redirect()->route('company.index')->with('error', 'Please enter a company first.');
        }

        return $next($request);
    }
}

