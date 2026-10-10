<?php
namespace App\Services;
use App\Models\County;
use App\Support\{MediaAssetIndex,MediaMapping};
/** One public heroVideo choice per county; generated preview stays visible until a hero is ready. */
class CountyMediaPresentation
{
    public function present(County $county, MediaAssetIndex $index, ?array $hero=null): array
    {
        $cfg=config('county_media.counties.'.$county->slug,[]);
        $aiImage=url('/images/placeholders/media-pending.svg');
        $hero??=MediaMapping::countyHero($county,$index);
        $configured=trim((string)($cfg['heroVideo']??''));
        $video=$configured!==''?$configured:($hero['video']??null);
        if($video&&str_starts_with($video,'/'))$video=url($video);
        if($video&&!preg_match('~^https?://~i',$video))$video=null;
        return ['heroVideo'=>$video,'aiImage'=>$aiImage,'image'=>$aiImage,'mediaState'=>$video?'distinct':'media-pending','mediaReason'=>$video?'Published county hero video; neutral preview remains the playback fallback.':'Upload a county hero video to display this county.','tileMedia'=>['state'=>'published','kind'=>$video?'video':'image','url'=>$video?:$aiImage,'poster'=>$aiImage,'fallbackImage'=>$aiImage,'source'=>$video?'county-hero-video':'media-pending','mediaPending'=>!$video],'hero'=>['video'=>$video,'poster'=>$aiImage,'state'=>$video?'distinct':'media-pending','reason'=>$video?'Uploaded county hero':'County media awaiting upload']];
    }
}
