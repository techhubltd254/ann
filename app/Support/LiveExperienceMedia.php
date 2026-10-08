<?php
namespace App\Support;
use App\Models\MediaAsset;
class LiveExperienceMedia {
 public static function resolve($record,string $type):array {
  $video=null;$poster=null;
  try {
   $owner=match($type){'venues'=>\App\Models\Venue::class,'counties'=>\App\Models\County::class,'institutions'=>\App\Models\CountyInstitution::class,default=>null};
   if($owner){$asset=MediaAsset::resolveSlot($owner,(int)$record->id,'hero_video');$video=$asset?->mp4Url();$poster=$asset?->posterUrl();if(!$poster){$image=MediaAsset::resolveSlot($owner,(int)$record->id,'hero_image');$poster=$image?->url();}}
   if($type==='products'){$video=$record->video_url;if(!$video){foreach(($record->videos??[]) as $v){$url=is_string($v)?$v:($v['url']??null);if($url){$video=$url;break;}}}$poster=$record->images->first()?->url??$record->variants->first()?->image_url;}
   if($type==='exhibitions'){$asset=MediaAsset::resolveSlot(\App\Models\Exhibition::class,(int)$record->id,'cover_video');$video=$asset?->mp4Url();$poster=$asset?->posterUrl()??$record->cover_image;}
   $poster=$poster??$record->cover_image??($type==='institutions'?$record->cover_image_url:null);
   foreach(['video','poster'] as $field){$url=$$field;if($url&&!preg_match('#^https?://#i',$url))$$field=media(ltrim($url,'/'));}
  }catch(\Throwable $e){/* Missing media never replaces real content with fabricated stock. */}
  return ['video'=>$video,'poster'=>$poster];
 }
}
