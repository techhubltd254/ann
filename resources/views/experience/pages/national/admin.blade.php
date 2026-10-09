@extends('layouts.nexora')
@section('title', 'National Government — Admin Control')
@section('content')
<div class="max-w-7xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-white mb-2">National Government Management</h1>
    <p class="text-zinc-400 text-sm mb-6">Manage ministries, agencies, national hero video, ministry media, and flag videos.</p>

    @if(session('success'))<div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>@endif

    {{-- Tab navigation --}}
    <div class="flex gap-1 mb-6 overflow-x-auto pb-2">
        @foreach($navItems as $item)
        <a href="{{ route('national.admin.v2.dashboard', ['tab' => $item['tab']]) }}"
           class="px-4 py-2 rounded-lg text-xs font-bold transition whitespace-nowrap
                  {{ $tab === $item['tab'] ? 'bg-[#0B0B0B] text-white' : 'glass-card text-zinc-400 hover:text-zinc-200 border-white/5' }}">
            {{ $item['label'] }}
        </a>
        @endforeach
    </div>

    @if($tab === 'ministries')
    {{-- MINISTRIES --}}
    <div class="glass-card rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-white">Ministries ({{ $stats['ministries'] }})</h2>
            <button onclick="document.getElementById('addMinistryForm').classList.toggle('hidden')" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-[#0B0B0B] text-white">+ Add</button>
        </div>
        <form id="addMinistryForm" method="POST" action="{{ route('national.admin.v2.ministry.store') }}" class="hidden space-y-2 mb-4 p-4 border border-white/10 rounded-xl">@csrf
            <input type="text" name="name" required placeholder="Ministry name" class="w-full h-9 px-3 rounded-lg glass-card/5 border border-white/10 text-white text-sm placeholder-zinc-500">
            <div class="grid grid-cols-2 gap-2"><input type="text" name="code" placeholder="Code (e.g. MITI)" class="h-9 px-3 rounded-lg glass-card/5 border border-white/10 text-white text-sm placeholder-zinc-500"><input type="text" name="color" placeholder="Color hex (e.g. #0B0B0B)" class="h-9 px-3 rounded-lg glass-card/5 border border-white/10 text-white text-sm placeholder-zinc-500"></div>
            <input type="url" name="website" placeholder="Website URL" class="w-full h-9 px-3 rounded-lg glass-card/5 border border-white/10 text-white text-sm placeholder-zinc-500">
            <textarea name="description" rows="2" placeholder="Description" class="w-full px-3 py-2 rounded-lg glass-card/5 border border-white/10 text-white text-sm placeholder-zinc-500"></textarea>
            <button class="h-9 px-4 rounded-lg bg-[#0B0B0B] text-white text-xs font-bold">Create</button>
        </form>
        <div class="space-y-3 max-h-[600px] overflow-y-auto">
            @foreach($ministries as $m)
            <div class="border border-white/10 rounded-xl p-4">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black text-white" style="background: {{ $m->color ?: '#0B0B0B' }}">{{ $m->code ?? substr($m->name, 0, 3) }}</div>
                        <div><div class="font-bold text-white text-sm">{{ $m->name }}</div><div class="text-xs text-zinc-500">{{ $m->agencies->count() }} agencies</div></div>
                    </div>
                    <div class="flex gap-1">
                        <button onclick="this.nextElementSibling.classList.toggle('hidden')" class="text-xs px-2 py-1 rounded border border-white/10 text-zinc-400 hover:text-white">Edit</button>
                        <form method="POST" action="{{ route('national.admin.v2.ministry.delete', $m->id) }}" style="display:inline" onsubmit="return confirm('Confirm this action?')">@csrf<button type="submit" class="text-xs px-2 py-1 rounded border border-red-500/30 text-red-400 hover:bg-red-500/10">×</button></form>
                    </div>
                </div>
                @if($m->description)<p class="text-xs text-zinc-500 mt-2">{{ Str::limit($m->description, 120) }}</p>@endif
                <form method="POST" action="{{ route('national.admin.v2.ministry.update', $m->id) }}" class="hidden mt-3 space-y-2">@csrf
                    <input type="text" name="name" value="{{ $m->name }}" class="w-full h-8 px-3 rounded-lg glass-card/5 border border-white/10 text-white text-xs">
                    <input type="text" name="code" value="{{ $m->code }}" class="w-full h-8 px-3 rounded-lg glass-card/5 border border-white/10 text-white text-xs">
                    <button class="h-8 px-3 rounded-lg bg-[#0B0B0B] text-white text-[10px] font-bold">Save</button>
                </form>
            </div>
            @endforeach
        </div>
    </div>

    @elseif($tab === 'agencies')
    {{-- AGENCIES --}}
    <div class="glass-card rounded-2xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-white">Agencies ({{ $stats['agencies'] }})</h2>
            <button onclick="document.getElementById('addAgencyForm').classList.toggle('hidden')" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-[#0B0B0B] text-white">+ Add</button>
        </div>
        <form id="addAgencyForm" method="POST" action="{{ route('national.admin.v2.agency.store') }}" class="hidden space-y-2 mb-4 p-4 border border-white/10 rounded-xl">@csrf
            <select name="ministry_id" required class="w-full h-9 px-3 rounded-lg glass-card/5 border border-white/10 text-white text-sm">@foreach($ministries as $m)<option value="{{ $m->id }}" class="bg-[#0B0B0B]">{{ $m->name }}</option>@endforeach</select>
            <input type="text" name="name" required placeholder="Agency name" class="w-full h-9 px-3 rounded-lg glass-card/5 border border-white/10 text-white text-sm placeholder-zinc-500">
            <input type="text" name="code" placeholder="Code" class="w-full h-9 px-3 rounded-lg glass-card/5 border border-white/10 text-white text-sm placeholder-zinc-500">
            <button class="h-9 px-4 rounded-lg bg-[#0B0B0B] text-white text-xs font-bold">Create</button>
        </form>
        <div class="space-y-2 max-h-[600px] overflow-y-auto">
            @foreach($agencies as $a)
            <div class="flex items-center justify-between border border-white/10 rounded-xl p-3">
                <div><span class="font-semibold text-white text-sm">{{ $a->name }}</span><div class="text-xs text-zinc-500">{{ $a->ministry?->name }}</div></div>
                <form method="POST" action="{{ route('national.admin.v2.agency.delete', $a->id) }}" style="display:inline" onsubmit="return confirm('Confirm this action?')">@csrf<button type="submit" class="text-xs px-2 py-1 rounded border border-red-500/30 text-red-400 hover:bg-red-500/10">×</button></form>
            </div>
            @endforeach
        </div>
    </div>

    @elseif($tab === 'hero')
    {{-- NATIONAL HERO VIDEO --}}
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="grid md:grid-cols-3 gap-0">
            <div class="md:col-span-2 bg-black relative min-h-[250px]">
                @if($nationalHero?->mp4Url())
                <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover">
                    <source src="{{ $nationalHero->mp4Url() }}" type="video/mp4">
                </video>
                <div class="absolute bottom-2 left-3 text-[10px] px-2 py-1 rounded bg-black/70 text-indigo-300 border border-indigo-500/30">National Hero Video Playing</div>
                @else
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto text-zinc-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <div class="text-sm font-semibold text-zinc-500">No national hero video</div>
                        <div class="text-[10px] text-zinc-600 mt-1">Upload to play on /national-government</div>
                    </div>
                </div>
                @endif
            </div>
            <div class="p-6 flex flex-col justify-center">
                <div class="text-sm font-bold text-white mb-1">National Hero Video</div>
                <div class="text-[10px] text-zinc-500 mb-4">Plays on the national government landing page</div>
                <form data-r2-upload method="POST" action="{{ route('national.admin.v2.hero.upload') }}" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <label class="flex items-center justify-center h-10 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 text-white text-xs font-bold cursor-pointer hover:from-indigo-400 hover:to-violet-500 transition active:scale-95">
                        <input type="file" name="video" accept="video/mp4,video/webm" class="sr-only" onchange="this.form.submit()">
                         Upload National Hero Video
                    </label>
                </form>
                @if($nationalHero?->mp4Url())
                <form method="POST" action="{{ route('national.admin.v2.hero.delete') }}" onsubmit="return confirm('Delete hero video?')">@csrf<button class="text-xs text-red-400 hover:text-red-300 underline">Delete</button></form>
                @endif
            </div>
        </div>
    </div>

    @elseif($tab === 'media')
    {{-- MINISTRY MEDIA --}}
    <div class="space-y-4">
        @foreach($ministries as $m)
        @php $media = $ministryMedia[$m->id] ?? ['video' => null, 'flag' => null]; @endphp
        <div class="glass-card rounded-2xl overflow-hidden">
            <div class="grid md:grid-cols-4 gap-0">
                <div class="md:col-span-2 bg-black relative min-h-[180px]">
                    @if($media['video']?->mp4Url())
                    <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover">
                        <source src="{{ $media['video']->mp4Url() }}" type="video/mp4">
                    </video>
                    <div class="absolute bottom-2 left-3 text-[10px] px-2 py-1 rounded bg-black/70 text-indigo-300 border border-indigo-500/30">Ministry Video</div>
                    @elseif($media['flag']?->mp4Url())
                    <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover">
                        <source src="{{ $media['flag']->mp4Url() }}" type="video/mp4">
                    </video>
                    <div class="absolute bottom-2 left-3 text-[10px] px-2 py-1 rounded bg-black/70 text-amber-300 border border-amber-500/30">Ministry Flag</div>
                    @else
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <div class="w-12 h-12 mx-auto rounded-xl flex items-center justify-center text-white text-lg font-black" style="background: {{ $m->color ?: '#0B0B0B' }}">{{ $m->code ?? substr($m->name, 0, 3) }}</div>
                            <div class="text-xs text-zinc-500 mt-2">No video or flag</div>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="p-5 flex flex-col justify-center border-t md:border-t-0 md:border-l border-white/10">
                    <div class="text-sm font-bold text-white mb-1">{{ $m->name }}</div>
                    <div class="text-[10px] text-zinc-500 mb-3">Upload ministry tile video or animated flag</div>
                    <form data-r2-upload method="POST" action="{{ route('national.admin.v2.ministry.video.upload', $m->id) }}" enctype="multipart/form-data" class="mb-2">
                        @csrf
                        <label class="flex items-center justify-center h-8 rounded-lg bg-[#0B0B0B]/20 text-zinc-400 text-[10px] font-bold cursor-pointer hover:bg-[#0B0B0B]/30 transition border border-[#0B0B0B]/30">
                            <input type="file" name="video" accept="video/mp4,video/webm" class="sr-only" onchange="this.form.submit()">
                             Upload Ministry Video
                        </label>
                    </form>
                    @if($media['video']?->mp4Url())
                    <form method="POST" action="{{ route('national.admin.v2.ministry.video.delete', $m->id) }}" onsubmit="return confirm('Delete ministry video?')">@csrf<button class="text-[10px] text-red-400 hover:text-red-300 underline mb-2">Delete video</button></form>
                    @endif
                    <form data-r2-upload method="POST" action="{{ route('national.admin.v2.ministry.flag.upload', $m->id) }}" enctype="multipart/form-data" class="mb-1">
                        @csrf
                        <label class="flex items-center justify-center h-8 rounded-lg bg-amber-500/10 text-amber-400 text-[10px] font-bold cursor-pointer hover:bg-amber-500/20 transition border border-amber-500/20">
                            <input type="file" name="video" accept="video/mp4,video/webm" class="sr-only" onchange="this.form.submit()">
                             Upload Ministry Flag
                        </label>
                    </form>
                    @if($media['flag']?->mp4Url())
                    <form method="POST" action="{{ route('national.admin.v2.ministry.flag.delete', $m->id) }}" onsubmit="return confirm('Delete ministry flag?')">@csrf<button class="text-[10px] text-red-400 hover:text-red-300 underline">Delete flag</button></form>
                    @endif
                </div>
                <div class="p-5 flex flex-col justify-center glass-card/5 border-t md:border-t-0 md:border-l border-white/10">
                    <div class="text-[10px] font-semibold text-zinc-400 mb-2">Fallback Chain</div>
                    <div class="text-[9px] text-zinc-500 space-y-1">
                        <div class="{{ $media['video']?->mp4Url() ? 'text-emerald-400 font-semibold' : '' }}">1. Ministry video</div>
                        <div>2. Agency videos</div>
                        <div class="{{ $media['flag']?->mp4Url() ? 'text-emerald-400 font-semibold' : '' }}">3. Ministry flag</div>
                        <div>4. National flag</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @elseif($tab === 'flag')
    {{-- NATIONAL FLAG --}}
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="grid md:grid-cols-3 gap-0">
            <div class="md:col-span-2 bg-black relative min-h-[200px]">
                @if($nationalFlag?->mp4Url())
                <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover">
                    <source src="{{ $nationalFlag->mp4Url() }}" type="video/mp4">
                </video>
                <div class="absolute bottom-2 left-3 text-[10px] px-2 py-1 rounded bg-black/70 text-amber-300 border border-amber-500/30">National Flag Playing</div>
                @else
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto text-zinc-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3v18h18M7 16l4-8 4 4 4-6"/></svg>
                        <div class="text-sm font-semibold text-zinc-500">No national flag video</div>
                        <div class="text-[10px] text-zinc-600 mt-1">Upload animated flag as last-resort fallback</div>
                    </div>
                </div>
                @endif
            </div>
            <div class="p-6 flex flex-col justify-center">
                <div class="text-sm font-bold text-white mb-1">National Animated Flag</div>
                <div class="text-[10px] text-zinc-500 mb-4">Plays on every tile when no other video is available (fallback level 5)</div>
                <form data-r2-upload method="POST" action="{{ route('national.admin.v2.flag.upload') }}" enctype="multipart/form-data" class="mb-3">
                    @csrf
                    <label class="flex items-center justify-center h-10 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white text-xs font-bold cursor-pointer hover:from-amber-400 hover:to-orange-500 transition active:scale-95">
                        <input type="file" name="video" accept="video/mp4,video/webm" class="sr-only" onchange="this.form.submit()">
                         Upload National Flag
                    </label>
                </form>
                @if($nationalFlag?->mp4Url())
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-emerald-400">Live</span>
                    <form method="POST" action="{{ route('national.admin.v2.flag.delete') }}" onsubmit="return confirm('Delete national flag?')">@csrf<button class="text-red-400 hover:text-red-300 underline">Delete</button></form>
                </div>
                @endif
            </div>
        </div>
    </div>

    @elseif($tab === 'pages')
    {{-- PAGES --}}
    <div class="glass-card rounded-2xl p-6">
        <h2 class="font-bold text-white mb-4">National Pages</h2>
        <div class="space-y-2">
            @foreach($nationalPages as $p)
            <div class="flex items-center justify-between border border-white/10 rounded-xl p-3">
                <div><span class="font-semibold text-white text-sm capitalize">{{ $p->slug }}</span><div class="text-xs text-zinc-500">{{ Str::limit($p->title ?? '', 60) }}</div></div>
                <a href="#" class="text-xs text-zinc-400 hover:underline">Edit</a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- COUNTY CLASSIFICATION --}}
    @if($tab === 'counties')
    <div class="space-y-6">
        <h2 class="text-xl font-bold text-white">County Classification</h2>
        <p class="text-zinc-400 text-sm">Dual-score quadrant: RPS (revenue potential) vs FNS (foundational need).</p>

        @if(isset($quadrantCounts))
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-zinc-800/80 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400">Engine</div>
                <div class="text-2xl font-bold text-emerald-400">{{ $quadrantCounts['engine'] ?? 0 }}</div>
            </div>
            <div class="bg-zinc-800/80 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400">Growth</div>
                <div class="text-2xl font-bold text-sky-400">{{ $quadrantCounts['growth'] ?? 0 }}</div>
            </div>
            <div class="bg-zinc-800/80 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400">Priority Development</div>
                <div class="text-2xl font-bold text-amber-400">{{ $quadrantCounts['priority_development'] ?? 0 }}</div>
            </div>
            <div class="bg-zinc-800/80 rounded-xl p-4 border border-zinc-700">
                <div class="text-xs text-zinc-400">Foundational Anchor</div>
                <div class="text-2xl font-bold text-rose-400">{{ $quadrantCounts['foundational_anchor'] ?? 0 }}</div>
            </div>
            <div class="bg-zinc-800/80 rounded-xl p-4 border border-zinc-700/70">
                <div class="text-xs text-zinc-400">Active Pipelines</div>
                <div class="text-2xl font-bold text-white">{{ number_format($activationTotal ?? 0) }}</div>
                <div class="text-xs text-zinc-500">across all counties</div>
            </div>
        </div>
        @endif

        <div class="mt-4 bg-zinc-800/80 rounded-xl border border-zinc-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead><tr class="bg-zinc-700/50 text-left text-zinc-300"><th class="p-3">County</th><th class="p-3">RPS</th><th class="p-3">FNS</th><th class="p-3">Quadrant</th><th class="p-3">Active Pipelines</th><th class="p-3">Codes</th></tr></thead>
                <tbody>
                @forelse($counties ?? [] as $c)
                @php $actCount = $activationCounts[$c->id] ?? 0; $actPipes = $activeByCounty[$c->id] ?? collect([]); @endphp
                <tr class="border-t border-zinc-700/50 text-zinc-300">
                    <td class="p-3 font-medium text-white">{{ $c->name }}</td>
                    <td class="p-3">{{ $c->classification_rps !== null ? number_format($c->classification_rps, 4) : '—' }}</td>
                    <td class="p-3">{{ $c->classification_fns !== null ? number_format($c->classification_fns, 4) : '—' }}</td>
                    <td class="p-3">
                        @if($c->classification_quadrant === 'engine')
                        <span class="px-2 py-0.5 rounded text-xs bg-emerald-900/50 text-emerald-300">Engine</span>
                        @elseif($c->classification_quadrant === 'growth')
                        <span class="px-2 py-0.5 rounded text-xs bg-sky-900/50 text-sky-300">Growth</span>
                        @elseif($c->classification_quadrant === 'priority_development')
                        <span class="px-2 py-0.5 rounded text-xs bg-amber-900/50 text-amber-300">Priority</span>
                        @elseif($c->classification_quadrant === 'foundational_anchor')
                        <span class="px-2 py-0.5 rounded text-xs bg-rose-900/50 text-rose-300">Foundational</span>
                        @else
                        <span class="text-zinc-500">—</span>
                        @endif
                    </td>
                    <td class="p-3">
                        <span class="font-bold text-white">{{ $actCount }}</span>
                    </td>
                    <td class="p-3 text-xs text-zinc-400 max-w-[200px] truncate" title="{{ $actPipes->pluck('code')->implode(', ') }}">
                        {{ $actPipes->pluck('code')->take(5)->implode(', ') }}{{ $actPipes->count() > 5 ? '…' : '' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="p-6 text-center text-zinc-500">No counties classified. Run <code class="text-zinc-400 bg-zinc-700 px-1 rounded">php artisan pool:classify-counties</code>.</td></tr>
                @endforelse
            </tbody></table>
        </div>
    </div>
    @endif

    @if($tab === 'exh_requests')
    <div class="glass-card rounded-2xl p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-white text-lg">📋 Exhibitor Setup Requests</h3>
            <span class="text-xs text-zinc-400">{{ count($exhRequests) }} pending</span>
        </div>
        <table class="w-full text-xs">
            <thead><tr class="text-zinc-500 uppercase tracking-wider text-[10px] font-bold">
                <th class="px-3 py-2 text-left">Exhibitor</th><th class="px-3 py-2 text-left">County</th>
                <th class="px-3 py-2 text-left">Business</th><th class="px-3 py-2 text-center">Complexity</th>
                <th class="px-3 py-2 text-left">Tagline</th>
            </tr></thead>
            <tbody>
                @forelse($exhRequests as $u)
                @php $m = $u->metadata ?? []; @endphp
                <tr class="border-t border-white/5 hover:glass-card/5 transition-all">
                    <td class="px-3 py-2 font-semibold text-white">{{ $u->name }}</td>
                    <td class="px-3 py-2 text-zinc-400">{{ $u->county?->name ?? '—' }}</td>
                    <td class="px-3 py-2 text-zinc-400">{{ $m['business_type_label'] ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">
                        @if($m['complexity'] === 'premium')<span class="px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-400 font-bold">🎬 Premium</span>
                        @elseif($m['complexity'] === 'custom')<span class="px-2 py-0.5 rounded-full bg-sky-500/15 text-sky-400 font-bold">🎨 Custom</span>
                        @else<span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 font-bold">🚀 Simple</span>@endif
                    </td>
                    <td class="px-3 py-2 text-zinc-500 max-w-[180px] truncate">{{ $m['tagline'] ?? '' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">No pending exhibitor requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

</div>
@push('scripts')
<x-r2-large-upload
    owner-type="national"
    :owner-id="0"
    r2-path="national/video/hero/hero.mp4"
/>
@endpush

@endsection