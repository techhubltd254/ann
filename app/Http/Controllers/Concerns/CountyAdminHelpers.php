<?php

namespace App\Http\Controllers\Concerns;

use App\Models\County;
use App\Services\CacheSyncService;
use Illuminate\Support\Facades\Auth;

/**
 * Shared helpers for county-scoped admin controllers.
 * Used by CountyAdminController and CountyMediaController.
 */
trait CountyAdminHelpers
{
    protected function authorizeCounty(string $slug): County
    {
        $user = Auth::user();
        abort_unless($user, 401);
        $county = County::where('slug', $slug)->firstOrFail();
        $allowed = $user->isAdmin()
            || $user->hasRole('kicc_admin')
            || $user->hasRole('national_admin')
            || ($user->county_id && $user->county_id == $county->id)
            || ($user->hasRole('county_admin') && $user->county_id == $county->id);
        abort_unless($allowed, 403, 'You do not have access to this county.');
        return $county;
    }

    protected function syncCounty(County $county): void
    {
        app(CacheSyncService::class)->county($county->id);
    }

    protected function syncSector(County $county, int $sectorId): void
    {
        app(CacheSyncService::class)->sector($county->id, $sectorId);
    }

    protected function syncInstitution(int $institutionId): void
    {
        app(CacheSyncService::class)->institution($institutionId);
    }
}