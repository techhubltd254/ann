<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>@yield('title', 'KICC') - Global Exhibition Platform</title>
    <meta name="description" content="@yield('description', "Africa's Premier Meeting Venue. A national icon since 1973.")">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'KICC') - Global Exhibition Platform">
    <meta property="og:description" content="@yield('description', "Africa's Premier Meeting Venue. A national icon since 1973.")">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', media('kicc/kicc-logo.png'))">
    <meta property="og:site_name" content="KICC Global Exhibition Platform">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#901C1E">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    {{-- GSAP + ScrollTrigger for scroll-driven cinematic experiences --}}
    <script src="{{ asset('js/gsap.min.js') }}"></script>
    <script src="{{ asset('js/ScrollTrigger.min.js') }}"></script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "Kenyatta International Convention Centre",
        "url": "https://kicctest.org",
        "logo": "https://kicc-r2-media.techhubltd254.workers.dev/storage/kicc/kicc-logo.png",
        "description": "Africa's Premier Meeting Venue. A national icon since 1973."
    }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('js/theme.js') }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js"></script>
    <script defer src="{{ asset('js/media-tile.js') }}?v={{ filemtime(public_path('js/media-tile.js')) }}"></script>
    <script defer src="{{ asset('js/alpine-data.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/colors.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        body { background-color: #F9FAFB; color: #111827; scroll-behavior: smooth; }
        #kicc-3d-bg { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 0; pointer-events: none; }
        .three-video-container { position: relative; z-index: 1; background: transparent; }
        .three-video-container canvas { display: block; width: 100% !important; height: 100% !important; }
        .scrollbar-hide { scrollbar-width: none; -ms-overflow-style: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        [x-cloak] { display: none !important; }
        @keyframes pulse-live { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }

        /*  NEWS TICKER (CNN-style county description crawl)  */
        .ticker-track {
            display: inline-block;
            animation: ticker-scroll 90s linear infinite;
            will-change: transform;
        }
        .ticker-track:hover {
            animation-play-state: paused;
        }
        @keyframes ticker-scroll {
            0%   { transform: translateX(100vw); }
            100% { transform: translateX(-100%); }
        }

        /*  FLOATING STATS BAR (0.75 speed — slower crawl)  */
        .floating-stats {
            display: inline-block;
            animation: stats-scroll 120s linear infinite;
            will-change: transform;
        }
        @keyframes stats-scroll {
            0%   { transform: translateX(100vw); }
            100% { transform: translateX(-100%); }
        }

        /*  SCROLL REVEAL  */
        .reveal-init { opacity: 0; transform: translateY(28px); transition: opacity 0.7s cubic-bezier(0.22,1,0.36,1), transform 0.7s cubic-bezier(0.22,1,0.36,1); will-change: opacity, transform; }
        .reveal-init.revealed { opacity: 1; transform: translateY(0); }
        .reveal-init[data-reveal="left"] { transform: translateX(-36px); }
        .reveal-init[data-reveal="right"] { transform: translateX(36px); }
        .reveal-init[data-reveal="zoom"] { transform: scale(0.92); }
        .reveal-init[data-reveal="left"].revealed,
        .reveal-init[data-reveal="right"].revealed,
        .reveal-init[data-reveal="zoom"].revealed { transform: none; }

        .card-hover { transition: transform 0.25s cubic-bezier(0.22,1,0.36,1), box-shadow 0.25s, border-color 0.25s; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(11,30,87,0.08); }

        [data-tilt] { position: relative; transform-style: preserve-3d; }
        .tilt-glare { position: absolute; inset: 0; border-radius: inherit; pointer-events: none; }

        /*  MARQUEE  */
        @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .animate-marquee { animation: marquee 30s linear infinite; }
        .marquee-paused:hover .animate-marquee { animation-play-state: paused; }

        /*  FLOAT  */
        @keyframes float-slow { 0%,100% { transform: translateY(0) translateX(0); } 50% { transform: translateY(-30px) translateX(20px); } }
        @keyframes float-slower { 0%,100% { transform: translateY(0) translateX(0); } 50% { transform: translateY(25px) translateX(-25px); } }
        .animate-float-slow { animation: float-slow 9s ease-in-out infinite; }
        .animate-float-slower { animation: float-slower 13s ease-in-out infinite; }

        /*  SHIMMER  */
        @keyframes shimmer { 0% { background-position: -200% center; } 100% { background-position: 200% center; } }
        .text-shimmer {
            background: linear-gradient(110deg, #FFCD05 25%, #0EA5E9 40%, #FFCD05 55%);
            background-size: 200% auto;
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 4s linear infinite;
        }

        @keyframes pulse-glow { 0%,100% { box-shadow: 0 0 0 0 rgba(255,205,5,0.35); } 50% { box-shadow: 0 0 30px 6px rgba(255,205,5,0.15); } }
        .animate-pulse-glow { animation: pulse-glow 3.2s ease-in-out infinite; }

        /*  SECTION TRANSITIONS  */
        .kicc-section-hidden {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s cubic-bezier(0.22,1,0.36,1),
                        transform 0.8s cubic-bezier(0.22,1,0.36,1);
        }
        .kicc-section-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /*  TEXT SPLIT REVEAL  */
        .kicc-split-word {
            display: inline-block;
            opacity: 0;
            transform: translateY(12px) rotateX(15deg);
            transition: opacity 0.5s cubic-bezier(0.22,1,0.36,1),
                        transform 0.5s cubic-bezier(0.22,1,0.36,1);
        }
        .kicc-split-visible {
            opacity: 1;
            transform: translateY(0) rotateX(0);
        }

        /*  CINEMATIC INTRO EMBLEM  */
        .emblem-outer-ring {
            stroke-dasharray: 380;
            stroke-dashoffset: 380;
            animation: emblem-draw 2s cubic-bezier(0.22,1,0.36,1) forwards 0.3s;
        }
        @keyframes emblem-draw {
            to { stroke-dashoffset: 0; }
        }

        /*  HERO ENTRANCE  */
        .hero-entrance {
            opacity: 0;
            transform: translateY(30px) scale(0.98);
            animation: hero-in 1s cubic-bezier(0.22,1,0.36,1) forwards;
        }
        .hero-entrance:nth-child(1) { animation-delay: 0.2s; }
        .hero-entrance:nth-child(2) { animation-delay: 0.4s; }
        .hero-entrance:nth-child(3) { animation-delay: 0.6s; }
        .hero-entrance:nth-child(4) { animation-delay: 0.8s; }

        @keyframes hero-in {
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /*  REDUCED MOTION  */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
            .reveal-init { opacity: 1; transform: none; }
            .kicc-section-hidden { opacity: 1; transform: none; }
            .kicc-split-word { opacity: 1; transform: none; }
        }

        /*  GLASSMORPHISM & GRADIENT SYSTEM (pipeline design language)  */

        /* Smooth surface gradients */
        .grad-surface {
            background: linear-gradient(135deg, #ffffff 0%, #f6f8fc 55%, #eef2fa 100%);
        }
        .grad-surface-dark {
            background: linear-gradient(135deg, #0d1220 0%, #141b2e 55%, #1a2337 100%);
        }
        .grad-accent {
            background: linear-gradient(135deg, #FFCD05 0%, #F59E0B 50%, #F97316 100%);
        }
        .grad-cta {
            background: linear-gradient(135deg, #901C1E 0%, #7b1618 100%);
        }

        /* Progressive blur frame — 0 → 90px masked by a smooth gradient.
           Light mode: white fill; dark mode: deep black for premium glass. */
        .glass-frame {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            backdrop-filter: blur(0px) saturate(1.4);
            -webkit-backdrop-filter: blur(0px) saturate(1.4);
            background:
                linear-gradient(to bottom,
                    rgba(255, 255, 255, 0.0) 0%,
                    rgba(255, 255, 255, 0.06) 30%,
                    rgba(255, 255, 255, 0.22) 60%,
                    rgba(255, 255, 255, 0.38) 100%);
            -webkit-mask-image: linear-gradient(to bottom, rgba(0,0,0,0) 0%, #000 45%);
            mask-image: linear-gradient(to bottom, rgba(0,0,0,0) 0%, #000 45%);
            animation: glass-fade 1.2s cubic-bezier(0.22,1,0.36,1) 0.4s forwards;
            opacity: 0;
        }
        @keyframes glass-fade {
            from { opacity: 0; backdrop-filter: blur(0px); }
            to   { opacity: 1; backdrop-filter: blur(16px) saturate(1.4); }
        }

        /* Dark-mode deep-black glass */
        .glass-frame-dark {
            background:
                linear-gradient(to bottom,
                    rgba(0, 0, 0, 0.0) 0%,
                    rgba(0, 0, 0, 0.35) 35%,
                    rgba(0, 0, 0, 0.75) 70%,
                    rgba(0, 0, 0, 0.92) 100%);
        }

        /* Reusable glass card (light) */
        .glass-card {
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(18px) saturate(1.5);
            -webkit-backdrop-filter: blur(18px) saturate(1.5);
            border: 1px solid rgba(255, 255, 255, 0.55);
            box-shadow: 0 8px 32px rgba(13, 18, 32, 0.08);
        }

        /* Reusable glass card (dark / over media) */
        .glass-card-dark {
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(18px) saturate(1.3);
            -webkit-backdrop-filter: blur(18px) saturate(1.3);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        /* UX LAW: Fitts's Law — minimum interactive target */
        .hit-target { min-height: 44px; min-width: 44px; }

        /* UX LAW: Hick's Law — recommended option ring */
        .opt-recommended {
            border-color: rgba(144, 28, 30, 0.55);
            background: linear-gradient(135deg, rgba(144,28,30,0.06), rgba(255,205,5,0.08));
        }

        /*  KICC BRAND DESIGN TOKENS (sourced from kicc.co.ke)  */
        :root {
            --kicc-navy: #0B1E57;
            --kicc-navy-light: #1a3070;
            --kicc-red: #901C1E;
            --kicc-red-light: #b71c1c;
            --kicc-crimson: #A6192E;
            --kicc-crimson-dark: #7A1122;
            --kicc-gold: #FFCD05;
            --kicc-gold-soft: #FFD966;
            --kicc-gold-light: #ffe44d;
            --kicc-dark: #0A1024;
            --kicc-ivory: #FAF7F2;
            --kicc-text: #5A6480;
            --kicc-text-light: #8a94a6;
            --kicc-bg: #F9FAFB;
            --kicc-bg-alt: #f0f2f5;
            --kicc-border: #E5E7EB;
            --kicc-success: #059669;
            --kicc-warning: #D97706;
            --kicc-error: #DC2626;
            --kicc-info: #0284C7;
            --focus-ring: 0 0 0 3px rgba(144, 28, 30, 0.35);
            --shadow-sm: 0 1px 2px rgba(11, 30, 87, 0.06);
            --shadow-md: 0 4px 12px rgba(11, 30, 87, 0.08);
            --shadow-lg: 0 8px 32px rgba(11, 30, 87, 0.12);
            --radius-sm: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
        }

        /*  SKELETON LOADERS  */
        .skeleton { background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%); background-size: 200% 100%; animation: skeleton-shimmer 1.5s ease infinite; border-radius: var(--radius-sm); }
        .skeleton-dark { background: linear-gradient(90deg, rgba(255,255,255,0.06) 25%, rgba(255,255,255,0.12) 50%, rgba(255,255,255,0.06) 75%); background-size: 200% 100%; animation: skeleton-shimmer 1.5s ease infinite; }
        .skeleton-text { height: 0.875rem; margin-bottom: 0.5rem; width: 80%; }
        .skeleton-title { height: 1.25rem; margin-bottom: 0.75rem; width: 60%; }
        .skeleton-avatar { width: 2.5rem; height: 2.5rem; border-radius: 9999px; }
        .skeleton-card { height: 12rem; border-radius: var(--radius-lg); }
        .skeleton-image { aspect-ratio: 4/3; border-radius: var(--radius-md); }
        @keyframes skeleton-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        /*  FOCUS / ACCESSIBILITY  */
        *:focus-visible { outline: none; box-shadow: var(--focus-ring); border-radius: var(--radius-sm); }
        a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible { box-shadow: var(--focus-ring); }
        .skip-link { position: absolute; top: -100%; left: 1rem; padding: 0.5rem 1rem; background: var(--kicc-navy); color: white; z-index: 10000; border-radius: var(--radius-sm); font-weight: 600; transition: top 0.2s; }
        .skip-link:focus { top: 0.5rem; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; } }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border-width: 0; }

        /*  KICC SEMANTIC BUTTONS  */
        .btn-kicc { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; font-weight: 700; font-size: 0.8125rem; padding: 0.625rem 1.25rem; border-radius: var(--radius-md); transition: all 0.2s; cursor: pointer; border: none; min-height: 44px; min-width: 44px; }
        .btn-kicc-primary { background: var(--kicc-red); color: white; box-shadow: var(--shadow-sm); }
        .btn-kicc-primary:hover { background: var(--kicc-red-light); box-shadow: var(--shadow-md); transform: translateY(-1px); }
        .btn-kicc-gold { background: var(--kicc-gold); color: var(--kicc-dark); box-shadow: var(--shadow-sm); }
        .btn-kicc-gold:hover { background: var(--kicc-gold-light); box-shadow: var(--shadow-md); transform: translateY(-1px); }
        .btn-kicc-outline { background: transparent; color: var(--kicc-navy); border: 1.5px solid var(--kicc-border); }
        .btn-kicc-outline:hover { border-color: var(--kicc-red); color: var(--kicc-red); background: rgba(144,28,30,0.04); }
        .btn-kicc-ghost { background: transparent; color: var(--kicc-text); border: none; }
        .btn-kicc-ghost:hover { background: var(--kicc-bg-alt); color: var(--kicc-dark); }

        /*  KICC CARD VARIANTS  */
        .card-kicc { background: white; border: 1px solid var(--kicc-border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); transition: all 0.25s; }
        .card-kicc:hover { box-shadow: var(--shadow-md); border-color: rgba(144,28,30,0.2); }
        .card-kicc-flush { border-radius: var(--radius-lg); overflow: hidden; }
        .card-kicc-glass { background: rgba(255,255,255,0.72); backdrop-filter: blur(18px) saturate(1.5); border: 1px solid rgba(255,255,255,0.55); box-shadow: var(--shadow-md); }

        /*  KICC BADGES  */
        .badge-kicc { display: inline-flex; align-items: center; padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .badge-kicc-red { background: rgba(144,28,30,0.1); color: var(--kicc-red); border: 1px solid rgba(144,28,30,0.2); }
        .badge-kicc-gold { background: rgba(255,205,5,0.15); color: #8B6914; border: 1px solid rgba(255,205,5,0.3); }
        .badge-kicc-green { background: rgba(5,150,105,0.1); color: var(--kicc-success); border: 1px solid rgba(5,150,105,0.2); }
        .badge-kicc-blue { background: rgba(2,132,199,0.1); color: var(--kicc-info); border: 1px solid rgba(2,132,199,0.2); }

        /*  STATS / KPI  */
        .kpi-kicc { padding: 1.25rem; border-radius: var(--radius-lg); background: white; border: 1px solid var(--kicc-border); box-shadow: var(--shadow-sm); }
        .kpi-kicc-value { font-size: 1.75rem; font-weight: 900; color: var(--kicc-navy); line-height: 1.1; }
        .kpi-kicc-label { font-size: 0.75rem; font-weight: 600; color: var(--kicc-text); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem; }

        /*  FORM ELEMENTS  */
        .input-kicc { width: 100%; padding: 0.625rem 0.875rem; border: 1.5px solid var(--kicc-border); border-radius: var(--radius-md); font-size: 0.875rem; color: var(--kicc-dark); background: white; transition: border-color 0.2s, box-shadow 0.2s; min-height: 44px; }
        .input-kicc:focus { border-color: var(--kicc-red); box-shadow: 0 0 0 3px rgba(144,28,30,0.12); outline: none; }
        .input-kicc::placeholder { color: var(--kicc-text-light); }
        .label-kicc { display: block; font-size: 0.75rem; font-weight: 600; color: var(--kicc-text); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.375rem; }

        /*  TABLES  */
        .table-kicc { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
        .table-kicc th { text-align: left; padding: 0.75rem 1rem; font-weight: 600; color: var(--kicc-text); text-transform: uppercase; font-size: 0.6875rem; letter-spacing: 0.05em; border-bottom: 1px solid var(--kicc-border); background: var(--kicc-bg); }
        .table-kicc td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--kicc-border); color: var(--kicc-dark); }
        .table-kicc tr:hover td { background: rgba(144,28,30,0.02); }
        .table-kicc-wrap { overflow-x: auto; border-radius: var(--radius-lg); border: 1px solid var(--kicc-border); }

        /*  RESPONSIVE UTILITIES  */
        @media (max-width: 640px) {
            .nav-link { padding: 0.625rem 0.75rem; font-size: 0.75rem; }
            .h1-responsive { font-size: 1.75rem !important; line-height: 1.2 !important; }
            .h2-responsive { font-size: 1.5rem !important; }
            .section-padding { padding-top: 2.5rem !important; padding-bottom: 2.5rem !important; }
            .sticky-sidebar { position: relative !important; top: auto !important; }
            .mobile-full { width: 100% !important; }
            .touch-target { min-height: 44px; min-width: 44px; }
        }
        @media (max-width: 768px) {
            .md-hidden { display: none !important; }
            .mobile-stack { flex-direction: column !important; }
            .mobile-text-center { text-align: center !important; }
        }
    </style>
    @stack('styles')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 3D is opt-in — only runs on pages with actual 3D elements.
    // This keeps THREE.js + WebGL off pages that don't need it,
    // which was blocking Alpine initialization and breaking video playback.
    var needs3d = document.querySelector('.three-video-container, .three-video-overlay');
    if (!needs3d) return;

    var script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js';
    script.onload = function() {
        // Load the 3D player + background scripts after THREE is ready
        ['three-video-player.js', 'three-background.js'].forEach(function(file) {
            var s = document.createElement('script');
            s.src = '/js/' + file;
            s.defer = false;
            document.body.appendChild(s);
        });
        // Small delay for scripts to parse, then init 3D elements
        setTimeout(function() {
            document.querySelectorAll('.three-video-container').forEach(function(el) {
                var videoUrl = el.dataset.video;
                if (videoUrl && window.Kicc3DVideoPlayer) {
                    try { new window.Kicc3DVideoPlayer({ container: el, videoUrl: videoUrl }); } catch(e) {}
                }
            });
        }, 500);
    };
    document.head.appendChild(script);
});
</script>
</head>
<body class="antialiased text-gray-900 bg-[#F9FAFB]">
    <a href="#main-content" class="skip-link" aria-label="Skip to main content">Skip to main content</a>
    <div id="kicc-3d-bg"></div>
    {{-- NAV --}}
    <nav x-data="{ scrolled: false, open: false }" x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 40)"
          class="fixed top-0 left-0 right-0 z-50 transition-all duration-500 h-20" role="navigation" aria-label="Main navigation"
          :class="scrolled ? 'bg-white backdrop-blur-xl border-b border-gray-200 shadow-lg shadow-[#0EA5E9]/5' : 'bg-transparent'">
        <div class="max-w-7xl mx-auto px-5 h-full flex items-center justify-between gap-4">
            <a href="/" class="flex items-center gap-3 shrink-0 group">
                <div class="flex items-center gap-3 touch-target.5">
                    <div class="rounded-xl bg-[#901C1E] px-2.5 py-1.5 flex items-center justify-center shadow-lg shadow-[#901C1E]/25">
                        <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-7 w-auto">
                    </div>
                    <div class="leading-tight">
                        <div class="font-black text-[#901C1E] text-sm tracking-tight group-hover:text-[#FFCD05] transition-colors uppercase">KICC</div>
                        <div class="text-[8px] text-[#FFCD05] font-bold tracking-[0.15em] uppercase leading-tight">Global Exhibition</div>
                    </div>
                </div>
            </a>
            <nav class="hidden lg:flex items-center gap-1">
                <a href="{{ route('counties.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all {{ request()->routeIs('counties.*') ? 'bg-[#901C1E] text-white' : 'text-[#901C1E] hover:text-[#FFCD05] hover:bg-gray-100' }}">Counties</a>
                <a href="{{ route('national-government.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all {{ request()->routeIs('national-government.*') ? 'bg-[#901C1E] text-white' : 'text-[#901C1E] hover:text-[#FFCD05] hover:bg-gray-100' }}">National</a>
                <a href="{{ route('marketplace.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all {{ request()->routeIs('marketplace.*') ? 'bg-[#901C1E] text-white' : 'text-[#901C1E] hover:text-[#FFCD05] hover:bg-gray-100' }}">Marketplace</a>
                <a href="{{ route('exhibitions.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all {{ request()->routeIs('exhibitions.*') ? 'bg-[#901C1E] text-white' : 'text-[#901C1E] hover:text-[#FFCD05] hover:bg-gray-100' }}">Exhibitions</a>
                <a href="{{ route('venues.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all {{ request()->routeIs('venues.*') ? 'bg-[#901C1E] text-white' : 'text-[#901C1E] hover:text-[#FFCD05] hover:bg-gray-100' }}">Venues</a>
                <a href="{{ route('streams.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all {{ request()->routeIs('streams.*') ? 'bg-[#901C1E] text-white' : 'text-[#901C1E] hover:text-[#FFCD05] hover:bg-gray-100' }}">Live Events</a>
                <a href="{{ route('national.index') }}" class="px-3.5 py-1.5 text-sm font-bold rounded-lg transition-all inline-flex items-center gap-1.5" style="background: #DC2626; color: white; animation: pulse-live 2s infinite;">
                    <span class="w-2 h-2 rounded-full bg-white"></span> LIVE
                </a>
                <a href="{{ route('screens.directory') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all {{ request()->routeIs('screens.*') ? 'bg-[#901C1E] text-white' : 'text-[#901C1E] hover:text-[#FFCD05] hover:bg-gray-100' }}">Screens</a>
                <a href="{{ route('packages.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-all {{ request()->routeIs('packages.*') ? 'bg-[#901C1E] text-white' : 'text-[#901C1E] hover:text-[#FFCD05] hover:bg-gray-100' }}">Packages</a>
            </nav>
            <div class="flex items-center gap-3 touch-target">
                <a href="{{ route('cart.index') }}" class="relative p-3 touch-target text-[#5A6480] hover:text-[#901C1E] transition-colors" aria-label="Cart">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    @if(auth()->check())
                    @php $cartBadge = \App\Models\Marketplace\ShoppingCart::where('user_id', auth()->id())->latest('id')->first(); @endphp
                    @if($cartBadge && $cartBadge->items()->count() > 0)
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-[#901C1E] text-white text-[9px] font-bold flex items-center justify-center leading-none">{{ $cartBadge->items()->sum('quantity') > 9 ? '9+' : $cartBadge->items()->sum('quantity') }}</span>
                    @endif
                    @else
                    @php $cartBadge = \App\Models\Marketplace\ShoppingCart::where('session_id', session()->getId())->latest('id')->first(); @endphp
                    @if($cartBadge && $cartBadge->items()->count() > 0)
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-[#901C1E] text-white text-[9px] font-bold flex items-center justify-center leading-none">{{ $cartBadge->items()->sum('quantity') > 9 ? '9+' : $cartBadge->items()->sum('quantity') }}</span>
                    @endif
                    @endif
                </a>
                @auth
                <a href="{{ route('dashboard.index') }}" class="inline-flex items-center gap-3 touch-target font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl bg-[#901C1E] text-gray-900 hover:bg-[#7a181a]">
                    Dashboard
                </a>
                <a href="{{ route('admin.portal') }}" class="hidden sm:inline-flex items-center gap-3 touch-target font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-100" title="Admin">
                    Admin
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">@csrf
                    <button type="submit" class="inline-flex items-center gap-3 touch-target font-bold tracking-wide transition-all duration-200 px-3 text-xs h-9 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-100">Logout</button>
                </form>
                @else
                <a href="{{ route('login') }}" class="inline-flex items-center gap-3 touch-target font-bold tracking-wide transition-all duration-200 px-4 text-xs h-9 rounded-xl bg-[#FFCD05] text-[#07090F] font-bold hover:bg-[#e6b904]">Sign In</a>
                @endauth
                <button @click="open = !open" class="lg:hidden text-[#5A6480] hover:text-[#901C1E] p-3 touch-target" aria-label="Menu">
                    <svg class="w-5 h-5" x-show="!open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg class="w-5 h-5" x-show="open" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div x-show="open" x-cloak x-transition class="lg:hidden absolute top-full left-0 right-0 bg-white border-b border-gray-100 p-4 flex flex-col gap-1">
            <a href="{{ route('counties.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">Counties</a>
            <a href="{{ route('national-government.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">National Government</a>
            <a href="{{ route('exhibitions.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">Exhibitions</a>
            <a href="{{ route('venues.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">Venues</a>
            <a href="{{ route('streams.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">Live Events</a>
            <a href="{{ route('national.index') }}" class="text-left px-4 py-3 text-sm font-semibold inline-flex items-center gap-2 text-red-600 hover:bg-red-50 rounded-lg">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span> LIVE
            </a>
            <a href="{{ route('screens.directory') }}" class="text-left px-4 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">Screens</a>
            <a href="{{ route('marketplace.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">Marketplace</a>
            @auth
            <a href="{{ route('dashboard.index') }}" class="text-left px-4 py-3 text-sm font-semibold text-kicc-gold hover:bg-gray-100 rounded-lg">Dashboard</a>
            <a href="{{ route('admin.portal') }}" class="text-left px-4 py-3 text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">Admin</a>
            @else
            <a href="{{ route('login') }}" class="text-left px-4 py-3 text-sm font-semibold text-[#5A6480] hover:text-[#901C1E] hover:bg-sky-50 rounded-lg">Sign In</a>
            @endauth
        </div>
    </nav>

    <main id="main-content" class="min-h-screen pt-20 relative z-10">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="bg-[#0B1E57] mt-20">
        <div class="max-w-7xl mx-auto px-5 py-14 grid grid-cols-1 md:grid-cols-4 gap-10">
            <div>
                <div class="flex items-center gap-3 touch-target.5 mb-3">
                    <div class="rounded-xl bg-[#901C1E] px-2.5 py-1.5 flex items-center justify-center">
                        <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-7 w-auto">
                    </div>
                    <div class="leading-tight">
                        <div class="font-black text-white text-sm tracking-tight uppercase">KICC</div>
                        <div class="text-[#FFCD05] text-[8px] font-bold tracking-[0.15em] uppercase">Global Exhibition</div>
                    </div>
                </div>
                <p class="text-white/60 text-sm leading-relaxed">Africa's Premier Meeting Venue. A national icon since 1973.</p>
                <div class="mt-5 flex flex-col gap-1 text-sm text-white/60">
                    <span class="flex items-center gap-3 touch-target">
                        <svg class="w-3.5 h-3.5 text-[#FFCD05]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        (+254) 20 3261000
                    </span>
                    <span class="flex items-center gap-3 touch-target">
                        <svg class="w-3.5 h-3.5 text-[#FFCD05]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        City Square, Nairobi CBD
                    </span>
                </div>
            </div>
            <div>
                <h4 class="font-bold text-white/50 text-xs uppercase tracking-[0.15em] mb-4">Platform</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('counties.index') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">Counties</a></li>
                    <li><a href="{{ route('marketplace.index') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">Marketplace</a></li>
                    <li><a href="{{ route('exhibitions.index') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">Exhibitions</a></li>
                    <li><a href="{{ route('venues.index') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">Venues</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white/50 text-xs uppercase tracking-[0.15em] mb-4">Dashboards</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('dashboard.index') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">My Dashboard</a></li>
                    <li><a href="{{ route('dashboard.exhibitions') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">My Exhibitions</a></li>
                    <li><a href="{{ route('dashboard.bookings') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">My Bookings</a></li>
                    <li><a href="{{ route('admin.portal') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">Admin Portal</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-white/50 text-xs uppercase tracking-[0.15em] mb-4">Information</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('streams.index') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">Live Events</a></li>
                    <li><a href="{{ route('screens.directory') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">Screens</a></li>
                    <li><a href="{{ route('exhibition-3d.map') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">3D Tour</a></li>
                    <li><a href="{{ route('operations.index') }}" class="text-white/50 hover:text-kicc-gold text-sm transition-colors">Operations</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10 py-5">
            <div class="max-w-7xl mx-auto px-5 flex flex-col md:flex-row justify-between items-center gap-3 touch-target text-white/40 text-xs">
                <span>&copy; {{ date('Y') }} Kenyatta International Convention Centre. All rights reserved.</span>
                <span class="flex items-center gap-1.5 text-white/40">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    KRA &middot; CBK &middot; KEBS Compliant
                </span>
            </div>
        </div>
    </footer>
    @stack('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@{{ config('kicc.alpine_version', '3.14.8') }}/dist/cdn.min.js"></script>
    {{-- Core motion system --}}
    <script src="{{ asset('js/animations.js') }}"></script>
    {{-- Immersive interaction engine --}}
    <script src="{{ asset('js/immersive.js') }}"></script>
</body>
</html>
