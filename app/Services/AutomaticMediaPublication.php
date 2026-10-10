<?php
namespace App\Services;
use App\Models\{MediaAsset,User};
use Illuminate\Support\Facades\Log;
class AutomaticMediaPublication {
 public function publishIfReady(MediaAsset $asset):void {
  $asset->refresh();$m=$asset->metadata??[];
  if(($m['namespace']??'')!=='national'||!($m['publish_on_ready']??false)||$asset->status!=='ready'||($m['publication']??'')==='published')return;
  $actor=User::find($m['actor_id']??0);
  try{app(NationalMediaService::class)->publish($asset,$actor);}catch(\Throwable $e){
   $asset->refresh();$m=$asset->metadata??[];$m['publication_error']='Automatic publication was blocked. An authorized administrator must retry.';$asset->update(['metadata'=>$m]);
   Log::warning('Automatic national publication blocked',['asset'=>$asset->id,'error'=>$e->getMessage()]);
  }
 }
}
