<!doctype html>
<html lang="en" data-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','KICC Admin')</title>
<script>(()=>{let t='light';try{t=localStorage.getItem('kicc.theme')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light')}catch(e){}document.documentElement.dataset.theme=t;document.documentElement.style.colorScheme=t;})();</script>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;1,9..144,400&family=Inter+Tight:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/admin-shell.css?v=admin-shell-v1">
<script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@stack('styles')</head>
<body class="admin-shell">
<header class="as-topbar">
 <a class="as-brand" href="{{ route('admin.portal') }}"><span class="as-mark">K</span><span><strong>KICC Admin</strong><small>National Exhibition Platform</small></span></a>
 <span class="as-crumb">@yield('title','Administration')</span>
 <div class="as-actions">
  <button type="button" class="as-btn" data-as-theme aria-pressed="false">Dark</button>
  <a class="as-btn" href="{{ route('admin.portal') }}">Portals</a>
  <a class="as-btn" href="{{ url('/') }}" target="_blank" rel="noopener">View site</a>
  <form method="POST" action="{{ route('logout') }}" style="margin:0">@csrf<button class="as-btn primary" type="submit">Sign out</button></form>
 </div>
</header>
<main class="as-main">@yield('content')</main>
<script defer src="/js/admin-shell.js?v=admin-shell-v1"></script>
@stack('scripts')
</body></html>
