<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KICC Admin')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        kicc: { bg: '#07090F', card: '#0D1220', surface: '#141B2E', red: '#901C1E', gold: '#FFCD05', navy: '#0B1E57', emerald: '#2D6A4F' }
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        body { background-color: #07090F; color: #ffffff; }
        .scrollbar-hide { scrollbar-width: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="antialiased">
    <div class="min-h-screen flex">
        {{-- Sidebar --}}
        @auth
        <aside class="w-64 bg-[#050709] border-r border-white/8 flex flex-col shrink-0">
            <div class="p-5 border-b border-white/8">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#901C1E] rounded-xl flex items-center justify-center">
                        <span class="text-white font-black text-lg">K</span>
                    </div>
                    <div>
                        <div class="text-white font-bold text-sm">KICC Admin</div>
                        <div class="text-[#FFCD05] text-[10px] font-bold tracking-wider uppercase">Platform</div>
                    </div>
                </div>
            </div>

            @php
                $role = auth()->user()->hasRole('kicc_admin') ? 'kicc' : (auth()->user()->hasRole('national_admin') ? 'national' : 'county');
                $navItems = $navItems ?? [];
                if (empty($navItems)) {
                    if ($role === 'kicc') {
                        $navItems = [
                            ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Dashboard', 'route' => 'kicc.admin'],
                            ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'label' => 'Admin Panel', 'url' => '/admin'],
                            ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => 'National', 'route' => 'national.admin'],
                            ['icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z', 'label' => 'Counties', 'route' => 'dashboard.county'],
                        ];
                    } elseif ($role === 'national') {
                        $navItems = [
                            ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Dashboard', 'route' => 'national.admin'],
                            ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'label' => 'Admin Panel', 'url' => '/admin'],
                        ];
                    } else {
                        $navItems = [
                            ['icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'label' => 'Dashboard', 'route' => 'dashboard.county'],
                            ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'label' => 'Admin Panel', 'url' => '/admin'],
                        ];
                    }
                }
            @endphp

            <nav class="flex-1 p-3 space-y-1">
                @foreach($navItems as $item)
                <a href="{{ isset($item['url']) ? $item['url'] : (isset($item['route']) ? route($item['route']) : '#') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all
                   {{ isset($item['route']) && request()->routeIs($item['route']) ? 'bg-[#901C1E]/15 text-[#FFCD05]' : (isset($item['url']) && request()->is(trim($item['url'], '/')) ? 'bg-[#901C1E]/15 text-[#FFCD05]' : 'text-white/50 hover:text-white hover:bg-white/5') }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
                @endforeach
            </nav>

            <div class="p-3 border-t border-white/8">
                <form method="POST" action="{{ route('logout') }}" class="flex">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-white/40 hover:text-white hover:bg-white/5 w-full transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </aside>
        @endauth

        {{-- Main Content --}}
        <main class="flex-1 @auth @else min-h-screen flex items-center justify-center @endauth">
            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>
</html>