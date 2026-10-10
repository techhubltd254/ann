<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Services\AdminHierarchyScope;
use App\Support\AdminPaths;
class AdminConsole
{
    public function handle(Request $request, Closure $next)
    {
        $actor=$request->user();
        if(!$actor){return $request->expectsJson()?response()->json(['message'=>'Authentication required.'],401):redirect()->guest(route('login'));}
        abort_unless(($actor->status??'active')==='active' && app(AdminHierarchyScope::class)->level($actor),403,'An active administration role is required.');
        $action=$request->route()->getAction();
        if($action['admin_mother_only']??false)abort_unless($actor->hasRole('kicc_admin'),403,'Mother/KICC administration access required.');
        abort_if($action['admin_disable_http_deploy']??false,410,'HTTP deployment and password-reset helpers are disabled. Use the controlled deployment or normal password-reset workflow.');
        if($request->isMethod('GET') && ($old=$action['admin_canonical_uri']??null)){
            $url=$old;
            foreach($request->route()->parameters() as $key=>$value){$value=$value instanceof \Illuminate\Contracts\Routing\UrlRoutable?$value->getRouteKey():$value;$url=str_replace(['{'.$key.'}','{'.$key.'?}'],rawurlencode((string)$value),$url);}
            $url=preg_replace('~/?\{[^}]+\?\}~','',$url);
            return redirect($url.($request->getQueryString()?'?'.$request->getQueryString():''));
        }
        $response=$next($request);
        $location=$response->headers->get('Location');
        if($location){$url=parse_url($location);if(($url['host']??$request->getHost())===$request->getHost()){
            $path=$url['path']??'/';$new=AdminPaths::canonical($path);
            if($new!==$path)$response->headers->set('Location',$new.(isset($url['query'])?'?'.$url['query']:''));
        }}
        if(str_contains($response->headers->get('Content-Type',''),'text/html')){
            $html=$response->getContent();
            // Correct old literal links as well as named routes. Public "View site"
            // remains a deliberate separate destination, not an editing action.
            foreach(['/kicc-admin/national'=>'/admin/national','/kicc-admin'=>'/admin/kicc','/county-admin'=>'/admin/counties','/institution-admin'=>'/admin/institutions','/national-admin'=>'/admin/national','/records-admin'=>'/admin/records','/portal'=>'/admin'] as $from=>$to){
                $html=preg_replace('~(["\'])'.preg_quote($from,'~').'(?=[/"\'?])~','$1'.$to,$html);
                $html=str_replace($request->getSchemeAndHttpHost().$from,$request->getSchemeAndHttpHost().$to,$html);
            }
            $response->setContent($html);
        }
        $response->headers->set('Cache-Control','private, no-store, max-age=0');
        $response->headers->set('CDN-Cache-Control','no-store');
        $response->headers->set('Cloudflare-CDN-Cache-Control','no-store');
        $response->headers->set('X-KICC-Admin','isolated-v1');
        return $response;
    }
}
