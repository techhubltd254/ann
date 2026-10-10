<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
class ProtectPrivateRevenue {
 public function handle(Request $request,Closure $next):Response {
  $response=$next($request);
  $privateContext=$request->is('admin/kicc','admin/kicc/*','kicc-admin','kicc-admin/*','api/pipeline/*');
  $mayRead=$privateContext&&Gate::allows('view-private-revenue');
  if(!$mayRead&&$response instanceof \Illuminate\Http\JsonResponse){
   $data=$response->getData(true);if(is_array($data))$response->setData(\App\Support\PrivateRevenue::redact($data));
  }
  if($privateContext){foreach(['Cache-Control'=>'private, no-store, max-age=0','CDN-Cache-Control'=>'no-store','Cloudflare-CDN-Cache-Control'=>'no-store','Vary'=>'Cookie, Authorization'] as $k=>$v)$response->headers->set($k,$v);}
  return $response;
 }
}
