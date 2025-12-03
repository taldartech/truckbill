<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // public function login(Request $request)
    // {
    //     $credentials = $request->validate([
    //         'email' => ['required', 'email'],
    //         'password' => ['required'],
    //     ]);

    //     if (Auth::attempt($credentials)) {
    //         $request->session()->regenerate();
    //         return redirect()->intended('/dashboard');
    //     }

    //     return back()->withErrors([
    //         'email' => 'Invalid credentials',
    //     ]);
    // }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);
        // Check for email existence
        $user = \App\Models\User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'Email does not exist.',
            ])->withInput();
        }
        if (Auth::attempt($credentials)) {
            // Fetch the company related to the logged-in user
            $company_id = Auth::user()->company_id;
            $company = \App\Models\Company::find($company_id);

            // If is_blocked is true (1), block login
            if ($company && $company->is_blocked) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is blocked.',
                ])->withInput();
            }

            // Otherwise, regenerate session and redirect
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'password' => 'Invalid password.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
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
