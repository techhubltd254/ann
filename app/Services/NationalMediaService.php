<?php
namespace App\Services;
use App\Models\{MediaAsset,County,Ministry};
use Illuminate\Support\Facades\{DB,Cache};
class NationalMediaService {
 public function authorize($user):void {abort_unless($user&&($user->status??'active')==='active'&&in_array(app(AdminHierarchyScope::class)->level($user),['kicc','national'],true),403,'National media requires national or KICC administration.');}
 public function target(MediaAsset $a):string {
  $slot=$a->metadata['target_slot']??$a->slot;
  $national=$a->owner_type===County::class&&(int)$a->owner_id===0&&in_array($slot,['national_hero_video','national_flag_video'],true);
  $ministry=$a->owner_type===Ministry::class&&Ministry::whereKey($a->owner_id)->exists()&&($slot==='ministry_flag_video'||str_starts_with($slot,'ministry_video_'));
  abort_unless($national||$ministry,404,'Not a national media asset');return $slot;
 }
 public function publish(MediaAsset $asset,$user):void {
  $this->authorize($user);$slot=$this->target($asset);
  abort_unless($asset->status==='ready'&&$asset->derivatives()->where('variant','stream-safe')->exists(),422,'Wait for verified browser-ready processing before publishing.');
  // Hold the per-slot mutex until after the database transaction commits.
  $lock=Cache::lock('national-publish:'.sha1($asset->owner_type.':'.$asset->owner_id.':'.$slot),60);
  abort_unless($lock->get(),409,'Another publication is in progress.');
  try{DB::transaction(function()use($asset,$slot){$a=MediaAsset::lockForUpdate()->findOrFail($asset->id);
   abort_unless($a->status==='ready'&&$a->derivatives()->where('variant','stream-safe')->exists(),422,'Video processing is not ready.');
   $existing=MediaAsset::where('owner_type',$a->owner_type)->where('owner_id',$a->owner_id)->where('slot',$slot)->where('id','!=',$a->id)->lockForUpdate()->get();
   foreach($existing as $old){$meta=$old->metadata??[];$meta['publication']='archived';$meta['target_slot']=$slot;$old->update(['slot'=>'archived__'.$slot,'metadata'=>$meta]);}
   $meta=$a->metadata??[];$meta['publication']='published';$meta['target_slot']=$slot;$meta['published_at']=now()->toIso8601String();$a->update(['slot'=>$slot,'metadata'=>$meta]);
  });}finally{$lock->release();}$this->bust();
 }
 public function unpublish(MediaAsset $a,$user):void {$this->authorize($user);$slot=$this->target($a);$meta=$a->metadata??[];$meta['publication']='draft';$meta['target_slot']=$slot;$a->update(['slot'=>'draft__'.$slot,'metadata'=>$meta]);$this->bust();}
 public function bust():void {Cache::forget('reference.native.v1');Cache::increment('kicc_cache_version');app(CacheSyncService::class)->national();}
 public function stream(MediaAsset $a):?string {$d=$a->derivatives->firstWhere('variant','stream-safe');return $d?url('/media/original/'.$d->path):($a->status==='ready'?url('/media/original/'.$a->path):null);}
}
