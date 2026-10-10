<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            $name = $googleUser->getName();
            $googleId = $googleUser->getId();
            
            $avatar = $googleUser->getAvatar();
            // Truncate avatar URL — Google returns extremely long signed URLs
            if (is_string($avatar) && strlen($avatar) > 500) {
                $avatar = substr($avatar, 0, 500);
                Log::warning('Google avatar truncated (exceeded 500 chars)');
            }

            abort_unless(filter_var($googleUser->getEmail(),FILTER_VALIDATE_EMAIL),401);
            $user=User::where('email',$googleUser->getEmail())->first();
            if($user){abort_unless(($user->status??'active')==='active' && $user->google_id && hash_equals((string)$user->google_id,(string)$googleId),403,'Sign in normally and link your verified Google identity first.');}
            else{$user=User::create(['email'=>$googleUser->getEmail(),'name'=>$name,'fullName'=>$name,'google_id'=>$googleId,'avatar'=>$avatar,'password'=>bcrypt(\Illuminate\Support\Str::random(64)),'email_verified_at'=>now(),'tier'=>'EXHIBITOR','account_type'=>'exhibitor','status'=>'active']);$user->assignRole('exhibitor');}
            Auth::login($user, true);
            request()->session()->regenerate();

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
            \Illuminate\Support\Facades\Log::error('Google OAuth callback failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->route('login')
                ->withErrors(['google' => 'Google login failed. Please try again.']);
        }
    }
}
