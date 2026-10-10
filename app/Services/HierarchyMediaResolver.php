<?php
namespace App\Services;
use App\Models\{County,CountyInstitution,MediaAsset,SectorEntity};
use App\Models\Marketplace\Product;
use App\Support\{MediaAssetIndex,MediaMapping};
class HierarchyMediaResolver
{
    public function published(MediaAsset $a): bool {return $a->status==='ready'&&$a->kind==='video'&&!str_starts_with($a->slot??'','draft__')&&!str_starts_with($a->slot??'','archived__')&&($a->metadata['publication']??'')!=='draft';}
    public function video(MediaAsset $a,string $source): array {return ['state'=>'published','kind'=>'video','url'=>$a->mp4Url()?:$a->url(),'mobileUrl'=>$a->derivativeUrl('video_mobile'),'adaptiveUrl'=>$a->derivativeUrl('hls_master'),'poster'=>$a->posterUrl(),'source'=>$source,'assetId'=>(string)$a->id,'ownerId'=>(string)$a->owner_id,'priority'=>'admin-upload','updatedAt'=>$a->updated_at?->toIso8601String()];}
    public function institution(CountyInstitution $i,array $profile,iterable $products,MediaAssetIndex $index,string $fallback): array
    {
        $sector=(int)$profile['id'];$slot='sector_video_'.$profile['slug'];
        $rows=$index->forOwner(CountyInstitution::class,(int)$i->id)->filter(fn($a)=>$this->published($a)&&((int)($a->metadata['sector_id']??0)===$sector||$a->slot===$slot));
        $entry=$i->sectorEntities->first(fn($e)=>(int)$e->sector_id===$sector&&$e->is_published);
        if($entry)$rows=$rows->merge($index->forOwner(SectorEntity::class,(int)$entry->id)->filter(fn($a)=>$this->published($a)));
        if($a=$rows->sortByDesc('id')->first())return $this->video($a,'institution-sector-admin-upload')+['fallbackImage'=>$fallback];
        $generic=$index->forOwner(CountyInstitution::class,(int)$i->id)->filter(fn($a)=>$this->published($a)&&in_array($a->slot,['hero_video','institution_video'],true)&&empty($a->metadata['sector_id']));
        if($a=$generic->sortByDesc('id')->first())return $this->video($a,'institution-admin-introduction')+['fallbackImage'=>$fallback];
        foreach($products as $p){if(!in_array((string)$p->id,$profile['productIds'],true))continue;$a=$index->forSlot(Product::class,(int)$p->id,'product_video');if($a&&$this->published($a))return $this->video($a,'linked-product-admin-upload')+['fallbackImage'=>$fallback,'productId'=>(string)$p->id];}
        $neutral=url('/images/placeholders/media-pending.svg');return ['state'=>'published','kind'=>'image','url'=>$neutral,'poster'=>$neutral,'fallbackImage'=>$neutral,'source'=>'media-pending','priority'=>'fallback'];
    }
    public function sector(County $county,array $sector,array $institutions,MediaAssetIndex $index,string $fallback): array
    {
        $a=$index->forSlot(County::class,(int)$county->id,'sector_video_'.$sector['slug']);if($a&&$this->published($a))return $this->video($a,'county-sector-admin-upload')+['fallbackImage'=>$fallback];
        foreach($institutions as $i){if((string)$i['countyId']!==(string)$county->id)continue;foreach($i['sectorProfiles']??[] as $p)if($p['slug']===$sector['slug']&&($p['tileMedia']['kind']??'')==='video')return $p['tileMedia']+['institutionId'=>$i['id'],'institutionName'=>$i['name']];}
        $neutral=url('/images/placeholders/media-pending.svg');return ['state'=>'published','kind'=>'image','url'=>$neutral,'poster'=>$neutral,'fallbackImage'=>$neutral,'source'=>'media-pending','priority'=>'fallback'];
    }
}
