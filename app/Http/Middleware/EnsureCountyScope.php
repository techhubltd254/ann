<?php

namespace App\Http\Middleware;

use App\Models\County;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Restrict county-scoped resources to the user's county.
 * KICC admins can pass `county_id=NN` to view a specific county.
 */
class EnsureCountyScope
{
    public function handle(Request $request, Closure $next, string $routeParam = 'county')
    {
        $user = Auth::user();
        if (!$user) abort(401);

        if ($user->hasRole('kicc_admin') || $user->hasRole('national_admin') || $user->hasAnyRole(['superadmin'])) {
            return $next($request);
        }

        $county = null;
        if ($request->routeIs('dashboard.county')) {
            $county = County::find((int) $user->county_id);
            abort_unless($county, 403, 'Your account has no county assigned.');
        } else {
            $slugOrId = $request->route($routeParam);
            if (!$slugOrId) return $next($request);
            $county = is_numeric($slugOrId)
                ? County::find((int) $slugOrId)
                : County::where('slug', $slugOrId)->first();
            abort_unless($county, 404);
        }

        abort_unless((int) $user->county_id === (int) $county->id, 403, 'Out-of-county access denied.');

        // Bind into request for downstream usage.
        $request->attributes->set('county', $county);
        return $next($request);
    }
}
