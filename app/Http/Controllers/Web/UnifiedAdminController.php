<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\{County, CountyInstitution};
use App\Services\AdminHierarchyScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Route, DB};

/** Navigation over existing controllers and the original TiDB hierarchy, not another admin backend. */
class UnifiedAdminController extends Controller
{
    private function actor(Request $r): \App\Models\User
    {
        $u=$r->user();abort_unless($u && app(AdminHierarchyScope::class)->level($u),403,'An assigned administration role is required.');return $u;
    }
    public function index(Request $r)
    {
        $u=$this->actor($r);$scope=app(AdminHierarchyScope::class);$level=$scope->level($u);
        $counties=$scope->counties($u)->withCount('sectors')->orderBy('name')->get(['counties.*']);
        $institutions=$scope->institutions($u)->with('county')->orderBy('name')->get();
        $catalog=json_decode(file_get_contents(base_path('verification/admin-tab-map.json')),true);
        $modules=[];
        foreach($catalog as $key=>$module){
            if(!Route::has($module['route']))continue;
            if(in_array($key,['kicc','national'],true) && ($key==='kicc'?$level!=='kicc':!$scope->global($u)))continue;
            if($key==='county' && $level==='institution')continue;
            $modules[$key]=$module;
        }
        $tools=[];
        if($level==='kicc'){
            foreach(['admin.index'=>'Legacy records publishing','admin.hierarchy'=>'Hierarchy editing','admin.media.index'=>'Entity video / R2 control','experience.images.index'=>'Image add / replace / delete','admin.audit'=>'Audit trail','admin.enquiries'=>'Enquiries','cms.admin.index'=>'CMS pages / team / timeline / FAQ','admin.3d.assets'=>'3D asset library','admin.ecommerce.dashboard'=>'Commerce control','agent.admin.index'=>'Agents','commission.admin.index'=>'Commissions','trade.admin.enquiries'=>'Trade enquiries'] as $name=>$label){if(Route::has($name))$tools[]=['label'=>$label,'url'=>route($name)];}
            $tools[]=['label'=>'Granular source-component editor','url'=>route('admin.components.ui')];
        }
        $routes=[];
        foreach(Route::getRoutes() as $route){
            $uri=$route->uri();
            if(!preg_match('#^(kicc-admin|county-admin|institution-admin|national-admin|records-admin|dashboard/admin)(/|$)#',$uri))continue;
            $group=explode('/',$uri)[0];
            if($level!=='kicc' && $group==='records-admin')continue;
            if($level!=='kicc' && $group==='kicc-admin' && !($level==='national' && str_starts_with($uri,'kicc-admin/national')))continue;
            if($level==='county' && $group==='national-admin')continue;
            if($level==='institution' && $group!=='institution-admin')continue;
            $routes[]=['name'=>$route->getName(),'uri'=>$uri,'methods'=>$route->methods(),'action'=>$route->getActionName()];
        }
        $registry=storage_path('app/legacy-admin-functions.json');$legacy=is_file($registry)?json_decode(file_get_contents($registry),true):[];
        $legacyCount=is_array($legacy)?count($legacy):0;
        return view('experience.admin.hub',compact('level','counties','institutions','modules','tools','routes','legacyCount'));
    }
    public function hierarchy(Request $r)
    {
        $u=$this->actor($r);$scope=app(AdminHierarchyScope::class);
        $county=$scope->counties($u)->where('counties.id',$r->integer('county_id'))->firstOrFail();
        $sectors=$county->sectors()->orderBy('sectors.name')->get(['sectors.id','sectors.name','sectors.slug']);
        $sectorId=$r->integer('sector_id');
        if($sectorId)abort_unless($sectors->contains('id',$sectorId),404,'Sector is not linked to this county.');
        $q=$scope->institutions($u)->where('county_id',$county->id);
        if($sectorId)$q->whereHas('sectorEntities',fn($q)=>$q->where('sector_id',$sectorId)->where('county_id',$county->id));
        $institutions=$q->orderBy('name')->get(['id','county_id','name','slug']);
        return response()->json(['county'=>['id'=>$county->id,'name'=>$county->name,'slug'=>$county->slug],'sectors'=>$sectors,
            'institutions'=>$institutions->map(fn($i)=>['id'=>$i->id,'name'=>$i->name,'slug'=>$i->slug,'admin_url'=>route('institution.admin',$i->slug)]),
            'source'=>'county_sector + sector_entities + county_institutions'])->header('Cache-Control','private,no-store');
    }
    public function components(Request $r)
    {
        $u=$this->actor($r);abort_unless($u->hasRole('kicc_admin'),403);
        return view('experience.admin.components');
    }
}
