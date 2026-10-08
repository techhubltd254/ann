<?php
namespace App\Services;

use App\Models\{User, County, CountyInstitution};
use Illuminate\Database\Eloquent\Builder;

/** The existing four-level hierarchy; no new roles or synthetic entity tree. */
class AdminHierarchyScope
{
    public function level(User $user): ?string
    {
        if ($user->hasRole('kicc_admin')) return 'kicc';
        if ($user->hasRole('national_admin')) return 'national';
        if ($user->hasRole('county_admin')) return 'county';
        if ($user->isInstitutionAdmin()) return 'institution';
        return null;
    }
    public function global(User $user): bool
    {
        return in_array($this->level($user), ['kicc', 'national'], true);
    }
    public function counties(User $user): Builder
    {
        $q=County::query();
        if ($this->global($user)) return $q;
        if ($this->level($user)==='county') return $q->where('id', (int)$user->county_id);
        $ids=$this->institutions($user)->pluck('county_id');
        return $q->whereIn('id',$ids);
    }
    public function institutions(User $user): Builder
    {
        $q=CountyInstitution::query();
        if ($this->global($user)) return $q;
        if ($this->level($user)==='county') return $q->where('county_id',(int)$user->county_id);
        if ($this->level($user)==='institution') {
            return $q->where(fn($q)=>$q->where('id',(int)$user->institution_id)->orWhere('user_id',(int)$user->id));
        }
        return $q->whereRaw('1=0');
    }
    public function canCounty(User $user, County $county): bool
    {
        return $this->global($user) || ($this->level($user)==='county' && (int)$user->county_id===(int)$county->id);
    }
    public function canInstitution(User $user, CountyInstitution $institution): bool
    {
        if ($this->global($user)) return true;
        if ($this->level($user)==='county') return (int)$user->county_id===(int)$institution->county_id;
        return $this->level($user)==='institution' && ((int)$user->institution_id===(int)$institution->id || (int)$institution->user_id===(int)$user->id);
    }
}
