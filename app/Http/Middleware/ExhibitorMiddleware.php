<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExhibitorMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // User must have exhibitor role OR own the booth being accessed
        if (!$user->hasRole('exhibitor') && !$user->hasRole(['kicc_admin', 'superadmin'])) {
            $boothId = $request->route('booth')?->id ?? $request->input('booth_id');
            if ($boothId) {
                $booth = \App\Models\Booth::find($boothId);
                if (!$booth || $booth->user_id !== $user->id) {
                    abort(403, 'You are not authorized to access this exhibitor studio.');
                }
            } else {
                abort(403, 'Exhibitor access required.');
            }
        }

        return $next($request);
    }
}