<?php
use Illuminate\Support\Facades\Route;use App\Http\Controllers\Web\ExperienceProductionController as E;
Route::prefix('experience')->group(function(){
 Route::get('/api/session',[E::class,'session']);
 Route::post('/api/login',[E::class,'login'])->middleware('throttle:experience-login');
 Route::post('/api/logout',[E::class,'logout']);
 Route::get('/api/public',[E::class,'publicData']);
 Route::get('/api/admin',[E::class,'adminData']);
 Route::put('/api/records/{type}',[E::class,'save']);
 Route::delete('/api/records/{type}',[E::class,'destroy']);
 Route::post('/api/assets',[E::class,'upload']);
 Route::delete('/api/assets/{id}',[E::class,'deleteAsset']);
 Route::get('/media/{id}',[E::class,'serve'])->whereUuid('id');
});

Route::prefix('experience')->group(function(){
 Route::get('/api/control/inventory',[\App\Http\Controllers\Web\MediaControlController::class,'inventory']);
 Route::get('/api/control/functions',[\App\Http\Controllers\Web\MediaControlController::class,'functions']);
 Route::get('/api/control/slots',[\App\Http\Controllers\Web\MediaControlController::class,'slots']);
 Route::get('/api/control/reconcile',[\App\Http\Controllers\Web\MediaControlController::class,'reconcile']);
 Route::get('/site-slot/{id}',[\App\Http\Controllers\Web\MediaControlController::class,'siteSlot']);
});

Route::prefix('experience/api')->group(function(){Route::get('/components',[\App\Http\Controllers\Web\SiteComponentsController::class,'index']);Route::put('/components/{id}',[\App\Http\Controllers\Web\SiteComponentsController::class,'update'])->where('id','[A-Za-z0-9_.:-]+');Route::post('/components/{id}/reset',[\App\Http\Controllers\Web\SiteComponentsController::class,'reset'])->where('id','[A-Za-z0-9_.:-]+');Route::get('/components-coverage',[\App\Http\Controllers\Web\SiteComponentsController::class,'coverage']);});
