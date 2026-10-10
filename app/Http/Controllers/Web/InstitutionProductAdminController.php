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
        $products=Product::where('institution_id',(string)$institution->id)->where('county_id',$institution->county_id)->orderBy('name')->paginate(30);
        return response()->view('experience.admin.institution-products',compact('institution','products'))->header('Cache-Control','private,no-store');
    }
    public function edit(Request $r,string $institution,int $product)
    {
        $institution=$this->institution($r,$institution);$product=$this->product($institution,$product);
        $sectors=$institution->sectorEntities()->with('sector')->get()->pluck('sector')->filter()->unique('id');
        $media=MediaAsset::where('owner_type',Product::class)->where('owner_id',$product->id)->where('kind','video')->latest('id')->get();
        return response()->view('experience.admin.product-edit',compact('institution','product','sectors','media'))->header('Cache-Control','private,no-store');
    }
    public function update(Request $r,string $institution,int $product)
    {
        $i=$this->institution($r,$institution);$p=$this->product($i,$product);
        $d=$r->validate(['name'=>'required|string|max:255','description'=>'nullable|string|max:10000','unit'=>'nullable|string|max:60','price'=>'required|numeric|min:0','stock'=>'required|integer|min:0']);
        DB::transaction(function()use($i,$p,$d){$oldName=$p->name;$p->update(['name'=>$d['name'],'description'=>$d['description']??'','unit'=>$d['unit']??'unit']);$v=$p->variants()->first();if($v)$v->update(['price'=>$d['price'],'stock'=>$d['stock']]);$json=$i->products??[];foreach($json as &$entry)if((int)($entry['marketplace_product_id']??0)===$p->id||($entry['name']??'')===$oldName)$entry=array_merge($entry,$d,['marketplace_product_id'=>$p->id]);unset($entry);$i->update(['products'=>$json]);});
        Cache::increment('kicc_cache_version');return back()->with('success','Product saved. Upload its video separately using the verified large-file controls below.');
    }
    public function destroyVideo(Request $r,string $institution,int $product,int $asset)
    {
        $i=$this->institution($r,$institution);$p=$this->product($i,$product);
        $a=MediaAsset::where('owner_type',Product::class)->where('owner_id',$p->id)->where('kind','video')->findOrFail($asset);
        $oldPath=$a->path;$url=url('/media/original/'.$oldPath);
        DB::transaction(function()use($a,$p,$i,$url,$oldPath){$urls=array_values(array_filter($p->videos??[],fn($v)=>!str_contains($v,$oldPath)));$p->update(['videos'=>$urls,'video_url'=>str_contains($p->video_url??'',$oldPath)?($urls[0]??null):$p->video_url]);$entries=$i->products??[];foreach($entries as &$e)if((int)($e['marketplace_product_id']??0)===$p->id||($e['name']??'')===$p->name){$e['videos']=$urls;$e['video_url']=$p->video_url;$e['marketplace_product_id']=$p->id;}unset($e);$i->update(['products'=>$entries]);$a->derivatives()->delete();$a->delete();});
        if(!MediaAsset::where('disk','r2')->where('path',$oldPath)->exists()&&!\App\Models\MediaDerivative::where('path',$oldPath)->exists())Storage::disk('r2')->delete($oldPath);
        Cache::increment('kicc_cache_version');return back()->with('success','This product video was removed.');
    }
}
