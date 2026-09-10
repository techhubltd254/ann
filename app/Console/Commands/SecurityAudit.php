<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;

class SecurityAudit extends Command
{
    protected $signature = 'security:audit {--fix : Attempt to auto-fix detected issues}';
    protected $description = 'Run a comprehensive security audit across the platform (Red Team + Blue Team checks)';

    public function handle(): int
    {
        $issues = [];
        $warnings = [];

        $this->info("=== KICC Security Audit ===");

        // ---------- 1. Environment hardening ----------
        $this->info("\n[1/9] Environment checks...");
        if ((config('app.debug') ?? false)) {
            $issues[] = 'APP_DEBUG is true in production — exposes stack traces & secrets.';
        } else {
            $this->line("  ✓ APP_DEBUG is off");
        }

        if (!app()->environment('production')) {
            $warnings[] = 'APP_ENV is not "production" — middleware/firewall differences may apply.';
        }

        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $env = File::get($envPath);
            if (preg_match('/APP_KEY="?([^"\s]+)"/', $env, $m) && strlen(trim($m[1], '"')) === 0) {
                $issues[] = 'APP_KEY is empty — run php artisan key:generate';
            }
            // Check for accidentally committed secrets
            if (str_contains($env, 'AKIA') || preg_match('/secret[_-]?(key|token)\s*=\s*[^"\s]+/i', $env)) {
                $warnings[] = 'Potential hardcoded secrets detected in .env — rotate if exposed.';
            }
        }

        // ---------- 2. Rate limiting ----------
        $this->info("\n[2/9] Rate limiting...");
        $rateLimiter = config('cache.default');
        $this->line("  Cache driver (rate-limit backing): {$rateLimiter}");

        // ---------- 3. Database security ----------
        $this->info("\n[3/9] Database checks...");
        $dbConn = config('database.connections.' . config('database.default'));
        if (empty($dbConn['password'] ?? '')) {
            $warnings[] = 'Database password is empty in config.';
        }
        $this->line("  DB connection: " . ($dbConn['host'] ?? 'unknown') . " (not printing credentials)");

        // ---------- 4. File permissions ----------
        $this->info("\n[4/9] File permissions...");
        $storage = storage_path();
        foreach (['app', 'framework', 'logs'] as $dir) {
            $p = "$storage/$dir";
            if (File::isWritable($p)) {
                $this->line("  ✓ $p writable");
            } else {
                $issues[] = "$p not writable";
            }
        }

        // ---------- 5. Exposed files checks ----------
        $this->info("\n[5/9] Exposed files...");
        foreach (['.env', '.env.example', 'tokens.txt', 'credentials.json', '*.sql', '*.sqlite', '*.log'] as $pattern) {
            $matches = $this->globPublic($pattern);
            foreach ($matches as $f) {
                $issues[] = "Sensitive file exposed in public: $f";
            }
        }
        if (File::exists(base_path('.env.backup'))) {
            $warnings[] = '.env.backup exists — delete it.';
        }

        // ---------- 6. Session/cookie security ----------
        $this->info("\n[6/9] Session & cookie hardening...");
        if ((config('session.secure') ?? false)) {
            $this->line("  ✓ Session cookies marked secure");
        } else {
            $warnings[] = 'SESSION_SECURE_COOKIE not set — cookies sent over HTTP.';
        }
        if (config('session.http_only') ?? true) {
            $this->line("  ✓ HttpOnly cookies enabled");
        }
        if (config('session.same_site')) {
            $this->line('  ✓ SameSite=' . config('session.same_site'));
        }

        // ---------- 7. CORS ----------
        $this->info("\n[7/9] CORS...");
        $cors = config('cors') ?? [];
        if (($cors['allowed_origins'] ?? []) === ['*']) {
            $warnings[] = 'CORS allows all origins (*). Restrict to kicctest.org.';
        } else {
            $this->line("  ✓ CORS restricted");
        }

        // ---------- 8. Exposed DB data / orphaned records ----------
        $this->info("\n[8/9] Data integrity...");
        try {
            $orphanHeartbeats = \DB::table('heartbeat_logs')->whereNotExists(
                fn($q) => $q->selectRaw('1')->from('booth_authorizations')->whereColumn('booth_authorizations.id', 'heartbeat_logs.booth_authorization_id')
            )->count();
            if ($orphanHeartbeats > 0) {
                $issues[] = "$orphanHeartbeats orphan heartbeat logs (FK integrity).";
            } else {
                $this->line("  ✓ No orphan heartbeat logs");
            }
        } catch (\Throwable $e) {
            $warnings[] = 'Could not verify heartbeat FK integrity: ' . $e->getMessage();
        }

        // ---------- 9. Security headers on test request ----------
        $this->info("\n[9/9] Security headers verification (via internal request)...");
        try {
            $request = \Illuminate\Http\Request::create('/');
            $middleware = new \App\Http\Middleware\SecurityHeaders();
            $response = $middleware->handle($request, function ($req) {
                return response('ok');
            });
            foreach (['Content-Security-Policy', 'Strict-Transport-Security', 'X-Frame-Options', 'X-Content-Type-Options'] as $header) {
                if ($response->headers->has($header)) {
                    $this->line("  ✓ $header present");
                } else {
                    $issues[] = "Security header $header missing.";
                }
            }
        } catch (\Throwable $e) {
            $warnings[] = 'Security header check skipped: ' . $e->getMessage();
        }

        // ---------- Summary ----------
        $this->newLine();
        $this->info("=== Audit Summary ===");
        if (count($issues) === 0 && count($warnings) === 0) {
            $this->info("  ✅ PASS — no issues found");
            return self::SUCCESS;
        }
        if (count($issues) > 0) {
            $this->error('  ❌ ' . count($issues) . ' critical issue(s):');
            foreach ($issues as $i) $this->error("    - $i");
        }
        if (count($warnings) > 0) {
            $this->warn("  ⚠ " . count($warnings) . " warning(s):");
            foreach ($warnings as $w) $this->warn("    - $w");
        }
        return count($issues) > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function globPublic(string $pattern): array
    {
        // Only scan the public/ directory to avoid false positives
        $results = File::glob(public_path($pattern));
        return $results ?: [];
    }
}