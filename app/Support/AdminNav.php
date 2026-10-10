<?php
namespace App\Support;
use App\Models\User;
use App\Services\AdminHierarchyScope;
use Illuminate\Support\Facades\Route;

/** Real native menus, categorised without changing handlers or permission checks. */
class AdminNav
{
 public static function groups(User $u,array $context=[]):array
 {
  $scope=app(AdminHierarchyScope::class);$level=$scope->level($u);
  $groups=[];foreach(['Dashboard','Content','Commerce','Operations','People','Analytics','Settings'] as $g)$groups[$g]=[];
  $add=function($g,$label,$name,$params=[])use(&$groups){if(Route::has($name))$groups[$g][]=['label'=>$label,'url'=>route($name,$params)];};
  $add('Dashboard','Control centre','admin.portal');
  $county=$context['county']??null;$institution=$context['institution']??null;
  $uri=\App\Support\AdminPaths::legacy(request()->path());$key=null;$route=null;$params=[];
  if(str_starts_with($uri,'kicc-admin') && !str_contains($uri,'national')){$key='kicc';$route='kicc.admin';}
  elseif(str_starts_with($uri,'county-admin/') && $county){$key='county';$route='county.admin.pro';$params=['slug'=>$county->slug];}
  elseif(str_starts_with($uri,'institution-admin/') && $institution){$key='institution';$route='institution.admin';$params=['institution'=>$institution->slug];}
  elseif(str_contains($uri,'national')){$key='national';$route=Route::has('national.admin.v2.dashboard')?'national.admin.v2.dashboard':'national.admin';}
  $catalog=json_decode(file_get_contents(base_path('verification/admin-tab-map.json')),true);
  $items=$context['navItems']??($key?($catalog[$key]['tabs']??[]):[]);
  foreach($items as $item){
   if(!is_array($item)||!isset($item['tab'])||!$route)continue;
   $t=$item['tab'];$g=$item['group']??null;
   if(!isset($groups[$g??'']))$g=match(true){
    in_array($t,['overview','portals','dashboard'])=>'Dashboard',
    (bool)preg_match('/hero|video|image|media|experience|venue|page|flag|3d|production|content/',$t)=>'Content',
    (bool)preg_match('/order|product|payment|pool|earning|ledger|package|price|provider|escrow/',$t)=>'Commerce',
    (bool)preg_match('/pipeline|integrat|request|sector|hierarch|broadcast|licence/',$t)=>'Operations',
    (bool)preg_match('/user|institution|exhibitor|county|counties|national|ministr|agenc|team/',$t)=>'People',
    (bool)preg_match('/analytic|report|audit|search/',$t)=>'Analytics',default=>'Settings'};
   $label=strtr($item['label']??ucwords(str_replace('_',' ',$t)),['Pool Engine'=>'Revenue Pool','Selling Pool'=>'Revenue Pool','Ledger'=>'Earnings Ledger','Pipeline Management'=>'Pipeline Licensing','Sub-Portals'=>'Admin Portals','Hero Media'=>'Hero Videos & Posters']);
   $add($g,$label,$route,$params+['tab'=>$t]);
  }
  if($level){
   $add('Content','Sequential media control','admin.mediaflow');$add('Content','Video upload · up to 2 GiB','admin.uploads');
   $add('Operations','County → Sector → Institution','admin.portal');
  }
  if($level==='kicc'){
   foreach(['experience.images.index'=>['Content','Image add / replace / delete'],'admin.media.index'=>['Content','Media library'],'cms.admin.index'=>['Content','CMS / FAQ'],'admin.3d.assets'=>['Content','3D library'],'admin.components.ui'=>['Content','Source components'],'admin.users'=>['People','Users & roles'],'admin.audit'=>['Analytics','Audit trail'],'admin.enquiries'=>['Operations','Enquiries'],'admin.ecommerce.dashboard'=>['Commerce','Commerce workspace']] as $name=>[$g,$label])$add($g,$label,$name);
  }
  if($institution)$add('Commerce','Product video editor','institution.products.index',[$institution->slug]);
  $add('Settings','Preview public website ↗','home');
  foreach($groups as $g=>$links){$unique=[];foreach($links as $link)$unique[$link['url']]=$link;$groups[$g]=array_values($unique);}
  return $groups;
 }
}
