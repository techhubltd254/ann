<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{County, CountyInstitution, Exhibition, MediaAsset, Screen, LiveStream, Venue};
use App\Models\Marketplace\Product;
use App\Services\ProductMediaResolver;
use App\Support\MediaMapping;
use Illuminate\Http\Request;

/** The approved HTML is the renderer, not a screenshot or another approximation. */
class ReferenceExperienceController extends Controller
{
    public function tables(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('reference.native.v1', 60, fn () => $this->buildTables());
    }

    private function buildTables(): array
    {
        $tables = array_fill_keys(['counties','institutions','products','venues','exhibitions','screens','streams','content','media','analytics'], []);
        $counties = County::with('sectors')->orderBy('code')->get();
        $countyNames = $counties->pluck('name', 'id');
        foreach ($counties as $c) {
            $hero = MediaMapping::countyHero($c);
            $poster = $hero['poster'] ?? MediaMapping::countyFallbackImage($c);
            $tables['counties'][] = ['id'=>(string)$c->id,'slug'=>$c->slug,'name'=>$c->name,'code'=>str_pad((string)$c->code,3,'0',STR_PAD_LEFT),'region'=>$c->region ?? $c->former_province,'pop'=>(int)$c->population_2024,'populationYear'=>2024,'sectors'=>$c->sectors->pluck('name')->all(),'description'=>$c->description,'image'=>$poster,'mediaState'=>$hero['state'],'mediaReason'=>$hero['reason'],'status'=>'published'];
            if ($hero['video']) $tables['media'][] = ['id'=>'county:'.$c->id,'ownerId'=>(string)$c->id,'kind'=>'video','role'=>'county','target'=>'/counties/'.$c->slug,'name'=>$c->name.' — county film','url'=>$hero['video'],'poster'=>$poster,'status'=>'published'];
        }
        $institutions = CountyInstitution::where('is_published',true)->with('sectorEntities')->orderBy('name')->get();
        foreach ($institutions as $i) {
            $hero = MediaMapping::institutionHero($i);
            $tables['institutions'][] = ['id'=>(string)$i->id,'countyId'=>(string)$i->county_id,'county'=>$countyNames[$i->county_id] ?? '', 'slug'=>$i->slug,'name'=>$i->name,'description'=>$i->description,'website'=>$i->website,'type'=>$i->type,'email'=>$i->email,'verified'=>(bool)$i->is_verified_trader,'sectors'=>$i->sectorEntities->pluck('sector_id')->unique()->values()->all(),'status'=>'published'];
            if ($hero['video']) $tables['media'][] = ['id'=>'institution:'.$i->id,'ownerId'=>(string)$i->id,'kind'=>'video','role'=>'experience','target'=>'/institutions/'.$i->slug,'name'=>$i->name.' — institution film','url'=>$hero['video'],'poster'=>$hero['poster'] ?? null,'status'=>'published'];
        }
        $resolver = app(ProductMediaResolver::class);
        foreach (Product::with('county','images','variants')->where('status','active')->orderBy('name')->get() as $p) {
            $image = $resolver->resolve($p);
            $tables['products'][] = ['id'=>(string)$p->id,'slug'=>$p->slug,'institutionId'=>(string)$p->institution_id,'n'=>$p->name,'name'=>$p->name,'c'=>$p->county?->name ?? '', 'cat'=>$p->category?->name ?? 'Product','p'=>(float)$p->price,'unit'=>$p->unit ?? '', 'r'=>0,'rv'=>0,'moq'=>(int)$p->moq,'incoterm'=>$p->incoterm,'v'=>'image','description'=>$p->short_description ?? $p->description,'image'=>$image['url'],'mediaLabel'=>$image['label'],'nativeUrl'=>route('marketplace.show',$p->slug),'status'=>'published','tileMedia'=>$image['url']?['state'=>'published','kind'=>'image','url'=>$image['url'],'alt'=>$image['label'] ?? $p->name]:['state'=>'empty']];
        }
        foreach (Venue::where('is_active',true)->orderBy('name')->get() as $v) {
            $asset = MediaAsset::where('owner_type',Venue::class)->where('owner_id',$v->id)->where('status','ready')->latest('id')->first();
            $tile = ['state'=>'empty'];
            if ($asset && MediaMapping::inR2($asset->path)) $tile=['state'=>'published','kind'=>$asset->kind,'url'=>url('/media/video/'.$asset->path),'alt'=>$v->name];
            $tables['venues'][]=['id'=>(string)$v->id,'slug'=>$v->slug,'name'=>$v->name,'type'=>$v->venue_type,'cap'=>(int)$v->capacity,'area'=>'Area on enquiry','rate'=>'Rate on enquiry','desc'=>$v->description,'am'=>is_array($v->amenities)?$v->amenities:[],'availability'=>'Available','status'=>'published','tileMedia'=>$tile];
        }
        foreach (Exhibition::whereNotIn('status',['draft','cancelled'])->with('venue')->get() as $e) $tables['exhibitions'][]=['id'=>(string)$e->id,'slug'=>$e->slug,'n'=>$e->name,'d'=>(string)$e->start_date,'venue'=>$e->venue?->name ?? '', 'availability'=>$e->status,'status'=>'published','booths'=>0,'reg'=>0];
        foreach (Screen::where('active',true)->get() as $s) $tables['screens'][]=['id'=>(string)$s->id,'slug'=>(string)$s->id,'n'=>$s->label,'loc'=>$s->location,'dim'=>'Dimensions on enquiry','pitch'=>$s->terminal_type,'price'=>'Rate on enquiry','tier'=>'screen','status'=>'published'];
        foreach (LiveStream::whereIn('status',['live','scheduled','upcoming'])->get() as $s) $tables['streams'][]=['id'=>(string)$s->id,'slug'=>(string)$s->id,'n'=>$s->name,'venue'=>'','q'=>'Auto','viewers'=>(int)$s->viewer_count,'url'=>$s->hls_url ?? $s->playback_url,'availability'=>$s->status,'status'=>'published'];
        return $tables;
    }

    public function data()
    {
        return response()->json(['source'=>'live-native-models','tables'=>$this->tables()])->withHeaders(['Cache-Control'=>'no-store','CDN-Cache-Control'=>'no-store']);
    }

    public function html(Request $request): string
    {
        $html = file_get_contents(resource_path('experience/reference-production.html'));
        $boot = json_encode(['path'=>$request->getPathInfo(),'tables'=>$this->tables()], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
        // This inserts JSON only; the approved document is never compiled as Blade.
        return str_replace('/*__KICC_NATIVE_BOOT__*/', 'window.KICC_NATIVE='.$boot.';', $html);
    }
}
