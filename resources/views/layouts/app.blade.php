<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KICC') - Global Exhibition Platform</title>
    <meta name="description" content="Africa's Premier Meeting Venue. A national icon since 1973.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/theme.js') }}"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        body { background-color: #ffffff; color: #1a1a2e; }
        .scrollbar-hide { scrollbar-width: none; -ms-overflow-style: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        [x-cloak] { display: none !important; }

        /* ── Scroll Reveal ─────────────────────────── */
        .reveal-init { opacity: 0; transform: translateY(28px); transition: opacity 0.7s cubic-bezier(0.22,1,0.36,1), transform 0.7s cubic-bezier(0.22,1,0.36,1); will-change: opacity, transform; }
        .reveal-init.revealed { opacity: 1; transform: translateY(0); }
        .reveal-init[data-reveal="left"] { transform: translateX(-36px); }
        .reveal-init[data-reveal="right"] { transform: translateX(36px); }
        .reveal-init[data-reveal="zoom"] { transform: scale(0.92); }
        .reveal-init[data-reveal="left"].revealed,
        .reveal-init[data-reveal="right"].revealed,
        .reveal-init[data-reveal="zoom"].revealed { transform: none; }

        /* ── Card hover lift ───────────────────────── */
        .card-hover { transition: transform 0.25s cubic-bezier(0.22,1,0.36,1), box-shadow 0.25s, border-color 0.25s; }
        .card-hover:hover { transform: translateY(-4px); }

        /* ── 3D tilt glare overlay ─────────────────── */
        [data-tilt] { position: relative; transform-style: preserve-3d; }
        .tilt-glare { position: absolute; inset: 0; border-radius: inherit; pointer-events: none; }

        /* ── Marquee ───────────────────────────────── */
        @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .animate-marquee { animation: marquee 30s linear infinite; }
        .marquee-paused:hover .animate-marquee { animation-play-state: paused; }

        /* ── Floating orbs ─────────────────────────── */
        @keyframes float-slow { 0%,100% { transform: translateY(0) translateX(0); } 50% { transform: translateY(-30px) translateX(20px); } }
        @keyframes float-slower { 0%,100% { transform: translateY(0) translateX(0); } 50% { transform: translateY(25px) translateX(-25px); } }
        .animate-float-slow { animation: float-slow 9s ease-in-out infinite; }
        .animate-float-slower { animation: float-slower 13s ease-in-out infinite; }

        /* ── Shimmer text ──────────────────────────── */
        @keyframes shimmer { 0% { background-position: -200% center; } 100% { background-position: 200% center; } }
        .text-shimmer {
            background: linear-gradient(110deg, #FFCD05 25%, #fff 40%, #FFCD05 55%);
            background-size: 200% auto;
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 4s linear infinite;
        }

        /* ── Pulse glow ────────────────────────────── */
        @keyframes pulse-glow { 0%,100% { box-shadow: 0 0 0 0 rgba(255,205,5,0.35); } 50% { box-shadow: 0 0 30px 6px rgba(255,205,5,0.15); } }
        .animate-pulse-glow { animation: pulse-glow 3.2s ease-in-out infinite; }

        @media (prefers-reduced-motion: reduce) {
            .reveal-init { opacity: 1; transform: none; transition: none; }
            .animate-marquee, .animate-float-slow, .animate-float-slower, .text-shimmer, .animate-pulse-glow { animation: none; }
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased text-gray-900 bg-white">
    {{-- NAV — fixed top bar with glass effect on scroll --}}
    <nav x-data="{ scrolled: false, open: false }" x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 40)"
          class="fixed top-0 left-0 right-0 z-50 transition-all duration-500 h-20"
          :class="scrolled ? 'bg-white/95 backdrop-blur-xl border-b border-gray-200 shadow-lg shadow-black/5' : 'bg-[#0a1024]/80 backdrop-blur-sm'">
        <div class="max-w-7xl mx-auto px-5 h-full flex items-center justify-between gap-4">
            <a href="/" class="flex items-center gap-3 shrink-0 group">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-10 w-auto object-contain" :class="scrolled ? '' : 'brightness-0 invert'" style="filter: brightness(0) invert(0);">
                <div class="hidden sm:block leading-tight border-l pl-3" :class="scrolled ? 'border-gray-300' : 'border-white/30'">
                    <div class="font-black text-[#901C1E] text-[11px] tracking-tight group-hover:text-kicc-gold transition-colors uppercase" :class="scrolled ? 'text-[#901C1E]' : 'text-white'">Global Exhibition</div>
                    <div class="text-[9px] text-kicc-gold font-bold tracking-[0.18em] uppercase">Platform</div>
                </div>
            </a>
            <nav class="hidden lg:flex items-center gap-1">
                <a href="{{ route('counties.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all"
                   :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/80 hover:text-white hover:bg-white/10'"
                   {{ request()->routeIs('counties.*') ? 'style=background:#901C1E;color:white' : '' }}>Counties</a>
                <a href="{{ route('marketplace.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all"
                   :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/80 hover:text-white hover:bg-white/10'"
                   {{ request()->routeIs('marketplace.*') ? 'style=background:#901C1E;color:white' : '' }}>Marketplace</a>
                <a href="{{ route('travel.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all"
                   :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/80 hover:text-white hover:bg-white/10'"
                   {{ request()->routeIs('travel.*') ? 'style=background:#901C1E;color:white' : '' }}>Travel</a>
                <a href="{{ route('venues.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all"
                   :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/80 hover:text-white hover:bg-white/10'"
                   {{ request()->routeIs('venues.*') ? 'style=background:#901C1E;color:white' : '' }}>Venues</a>
                <a href="{{ route('exhibitions.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all"
                   :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/80 hover:text-white hover:bg-white/10'"
                   {{ request()->routeIs('exhibitions.*') ? 'style=background:#901C1E;color:white' : '' }}>Events</a>
                <a href="{{ route('screens.directory') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all"
                   :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/80 hover:text-white hover:bg-white/10'"
                   {{ request()->routeIs('screens.*') ? 'style=background:#901C1E;color:white' : '' }}>Screens</a>
                <a href="{{ route('exhibition-3d.map') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all"
                   :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/80 hover:text-white hover:bg-white/10'"
                   {{ request()->routeIs('exhibition-3d.*') ? 'style=background:#901C1E;color:white' : '' }}>3D Tour</a>
            </nav>
            <div class="flex items-center gap-2">
                <a href="{{ route('cart.index') }}" class="relative p-2 transition-colors" :class="scrolled ? 'text-gray-500 hover:text-kicc-red' : 'text-white/70 hover:text-white'" aria-label="Cart">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </a>
                @auth
                <a href="{{ route('dashboard.index') }}" class="inline-flex items-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl"
                   :class="scrolled ? 'bg-kicc-red text-white hover:bg-[#7a181a]' : 'bg-white/20 text-white hover:bg-white/30'">
                    Dashboard
                </a>
                <a href="{{ route('admin.portal') }}" class="hidden sm:inline-flex items-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl border"
                   :class="scrolled ? 'border-kicc-red/50 text-kicc-red hover:bg-kicc-red/10' : 'border-white/30 text-white/80 hover:bg-white/10'" title="Admin">
                    Admin
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">@csrf
                    <button type="submit" class="inline-flex items-center gap-2 font-bold tracking-wide transition-all duration-200 px-3 text-xs h-9 rounded-xl border"
                            :class="scrolled ? 'border-gray-300 text-gray-600 hover:bg-gray-100' : 'border-white/30 text-white/80 hover:bg-white/10'">Logout</button>
                </form>
                @else
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl"
                   :class="scrolled ? 'bg-kicc-red text-white hover:bg-[#7a181a]' : 'bg-white/20 text-white hover:bg-white/30'">Sign In</a>
                @endauth
                <button @click="open = !open" class="lg:hidden p-2 transition-colors" :class="scrolled ? 'text-gray-600 hover:text-kicc-red' : 'text-white/70 hover:text-white'" aria-label="Menu">
                    <svg class="w-5 h-5" x-show="!open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg class="w-5 h-5" x-show="open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div x-show="open" x-cloak x-transition class="lg:hidden absolute top-full left-0 right-0 p-4 flex flex-col gap-1 border-b shadow-lg"
             :class="scrolled ? 'bg-white border-gray-200' : 'bg-[#0a1024]/95 border-white/10'">
            <a href="{{ route('counties.index') }}" class="text-left px-4 py-3 text-sm font-semibold rounded-lg"
               :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/70 hover:text-white hover:bg-white/10'">Counties</a>
            <a href="{{ route('marketplace.index') }}" class="text-left px-4 py-3 text-sm font-semibold rounded-lg"
               :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/70 hover:text-white hover:bg-white/10'">Marketplace</a>
            <a href="{{ route('travel.index') }}" class="text-left px-4 py-3 text-sm font-semibold rounded-lg"
               :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/70 hover:text-white hover:bg-white/10'">Travel</a>
            <a href="{{ route('venues.index') }}" class="text-left px-4 py-3 text-sm font-semibold rounded-lg"
               :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/70 hover:text-white hover:bg-white/10'">Venues</a>
            <a href="{{ route('exhibitions.index') }}" class="text-left px-4 py-3 text-sm font-semibold rounded-lg"
               :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/70 hover:text-white hover:bg-white/10'">Events</a>
            <a href="{{ route('screens.directory') }}" class="text-left px-4 py-3 text-sm font-semibold rounded-lg"
               :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/70 hover:text-white hover:bg-white/10'">Screens</a>
            <a href="{{ route('exhibition-3d.map') }}" class="text-left px-4 py-3 text-sm font-semibold rounded-lg"
               :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/70 hover:text-white hover:bg-white/10'">3D Tour</a>
            <a href="{{ route('subscriptions.index') }}" class="text-left px-4 py-3 text-sm font-semibold rounded-lg"
               :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/70 hover:text-white hover:bg-white/10'">Plans</a>
            @auth
            <a href="{{ route('dashboard.index') }}" class="text-left px-4 py-3 text-sm font-semibold rounded-lg"
               :class="scrolled ? 'text-kicc-red bg-gray-50' : 'text-kicc-gold hover:bg-white/10'">Dashboard</a>
            <a href="{{ route('admin.portal') }}" class="text-left px-4 py-3 text-sm font-semibold rounded-lg"
               :class="scrolled ? 'text-kicc-red hover:bg-gray-100' : 'text-white/70 hover:text-white hover:bg-white/10'">Admin</a>
            @else
            <a href="{{ route('login') }}" class="text-left px-4 py-3 text-sm font-semibold rounded-lg"
               :class="scrolled ? 'text-gray-600 hover:text-kicc-red hover:bg-gray-100' : 'text-white/70 hover:text-white hover:bg-white/10'">Sign In</a>
            @endauth
        </div>
    </nav>

    <main class="min-h-screen pt-20">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="bg-[#0B1E57] mt-20">
        <div class="max-w-7xl mx-auto px-5 py-14 grid grid-cols-1 md:grid-cols-4 gap-10">
            <div>
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-12 w-auto object-contain mb-3 brightness-0 invert" style="filter: brightness(0) invert(1);">
                <div class="text-kicc-gold text-[10px] font-bold tracking-[0.18em] uppercase mb-3">Global Exhibition Platform</div>
                <p class="text-white/60 text-sm leading-relaxed">Africa's Premier Meeting Venue. A national icon since 1973.</p>
                <div class="mt-5 flex flex-col gap-1 text-sm text-white/50">
                    <span class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-kicc-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        (+254) 20 3261000
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-kicc-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        City Square, Nairobi CBD
                    </span>
                </div>
            </div>
            @php $footerLinks = [
                'Platform' => [['Counties','counties.index'],['Marketplace','marketplace.index'],['Travel','travel.index'],['Exhibitions','exhibitions.index'],['Venues','venues.index']],
                'Experiences' => [['3D County Map','exhibition-3d.map'],['Sector Explorer','exhibition-3d.sector'],['Booth Tour','exhibition-3d.booth'],['3D Rooms','room3d.index'],['Screens','screens.directory']],
                'Account' => [['Subscriptions','subscriptions.index'],['Operations','operations.index'],['Admin Portal','admin.portal']],
            ]; @endphp
            @foreach($footerLinks as $title => $links)
            <div>
                <h4 class="font-bold text-white/70 text-xs uppercase tracking-[0.15em] mb-4">{{ $title }}</h4>
                <ul class="space-y-2.5">
                    @foreach($links as $link)
                    <li><a href="{{ route($link[1]) }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">{{ $link[0] }}</a></li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
        <div class="border-t border-white/10 py-5">
            <div class="max-w-7xl mx-auto px-5 flex flex-col md:flex-row justify-between items-center gap-2 text-white/30 text-xs">
                <span>&copy; {{ date('Y') }} Kenyatta International Convention Centre. All rights reserved.</span>
                <span class="flex items-center gap-1.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    KRA &middot; CBK &middot; KEBS Compliant
                </span>
            </div>
        </div>
    </footer>
    @stack('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="{{ asset('js/animations.js') }}"></script>
</body>
</html>
