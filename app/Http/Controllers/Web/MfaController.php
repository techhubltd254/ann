<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FAQRCode\Google2FA;

class MfaController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    public function showSetup()
    {
        $user = Auth::user();
        $google2fa = new Google2FA();
        $secret = session('mfa_secret') ?? $google2fa->generateSecretKey();
        session(['mfa_secret' => $secret]);
        $qrCode = $google2fa->getQRCodeInline(
            config('app.name', 'KICC Platform'),
            $user->email,
            $secret
        );
        return view('auth.mfa-setup', compact('secret', 'qrCode'));
    }

    public function confirmSetup(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|size:6']);
        $google2fa = new Google2FA();
        $secret = session('mfa_secret');
        if (!$secret || !$google2fa->verifyKey($secret, $data['code'])) {
            return back()->withErrors(['code' => 'Invalid code. Try again.']);
        }
        $user = Auth::user();
        $user->mfa_enabled = true;
        $user->mfa_secret = encrypt($secret);
        $user->mfa_secret || $user->mfa_enabled = true;
        $user->save();
        session()->forget('mfa_secret');
        return redirect()->route('dashboard.index')->with('success', 'Two-factor authentication enabled.');
    }

    public function showChallenge()
    {
        if (!session('mfa_user_id')) {
            return redirect()->route('login');
        }
        return view('auth.mfa-challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|size:6']);
        $userId = session('mfa_user_id');
        $user = User::find($userId);
        if (!$user || !$user->mfa_enabled || !$user->mfa_secret) {
            return redirect()->route('login');
        }
        $google2fa = new Google2FA();
        if ($google2fa->verifyKey(decrypt($user->mfa_secret), $data['code'])) {
            session()->forget('mfa_user_id');
            Auth::login($user, session('mfa_remember'));
            session()->forget('mfa_remember');
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard.index'));
        }
        return back()->withErrors(['code' => 'Invalid code.']);
    }

    public function disable()
    {
        $user = Auth::user();
        $user->mfa_enabled = false;
        $user->mfa_secret = null;
        $user->save();
        return back()->with('success', 'Two-factor authentication disabled.');
    }
}