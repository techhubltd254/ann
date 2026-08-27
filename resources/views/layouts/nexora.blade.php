<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Nexora Control</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/hls.js@1.5.13/dist/hls.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @stack('styles')
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        body { background: #0D0F12; color: #E2E8F0; }
        .glass { backdrop-filter: blur(12px); }
        .glass-card { background: rgba(22,25,32,0.85); border: 1px solid rgba(255,255,255,0.06); backdrop-filter: blur(12px); }
        .glass-nav { background: rgba(13,15,18,0.92); border-right: 1px solid rgba(255,255,255,0.06); backdrop-filter: blur(16px); }
        .glass-header { background: rgba(13,15,18,0.88); border-bottom: 1px solid rgba(255,255,255,0.06); backdrop-filter: blur(16px); }
        .glass-drawer { background: rgba(22,25,32,0.96); border-left: 1px solid rgba(255,255,255,0.06); backdrop-filter: blur(16px); }
        .scrollbar-hide { scrollbar-width: thin; scrollbar-color: rgba(99,102,241,0.3) transparent; }
        .scrollbar-hide::-webkit-scrollbar { width: 4px; }
        .scrollbar-hide::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-hide::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.3); border-radius: 4px; }
        .hover-scale { transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .hover-scale:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
        .skeleton { background: linear-gradient(90deg, rgba(22,25,32,0.6) 25%, rgba(30,35,50,0.8) 50%, rgba(22,25,32,0.6) 75%); background-size: 200% 100%; animation: shimmer 2s infinite; }
        @keyframes shimmer { 0% { background-position: -200% center; } 100% { background-position: 200% center; } }
        .badge { @apply text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider; }
        .sidebar-link { @apply flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all cursor-pointer; }
        .sidebar-link-active { @apply bg-indigo-500/10 text-indigo-400 border border-indigo-500/20; }
        .sidebar-link-inactive { @apply text-zinc-400 hover:text-zinc-200 hover:bg-white/5 border border-transparent; }
        .kpi-card { @apply glass-card rounded-2xl p-5 hover-scale relative overflow-hidden; }
        .progress-bar { @apply h-1.5 rounded-full bg-white/5 overflow-hidden; }
        .progress-fill { @apply h-full rounded-full transition-all; }
        input, select, textarea { background: rgba(22,25,32,0.85) !important; border: 1px solid rgba(255,255,255,0.08) !important; color: #E2E8F0 !important; border-radius: 0.75rem !important; padding: 0.5rem 0.75rem !important; font-size: 0.8125rem !important; outline: none !important; }
        input:focus, select:focus, textarea:focus { border-color: #6366F1 !important; box-shadow: 0 0 0 3px rgba(99,102,241,0.15) !important; }
        ::placeholder { color: rgba(148,163,184,0.4) !important; }
        .btn-primary { @apply inline-flex items-center justify-center gap-2 font-semibold text-sm px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 text-white hover:from-indigo-400 hover:to-violet-500 transition-all active:scale-95 border-0; }
        .btn-ghost { @apply inline-flex items-center justify-center gap-2 font-semibold text-sm px-4 py-2 rounded-xl bg-white/5 text-zinc-300 hover:bg-white/10 hover:text-white transition-all border border-white/5; }
        .btn-danger { @apply inline-flex items-center justify-center gap-2 font-semibold text-sm px-4 py-2 rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-all border border-red-500/20; }
        .btn-success { @apply inline-flex items-center justify-center gap-2 font-semibold text-sm px-4 py-2 rounded-xl bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-all border border-emerald-500/20; }
        .status-pill { @apply px-2.5 py-0.5 rounded-full text-[11px] font-semibold border; }
        .status-paid { @apply status-pill bg-emerald-500/10 text-emerald-400 border-emerald-500/20; }
        .status-pending { @apply status-pill bg-amber-500/10 text-amber-400 border-amber-500/20; }
        .status-failed { @apply status-pill bg-red-500/10 text-red-400 border-red-500/20; }
        .status-active { @apply status-pill bg-indigo-500/10 text-indigo-400 border-indigo-500/20; }
        .status-draft { @apply status-pill bg-zinc-500/10 text-zinc-400 border-zinc-500/20; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        .drawer-open { animation: slideIn 0.2s ease-out; }
        [x-cloak] { display: none !important; }
    /* Responsive touch targets */
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
</head>
<body class="antialiased">
    @yield('content')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>