<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{AuthController,PublicController,AdminController};
Route::get('/release-health',\App\Http\Controllers\ReleaseHealthController::class)->name('release.health');
Route::get('/',[PublicController::class,'home'])->name('home');
Route::get('/login',[AuthController::class,'form'])->middleware('guest')->name('login');
Route::post('/login',[AuthController::class,'login'])->middleware(['guest','throttle:6,1'])->name('login.submit');
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::get('/marketplace',fn()=>redirect('/products',301));
Route::get('/marketplace/{slug}',fn($slug)=>redirect('/products/'.$slug,301));
Route::get('/national-government',[PublicController::class,'national'])->name('national');
Route::get('/national-sector',fn()=>redirect('/sectors',301));
Route::get('/national-sector/{slug}',fn($slug)=>redirect('/sectors/'.$slug,301));
Route::get('/media/{media}',[PublicController::class,'media'])->name('media.show');
// R2 Presigned Upload — bypass Cloudflare 100MB Worker limit
Route::post('/api/r2/presigned-upload',[\App\Http\Controllers\R2UploadController::class,'presignedUploadUrl'])->middleware('auth');
Route::post('/api/r2/confirm-upload',[\App\Http\Controllers\R2UploadController::class,'confirmR2Upload'])->middleware('auth');
// Seed/cache-flush utility
Route::get('/trigseed/{token}',function(string $token){if($token!=='kicc-seed-2026x')abort(403);\Illuminate\Support\Facades\Artisan::call('db:seed',['--class'=>'ReferenceContentSeeder','--force'=>true]);\Illuminate\Support\Facades\Cache::flush();return response('<pre>'.\Illuminate\Support\Facades\Artisan::output().'</pre>');});

Route::post('/enquiries/{record}',[PublicController::class,'enquire'])->middleware('throttle:8,1')->name('enquire');
Route::get('/kicc-admin/login',fn()=>redirect('/login'));
Route::get('/kicc-admin',fn()=>redirect('/admin'));
Route::middleware(['auth',\App\Http\Middleware\RequireAdmin::class])->prefix('admin')->name('admin.')->group(function(){
 Route::get('/',[AdminController::class,'index'])->name('index');
 Route::get('/audit',[AdminController::class,'auditLog'])->name('audit');
 Route::get('/enquiries',[AdminController::class,'enquiries'])->name('enquiries');
 Route::patch('/enquiries/{enquiry}',[AdminController::class,'enquiryStatus'])->name('enquiry.status');
 Route::get('/content/{type}',[AdminController::class,'listing'])->name('list');
 Route::get('/content/{type}/create',[AdminController::class,'create'])->name('create');
 Route::post('/records',[AdminController::class,'store'])->name('store');
 Route::get('/records/{record}/edit',[AdminController::class,'edit'])->name('edit');
 Route::put('/records/{record}',[AdminController::class,'update'])->name('update');
 Route::delete('/records/{record}',[AdminController::class,'delete'])->name('delete');
 Route::post('/records/{record}/media',[AdminController::class,'upload'])->name('upload');
 Route::patch('/media/{media}',[AdminController::class,'mediaStatus'])->name('media.status');
 Route::delete('/media/{media}',[AdminController::class,'deleteMedia'])->name('media.delete');
});
$types=implode('|',array_map(fn($x)=>preg_quote($x,'/'),array_keys(config('kicc.types'))));
Route::get('/kicc/{slug}',fn($slug)=>app(PublicController::class)->detail('pages',$slug))->name('kicc.page');
Route::get('/{type}',[PublicController::class,'directory'])->where('type',$types)->name('directory');
Route::get('/{type}/{slug}',[PublicController::class,'detail'])->where('type',$types)->name('detail');
