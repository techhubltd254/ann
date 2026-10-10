<!doctype html>
<html lang="en" data-theme="dark"><head><meta charset="utf-8"><link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}"><link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16.png') }}"><link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}"><link rel="shortcut icon" href="{{ asset('favicon.ico') }}"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','KICC Admin')</title>
<script>(()=>{let t='dark';try{t=localStorage.getItem('kicc.theme')||'dark'}catch(e){}document.documentElement.dataset.theme=t;document.documentElement.style.colorScheme=t;})();</script>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;1,9..144,400&family=Inter+Tight:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="/js/alpine-data.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
<link rel="stylesheet" href="/css/admin-shell.css?v=admin-safe-v3">
<link rel="stylesheet" href="/css/admin-safe.css?v=admin-safe-v3">
<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="/css/admin-workflow.css?v=admin-product-v1"><link rel="stylesheet" href="/css/admin-complete.css?v=complete-v4">@stack('styles')</head>
@php
 $actor=auth()->user();
 if(isset($institution)&&is_string($institution))$institution=($inst??null) instanceof \App\Models\CountyInstitution?$inst:\App\Models\CountyInstitution::where('slug',$institution)->first();
 if(!isset($institution)&&isset($inst)&&$inst instanceof \App\Models\CountyInstitution)$institution=$inst;

 $adminLevel=$actor?app(\App\Services\AdminHierarchyScope::class)->level($actor):null;

 $adminContext=request()->is('county-admin/*','admin/counties/*')?'county':(request()->is('institution-admin/*','admin/institutions/*')?'institution':(request()->is('*national*')?'national':(request()->is('kicc-admin*','admin/kicc*')?'kicc':($adminLevel??'kicc'))));
 $adminVariant=$adminContext==='county'?'galaxy':($adminContext==='institution'?'nexora':'adminora');
 $adminLinks=$actor?\App\Support\AdminNav::groups($actor,['county'=>$county??null,'institution'=>$institution??null,'navItems'=>$navItems??null]):[];
@endphp
<body class="admin-shell" data-admin-tier="{{ $adminContext }}" data-admin-variant="{{ $adminVariant }}">
<aside class="as-side">
 <a class="as-brand" href="{{ route('admin.portal') }}"><span class="as-mark as-mark-logo"><img src="{{ tile_url('logo') }}" alt="KICC"></span><span><strong>KICC Admin</strong><small>{{ ucfirst($adminContext) }} control centre</small></span></a>
 <div class="as-nav-label">{{ ucfirst($adminContext) }} · {{ ucfirst($adminVariant) }}</div>
 <nav class="as-nav" aria-label="Administration controls">
 @isset($institution)<a class="as-cta" style="display:block;margin:12px 0;white-space:normal" href="{{ route('institution.products.index',$institution->slug) }}">Products, Services & Experiences →</a>@endisset
 @foreach($adminLinks as $section=>$links)
  @if(count($links))
  @php $active=collect($links)->contains(fn($a)=>$a['url']===url()->full()); @endphp
  <details class="as-nav-group" @if($active || $section==='Dashboard' || ($section==='Commerce' && isset($institution))) open @endif>
   <summary>{{ $section }} <small>{{ count($links) }}</small></summary>
   @foreach($links as $link)<a href="{{ $link['url'] }}" @if($link['url']===route('home')) target="_blank" rel="noopener noreferrer" @endif @class(['active'=>$link['url']===url()->full()])>{{ $link['label'] }}</a>@endforeach
  </details>
  @endif
 @endforeach
 </nav>
 <div class="as-profile"><span class="as-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A',0,1)) }}</span><div><strong style="font-size:13px">{{ auth()->user()->name ?? 'Admin' }}</strong><small>{{ ucwords(str_replace('_',' ',auth()->user()?->roles?->first()?->name ?? 'admin')) }}</small></div></div>
</aside>
<div>
<header class="as-top">
 <button class="as-icon-btn as-burger" data-as-burger aria-label="Menu">☰</button>
 <div class="as-greet"><h1>@yield('title','Dashboard')</h1><p>Welcome back — here's what's happening across the platform today.</p></div>
 <div class="as-search"><input type="search" placeholder="Search anything…" data-as-search aria-label="Search"></div>
 <div class="as-acts">
  <button type="button" class="as-icon-btn" data-as-theme aria-pressed="true" title="Toggle theme">◐</button>
  <a class="as-icon-btn" href="{{ route('admin.mediaflow') }}" title="Media">▶</a>
  <a class="as-cta" href="{{ route('admin.portal') }}">Portals</a>
  <form method="POST" action="{{ route('logout') }}" style="margin:0">@csrf<button class="as-icon-btn" type="submit" title="Sign out">⏻</button></form>
 </div>
</header>
<main class="as-main">@yield('content')</main>
</div>
<script defer src="/js/admin-shell.js?v=admin-shell-v2"></script>
<script src="/js/resumable-entity-upload.js?v=entity-v4"></script>
@stack('scripts')
</body></html>
