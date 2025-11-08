<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

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
        ]);

        if (Auth::attempt($credentials)) {
            //fetch the user_id from the user table , from users table fetch company id and check if is_blocked is 1 than do not allow to loin show msg
               //from users table fetdch company id
            $company_id = Auth::user()->company_id;
            $company = \App\Models\Company::where('id', $company_id)->first();
            //if is_blocked is null than allow to loin
            if ($company->is_blocked != null) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account is blocked',
                ]);
            }
            else{
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
            }
        }

        return back()->withErrors([
            'email' => 'Invalid credentials',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}