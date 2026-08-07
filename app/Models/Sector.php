<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Sector extends Model
{
    /**
     * Canonical major groups for national-sector organization.
     * key => [display name, icon]. Assigned by infra/sector-groups.php.
     */
    public const GROUP_META = [
        'agriculture'    => ['Agriculture, Livestock & Fisheries', '🌾'],
        'health'         => ['Health & Sanitation', '🏥'],
        'education'      => ['Education, Youth & Sports', '🎓'],
        'trade'          => ['Trade, Industry & Cooperatives', '🛍️'],
        'tourism'        => ['Tourism, Culture & Heritage', '🏖️'],
        'infrastructure' => ['Infrastructure, Transport & Public Works', '🚧'],
        'lands'          => ['Lands, Housing & Urban Development', '🏘️'],
        'water'          => ['Water, Environment & Climate', '💧'],
        'energy'         => ['Energy', '⚡'],
        'ict'            => ['ICT & Digital Economy', '💻'],
        'finance'        => ['Finance & Economic Planning', '💰'],
        'public-service' => ['Public Service & Administration', '🏛️'],
        'social'         => ['Gender & Social Services', '🤝'],
    ];

    protected $fillable = [
        'name', 'slug', 'code', 'emoji', 'description',
        'parent_id', 'icon', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function counties()
    {
        return $this->belongsToMany(County::class)
            ->withPivot('sub_sectors');
    }

    public function parent()
    {
        return $this->belongsTo(Sector::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Sector::class, 'parent_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (Sector $sector) {
            if (empty($sector->slug)) {
                $sector->slug = Str::slug($sector->name);
            }
        });
    }
}
