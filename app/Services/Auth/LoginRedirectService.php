<?php

namespace App\Services\Auth;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

/**
 * LoginRedirectService — isolated redirect logic for all user types.
 *
 * One method, one file, testable independently.
 * AuthController calls this with the authenticated user and gets back
 * the correct redirect response. Zero inline if/else chains in controllers.
 */
class LoginRedirectService
{
    /**
     * Determine the correct post-login redirect for the given user.
     *
     * Order of precedence:
     *   1. Institution admin  → /institution-admin/{slug}
     *   2. KICC admin         → /portal
     *   3. County admin       → /county-admin/{slug}/pro
     *   4. National admin     → /national-admin
     *   5. Exhibitor          → /exhibitor-admin
     *   6. Provider           → /provider-admin
     *   7. Fallback           → /dashboard
     */
    public function redirect(User $user): RedirectResponse
    {
        // Institution admin — strict per-institution access only
        if ($user->isInstitutionAdmin() && $user->institution_id) {
            $inst = CountyInstitution::find($user->institution_id);
            if ($inst) {
                return redirect()->route('institution.admin', $inst->slug);
            }
        }

        // KICC admin — portal picker
        if ($user->hasRole('kicc_admin')) {
            return redirect('/portal');
        }

        // County admin — land in their county's professional dashboard
        if ($user->hasRole('county_admin')) {
            if ($user->county_id) {
                $county = County::find($user->county_id);
                if ($county) {
                    return redirect()->route('county.admin.pro', $county->slug);
                }
            }
            return redirect()->route('dashboard.county');
        }

        // National admin
        if ($user->hasRole('national_admin')) {
            return redirect()->route('national.admin');
        }

        // Exhibitor — private portal
        if ($user->hasRole('exhibitor') || $user->account_type === 'exhibitor') {
            return redirect()->route('exhibitor.admin')
                ->with('success', "Welcome back, {$user->name}!");
        }

        // Travel provider
        if ($user->account_type === 'provider') {
            return redirect()->route('provider.admin')
                ->with('success', "Welcome back, {$user->name}!");
        }

        // Any admin-type account goes to portal picker
        if (in_array($user->account_type, ['superadmin', 'admin', 'county', 'ministry'])) {
            return redirect('/portal');
        }

        // Final fallback
        return redirect()->intended(route('dashboard.index'));
    }
}