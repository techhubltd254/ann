<?php

namespace App\Models\Traits;

trait HasTenant
{
    public static function getCurrentCountyId(): ?int
    {
        $user = request()->user();
        if (!$user) return null;

        if ($user->hasRole('kicc_admin') || $user->hasRole('national_admin')) {
            return null; // no scope
        }

        if ($user->hasRole('county_admin')) {
            return (int) (session('admin_county_id') ?? $user->county_id);
        }

        return $user->county_id;
    }

    public static function isTenantScoped(): bool
    {
        $user = request()->user();
        if (!$user) return false;
        return !$user->hasRole('kicc_admin') && !$user->hasRole('national_admin');
    }
}
