<!doctype html>
<html lang="en" data-theme="dark"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','KICC Admin')</title>
<script>(()=>{let t='dark';try{t=localStorage.getItem('kicc.theme')||'dark'}catch(e){}document.documentElement.dataset.theme=t;document.documentElement.style.colorScheme=t;})();</script>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;1,9..144,400&family=Inter+Tight:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/admin-shell.css?v=admin-shell-v2">
<style>
:root {
    --theme-accent: {{ $themeColor ?? '#FFCD05' }};
    --theme-accent-dim: {{ $themeColor ?? '#FFCD05' }}22;
    --theme-glow: {{ $themeColor ?? '#FFCD05' }}33;
    --theme-primary: {{ $themeColor ?? '#FFCD05' }};
}
/* Accent overrides for themed admin */
.text-kicc-gold, .text-[#FFCD05] { color: var(--theme-accent) !important; }
.bg-kicc-gold, .bg-[#FFCD05] { background-color: var(--theme-accent) !important; }
.border-kicc-gold { border-color: var(--theme-accent) !important; }
.as-nav a.active { border-left-color: var(--theme-accent) !important; color: var(--theme-accent) !important; }
.as-cta { background: var(--theme-accent) !important; }
</style>
<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@stack('styles')</head>
<body class="admin-shell">
<aside class="as-side">
 <a class="as-brand" href="{{ route('admin.portal') }}"><span class="as-mark as-mark-logo"><img src="/kicc-logo.png" alt="KICC"></span><span><strong>{{ $brandName ?? 'KICC Admin' }}</strong><small>{{ $brandSub ?? 'National Platform' }}</small></span></a>
 <div class="as-nav-label">Manage</div>
 <nav class="as-nav">
  <a href="{{ route('admin.portal') }}" class="{{ request()->is('portal') ? 'active' : '' }}"><span class="ic">▦</span>Dashboard</a>
  <a href="{{ route('kicc.admin') }}" class="{{ request()->is('kicc-admin') ? 'active' : '' }}"><span class="ic">◎</span>Mother Admin</a>
  <a href="{{ route('county.admin') }}" class="{{ request()->is('county-admin*') ? 'active' : '' }}"><span class="ic">◈</span>Counties</a>
  <a href="{{ route('national.admin') }}" class="{{ request()->is('national-admin*') ? 'active' : '' }}"><span class="ic">▣</span>National</a>
  <a href="{{ route('experience.images.index') }}" class="{{ request()->is('experience/images*') ? 'active' : '' }}"><span class="ic">▤</span>Images</a>
  <a href="{{ route('admin.mediaflow') }}" class="{{ request()->is('portal/media-flow*') ? 'active' : '' }}"><span class="ic">▶</span>Media Flow</a>
 </nav>
 <div class="as-nav-label">Tools</div>
 <nav class="as-nav">
  <a href="{{ route('admin.mediaflow') }}" class="{{ request()->is('portal/media-flow*') ? 'active' : '' }}"><span class="ic">⇄</span>Videos &amp; R2</a>
  <a href="{{ url('/records-admin') }}" class="{{ request()->is('records-admin*') ? 'active' : '' }}"><span class="ic">▦</span>Records</a>
  <a href="{{ url('/portal/media-flow') }}" class="{{ request()->is('portal/media-flow*') ? 'active' : '' }}"><span class="ic">◉</span>Hierarchy</a>
  <a href="{{ url('/') }}" target="_blank" rel="noopener"><span class="ic">⌂</span>View Site</a>
 </nav>
 <div class="as-profile"><span class="as-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A',0,1)) }}</span><div><strong style="font-size:13px">{{ auth()->user()->name ?? 'Admin' }}</strong><small>{{ ucwords(str_replace('_',' ',auth()->user()->roles->first()->name ?? 'admin')) }}</small></div></div>
</aside>
<div>
<header class="as-top">
 <button class="as-icon-btn as-burger" data-as-burger aria-label="Menu">☰</button>
 <div class="as-greet"><h1>@yield('title','Dashboard')</h1><p>{{ $greeting ?? 'Welcome back — here\'s what\'s happening across the platform today.' }}</p></div>
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
@stack('scripts')
</body></html>
