<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KICC Platform')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>var _cw=console.warn;console.warn=function(m){if(typeof m==="string"&&m.includes("tailwindcss.com"))return;_cw.apply(this,arguments)}</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        body { background: #070708; color: #ffffff; }
        .scrollbar-hide { scrollbar-width: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-text-fill-color: #ffffff !important;
            -webkit-box-shadow: 0 0 0px 1000px rgba(0,0,0,0.4) inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    @php $alpineUrl = 'https://cdn.jsdelivr.net/npm/alpinejs@' . config('kicc.alpine_version', '3.14.8') . '/dist/cdn.min.js'; @endphp
</head>
<body class="antialiased text-white scrollbar-hide">
    @yield('content')
    <script defer src="{{ $alpineUrl }}"></script>
</body>
</html>