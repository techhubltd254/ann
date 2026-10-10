<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;use App\Models\{MediaAsset,CountyInstitution};use App\Models\Marketplace\Product;
class PublicModelController extends Controller {
 public function show(MediaAsset $asset){abort_unless($asset->kind==='model'&&$asset->status==='ready',404);$owner=match($asset->owner_type){CountyInstitution::class=>CountyInstitution::where('is_published',true)->find($asset->owner_id),Product::class=>Product::where('status','active')->whereIn('institution_id',CountyInstitution::where('is_published',true)->select('id'))->find($asset->owner_id),default=>null};abort_unless($owner,404);return response()->view('experience.models.show',['asset'=>$asset,'modelUrl'=>url('/media/original/'.$asset->path),'format'=>strtolower(pathinfo($asset->path,PATHINFO_EXTENSION))])->header('Cache-Control','no-store');}

 public function preview(\Illuminate\Http\Request $r,MediaAsset $asset){abort_unless($asset->kind==='model'&&$asset->status==='ready',404);$institution=match($asset->owner_type){CountyInstitution::class=>CountyInstitution::find($asset->owner_id),Product::class=>CountyInstitution::find(Product::find($asset->owner_id)?->institution_id),default=>null};abort_unless($institution&&app(\App\Services\AdminHierarchyScope::class)->canInstitution($r->user(),$institution),403);return response()->view('experience.models.show',['asset'=>$asset,'modelUrl'=>url('/media/original/'.$asset->path),'format'=>strtolower(pathinfo($asset->path,PATHINFO_EXTENSION))])->header('Cache-Control','private,no-store');}
}
