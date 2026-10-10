<?php
use Illuminate\Support\Facades\Route;
use App\Support\AdminPaths;
use App\Http\Middleware\AdminConsole;
// Run after web.php AND the preserved records routes are registered.
$router=app('router');$originals=iterator_to_array($router->getRoutes()->getIterator());
foreach($originals as $r){
    $uri=$r->uri();$action=$r->getAction();$controller=$action['controller']??'';
    $media=str_contains($controller,'MediaLibraryController')||str_contains($controller,'MediaApiController');
    $protected=(bool)preg_match('~^(portal|kicc-admin|county-admin|institution-admin|national-admin|records-admin|admin)(/|$)~',$uri)||preg_match('~^(admin\.|cms\.admin\.|dashboard\.admin|kicc\.admin\.|county\.admin\.|institution\.admin\.|national\.admin\.)~',$r->getName()??'');
    if(str_contains($controller,'Controllers\\Admin\\')||str_contains($controller,'Controllers\\Live\\LiveAdminController'))$protected=true;
    $legacyOperations=preg_match('~^(__admin|__reset-admin-pwd|kicc-v2-deploy|kicc-v2-seed-deploy)(/|$)~',$uri);
    if($legacyOperations||str_ends_with($uri,'reset-pwd'))$protected=true;
    $login=preg_match('~(?:login)$~',$uri);
    if($login)continue;
    if(!$protected&&!$media&&!str_starts_with($uri,'api/media/'))continue;
    $r->middleware(str_starts_with($uri,'api/')?['auth:sanctum',AdminConsole::class]:['auth',AdminConsole::class]);
    if($media||str_starts_with($uri,'api/media/')||$legacyOperations){$action=$r->getAction();$action['admin_mother_only']=true;if($legacyOperations && in_array('GET',$r->methods(),true))$action['admin_disable_http_deploy']=true;$r->setAction($action);}
    $canonical=AdminPaths::canonical('/'.$uri);
    if($media&&!str_starts_with($uri,'api/'))$canonical=str_starts_with($uri,'kicc-admin/site-images')?'/admin/kicc/site-images'.substr($uri,strlen('kicc-admin/site-images')):'/admin/'.$uri;
    if($protected&&$canonical==='/'.$uri&&!str_starts_with($uri,'admin/'))$canonical='/admin/tools/'.$uri;
    if($uri==='admin'||$canonical==='/'.$uri)continue;
    $copy=clone $r;$fields=$r->bindingFields();$copy->setUri(ltrim($canonical,'/'));$copy->setBindingFields($fields);$canonicalAction=$r->getAction();unset($canonicalAction['prefix']);$copy->setAction($canonicalAction);
    $router->getRoutes()->add($copy);
    $old=$r->getAction();unset($old['as']);$old['admin_canonical_uri']=$canonical;$r->setAction($old);
}
$router->getRoutes()->refreshNameLookups();$router->getRoutes()->refreshActionLookups();
