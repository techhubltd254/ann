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
        return app(\App\Services\AdminHierarchyScope::class)->canCounty($user, $county);
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