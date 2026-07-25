@extends('layouts.blank')

@section('title', 'Admin Access — KICC Platform')

@section('content')
<div class="min-h-screen flex items-center justify-center px-5 bg-[#07090F]">
    <div class="w-full max-w-2xl">
        <div class="text-center mb-10">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-16 w-auto mx-auto mb-5 brightness-0 invert" style="filter: brightness(0) invert(1);">
            <h1 class="text-3xl font-black text-white">Admin Access</h1>
            <p class="text-white/50 mt-2">Select your administration portal</p>
        </div>
        <div class="grid md:grid-cols-2 gap-6">
            <a href="{{ route('dashboard.admin') }}" class="group bg-[#0D1220] border border-white/10 rounded-2xl p-8 text-center hover:border-[#901C1E]/50 transition-all hover:-translate-y-1">
                <div class="w-20 h-20 bg-[#901C1E]/15 rounded-2xl flex items-center justify-center mx-auto mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-10 h-10 text-[#901C1E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <h2 class="text-xl font-black text-white mb-2">KICC National Admin</h2>
                <p class="text-white/40 text-sm leading-relaxed">Full platform oversight. Manage counties, users, payments, exhibitions, marketplace, content, and system-wide settings across all 47 counties.</p>
                <div class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-[#901C1E]">
                    Enter National Admin
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </div>
            </a>
            <a href="{{ route('dashboard.county') }}" class="group bg-[#0D1220] border border-white/10 rounded-2xl p-8 text-center hover:border-[#0B1E57]/50 transition-all hover:-translate-y-1">
                <div class="w-20 h-20 bg-[#0B1E57]/15 rounded-2xl flex items-center justify-center mx-auto mb-5 group-hover:scale-110 transition-transform">
                    <svg class="w-10 h-10 text-[#0B1E57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="text-xl font-black text-white mb-2">County Admin</h2>
                <p class="text-white/40 text-sm leading-relaxed">Manage your county's slots, subscribers, content, events, and revenue. Approve exhibitors, allocate booth spaces, and track settlements.</p>
                <div class="mt-6 inline-flex items-center gap-2 text-sm font-bold text-[#0B1E57]">
                    Enter County Admin
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection