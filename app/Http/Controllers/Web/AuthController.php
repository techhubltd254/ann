<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PhoneVerificationCode;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function sendCode(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:20|regex:/^\+?[0-9]{9,15}$/',
        ]);

        $phone = $data['phone'];

        $key = 'send-code:' . $phone;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['phone' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.']);
        }
        RateLimiter::hit($key, 60);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PhoneVerificationCode::where('phone', $phone)->where('used_at', null)->update(['used_at' => now()]);

        PhoneVerificationCode::create([
            'phone' => $phone,
            'code' => Hash::make($code),
            'purpose' => 'registration',
            'expires_at' => now()->addMinutes(10),
        ]);

        $sms = app(SMSService::class);
        $sms->sendVerificationCode($phone, $code);

        session(['reg_phone' => $phone]);

        return redirect()->route('register.verify')->with('message', 'Code sent to ' . $phone);
    }

    public function showVerify()
    {
        if (!session('reg_phone')) {
            return redirect()->route('register');
        }
        return view('auth.verify', ['phone' => session('reg_phone')]);
    }

    public function verifyCode(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $phone = session('reg_phone');
        if (!$phone) {
            return redirect()->route('register')->withErrors(['phone' => 'Session expired.']);
        }

        $key = 'verify-code:' . $phone;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Too many attempts. Try again later.']);
        }
        RateLimiter::hit($key, 120);

        $record = PhoneVerificationCode::where('phone', $phone)
            ->where('purpose', 'registration')
            ->where('used_at', null)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record || !Hash::check($data['code'], $record->code)) {
            return back()->withErrors(['code' => 'Invalid or expired code.']);
        }

        $record->update(['used_at' => now()]);

        return redirect()->route('register.details');
    }

    public function showDetails()
    {
        $phone = session('reg_phone');
        if (!$phone) {
            return redirect()->route('register');
        }

        if (User::where('phone', $phone)->exists()) {
            $user = User::where('phone', $phone)->first();
            $user->update(['phone_verified_at' => now()]);
            Auth::login($user);
            session()->forget('reg_phone');
            return redirect()->route('dashboard.index');
        }

        return view('auth.details', ['phone' => $phone]);
    }

    public function completeRegistration(Request $request)
    {
        $phone = session('reg_phone');
        if (!$phone) {
            return redirect()->route('register');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'account_type' => 'nullable|string|in:individual,sme,school',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $phone,
            'account_type' => $data['account_type'] ?? 'individual',
            'password' => Hash::make($data['password']),
            'phone_verified_at' => now(),
        ]);

        Auth::login($user);
        session()->forget('reg_phone');

        return redirect()->route('dashboard.index');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'admin_type' => 'nullable|string|in:kicc,national,county',
        ]);

        $key = 'login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['login' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.']);
        }

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (Auth::attempt([$field => $data['login'], 'password' => $data['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            RateLimiter::clear($key);

            $user = Auth::user();
            $adminType = $request->input('admin_type');

            // Role-based redirect
            if ($adminType === 'kicc' && $user->hasRole('kicc_admin')) {
                return redirect('/portal');
            }
            if ($adminType === 'national' && $user->hasRole('national_admin')) {
                return redirect()->route('admin.national');
            }
            if ($adminType === 'county' && $user->hasRole('county_admin')) {
                return redirect()->route('dashboard.county');
            }
            // Fallback: redirect by user's actual role
            if ($user->hasRole('kicc_admin')) {
                return redirect('/portal');
            }
            if ($user->hasRole('national_admin')) {
                return redirect()->route('admin.national');
            }
            if ($user->hasRole('county_admin')) {
                return redirect()->route('dashboard.county');
            }

            return redirect()->intended(route('dashboard.index'));
        }

        RateLimiter::hit($key, 120);
        return back()->withErrors(['login' => 'Invalid credentials.'])->onlyInput('login');
    }

    public function sendLoginCode(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
        ]);

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $data['login'])->first();

        if (!$user || !$user->phone) {
            return back()->withErrors(['login' => 'No account found with a linked phone.']);
        }

        $key = 'login-code:' . $user->phone;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['login' => 'Too many attempts. Try again later.']);
        }
        RateLimiter::hit($key, 60);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PhoneVerificationCode::where('phone', $user->phone)->where('used_at', null)->update(['used_at' => now()]);

        PhoneVerificationCode::create([
            'phone' => $user->phone,
            'code' => Hash::make($code),
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(5),
        ]);

        \Illuminate\Support\Facades\Log::info("Login code for {$user->phone}: {$code}");

        session(['login_code_phone' => $user->phone]);

        return redirect()->route('login.code')->with('message', 'Code sent to your phone.');
    }

    public function showLoginCode()
    {
        if (!session('login_code_phone')) {
            return redirect()->route('login');
        }
        return view('auth.login-code', ['phone' => session('login_code_phone')]);
    }

    public function verifyLoginCode(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|size:6']);

        $phone = session('login_code_phone');
        if (!$phone) {
            return redirect()->route('login');
        }

        $key = 'verify-login:' . $phone;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Too many attempts.']);
        }
        RateLimiter::hit($key, 120);

        $record = PhoneVerificationCode::where('phone', $phone)
            ->where('purpose', 'login')
            ->where('used_at', null)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record || !Hash::check($data['code'], $record->code)) {
            return back()->withErrors(['code' => 'Invalid or expired code.']);
        }

        $record->update(['used_at' => now()]);

        $user = User::where('phone', $phone)->first();
        if ($user) {
            Auth::login($user);
            session()->forget('login_code_phone');
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard.index'));
        }

        return redirect()->route('login')->withErrors(['login' => 'Account not found.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
