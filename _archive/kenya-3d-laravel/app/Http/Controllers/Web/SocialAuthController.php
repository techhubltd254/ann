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
            
            return redirect()->intended(route('dashboard.index'))
                ->with('success', 'Welcome, ' . $user->name . '!');
                
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Google login failed. Please try again.']);
        }
    }
}
