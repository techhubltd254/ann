<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name')) - KICC</title>
    <meta name="description" content="@yield('description', 'Kenya International Convention Centre - Exhibition & Booking Platform')">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        /* KICC brand system — amber scale is remapped to KICC crimson so all
           existing amber-* classes inherit the brand automatically. */
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        amber: {
                            50: '#FBEBEC', 100: '#F6D7D9', 200: '#EEAFB3', 300: '#E1868C',
                            400: '#CE4B54', 500: '#B0212A', 600: '#981C24', 700: '#7D161D',
                            800: '#611117', 900: '#460C10',
                        },
                        gold: {
                            50: '#FEF7E6', 100: '#FDEDC2', 200: '#FBDD8A', 300: '#F8C94D',
                            400: '#F5B81F', 500: '#F0A500', 600: '#C78900', 700: '#9E6D00',
                            800: '#755100', 900: '#4C3500',
                        },
                        charcoal: '#17151A',
                        cream: '#FAF7F2',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@2.0.2/dist/lottie-player.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .scrollbar-hide { scrollbar-width: none; -ms-overflow-style: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .btn-amber { @apply bg-amber-500 text-white px-6 py-2.5 rounded-xl font-semibold hover:bg-amber-600 shadow-lg shadow-amber-500/25 transition-all; }
        .btn-outline { @apply bg-white/10 backdrop-blur-sm text-white px-6 py-2.5 rounded-xl font-semibold border border-white/20 hover:bg-white/20 transition-all; }
        body { background-color: #f8fafc; }
        lottie-player { display: inline-block; width: 1em; height: 1em; vertical-align: middle; }
    </style>
    {{-- media() and lottie() helpers live in app/Support/helpers.php (composer-autoloaded) --}}
    @stack('styles')
</head>
<body class="text-gray-900 antialiased flex flex-col min-h-screen bg-cream">
    <nav class="bg-white/85 backdrop-blur-lg border-b border-gray-200/60 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <a href="/" class="flex items-center gap-3">
                    <img src="{{ media('kicc/logo.png') }}" alt="KICC" class="h-10 w-auto object-contain">
                    <div class="border-l border-gray-200 pl-3">
                        <span class="text-sm font-extrabold text-amber-600 leading-none tracking-tight">KICC</span>
                        <span class="text-[10px] text-gray-500 block leading-tight tracking-wide">Kenyatta International Convention Centre</span>
                    </div>
                </a>
                <div class="flex items-center gap-1">
                    <a href="{{ route('counties.index') }}" class="text-gray-600 hover:text-amber-600 hover:bg-amber-50 px-3 py-2 rounded-lg text-sm font-medium transition-all">Counties</a>
                    <a href="{{ route('marketplace.index') }}" class="text-gray-600 hover:text-amber-600 hover:bg-amber-50 px-3 py-2 rounded-lg text-sm font-medium transition-all">Marketplace</a>
                    <a href="{{ route('travel.index') }}" class="text-gray-600 hover:text-amber-600 hover:bg-amber-50 px-3 py-2 rounded-lg text-sm font-medium transition-all">Travel</a>
                    <a href="{{ route('operations.index') }}" class="text-gray-600 hover:text-amber-600 hover:bg-amber-50 px-3 py-2 rounded-lg text-sm font-medium transition-all">Operations</a>
                    <a href="{{ route('exhibitions.index') }}" class="text-gray-600 hover:text-amber-600 hover:bg-amber-50 px-3 py-2 rounded-lg text-sm font-medium transition-all">Exhibitions</a>
                    <a href="{{ route('venues.index') }}" class="text-gray-600 hover:text-amber-600 hover:bg-amber-50 px-3 py-2 rounded-lg text-sm font-medium transition-all">Venues</a>
                    <a href="{{ route('screens.directory') }}" class="text-gray-600 hover:text-amber-600 hover:bg-amber-50 px-3 py-2 rounded-lg text-sm font-medium transition-all">Screens</a>
                    @auth
                    <a href="{{ route('dashboard.index') }}" class="text-gray-600 hover:text-amber-600 hover:bg-amber-50 px-3 py-2 rounded-lg text-sm font-medium transition-all">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-amber-600 hover:bg-amber-50 px-3 py-2 rounded-lg text-sm font-medium transition-all">Logout</button>
                    </form>
                    @else
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-amber-600 hover:bg-amber-50 px-3 py-2 rounded-lg text-sm font-medium transition-all">Login</a>
                    <a href="{{ route('register') }}" class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-amber-600 shadow-lg shadow-amber-500/25 transition-all ml-2">Register</a>
                    @endauth
                    <a href="{{ url('/admin') }}" class="text-gray-400 hover:text-gray-600 px-3 py-2 rounded-lg text-sm font-medium transition-all ml-2">Admin</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="bg-charcoal text-gray-400 pt-16 pb-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 pb-12 border-b border-white/10">
                <div class="col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2.5 mb-4">
                        <span class="text-xl font-extrabold text-white tracking-tight">K<span class="text-amber-500">I</span>CC</span>
                        <span class="text-[10px] uppercase tracking-widest text-gold-400 border border-gold-400/30 rounded-full px-2 py-0.5">Since 1973</span>
                    </div>
                    <p class="text-sm leading-relaxed text-gray-500">A national icon — Africa's premier meetings and exhibitions destination, connecting business across all 47 counties of Kenya.</p>
                </div>
                <div>
                    <h3 class="text-white font-semibold text-sm uppercase tracking-wider mb-4">Explore</h3>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('counties.index') }}" class="text-gray-400 hover:text-gold-400 transition-colors">Counties</a></li>
                        <li><a href="{{ route('exhibitions.index') }}" class="text-gray-400 hover:text-gold-400 transition-colors">Exhibitions</a></li>
                        <li><a href="{{ route('venues.index') }}" class="text-gray-400 hover:text-gold-400 transition-colors">Venues</a></li>
                        <li><a href="{{ route('screens.directory') }}" class="text-gray-400 hover:text-gold-400 transition-colors">Screens</a></li>
                        <li><a href="{{ route('exhibition-3d.map') }}" class="text-gray-400 hover:text-gold-400 transition-colors">3D Map</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-semibold text-sm uppercase tracking-wider mb-4">Account</h3>
                    <ul class="space-y-2.5 text-sm">
                        @auth
                        <li><a href="{{ route('dashboard.index') }}" class="text-gray-400 hover:text-gold-400 transition-colors">Dashboard</a></li>
                        <li><a href="{{ route('dashboard.bookings') }}" class="text-gray-400 hover:text-gold-400 transition-colors">Bookings</a></li>
                        @else
                        <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-gold-400 transition-colors">Login</a></li>
                        <li><a href="{{ route('register') }}" class="text-gray-400 hover:text-gold-400 transition-colors">Register</a></li>
                        @endauth
                        <li><a href="{{ url('/admin') }}" class="text-gray-400 hover:text-gold-400 transition-colors">Admin</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-white font-semibold text-sm uppercase tracking-wider mb-4">Contact</h3>
                    <ul class="space-y-2.5 text-sm">
                        <li class="text-gray-400">Harambee Avenue, Nairobi</li>
                        <li class="text-gray-400">info@kicc.co.ke</li>
                        <li class="text-gray-400">(+254) 20 3261000</li>
                    </ul>
                </div>
            </div>
            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-600">
                <span>&copy; {{ date('Y') }} Kenyatta International Convention Centre. All rights reserved.</span>
                <span class="text-gray-700">Africa's Premier Meeting Venue</span>
            </div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
