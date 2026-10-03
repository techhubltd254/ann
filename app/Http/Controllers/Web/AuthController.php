<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PhoneVerificationCode;
use App\Services\AuditLogger;
use App\Events\UserEvent;
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
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($data['email']));

        $key = 'send-code:' . $email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['email' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.']);
        }
        RateLimiter::hit($key, 60);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PhoneVerificationCode::where('phone', $email)->where('used_at', null)->update(['used_at' => now()]);

        PhoneVerificationCode::create([
            'phone' => $email,
            'code' => Hash::make($code),
            'purpose' => 'registration',
            'expires_at' => now()->addMinutes(10),
        ]);

        session(['reg_email' => $email]);

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\VerificationCodeMail($code, 'registration'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Verification email to {$email} failed: " . $e->getMessage());
        }
        \Illuminate\Support\Facades\Log::info("Auth: registration code sent to {$email}");

        return redirect()->route('register.verify')->with('message', 'Code sent to ' . $email);
    }

    public function showVerify()
    {
        if (!session('reg_email')) {
            return redirect()->route('register');
        }
        return view('auth.verify', ['email' => session('reg_email')]);
    }

    public function verifyCode(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $email = session('reg_email');
        if (!$email) {
            return redirect()->route('register')->withErrors(['email' => 'Session expired.']);
        }

        $key = 'verify-code:' . $email;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Too many attempts. Try again later.']);
        }
        RateLimiter::hit($key, 120);

        $record = PhoneVerificationCode::where('phone', $email)
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
        $email = session('reg_email');
        if (!$email) {
            return redirect()->route('register');
        }

        if (User::where('email', $email)->exists()) {
            $user = User::where('email', $email)->first();
            $user->update(['email_verified_at' => now()]);
            Auth::login($user);
            session()->forget('reg_email');
            return redirect()->route('dashboard.index');
        }

        return view('auth.details', ['email' => $email]);
    }

    public function completeRegistration(Request $request)
    {
        $email = session('reg_email');
        if (!$email) {
            return redirect()->route('register');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|regex:/^\+?[0-9]{9,15}$/',
            'account_type' => 'required|string|in:individual,exhibitor,sme,school',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'fullName' => $data['name'],
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'account_type' => $data['account_type'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'active' => true,
            'mfaEnabled' => false,
            'tier' => 'EXHIBITOR',
            'status' => 'active',
        ]);

        event(new UserEvent($user->id, 'registered', $request->ip()));

        Auth::login($user);
        session()->forget('reg_email');

        // Exhibitors go straight to their setup wizard — their personalized
        // website is built from the answers.
        if ($user->account_type === 'exhibitor') {
            $user->assignRole('exhibitor');
            return redirect()->route('exhibitor.onboarding')
                ->with('success', "🎉 Account created, {$user->name}! Answer 3 quick questions to set up your exhibitor website.");
        }

        return redirect()->route('dashboard.index')
            ->with('success', "🎉 Account created successfully — welcome, {$user->name}!");
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return app(\App\Services\Auth\LoginRedirectService::class)->redirect(Auth::user());
        }
        return view('auth.login');
    }

    /** Hidden admin login page (accessible at /kicc-admin/login — no public link). */
    public function showAdminLogin()
    {
        if (Auth::check()) {
            return app(\App\Services\Auth\LoginRedirectService::class)->redirect(Auth::user());
        }
        return view('auth.admin-login');
    }

    /** Hidden admin login POST — authenticates and redirects by role. */
    public function adminLogin(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $data['login'] ?? '';
        $key = 'login:' . $request->ip() . ':' . strtolower($login);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['login' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.']);
        }

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (Auth::attempt([$field => $data['login'], 'password' => $data['password'], 'status' => 'active'], $request->boolean('remember'))) {
            $request->session()->regenerate();
            RateLimiter::clear($key);
            $user = Auth::user();
            AuditLogger::log($user->id, 'auth.admin_login_success', 'User', $user->id, ['ip' => $request->ip()]);
            \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
                \Sentry\Breadcrumb::LEVEL_INFO,
                \Sentry\Breadcrumb::TYPE_USER,
                'auth',
                "Admin login success: {$user->email}",
                ['user_id' => $user->id]
            ));

            // Role-based redirect — determined server-side, no portal selector needed
            if ($user->hasRole('kicc_admin')) {
                return redirect('/portal');
            }
            if ($user->hasRole('national_admin')) {
                return redirect()->route('national.admin.v2.dashboard');
            }
            if ($user->hasRole('county_admin')) {
                $countyId = $user->county_id;
                if ($countyId) {
                    $county = \App\Models\County::find($countyId);
                    if ($county) return redirect()->route('county.admin.pro', $county->slug);
                }
                return redirect()->route('dashboard.county');
            }
            // Generic admin fallback
            if ($user->hasRole('exhibitor') || $user->account_type === 'exhibitor') {
                return redirect()->route('exhibitor.admin');
            }
            // Authenticated but no admin role — use standard redirect
            return app(\App\Services\Auth\LoginRedirectService::class)->redirect($user);
        }

        RateLimiter::hit($key, 120);
        AuditLogger::log(null, 'auth.admin_login_failed', 'User', null, ['login_attempt' => $login, 'ip' => $request->ip()]);

        return back()->withErrors(['login' => 'Invalid admin credentials.'])->onlyInput('login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'admin_type' => 'nullable|string|in:kicc,national,county,exhibitor,public',
            'county_id' => 'nullable|integer|exists:counties,id',
        ]);

        $login = $data['login'] ?? '';
        $key = 'login:' . $request->ip() . ':' . strtolower($login);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['login' => 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.']);
        }

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (Auth::attempt([$field => $data['login'], 'password' => $data['password'], 'status' => 'active'], $request->boolean('remember'))) {
            $request->session()->regenerate();
            RateLimiter::clear($key);
            $user = Auth::user();
            AuditLogger::log($user->id, 'auth.login_success', 'User', $user->id, ['ip' => $request->ip(), 'field' => $field]);
            \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
                \Sentry\Breadcrumb::LEVEL_INFO,
                \Sentry\Breadcrumb::TYPE_USER,
                'auth',
                "Login success: {$user->email}",
                ['user_id' => $user->id, 'field' => $field]
            ));
            $adminType = $request->input('admin_type');

            // Role-based redirect
            if ($adminType === 'kicc' && $user->hasRole('kicc_admin')) {
                return redirect('/portal');
            }
            if ($adminType === 'national' && $user->hasRole('national_admin')) {
                return redirect()->route('national.admin.v2.dashboard');
            }
            if ($adminType === 'county' && $user->hasRole('county_admin')) {
                $countyId = $request->input('county_id') ?: $user->county_id;
                $county = \App\Models\County::find($countyId);
                if ($county) return redirect()->route('county.admin.pro', $county->slug);
                return redirect()->route('dashboard.county');
            }
            if ($adminType === 'exhibitor' && ($user->hasRole('exhibitor') || $user->account_type === 'exhibitor')) {
                return redirect()->route('exhibitor.admin')
                    ->with('success', "Welcome back, {$user->name}!");
            }
            // Fallback: redirect by user's actual role / account type
            return app(\App\Services\Auth\LoginRedirectService::class)->redirect($user);
        }

        RateLimiter::hit($key, 120);
        AuditLogger::log(null, 'auth.login_failed', 'User', null, ['login_attempt' => $login, 'ip' => $request->ip(), 'field' => $field]);
        \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
            \Sentry\Breadcrumb::LEVEL_WARNING,
            \Sentry\Breadcrumb::TYPE_USER,
            'auth',
            "Login failed: {$login}",
            ['ip' => $request->ip(), 'field' => $field]
        ));
        return back()->withErrors(['login' => 'Invalid credentials.'])->onlyInput('login');
    }

    public function sendLoginCode(Request $request)
    {
        $data = $request->validate([
            'login' => 'required|string',
        ]);

        $user = User::where('email', strtolower(trim($data['login'])))->first()
            ?? User::where('phone', $data['login'])->first();

        // Rate-limit by IP to prevent enumeration attacks
        $ipKey = 'login-code-ip:' . $request->ip();
        RateLimiter::hit($ipKey, 360); // 6 per hour
        if (RateLimiter::tooManyAttempts($ipKey, 6)) {
            return back()->withErrors(['login' => 'Too many attempts from this IP. Try again later.']);
        }

        if (!$user || !$user->email) {
            RateLimiter::clear($ipKey);
            return redirect()->route('login.code')->with('message', 'If an account exists, a code has been sent.');
        }

        $email = strtolower(trim($user->email));

        $key = 'login-code:' . $email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withErrors(['login' => 'Too many attempts. Try again later.']);
        }
        RateLimiter::hit($key, 60);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PhoneVerificationCode::where('phone', $email)->where('used_at', null)->update(['used_at' => now()]);

        PhoneVerificationCode::create([
            'phone' => $email,
            'code' => Hash::make($code),
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(10),
        ]);

        session(['login_code_email' => $email]);

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\VerificationCodeMail($code, 'login'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Login code email to {$email} failed: " . $e->getMessage());
        }
        \Illuminate\Support\Facades\Log::info("Login code sent to {$email}");

        return redirect()->route('login.code')->with('message', 'Code sent to your email.');
    }

    public function showLoginCode()
    {
        if (!session('login_code_email')) {
            return redirect()->route('login');
        }
        return view('auth.login-code', ['email' => session('login_code_email')]);
    }

    public function verifyLoginCode(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|size:6']);

        $email = session('login_code_email');
        if (!$email) {
            return redirect()->route('login');
        }

        $key = 'verify-login:' . $email;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Too many attempts.']);
        }
        RateLimiter::hit($key, 120);

        $record = PhoneVerificationCode::where('phone', $email)
            ->where('purpose', 'login')
            ->where('used_at', null)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$record || !Hash::check($data['code'], $record->code)) {
            return back()->withErrors(['code' => 'Invalid or expired code.']);
        }

        $record->update(['used_at' => now()]);

        $user = User::where('email', $email)->first();
        if ($user) {
            Auth::login($user);
            session()->forget('login_code_email');
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard.index'));
        }

        return redirect()->route('login')->withErrors(['login' => 'Account not found.']);
    }

    /** Show the forgot-password form. */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /** Send password reset link (mock — logs the link instead of sending mail). */
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $token = \Illuminate\Support\Str::random(60);
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => \Illuminate\Support\Facades\Hash::make($token), 'created_at' => now()]
        );

        $resetUrl = route('password.reset', ['token' => $token, 'email' => $request->email]);
        \Illuminate\Support\Facades\Log::info('Password reset link: ' . $resetUrl);

        // In production, send email via NotificationService
        try {
            app(\App\Services\NotificationService::class)->send($request->email, 'reset_password', [
                'reset_url' => $resetUrl,
                'name' => \App\Models\User::where('email', $request->email)->value('name'),
            ]);
        } catch (\Throwable) {
            // Log-only fallback
        }

        return back()->with('success', "Password reset link sent to {$request->email}. Check your email (or check the logs).");
    }

    /** Show the reset-password form. */
    public function showResetForm(string $token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /** Actually reset the password. */
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $data['email'])->first();

        if (! $record || ! \Illuminate\Support\Facades\Hash::check($data['token'], $record->token)) {
            return back()->withErrors(['email' => 'Invalid or expired reset token.']);
        }

        \App\Models\User::where('email', $data['email'])->update([
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
        ]);

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return redirect()->route('login')->with('success', 'Password reset successfully. Sign in with your new password.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        \Illuminate\Support\Facades\Log::info('Auth: user logged out', ['ip' => $request->ip()]);
        return redirect('/');
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        Auth::logoutOtherDevices($data['new_password']);
        $request->session()->regenerate();

        \Illuminate\Support\Facades\Log::info('Auth: password changed', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return redirect()->route('dashboard.index')->with('success', 'Password changed successfully. All other sessions have been logged out.');
    }
}
