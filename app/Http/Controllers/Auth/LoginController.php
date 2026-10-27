<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'remember' => ['sometimes', 'boolean'],
        ]);
        $remember = $request->boolean('remember');
        unset($credentials['remember']);
        // Check for email existence
        $user = \App\Models\User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'Email does not exist.',
            ])->withInput();
        }
        if (Auth::attempt($credentials, $remember)) {
            $authenticatedUser = Auth::user();
            if ($authenticatedUser && $authenticatedUser->is_blocked) {
                Auth::logout();
                $reasonText = $authenticatedUser->block_reason ? ' Reason: '.$authenticatedUser->block_reason : '';
                return back()->withErrors([
                    'email' => 'Your account is locked.'.$reasonText,
                ])->withInput();
            }

            $company = Company::find($authenticatedUser->company_id);

            // If is_blocked is true (1), block login
            if ($company && $company->is_blocked) {
                Auth::logout();
                $companyReason = '';
                if (! empty($company->block_reason)) {
                    $companyReason = ' Reason: '.$company->block_reason;
                }
                return back()->withErrors([
                    'email' => 'Your account is locked.'.$companyReason,
                ])->withInput();
            }

            $user = Auth::user();
            
            // Generate a new session token for this login
            $sessionToken = \Illuminate\Support\Str::random(60);
            
            // Invalidate all other sessions for this user by deleting them from sessions table
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
            
            // Update user's session token
            $user->session_token = $sessionToken;
            $user->save();
            
            // Store session token in current session
            $request->session()->put('session_token', $sessionToken);
            
            // Regenerate session ID for security
            $request->session()->regenerate();
            
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'password' => 'Invalid password.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        
        // Clear session token when user logs out
        if ($user) {
            $user->session_token = null;
            $user->save();
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
    public function showChangePasswordForm()
    {
        return view('auth.changepassword');
    }
    public function changePassword(Request $request)
    {

        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required',
            'new_password_confirmation' => 'required|same:new_password',
        ]);

        /** @var User $user */
        $user = Auth::user();


        if (!Hash::check($request->old_password, $user->password)) {
            return back()->withErrors([
                'old_password' => 'Old password does not match',
            ]);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Password changed successfully.');
    }

}
