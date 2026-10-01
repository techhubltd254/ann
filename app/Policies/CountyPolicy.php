<?php

namespace App\Policies;

use App\Models\County;
use App\Models\User;

class CountyPolicy
{
    public function view(?User $user, County $county): bool
    {
        return true; // public resource
    }

    public function update(User $user, County $county): bool
    {
        return $user->isAdmin()
            || $user->hasRole('kicc_admin')
            || $user->hasRole('national_admin')
            || ($user->county_id && $user->county_id == $county->id)
            || ($user->hasRole('county_admin') && $user->county_id == $county->id);
    }

    public function manageMedia(User $user, County $county): bool
    {
        return $this->update($user, $county);
    }

    public function manageCommerce(User $user, County $county): bool
    {
        return $this->update($user, $county);
    }
}