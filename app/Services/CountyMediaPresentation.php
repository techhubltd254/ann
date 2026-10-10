<?php
namespace App\Services;
use App\Models\County;
use App\Support\{MediaAssetIndex,MediaMapping};
/** Published county-owned media first; locally served source photographs second. */
class CountyMediaPresentation
{
    public function present(County $county, MediaAssetIndex $index, ?array $hero=null): array
    {
        $cfg=config('county_media.counties.'.$county->slug,[]);
        $hero??=MediaMapping::countyHero($county,$index);
        $configured=trim((string)($cfg['heroVideo']??''));
        $video=$hero['video']??($configured!==''?$configured:null);
        if($video&&str_starts_with($video,'/'))$video=url($video);
        if($video&&!preg_match('~^https?://~i',$video))$video=null;
        $image=null;$srcset='';$credit=null;$alt=$county->name.' county media';
        // Never expose an uploaded draft image merely because its processing is ready.
        $own=$index->forSlot(County::class,(int)$county->id,['fallback_image','hero_image']);
        if($own&&$own->status==='ready'&&$own->kind==='image'&&($own->metadata['publication']??'')!=='draft')$image=MediaMapping::countyFallbackImage($county,$index);
        if(!$image&&!empty($hero['video'])&&!empty($hero['poster']))$image=$hero['poster'];
        $local=trim((string)($cfg['fallbackImage']??''));
        if(!$image&&str_starts_with($local,'/images/county-landmarks/')&&is_file(public_path(ltrim($local,'/')))){
            $image=url($local);$srcset=(string)($cfg['imageSrcset']??'');$credit=$cfg['imageCredit']??null;$alt=(string)($cfg['imageAlt']??$alt);
        }
        $image??=url('/images/placeholders/media-pending.svg');
        $available=!str_contains($image,'/placeholders/');
        $reason=$video?'Published county hero video.':($available?'County photograph.':'No county media published.');
        return ['heroVideo'=>$video,'aiImage'=>$image,'image'=>$image,'imageSrcset'=>$srcset,'imageAlt'=>$alt,'imageCredit'=>$credit,'mediaState'=>$video?'distinct':($available?'county-photograph':'media-pending'),'mediaReason'=>$reason,
            'tileMedia'=>['state'=>'published','kind'=>$video?'video':'image','url'=>$video?:$image,'poster'=>$image,'posterSrcset'=>$srcset,'alt'=>$alt,'credit'=>$credit,'fallbackImage'=>$image,'source'=>$video?'county-hero-video':($available?'county-photograph':'media-pending'),'mediaPending'=>!$video&&!$available],
            'hero'=>['video'=>$video,'poster'=>$image,'state'=>$video?'distinct':($available?'county-photograph':'media-pending'),'reason'=>$reason]];
    }
}
