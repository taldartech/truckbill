<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CheckBlockMiddleware
{
    /**
     * Handle an incoming request.
     *
     * This middleware combines:
     * 1. Authentication check
     * 2. Company block check
     * 3. Single device login check
     *
     * It ensures the user is authenticated, their company is not blocked,
     * and they haven't logged in from another device.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 2. Check single device login
        $sessionToken = Session::get('session_token');

        // Check if the session token matches the user's current session token
        if ($user->session_token && $sessionToken !== $user->session_token) {
            // Session token doesn't match - user logged in from another device
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'You have been logged out because you logged in from another device.');
        }

        // Store session token in session if not already present
        if (!$sessionToken && $user->session_token) {
            Session::put('session_token', $user->session_token);
        }

        // 3. Check if company exists and is blocked
        $company_id = $user->company_id;
        $company = \App\Models\Company::find($company_id);

        // If is_blocked is true (1), block access
        if ($company && $company->is_blocked) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
