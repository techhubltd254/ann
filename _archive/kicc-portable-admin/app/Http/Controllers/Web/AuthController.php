<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\County;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return view('layouts.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'admin_type' => 'required|string|in:kicc,national,county',
            'county_slug' => 'required_if:admin_type,county|string',
        ]);

        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['login' => "Too many attempts. Try again in {$seconds}s."]);
        }

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (!Auth::attempt([$field => $data['login'], 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 120);
            return back()->withErrors(['login' => 'Invalid credentials.'])->onlyInput('login');
        }

        $request->session()->regenerate();
        RateLimiter::clear($key);
        $user = Auth::user();
        $adminType = $request->input('admin_type');
        $countySlug = $request->input('county_slug');

        // ── Strict role-based access — NO fallbacks ──
        if ($adminType === 'kicc') {
            if (!$user->hasRole('kicc_admin')) {
                Auth::logout();
                return back()->withErrors(['login' => 'You do not have KICC Admin access.']);
            }
            return redirect('/kicc-admin');
        }

        if ($adminType === 'national') {
            if (!$user->hasRole('national_admin')) {
                Auth::logout();
                return back()->withErrors(['login' => 'You do not have National Admin access.']);
            }
            return redirect('/national-admin');
        }

            if ($adminType === 'county') {
                if (!$user->hasRole('county_admin')) {
                    Auth::logout();
                    return back()->withErrors(['login' => 'You do not have County Admin access.']);
                }
                // Verify the county exists
                $county = County::where('slug', $countySlug)->first();
                if (!$county) {
                    Auth::logout();
                    return back()->withErrors(['login' => 'Invalid county selected.']);
                }
                session(['admin_county_slug' => $countySlug]);
                session(['admin_county_name' => $county->name]);
                session(['admin_county_id' => $county->id]);
                // Redirect to the county's professional admin dashboard
                return redirect("/county-admin/{$countySlug}/pro");
            }

        // Should never reach here
        Auth::logout();
        return redirect('/login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}