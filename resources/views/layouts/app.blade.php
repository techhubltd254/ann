<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KICC') - Global Exhibition Platform</title>
    <meta name="description" content="Africa's Premier Meeting Venue. A national icon since 1973.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        kicc: {
                            bg: '#07090F', card: '#0D1220', surface: '#141B2E',
                            red: '#901C1E', gold: '#FFCD05', navy: '#0B1E57',
                            cream: '#F9FAFB',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        body { background-color: #07090F; }
        .scrollbar-hide { scrollbar-width: none; -ms-overflow-style: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .card-hover { @apply transition-all duration-200; }
        .card-hover:hover { @apply -translate-y-1; }
    </style>
    @stack('styles')
</head>
<body class="antialiased text-white">
    {{-- NAV — fixed top bar with glass effect on scroll --}}
    <nav x-data="{ scrolled: false, open: false }" x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 40)"
          class="fixed top-0 left-0 right-0 z-50 transition-all duration-500 h-20"
          :class="scrolled ? 'bg-[#07090F]/95 backdrop-blur-xl border-b border-white/8 shadow-2xl shadow-black/40' : 'bg-transparent'">
        <div class="max-w-7xl mx-auto px-5 h-full flex items-center justify-between gap-4">
            <a href="/" class="flex items-center gap-3 shrink-0 group">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-10 w-auto object-contain brightness-0 invert" style="filter: brightness(0) invert(1);">
                <div class="hidden sm:block leading-tight border-l border-white/20 pl-3">
                    <div class="font-black text-white text-[11px] tracking-tight group-hover:text-kicc-gold transition-colors uppercase">Global Exhibition</div>
                    <div class="text-[9px] text-kicc-gold font-bold tracking-[0.18em] uppercase">Platform</div>
                </div>
            </a>
            <nav class="hidden md:flex items-center gap-1">
                <a href="{{ route('counties.index') }}" class="px-4 py-2 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg transition-all">Counties</a>
                <a href="{{ route('marketplace.index') }}" class="px-4 py-2 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg transition-all">Marketplace</a>
                <a href="{{ route('travel.index') }}" class="px-4 py-2 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg transition-all">Travel</a>
                <a href="{{ route('venues.index') }}" class="px-4 py-2 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg transition-all">Venues</a>
                <a href="{{ route('exhibitions.index') }}" class="px-4 py-2 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg transition-all">Events</a>
                <a href="{{ route('subscriptions.index') }}" class="px-4 py-2 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg transition-all">Plans</a>
            </nav>
            <div class="flex items-center gap-2">
                <a href="{{ route('cart.index') }}" class="relative p-2 text-white/60 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl border border-white/25 text-white hover:bg-white/10 hover:border-white/50">Sign In</a>
                <button @click="open = !open" class="md:hidden text-white/70 hover:text-white p-2">
                    <svg class="w-5 h-5" x-show="!open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg class="w-5 h-5" x-show="open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div x-show="open" x-cloak class="md:hidden absolute top-full left-0 right-0 bg-[#0D1220] border-b border-white/8 p-4 flex flex-col gap-1">
            <a href="{{ route('counties.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg">Counties</a>
            <a href="{{ route('marketplace.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg">Marketplace</a>
            <a href="{{ route('travel.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg">Travel</a>
            <a href="{{ route('venues.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg">Venues</a>
            <a href="{{ route('exhibitions.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg">Events</a>
            <a href="{{ route('login') }}" class="text-left px-4 py-3 text-sm font-semibold text-white/70 hover:text-white hover:bg-white/8 rounded-lg">Sign In</a>
        </div>
    </nav>

    <main class="min-h-screen pt-20">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="bg-[#050709] border-t border-white/8 mt-20">
        <div class="max-w-7xl mx-auto px-5 py-14 grid grid-cols-1 md:grid-cols-4 gap-10">
            <div>
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-12 w-auto object-contain mb-3 brightness-0 invert" style="filter: brightness(0) invert(1);">
                <div class="text-kicc-gold text-[10px] font-bold tracking-[0.18em] uppercase mb-3">Global Exhibition Platform</div>
                <p class="text-white/40 text-sm leading-relaxed">Africa's Premier Meeting Venue. A national icon since 1973.</p>
                <div class="mt-5 flex flex-col gap-1 text-sm text-white/40">
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
                'Dashboards' => [['Subscriptions','subscriptions.index'],['Screens','screens.directory'],['Operations','operations.index']],
                'Information' => [['About KICC','home'],['Contact Us','home']],
            ]; @endphp
            @foreach($footerLinks as $title => $links)
            <div>
                <h4 class="font-bold text-white/60 text-xs uppercase tracking-[0.15em] mb-4">{{ $title }}</h4>
                <ul class="space-y-2.5">
                    @foreach($links as $link)
                    <li><a href="{{ route($link[1]) }}" class="text-white/40 hover:text-kicc-gold text-sm transition-colors">{{ $link[0] }}</a></li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>
        <div class="border-t border-white/5 py-5">
            <div class="max-w-7xl mx-auto px-5 flex flex-col md:flex-row justify-between items-center gap-2 text-white/25 text-xs">
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
</body>
</html>