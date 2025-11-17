<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckBlockMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $company_id = Auth::user()->company_id;
        $company = \App\Models\Company::find($company_id);

        // If is_blocked is true (1), block login
        if ($company && $company->is_blocked) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
