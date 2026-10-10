<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Models\CountyInstitution;
use App\Models\Marketplace\Product;
use App\Models\MediaAsset;
use App\Services\AdminHierarchyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage,Cache};
class InstitutionProductAdminController extends Controller
{
    public function institution(Request $r,string $slug):CountyInstitution
    {
        $i=CountyInstitution::where('slug',$slug)->firstOrFail();
        abort_unless($r->user() && app(AdminHierarchyScope::class)->canInstitution($r->user(),$i),403,'This institution is outside your administration scope.');
        return $i;
    }
    public function product(CountyInstitution $i,int $id):Product
    {
        // Never authorize by array position, shared seller account or display name.
        return Product::where('institution_id',(string)$i->id)->where('county_id',$i->county_id)->findOrFail($id);
    }
    public function index(Request $r,string $institution)
    {
        $institution=$this->institution($r,$institution);
        $query=Product::where('institution_id',(string)$institution->id)->where('county_id',$institution->county_id)->with('offers');
        if($r->filled('kind'))$query->where('offering_kind',$r->validate(['kind'=>'in:product,service,experience'])['kind']);
        if($r->filled('q')){$search=$r->validate(['q'=>'string|max:150'])['q'];$query->where('name','like','%'.$search.'%');}
        $products=$query->orderBy('name')->paginate(30)->withQueryString();
        return response()->view('experience.admin.institution-products',compact('institution','products'))->header('Cache-Control','private,no-store');
    }
    public function edit(Request $r,string $institution,int $product)
    {
        $institution=$this->institution($r,$institution);$product=$this->product($institution,$product);
        $sectors=$institution->sectorEntities()->with('sector')->get()->pluck('sector')->filter()->unique('id');
        $media=MediaAsset::where('owner_type',Product::class)->where('owner_id',$product->id)->where('kind','video')->latest('id')->get();
        return response()->view('experience.admin.product-edit',compact('institution','product','sectors','media'))->header('Cache-Control','private,no-store');
    }
    private function fields(Request $r):array {
        $d=$r->validate(['name'=>'required|string|max:255','category'=>'nullable|string|max:255','description'=>'nullable|string|max:10000','expected_video_description'=>'nullable|string|max:2000','unit'=>'nullable|string|max:50','price'=>'nullable|numeric|min:0','stock'=>'required|integer|min:0','offering_kind'=>'required|in:product,service,experience','price_mode'=>'required|in:fixed,from,enquiry','publication_status'=>'required|in:draft,active','booking_url'=>['nullable','url','regex:~^https?://~i'],'source_url'=>['nullable','url','regex:~^https?://~i'],'duration_minutes'=>'nullable|integer|min:1|max:100000','max_guests'=>'nullable|integer|min:1|max:100000','inclusions'=>'nullable|string|max:6000']);
        if($d['price_mode']!=='enquiry' && !isset($d['price']))throw \Illuminate\Validation\ValidationException::withMessages(['price'=>'Enter a published price or use Price on enquiry.']);
        $d['offering_details']=['expected_video_description'=>$d['expected_video_description']??null,'duration_minutes'=>$d['duration_minutes']??null,'max_guests'=>$d['max_guests']??null,'inclusions'=>array_values(array_filter(array_map('trim',explode("\n",$d['inclusions']??''))))];
        $d['price']=$d['price_mode']==='enquiry'?0:$d['price'];$d['category']=$d['category']??($d['offering_kind']==='experience'?'Tourism':($d['offering_kind']==='service'?'Services':'General'));
        unset($d['duration_minutes'],$d['max_guests'],$d['inclusions'],$d['expected_video_description']);return $d;
    }
    public function create(Request $r,string $institution){
        $institution=$this->institution($r,$institution);$product=new Product(['offering_kind'=>'product','price_mode'=>'enquiry','status'=>'draft']);
        return response()->view('experience.admin.offering-create',compact('institution','product'))->header('Cache-Control','private,no-store');
    }
    public function store(Request $r,string $institution){
        $i=$this->institution($r,$institution);$d=$this->fields($r);$d['source_key']='manual-'.\Illuminate\Support\Str::uuid();
        DB::transaction(function()use($i,$d){$locked=CountyInstitution::lockForUpdate()->findOrFail($i->id);$entries=$locked->products??[];$entries[]=$d;$locked->syncing=true;$locked->update(['products'=>$entries]);});
        $i->refresh();$summary=app(\App\Services\InstitutionSyncService::class)->sync($i);
        $p=Product::where('institution_id',(string)$i->id)->where('sync_key',$d['source_key'])->first();
        if(!$p)return back()->withErrors(['sync'=>'Saved the offering, but publication failed. Check the sync errors before retrying.'])->withInput();
        Cache::forget('reference.native.v1');Cache::increment('kicc_cache_version');return redirect()->route('institution.products.edit',[$i->slug,$p->id])->with('success','Offering created and synced. Upload its video below.');
    }
    public function update(Request $r,string $institution,int $product){
        $i=$this->institution($r,$institution);$p=$this->product($i,$product);$d=$this->fields($r);$d['offering_details']=array_merge($p->offering_details??[],$d['offering_details']);
        DB::transaction(function()use($i,$p,$d){$oldName=$p->name;$data=$d;unset($data['price'],$data['stock'],$data['category'],$data['publication_status']);$data['status']=$d['publication_status'];$p->update($data);
            $v=$p->variants()->first();if($v)$v->update(['price'=>$d['price'],'stock'=>$d['stock']]);
            $locked=CountyInstitution::lockForUpdate()->findOrFail($i->id);$entries=$locked->products??[];$found=false;
            foreach($entries as &$e)if((int)($e['marketplace_product_id']??0)===$p->id||($e['name']??'')===$oldName){$e=array_merge($e,$d,['marketplace_product_id'=>$p->id]);$found=true;}unset($e);
            if(!$found)$entries[]=array_merge($d,['marketplace_product_id'=>$p->id,'source_key'=>$p->sync_key?:('manual-'.$p->id)]);
            $locked->syncing=true;$locked->update(['products'=>$entries]);
        });
        $i->refresh();$summary=app(\App\Services\InstitutionSyncService::class)->sync($i);Cache::forget('reference.native.v1');Cache::increment('kicc_cache_version');
        return back()->with('success','Offering saved and synced; existing videos were retained.');
    }
    public function storeOffer(Request $r,string $institution,int $product){
        $i=$this->institution($r,$institution);$p=$this->product($i,$product);
        $d=$r->validate(['title'=>'required|string|max:255','terms'=>'required|string|max:10000','price'=>'nullable|numeric|min:0','starts_at'=>'nullable|date','ends_at'=>'nullable|date|after:starts_at','is_published'=>'nullable|boolean','source_url'=>['nullable','url','regex:~^https?://~i']]);
        $d['is_published']=$r->boolean('is_published');$d['institution_id']=$i->id;$d['product_id']=$p->id;
        \App\Models\InstitutionOffer::create($d);Cache::forget('reference.native.v1');Cache::increment('kicc_cache_version');return back()->with('success','Offer saved. Only published offers within their date window appear publicly.');
    }
    public function deleteOffer(Request $r,string $institution,int $product,int $offer){
        $i=$this->institution($r,$institution);$p=$this->product($i,$product);
        \App\Models\InstitutionOffer::where('institution_id',$i->id)->where('product_id',$p->id)->findOrFail($offer)->delete();
        Cache::forget('reference.native.v1');Cache::increment('kicc_cache_version');return back()->with('success','Offer removed.');
    }
    public function destroyVideo(Request $r,string $institution,int $product,int $asset)
    {
        $i=$this->institution($r,$institution);$p=$this->product($i,$product);
        $a=MediaAsset::where('owner_type',Product::class)->where('owner_id',$p->id)->where('kind','video')->findOrFail($asset);
        $oldPath=$a->path;$url=url('/media/original/'.$oldPath);
        DB::transaction(function()use($a,$p,$i,$url,$oldPath){$urls=array_values(array_filter($p->videos??[],fn($v)=>!str_contains($v,$oldPath)));$p->update(['videos'=>$urls,'video_url'=>str_contains($p->video_url??'',$oldPath)?($urls[0]??null):$p->video_url]);$entries=$i->products??[];foreach($entries as &$e)if((int)($e['marketplace_product_id']??0)===$p->id||($e['name']??'')===$p->name){$e['videos']=$urls;$e['video_url']=$p->video_url;$e['marketplace_product_id']=$p->id;}unset($e);$i->update(['products'=>$entries]);$a->derivatives()->delete();$a->delete();});
        if(!MediaAsset::where('disk','r2')->where('path',$oldPath)->exists()&&!\App\Models\MediaDerivative::where('path',$oldPath)->exists())Storage::disk('r2')->delete($oldPath);
        Cache::forget('reference.native.v1');Cache::increment('kicc_cache_version');return back()->with('success','This product video was removed.');
    }
}
