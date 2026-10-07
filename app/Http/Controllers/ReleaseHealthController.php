<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\{DB,Storage};
class ReleaseHealthController extends Controller {
 public function __invoke(){try{DB::select('SELECT 1');$rows=DB::table('records')->count();$writable=is_writable(storage_path('framework/views'))&&is_writable(storage_path('app/private'));if(!$writable)return response()->json(['status'=>'unhealthy'],503);return response()->json(['status'=>'ok','records'=>$rows]);}catch(\Throwable $e){return response()->json(['status'=>'unhealthy'],503);}}
}
