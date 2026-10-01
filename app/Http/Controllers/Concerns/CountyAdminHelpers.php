<?php

namespace App\Http\Controllers\Concerns;

use App\Models\County;
use App\Services\CacheSyncService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
        Gate::authorize('update', $county);
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