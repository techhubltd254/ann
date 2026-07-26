@extends('layouts.blank')

@section('title', 'Admin Portal — KICC Platform')

@section('content')
<div class="min-h-screen bg-[#F9FAFB] flex items-center justify-center p-5">
    <div class="w-full max-w-5xl">
        <div class="text-center mb-10">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-16 w-auto mx-auto mb-4" style="filter: brightness(0) invert(0);">
            <h1 class="text-3xl font-black text-[#0B1E57]">Admin Portal</h1>
            <p class="text-[#5A6480] mt-2">Select your administrative access level</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">
            {{-- KICC Admin --}}
            <a href="/admin" class="group bg-white rounded-2xl border-2 border-[#901C1E]/20 hover:border-[#901C1E] p-8 text-center transition-all hover:shadow-xl hover:shadow-[#901C1E]/5">
                <div class="w-16 h-16 bg-[#901C1E]/10 rounded-2xl flex items-center justify-center mx-auto mb-5 group-hover:bg-[#901C1E]/20 transition-colors">
                    <svg class="w-8 h-8 text-[#901C1E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h2 class="font-black text-[#0B1E57] text-xl mb-2">KICC Admin</h2>
                <p class="text-[#5A6480] text-sm leading-relaxed">Full platform control. Manage all content, users, counties, and system settings.</p>
                <div class="mt-4 text-[#901C1E] text-xs font-bold uppercase tracking-widest">Full Access</div>
            </a>

            {{-- National Government Admin --}}
            <a href="/admin/national" class="group bg-white rounded-2xl border-2 border-[#0B1E57]/20 hover:border-[#0B1E57] p-8 text-center transition-all hover:shadow-xl hover:shadow-[#0B1E57]/5">
                <div class="w-16 h-16 bg-[#0B1E57]/10 rounded-2xl flex items-center justify-center mx-auto mb-5 group-hover:bg-[#0B1E57]/20 transition-colors">
                    <svg class="w-8 h-8 text-[#0B1E57]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <h2 class="font-black text-[#0B1E57] text-xl mb-2">National Government</h2>
                <p class="text-[#5A6480] text-sm leading-relaxed">Manage ministries, agencies, national content, and cross-county coordination.</p>
                <div class="mt-4 text-[#0B1E57] text-xs font-bold uppercase tracking-widest">Ministries & Agencies</div>
            </a>

            {{-- County Admin --}}
            <a href="/admin/county" class="group bg-white rounded-2xl border-2 border-[#FFCD05]/20 hover:border-[#FFCD05] p-8 text-center transition-all hover:shadow-xl hover:shadow-[#FFCD05]/5">
                <div class="w-16 h-16 bg-[#FFCD05]/10 rounded-2xl flex items-center justify-center mx-auto mb-5 group-hover:bg-[#FFCD05]/20 transition-colors">
                    <svg class="w-8 h-8 text-[#FFCD05]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h2 class="font-black text-[#0B1E57] text-xl mb-2">County Admin</h2>
                <p class="text-[#5A6480] text-sm leading-relaxed">Manage your county's content, products, subscribers, slots, and revenue.</p>
                <div class="mt-4 text-[#FFCD05] text-xs font-bold uppercase tracking-widest">County Scope</div>
            </a>
        </div>

        <div class="mt-8 text-center text-[#5A6480] text-xs">
            Signed in as <span class="font-semibold text-[#0B1E57]">{{ auth()->user()->name ?? 'Guest' }}</span>
            — <a href="{{ route('home') }}" class="text-[#901C1E] hover:underline">Back to site</a>
        </div>
    </div>
</div>
@endsection
