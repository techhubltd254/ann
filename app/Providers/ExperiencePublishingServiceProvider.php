<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
class ExperiencePublishingServiceProvider extends ServiceProvider {
 public function boot():void {
  RateLimiter::for('experience-login',fn(Request $request)=>Limit::perMinute(6)->by($request->ip().'|'.strtolower((string)$request->input('email'))));
 }
}
