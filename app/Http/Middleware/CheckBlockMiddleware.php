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

        if ($user->is_blocked) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $reasonText = $user->block_reason ? ' Reason: '.$user->block_reason : '';

            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Your account is locked.'.$reasonText,
                ]);
        }

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

        // 3. Check if user's permanent company exists and is blocked
        $company_id = $user->getRawOriginal('company_id');
        $company = $company_id ? \App\Models\Company::find($company_id) : null;

        if ($company && $company->is_blocked) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $companyReason = '';
            if (! empty($company->block_reason)) {
                $companyReason = ' Reason: '.$company->block_reason;
            }

            return redirect()->route('login')
                ->withErrors([
                    'email' => 'Your account is locked.'.$companyReason,
                ]);
        }

        return $next($request);
    }
}
