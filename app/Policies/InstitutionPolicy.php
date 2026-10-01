<?php

namespace App\Policies;

use App\Models\CountyInstitution;
use App\Models\User;

class InstitutionPolicy
{
    public function view(?User $user, CountyInstitution $institution): bool
    {
        return true; // public resource
    }

    public function update(User $user, CountyInstitution $institution): bool
    {
        return $user->isAdmin()
            || $user->hasRole('kicc_admin')
            || $user->hasRole('national_admin')
            || ($user->county_id && $user->county_id == $institution->county_id)
            || ($user->hasRole('county_admin') && $user->county_id == $institution->county_id)
            || ($user->institution_id && $user->institution_id == $institution->id);
    }

    public function delete(User $user, CountyInstitution $institution): bool
    {
        return $user->isAdmin()
            || $user->hasRole('kicc_admin')
            || ($user->hasRole('county_admin') && $user->county_id == $institution->county_id);
    }
}