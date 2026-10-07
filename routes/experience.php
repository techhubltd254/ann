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
