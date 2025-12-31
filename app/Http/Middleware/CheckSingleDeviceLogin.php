<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckSingleDeviceLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
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
        }

        return $next($request);
    }
}
