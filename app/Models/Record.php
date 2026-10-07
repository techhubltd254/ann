<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class Record extends Model {
 use HasUuids;
 protected $fillable=['type','slug','name','description','status','payload','parent_id','updated_by'];
 protected function casts():array{return ['payload'=>'array','revision'=>'integer'];}
 public function media(){return $this->hasMany(MediaAsset::class)->orderByDesc('created_at');}
 public function publicMedia(){return $this->hasMany(MediaAsset::class)->where('status','published')->orderByDesc('created_at');}
 public function parent(){return $this->belongsTo(self::class,'parent_id');}
 public function scopePublished($q){return $q->where('status','published');}

    public static function findByOwner(string $ownerType, int $ownerId): ?self
    {
        $typeMap = [
            'App\\Models\\County'            => 'counties',
            'county'                         => 'counties',
            'App\\Models\\CountyInstitution' => 'institutions',
            'institution'                    => 'institutions',
            'App\\Models\\Sector'            => 'sectors',
        ];

        $type = $typeMap[$ownerType] ?? null;
        if (!$type) return null;

        return static::where('type', $type)
            ->where('payload->source_id', $ownerId)
            ->first();
    }
}
