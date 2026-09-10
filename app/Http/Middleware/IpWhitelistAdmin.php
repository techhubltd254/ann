<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IpWhitelistAdmin
{
    private array $whitelist = [];

    public function __construct()
    {
        $envList = env('ADMIN_IP_WHITELIST', '');
        if ($envList) {
            $this->whitelist = array_map('trim', explode(',', $envList));
        }
    }

    public function handle(Request $request, Closure $next)
    {
        if (!config('app.debug') && !empty($this->whitelist)) {
            $ip = $request->ip();
            $allowed = false;
            foreach ($this->whitelist as $cidr) {
                if ($this->ipInRange($ip, $cidr)) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                Log::warning('Admin access blocked from IP', ['ip' => $ip, 'route' => $request->path()]);
                abort(403, 'Access restricted to authorized networks.');
            }
        }
        return $next($request);
    }

    private function ipInRange(string $ip, string $range): bool
    {
        if (str_contains($range, '/')) {
            [$subnet, $bits] = explode('/', $range);
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - (int) $bits);
            $subnet &= $mask;
            return ($ip & $mask) === $subnet;
        }
        return $ip === $range;
    }
}