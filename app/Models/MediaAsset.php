<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class MediaAsset extends Model {
 use HasUuids;
 protected $guarded=['id'];
 public function record(){return $this->belongsTo(Record::class);}
 public function isVideo():bool{return str_starts_with($this->mime,'video/');}
 public function url():string{return route('media.show',$this);}
}
