<?php
namespace App\Services;
use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
/** Public, evidence-based many-to-many mapping. No draft promotion or fabricated offerings. */
class InstitutionSectorCatalogue
{
    private array $taxonomy;
    public function __construct(){ $this->taxonomy=\App\Models\Sector::all()->mapWithKeys(fn($s)=>[$s->slug=>['id'=>(int)$s->id,'slug'=>$s->slug,'name'=>$s->name]])->all(); }
    public function sectors(): array {return array_values($this->taxonomy);}
    public function offeringSectors(Product $p): array
    {
        $d=$p->offering_details??[];
        $explicit=$d['sector_slugs']??$d['sectors']??null;
        if(is_array($explicit))return array_values(array_intersect(array_keys($this->taxonomy),array_map(fn($s)=>is_array($s)?($s['slug']??''):(string)$s,$explicit)));
        $ids=[];$category=strtolower(($p->category?->slug??'').' '.($p->category?->name??''));
        $declared=$p->category?->sector;
        if(is_string($declared)&&isset($this->taxonomy[$declared]))$ids[]=$declared;
        $text=strtolower($p->name.' '.$p->description);
        $rules=[
            'agriculture'=>'/agricultur|fresh.produce|dairy|coffee|tea|avocado|blueberr|macadamia|livestock|forestry|seedling|orchard|farm.market/',
            'tourism'=>'/tourism|rafting|guided.tour|farm.tour|farm.experience|tea.experience|river.adventure|safari|kayak|zip.?lin|dhow.cruise/',
            'industry'=>'/manufactur|cold.pressed|value.added|ready.to.eat|processing|handicraft|textile|woodwork|furniture|carving/',
            'education'=>'/education|research|training|workshop|course|tuition|scholarship/',
            'health'=>'/medical|healthcare|wheelchair|prosthetic|orthotic|rehabilitation|mobility.aid/',
            'hospitality'=>'/hospitality|hotel|accommodation|room.stay|overnight|restaurant|meal|dining|catering/',
            'culture'=>'/crafts.artisan|handicraft|carving|heritage|cultural/',
            'technology'=>'/technology|software|digital.service|ict|data.processing/',
            'infrastructure'=>'/construction|building.material|infrastructure/',
            'environment'=>'/conservation|environment|renewable.energy|solar.power/',
            'finance'=>'/financial.service|insurance|credit.service|investment.service/',
            'sports'=>'/sports|recreation|rafting|kayak|zip.?lin/'
        ];
        foreach($rules as $slug=>$pattern)if(preg_match($pattern,$category.' '.$text))$ids[]=$slug;
        // Commerce is also relevant to a saleable product, but never forces it into an unrelated niche.
        if(($p->offering_kind??'product')==='product')$ids[]='commerce';
        return array_values(array_unique(array_filter($ids,fn($s)=>isset($this->taxonomy[$s]))));
    }
    public function profiles(CountyInstitution $i, iterable $products): array
    {
        $profiles=[];$blocked=[];
        foreach($i->sector_mappings??[] as $mapping){$slug=$mapping['sector_slug']??'';if(!isset($this->taxonomy[$slug]))continue;
            if(in_array($mapping['publication_status']??'active',['draft','archived','inactive'],true)){$blocked[$slug]=true;continue;}
            $profiles[$slug]=$this->taxonomy[$slug]+['title'=>$mapping['entry_name']??$i->name,'description'=>$mapping['description']??'','sourceUrl'=>$mapping['source_url']??$i->website,'mappingSource'=>'approved-institution-mapping','productIds'=>[]];
        }
        foreach($i->sectorEntities as $entry){if(!$entry->is_published||in_array('automatic-sector-map',$entry->tags??[],true))continue;$sector=collect($this->taxonomy)->firstWhere('id',(int)$entry->sector_id);if(!$sector||isset($blocked[$sector['slug']]))continue;$slug=$sector['slug'];$profiles[$slug]??=$sector+['title'=>$i->name,'description'=>$entry->description??'','sourceUrl'=>$i->website,'mappingSource'=>'published-sector-link','productIds'=>[]];}
        foreach($products as $p){if($p->status!=='active'||(int)$p->institution_id!==(int)$i->id)continue;foreach($this->offeringSectors($p) as $slug){/* A newly published offering is separate evidence; never copy the old draft mapping. */$profiles[$slug]??=$this->taxonomy[$slug]+['title'=>$i->name.' — '.$this->taxonomy[$slug]['name'],'description'=>'','sourceUrl'=>$p->source_url?:$i->website,'mappingSource'=>'automatic-offering-classification','productIds'=>[]];$profiles[$slug]['productIds'][]=(string)$p->id;}}
        foreach($profiles as $slug=>&$profile){$profile['productIds']=array_values(array_unique($profile['productIds']));if(!$profile['description'])$profile['description']=collect($products)->whereIn('id',$profile['productIds'])->pluck('description')->filter()->take(3)->implode(' ');$profile['history']=$i->founded_year?'Established in '.$i->founded_year.'.':'';$stepPatterns=['agriculture'=>'/seedling|cultivat|orchard|grow|harvest|plant|livestock/i','industry'=>'/process|manufactur|pack|quality|cool|press/i','tourism'=>'/tour|visit|walk|tast|experience/i'];$profile['operations']=isset($stepPatterns[$slug])?collect($i->production_chain??[])->filter(fn($step)=>preg_match($stepPatterns[$slug],$step['step']??''))->values()->all():[];}
        unset($profile);return array_values($profiles);
    }
    public function persistLinks(CountyInstitution $i): int
    {
        $i->load('sectorEntities');$products=Product::with('category')->where('institution_id',$i->id)->where('status','active')->get();$profiles=$this->profiles($i,$products);$kept=[];$count=0;
        foreach($profiles as $profile){if($profile['mappingSource']!=='automatic-offering-classification')continue;$kept[]=$profile['id'];
            $existing=$i->sectorEntities->firstWhere('sector_id',$profile['id']);if($existing&&$existing->is_published&&!in_array('automatic-sector-map',$existing->tags??[],true))continue;
            $i->county?->sectors()->syncWithoutDetaching([$profile['id']=>['display_on_tile'=>'yes']]);
            $fields=['name'=>$profile['title'],'description'=>\Illuminate\Support\Str::limit($profile['description'],240),'sector_type'=>$profile['slug'],'capture_status'=>'none','is_published'=>(bool)$i->is_published,'tags'=>[$profile['slug'],'automatic-sector-map'],'contact_info'=>['website'=>$profile['sourceUrl']]];
            if($existing)$existing->update($fields);else \App\Models\SectorEntity::create($fields+['county_id'=>$i->county_id,'countyId'=>$i->county_id,'sector_id'=>$profile['id'],'sectorId'=>$profile['id'],'entity_id'=>$i->id,'entityId'=>$i->id,'entity_type'=>CountyInstitution::class,'entityType'=>CountyInstitution::class,'captureStatus'=>'none']);$count++;
        }
        foreach($i->sectorEntities as $row)if(in_array('automatic-sector-map',$row->tags??[],true)&&!in_array((int)$row->sector_id,$kept,true))$row->delete();
        return $count;
    }
}
