<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    protected string $column;

    public function __construct(string $column = 'county_id')
    {
        $this->column = $column;
    }

    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();
        if (!$user) return;

        // KICC admin sees everything — no scope applied
        if ($user->hasRole('kicc_admin')) return;

        // National admin also sees everything (they have oversight)
        if ($user->hasRole('national_admin')) return;

        // County admin: scope to their county only
        if ($user->hasRole('county_admin')) {
            $countyId = $this->resolveCountyId($user);
            if ($countyId) {
                $builder->where($model->getTable() . '.' . $this->column, $countyId);
            }
            return;
        }

        // Exhibitor/provider: scope by their user-level county_id
        if ($user->county_id) {
            $builder->where($model->getTable() . '.' . $this->column, $user->county_id);
        }
    }

    protected function resolveCountyId($user): ?int
    {
        // Check session first (set during login)
        if ($sessionId = session('admin_county_id')) {
            return (int) $sessionId;
        }
        // Fall back to user's county_id
        return $user->county_id;
    }
}
