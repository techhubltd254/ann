@extends('layouts.blank')

@section('title', 'National Government Portal — KICC')

@section('content')
@php $accent = '#1890D7'; @endphp
<div class="flex min-h-screen bg-[#F9FAFB]">
    {{-- Sidebar --}}
    <div class="w-56 bg-white border-r border-gray-200 flex flex-col shrink-0 min-h-screen">
        <div class="flex items-center gap-3 px-4 border-b border-gray-200 h-16">
            <a href="{{ route('admin.portal') }}" class="text-gray-400 hover:text-gray-700 text-xs">&larr; Portal</a>
            <div class="flex items-center gap-2">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-7 w-auto" style="filter: brightness(0) invert(1);">
                <div style="color: {{ $accent }}" class="text-[9px] font-black tracking-[0.15em] uppercase">National<br>Portal</div>
            </div>
        </div>
        <div class="flex-1 py-3 overflow-y-auto">
            @foreach($navItems as $item)
            <a href="{{ route('national.admin', ['tab' => $item['tab']]) }}"
               class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all {{ $tab === $item['tab'] ? 'text-gray-900 border-r-2' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800' }}"
               style="{{ $tab === $item['tab'] ? 'background: '.$accent.'33; border-color: '.$accent : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                <span class="truncate text-xs">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </div>
        <a href="/" class="flex items-center gap-3 px-4 py-4 border-t border-gray-200 text-gray-400 hover:text-gray-700 text-xs transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Exit</span>
        </a>
    </div>

    {{-- Main --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-white border-b border-gray-200 px-6 h-16 flex items-center justify-between shrink-0">
            <div>
                <div class="font-black text-gray-900 text-sm">National Government of Kenya</div>
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: {{ $accent }}">National Exhibitor — Ministries &amp; Agencies</div>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6">

            @if($tab === 'overview')
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['ministries'] }}</div><div class="text-xs text-gray-400 mt-1">Ministries</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['agencies'] }}</div><div class="text-xs text-gray-400 mt-1">Agencies</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['sectors'] }}</div><div class="text-xs text-gray-400 mt-1">Economic Sectors</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['counties'] }}</div><div class="text-xs text-gray-400 mt-1">Counties</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['exhibitors'] }}</div><div class="text-xs text-gray-400 mt-1">Private Exhibitors</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['products'] }}</div><div class="text-xs text-gray-400 mt-1">Products Live</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $stats['orders'] }}</div><div class="text-xs text-gray-400 mt-1">Orders</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black" style="color: {{ $accent }}">KES {{ number_format($stats['tradeVolume']) }}</div><div class="text-xs text-gray-400 mt-1">National Trade Volume</div></div>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-3">National exhibition mandate</h3>
                <p class="text-sm text-gray-500 leading-relaxed">The National Government exhibits through its ministries and agencies. Each ministry operates an independent public website under the national pavilion, while this portal provides the consolidated view of the entire country's trade, counties and exhibitors on the KICC platform.</p>
            </div>
            @endif

            @if($tab === 'ministries')
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900">Ministries ({{ $ministries->count() }})</h3>
                <button @click="$el.nextElementSibling.classList.toggle('hidden')" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-[#046bd2] text-white">+ Add Ministry</button>
            </div>
            <form method="POST" action="{{ route('national.admin.ministry.store') }}" class="hidden bg-white border border-gray-200 rounded-2xl p-4 mb-4 space-y-2">@csrf
                <div class="grid sm:grid-cols-2 gap-2"><input type="text" name="name" required placeholder="Ministry name" class="h-9 px-3 rounded-lg border border-gray-200 text-sm"><input type="text" name="code" placeholder="Code" class="h-9 px-3 rounded-lg border border-gray-200 text-sm"></div>
                <input type="text" name="description" placeholder="Description" class="w-full h-9 px-3 rounded-lg border border-gray-200 text-sm">
                <div class="grid sm:grid-cols-2 gap-2"><input type="url" name="website" placeholder="Website" class="h-9 px-3 rounded-lg border border-gray-200 text-sm"><input type="email" name="contact_email" placeholder="Email" class="h-9 px-3 rounded-lg border border-gray-200 text-sm"></div>
                <button class="h-9 px-4 rounded-lg bg-[#046bd2] text-white text-xs font-bold">Create Ministry</button>
            </form>
            <div class="grid md:grid-cols-2 gap-4">
                @foreach($ministries as $m)
                <div class="bg-white border border-gray-200 rounded-2xl p-6">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-900 font-black text-xs" style="background: {{ $m->color ?: '#1890D7' }}">{{ $m->code }}</div>
                        <div class="flex gap-2">
                            <button @click="$el.nextElementSibling.classList.toggle('hidden')" class="text-[10px] font-bold px-2.5 py-1 rounded-full border border-gray-200 text-gray-500 hover:text-gray-900">✏️</button>
                            <a href="{{ route('national.admin.ministry.delete', $m->id) }}" class="text-[10px] font-bold px-2.5 py-1 rounded-full border border-red-200 text-red-500 hover:bg-red-50" onclick="return confirm('Delete {{ $m->name }}?')">×</a>
                            <a href="{{ route('national.site', $m->slug) }}" class="text-[10px] font-bold px-2.5 py-1 rounded-full border border-gray-200 text-gray-500 hover:text-gray-900">WEBSITE &nearr;</a>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('national.admin.ministry.update', $m->id) }}" class="hidden space-y-2 mb-3">@csrf
                        <input type="text" name="name" value="{{ $m->name }}" class="w-full h-9 px-3 rounded-lg border border-gray-200 text-sm">
                        <input type="text" name="code" value="{{ $m->code }}" class="w-full h-9 px-3 rounded-lg border border-gray-200 text-sm">
                        <input type="text" name="description" value="{{ $m->description }}" class="w-full h-9 px-3 rounded-lg border border-gray-200 text-sm">
                        <button class="h-8 px-4 rounded-lg bg-[#046bd2] text-white text-xs font-bold">Save</button>
                    </form>
                    <div class="font-bold text-gray-900 mb-1">{{ $m->name }}</div>
                    <p class="text-xs text-gray-500 leading-relaxed mb-2">{{ \Illuminate\Support\Str::limit($m->description, 140) }}</p>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">{{ $m->agencies->count() }} agencies</div>
                </div>
                @endforeach
            </div>
            @endif

            @if($tab === 'agencies')
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-900">Agencies ({{ $agencies->count() }})</h3>
                <button @click="$el.nextElementSibling.classList.toggle('hidden')" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-[#046bd2] text-white">+ Add Agency</button>
            </div>
            <form method="POST" action="{{ route('national.admin.agency.store') }}" class="hidden bg-white border border-gray-200 rounded-2xl p-4 mb-4 space-y-2">@csrf
                <div class="grid sm:grid-cols-2 gap-2">
                    <select name="ministry_id" required class="h-9 px-3 rounded-lg border border-gray-200 text-sm">@foreach($ministries as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select>
                    <input type="text" name="name" required placeholder="Agency name" class="h-9 px-3 rounded-lg border border-gray-200 text-sm">
                </div>
                <button class="h-9 px-4 rounded-lg bg-[#046bd2] text-white text-xs font-bold">Create Agency</button>
            </form>
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">Government Agencies ({{ $agencies->count() }})</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                            <th class="pb-3 pr-4">Code</th><th class="pb-3 pr-4">Agency</th><th class="pb-3 pr-4">Parent Ministry</th><th class="pb-3">Actions</th>
                        </tr></thead>
                        <tbody>
                        @foreach($agencies as $a)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4"><span class="text-[10px] font-black px-2 py-1 rounded-md text-gray-900" style="background: {{ $a->ministry?->color ?: '#1890D7' }}">{{ $a->code }}</span></td>
                            <td class="py-2.5 pr-4 font-semibold text-gray-900">{{ $a->name }}</td>
                            <td class="py-2.5 pr-4 text-gray-500 text-xs">{{ $a->ministry?->name }}</td>
                            <td class="py-2.5">
                                <button @click="$el.nextElementSibling.classList.toggle('hidden')" class="text-[10px] font-bold px-2 py-1 rounded border border-gray-200 hover:bg-gray-50">✏️</button>
                                <a href="{{ route('national.admin.agency.delete', $a->id) }}" class="text-[10px] font-bold px-2 py-1 rounded border border-red-200 text-red-500 hover:bg-red-50" onclick="return confirm('Delete {{ $a->name }}?')">×</a>
                                <form method="POST" action="{{ route('national.admin.agency.update', $a->id) }}" class="hidden mt-2 flex gap-2">@csrf
                                    <input type="hidden" name="ministry_id" value="{{ $a->ministry_id }}">
                                    <input type="text" name="name" value="{{ $a->name }}" class="flex-1 h-8 px-3 rounded-lg border border-gray-200 text-xs">
                                    <button class="h-8 px-3 rounded-lg bg-[#046bd2] text-white text-[10px] font-bold">Save</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($tab === 'trade')
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">National Trade Picture</h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="border border-gray-100 rounded-xl p-4"><div class="text-xl font-black text-gray-900">{{ $stats['products'] }}</div><div class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Products (47 counties)</div></div>
                    <div class="border border-gray-100 rounded-xl p-4"><div class="text-xl font-black text-gray-900">{{ $stats['orders'] }}</div><div class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Total Orders</div></div>
                    <div class="border border-gray-100 rounded-xl p-4"><div class="text-xl font-black text-gray-900">{{ $stats['exhibitors'] }}</div><div class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Exhibitors</div></div>
                    <div class="border border-gray-100 rounded-xl p-4"><div class="text-xl font-black" style="color: {{ $accent }}">KES {{ number_format($stats['tradeVolume']) }}</div><div class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Escrow Volume</div></div>
                </div>
                <p class="text-xs text-gray-400 mt-6">Aggregated live from all county trade boards and private exhibitors on the KICC platform.</p>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
