<?php
namespace App\Http\Middleware;

use App\Models\{County, CountyInstitution};
use App\Services\AdminHierarchyScope;
use Illuminate\Http\Request;
use Closure;

/** Applies to old and new admin routes alike; navigation is not authorization. */
class EnforceAdminHierarchy
{
    public function handle(Request $r, Closure $next)
    {
        $path=\App\Support\AdminPaths::legacy($r->path());$name=$r->route()?->getName()??'';
        $protected=preg_match('#^(portal|kicc-admin|county-admin|institution-admin|national-admin|records-admin)(/|$)#',$path);
        if(!$protected || in_array($path,['kicc-admin/login','kicc-admin/reset-pwd'],true))return $next($r);
        $u=$r->user();if(!$u)return redirect()->guest(route('login'));
        $scope=app(AdminHierarchyScope::class);$level=$scope->level($u);
        abort_unless($level,403,'No administration role is assigned.');
        if($level==='kicc')return $next($r);
        if(str_starts_with($path,'records-admin'))abort(403,'Mother/KICC publishing access required.');
        if(str_starts_with($path,'kicc-admin')){
            abort_unless(str_starts_with($path,'kicc-admin/national') && $level==='national',403,'Mother/KICC administration access required.');
        }
        if(str_starts_with($path,'national-admin'))abort_unless($level==='national',403);
        if(str_starts_with($path,'county-admin')){
            abort_unless(in_array($level,['national','county'],true),403);
            $value=$r->route('slug')??$r->route('county');
            if($value){$county=$value instanceof County?$value:County::where('slug',(string)$value)->firstOrFail();abort_unless($scope->canCounty($u,$county),403,'Out-of-county administration denied.');}
        }
        if(str_starts_with($path,'institution-admin')){
            $value=$r->route('institution');
            if($value){$inst=$value instanceof CountyInstitution?$value:CountyInstitution::where('slug',(string)$value)->firstOrFail();abort_unless($scope->canInstitution($u,$inst),403,'Out-of-institution administration denied.');}
        }
        return $next($r);
    }
}
