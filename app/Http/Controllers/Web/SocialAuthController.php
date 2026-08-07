<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::updateOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => bcrypt(\Illuminate\Support\Str::random(24)),
                'email_verified_at' => now(),
            ]);

            Auth::login($user, true);

            // Route by role (same as login)
            if ($user->hasRole('kicc_admin')) {
                return redirect()->route('kicc.admin', ['tab' => 'portals']);
            }
            if ($user->hasRole('national_admin')) {
                return redirect()->route('national.admin');
            }
            if ($user->hasRole('county_admin') && $user->county_id) {
                $county = \App\Models\County::find($user->county_id);
                if ($county) return redirect()->route('county.admin.pro', $county->slug);
            }
            if ($user->hasRole('exhibitor') || $user->account_type === 'exhibitor') {
                return redirect()->route('exhibitor.admin');
            }
            if ($user->account_type === 'provider') {
                return redirect()->route('provider.admin');
            }

            return redirect()->intended(route('dashboard.index'))
                ->with('success', 'Welcome, ' . $user->name . '!');
                
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Google login failed. Please try again.']);
        }
    }
}
