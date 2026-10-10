<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{County, CountyInstitution, Exhibition, MediaAsset, Screen, LiveStream, Venue};
use App\Models\Marketplace\Product;
use App\Services\ProductMediaResolver;
use App\Services\TileMediaResolver;
use App\Support\MediaAssetIndex;
use App\Support\MediaMapping;
use Illuminate\Http\Request;

/** The approved HTML is the renderer, not a screenshot or another approximation. */
class ReferenceExperienceController extends Controller
{
    /**
     * The payload is expensive to assemble, so it is cached. The cache is
     * invalidated whenever an admin uploads or removes tile media, and this TTL
     * is only a backstop.
     */
    public const CACHE_KEY = 'reference.native.v1';
    public const CACHE_TTL = 300;

    public function tables(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->buildTables());
    }

    private function buildTables(): array
    {
        $tables = array_fill_keys(['counties','institutions','products','venues','exhibitions','screens','streams','content','media','analytics','travel_groups','airports','tileDefaults'], []);

        // ---- load the entities once, then prime one media index for all of them.
        // Every tile lookup below is answered from this index, which is what
        // removes ~1000 per-tile round trips to TiDB.
        $index = new MediaAssetIndex();

        $counties = County::with('sectors')->orderBy('code')->get();
        $index->prime(County::class, $counties->pluck('id')->all());

        $institutions = CountyInstitution::where('is_published', true)->with('sectorEntities')->orderBy('name')->get();
        $index->prime(CountyInstitution::class, $institutions->pluck('id')->all());
        $index->prime('institution', $institutions->pluck('id')->all());

        $products = Product::with(['county','category','images','variants','offers'=>fn($q)=>$q->current()])->where('status','active')->where(fn($q)=>$q->whereNull('institution_id')->orWhereIn('institution_id',CountyInstitution::where('is_published',true)->select('id')))->orderByDesc('updated_at')->orderByDesc('id')->get();
        $index->prime(Product::class, $products->pluck('id')->all());

        $venues = Venue::where('is_active', true)->orderBy('name')->get();
        $index->prime(Venue::class, $venues->pluck('id')->all());

        $index->prime(TileMediaResolver::OWNER_TYPE, [TileMediaResolver::OWNER_ID]);
        $index->prime(TileMediaResolver::SEED_TYPE, [TileMediaResolver::OWNER_ID]);

        $tiles = app(TileMediaResolver::class)->withIndex($index);
        $tables['tileDefaults'] = $tiles->defaults();

        $countyNames = $counties->pluck('name', 'id');
        foreach ($counties as $c) {
            $hero = MediaMapping::countyHero($c, $index);
            $poster = $hero['poster'] ?? MediaMapping::countyFallbackImage($c, $index);
            $tables['counties'][] = ['id'=>(string)$c->id,'slug'=>$c->slug,'name'=>$c->name,'code'=>str_pad((string)$c->code,3,'0',STR_PAD_LEFT),'region'=>$c->region ?? $c->former_province,'pop'=>(int)$c->population_2024,'populationYear'=>2024,'sectors'=>$c->sectors->pluck('name')->all(),'description'=>$c->description,'image'=>$poster,'mediaState'=>$hero['state'],'mediaReason'=>$hero['reason'],'status'=>'published','tileMedia'=>$tiles->tile(\App\Models\County::class,(int)$c->id,'hero')];
            if ($hero['video']) $tables['media'][] = ['id'=>'county:'.$c->id,'ownerId'=>(string)$c->id,'kind'=>'video','role'=>'county','target'=>'/counties/'.$c->slug,'name'=>$c->name.' — county film','url'=>$hero['video'],'poster'=>$poster,'status'=>'published'];
        }
        foreach ($institutions as $i) {
            $hero = MediaMapping::institutionHero($i, $index);
            $tables['institutions'][] = ['id'=>(string)$i->id,'countyId'=>(string)$i->county_id,'county'=>$countyNames[$i->county_id] ?? '', 'slug'=>$i->slug,'name'=>$i->name,'description'=>$i->description,'website'=>$i->website,'type'=>$i->type,'email'=>$i->email,'verified'=>(bool)$i->is_verified_trader,'sectors'=>$i->sectorEntities->pluck('sector_id')->unique()->values()->all(),'status'=>'published','tileMedia'=>$tiles->tile(\App\Models\CountyInstitution::class,(int)$i->id,'hero')];
            $tables['institutions'][array_key_last($tables['institutions'])]['models']=app(\App\Services\PublicModelResolver::class)->forOwner(CountyInstitution::class,(int)$i->id,$index);
            if ($hero['video']) $tables['media'][] = ['id'=>'institution:'.$i->id,'ownerId'=>(string)$i->id,'kind'=>'video','role'=>'experience','target'=>'/institutions/'.$i->slug,'name'=>$i->name.' — institution film','url'=>$hero['video'],'poster'=>$hero['poster'] ?? null,'status'=>'published'];
        }
        $nationalHero=MediaAsset::resolveSlot(\App\Models\County::class,0,'national_hero_video');
        if($nationalHero){$tables['media'][]=['id'=>'national:hero','ownerId'=>'national','kind'=>'video','role'=>'national','target'=>'/national-government','name'=>$nationalHero->alt_text?:'National Government — hero film','url'=>app(\App\Services\NationalMediaService::class)->stream($nationalHero),'poster'=>$nationalHero->posterUrl(),'status'=>'published'];}
        // Landing page hero video (kiccwalkin.mp4)
        $landingHero = MediaAsset::where('owner_type', 'landing_page')->where('slot', 'hero_video')->first();
        if ($landingHero) {
            $tables['media'][] = [
                'id' => 'landing:1',
                'ownerId' => '1',
                'kind' => 'video',
                'role' => 'hero',
                'target' => '/',
                'name' => 'KICC Landing Hero',
                'url' => url('/media/video/' . $landingHero->path),
                'poster' => null,
                'status' => 'published',
            ];
        }

        // Institutions are keyed by id for the product fallback, and the media
        // index is primed for every one of them so the fallback costs no query.
        $institutionMap = CountyInstitution::query()->get()->keyBy('id');
        $index->prime(CountyInstitution::class, $institutionMap->keys()->all());
        $index->prime('institution', $institutionMap->keys()->all());

        $resolver = app(ProductMediaResolver::class)->withIndex($index)->withInstitutions($institutionMap);
        foreach ($products as $p) {
            $image = $resolver->resolve($p);
            $tables['products'][] = ['updatedAt'=>$p->updated_at?->toIso8601String(),'id'=>(string)$p->id,'slug'=>$p->slug,'institutionId'=>(string)$p->institution_id,'n'=>$p->name,'name'=>$p->name,'c'=>$p->county?->name ?? '', 'cat'=>$p->category?->name ?? 'Product','p'=>(float)$p->price,'unit'=>$p->unit ?? '', 'r'=>0,'rv'=>0,'moq'=>(int)$p->moq,'incoterm'=>$p->incoterm,'v'=>'image','description'=>$p->short_description ?? $p->description,'image'=>$image['url'],'mediaLabel'=>$image['label'],'nativeUrl'=>route('marketplace.show',$p->slug),'status'=>'published','tileMedia'=>($t=$tiles->tile(\App\Models\Marketplace\Product::class,(int)$p->id,'product_image'))['state']==='published'?$t:($image['url']?['state'=>'published','kind'=>'image','url'=>$image['url'],'description'=>$image['label'] ?? $p->name,'alt'=>$image['label'] ?? $p->name,'source'=>'derived']:['state'=>'empty'])];
            $last=array_key_last($tables['products']);
            $tables['products'][$last]['models']=app(\App\Services\PublicModelResolver::class)->forOwner(Product::class,(int)$p->id,$index);
            $tables['products'][$last]['offeringKind']=$p->offering_kind;
            $tables['products'][$last]['priceMode']=$p->price_mode;
            $tables['products'][$last]['priceLabel']=$p->price_mode==='enquiry'?'Price on enquiry':($p->price_mode==='from'?'From ':'').'KES '.number_format($p->price??0);
            $tables['products'][$last]['sourceUrl']=$p->source_url;
            $tables['products'][$last]['bookingUrl']=$p->booking_url;
            $tables['products'][$last]['offeringDetails']=$p->offering_details??[];
            $tables['products'][$last]['offers']=$p->offers->map(fn($o)=>$o->only(['title','terms','price','starts_at','ends_at']))->all();
            $video=$index->forSlot(\App\Models\Marketplace\Product::class,(int)$p->id,'product_video');
            if($video && $video->kind==='video'){
                $prepared=$video->derivatives->firstWhere('variant','stream-safe');
                $videoUrl=url('/media/original/'.($prepared?->path?:$video->path));
                $tables['media'][]=['id'=>'product:'.$p->id,'ownerId'=>(string)$p->id,'kind'=>'video','role'=>'product','target'=>'/marketplace/'.$p->slug,'name'=>$p->name.' — product film','url'=>$videoUrl,'poster'=>$video->posterUrl()?:$image['url'],'status'=>'published'];
                $last=array_key_last($tables['products']);$tables['products'][$last]['v']='video';$tables['products'][$last]['mediaLabel']='Institution-uploaded product film';
                $tables['products'][$last]['tileMedia']=['state'=>'published','kind'=>'video','url'=>$videoUrl,'poster'=>$video->posterUrl()?:$image['url'],'alt'=>$p->name,'source'=>'admin-upload'];
            }
        }
        foreach ($venues as $v) {
            $asset = $index->forOwner(Venue::class, (int) $v->id)->last();
            $tile = ['state'=>'empty'];
            if ($asset && MediaMapping::inR2($asset->path)) $tile=['state'=>'published','kind'=>$asset->kind,'url'=>url('/media/video/'.$asset->path),'alt'=>$v->name];
            $tables['venues'][]=['id'=>(string)$v->id,'slug'=>$v->slug,'name'=>$v->name,'type'=>$v->venue_type,'cap'=>(int)$v->capacity,'area'=>'Area on enquiry','rate'=>'Rate on enquiry','desc'=>$v->description,'am'=>is_array($v->amenities)?$v->amenities:[],'availability'=>'Available','status'=>'published','tileMedia'=>($vt=$tiles->tile(\App\Models\Venue::class,(int)$v->id,'hero'))['state']==='published'?$vt:$tile];
        }
        foreach (Exhibition::whereNotIn('status',['draft','cancelled'])->with('venue')->get() as $e) $tables['exhibitions'][]=['id'=>(string)$e->id,'slug'=>$e->slug,'n'=>$e->name,'d'=>(string)$e->start_date,'venue'=>$e->venue?->name ?? '', 'availability'=>$e->status,'status'=>'published','booths'=>0,'reg'=>0];
        foreach (Screen::where('active',true)->get() as $s) $tables['screens'][]=['id'=>(string)$s->id,'slug'=>(string)$s->id,'n'=>$s->label,'loc'=>$s->location,'dim'=>'Dimensions on enquiry','pitch'=>$s->terminal_type,'price'=>'Rate on enquiry','tier'=>'screen','status'=>'published'];
        foreach (LiveStream::whereIn('status',['live','scheduled','upcoming'])->get() as $s) $tables['streams'][]=['id'=>(string)$s->id,'slug'=>(string)$s->id,'n'=>$s->name,'venue'=>'','q'=>'Auto','viewers'=>(int)$s->viewer_count,'url'=>$s->hls_url ?? $s->playback_url,'availability'=>$s->status,'status'=>'published'];
        $airports = \App\Models\Travel\Airport::where('is_active', true)->orderBy('name')->get();
        $tables['airports'] = $airports->map(fn($a)=>['code'=>$a->iata_code,'name'=>$a->name])->all();
        $flights = \App\Models\Travel\FlightInventory::query()->join('flights','flight_inventory.flight_id','=','flights.id')->join('airports','flights.destination_airport_id','=','airports.id')->where('flight_inventory.is_active',true)->where('flight_inventory.date','>=',now()->toDateString())->where('flight_inventory.available_seats','>',0)->orderBy('flight_inventory.price')->limit(4)->get(['flights.flight_number','airports.name','flight_inventory.price']);
        $hotels = \App\Models\Travel\Hotel::where('is_active',true)->orderBy('name')->limit(4)->get();
        $hotelRows = $hotels->map(function($h){$price=\App\Models\Travel\HotelRoom::where('hotel_id',$h->id)->where('is_active',true)->min('price_per_night');return [$h->name,$price?'KES '.number_format($price).' / night':'Rates on enquiry'];})->all();
        $transfers = \App\Models\Travel\AirportTransfer::where('is_active',true)->orderBy('price')->limit(4)->get();
        $tables['travel_groups'] = [
          ['Flights','✈','Upcoming flight inventory from the native booking service.',$flights->map(fn($f)=>[$f->flight_number.' · '.$f->name,'KES '.number_format($f->price)])->all()],
          ['Hotels','⌂','Published accommodation and active room rates.',$hotelRows],
          ['Transfers','⇄','Active airport transfer providers and quoted rates.',$transfers->map(fn($t)=>[$t->provider_name.' · '.$t->vehicle_type,'KES '.number_format($t->price)])->all()],
          ['Rentals','◎','Rental offers must be published by their responsible provider.',[]]
        ];
        $secure = function ($value) use (&$secure) {
            if (is_array($value)) return array_map($secure, $value);
            if (is_string($value)) return preg_replace('~^http://kicctest\\.org(?=/|$)~', 'https://kicctest.org', $value);
            return $value;
        };
        return \App\Support\PrivateRevenue::redact($secure($tables));
    }

    public function data()
    {
        return response()->json(['source'=>'live-native-models','revision'=>(int)\Illuminate\Support\Facades\Cache::get('kicc_cache_version',0),'tables'=>$this->tables()])->withHeaders(['Cache-Control'=>'no-store','CDN-Cache-Control'=>'no-store']);
    }

    public function html(Request $request): string
    {
        $html = file_get_contents(resource_path('experience/reference-production.html'));
        // A changed palette must never reuse an immutable browser/edge cache key.
        $paletteHash = substr(hash_file('sha256', public_path('css/reference-palette.css')), 0, 12);
        $html = str_replace('/css/reference-palette.css?v=reference-replica-v2', '/css/reference-palette.css?v='.$paletteHash, $html);
        $tables = $this->tables();
        $hero = \App\Models\MediaAsset::where('owner_type','landing_page')->where('owner_id',1)->where('slot','hero_video')->where('kind','video')->where('status','ready')->with('derivatives')->latest('id')->first();
        $tile = app(\App\Services\TileMediaResolver::class)->tile(\App\Services\TileMediaResolver::OWNER_TYPE, \App\Services\TileMediaResolver::OWNER_ID, 'hero');
        $tables['heroMedia'] = ['ownerId'=>1,'video'=>$hero ? ($hero->mp4Url() ?: $hero->url()) : (($tile['kind'] ?? null)==='video' ? ($tile['url'] ?? null) : null), 'poster'=>$hero?->posterUrl() ?: ($tile['poster'] ?? $tables['tileDefaults']['ed_nairobi'] ?? null)];
        $payload = ['path'=>$request->getPathInfo(),'tables'=>$tables];
        $boot = json_encode($payload, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
        // This inserts JSON only; the approved document is never compiled as Blade.
        return str_replace('/*__KICC_NATIVE_BOOT__*/', 'window.KICC_NATIVE='.$boot.';', $html);
    }
}
