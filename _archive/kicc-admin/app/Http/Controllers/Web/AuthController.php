<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['login' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (Auth::attempt([$field => $data['login'], 'password' => $data['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            RateLimiter::clear($key);
            $user = Auth::user();

            // Role-based redirect
            if ($user->hasRole('kicc_admin')) return redirect('/portal');
            if ($user->hasRole('national_admin')) return redirect()->route('admin.national');
            if ($user->hasRole('county_admin')) return redirect()->route('dashboard.county');

            return redirect('/portal');
        }

        RateLimiter::hit($key, 120);
        return back()->withErrors(['login' => 'Invalid credentials.'])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}