@extends('layouts.nexora')
@section('title', 'National Government — Admin Control')
@section('content')
<div class="max-w-7xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-white mb-2">National Government Management</h1>
    <p class="text-zinc-400 text-sm mb-6">Manage national content and media. Large uploads are resumable; new versions are drafted, processed and previewed before publication.</p>
<link rel="stylesheet" href="/css/national-media-admin.css?v=national-resumable-v1">
<div class="nm-summary"><div><strong>{{ $stats['ministries'] }}</strong><span>Ministries</span></div><div><strong>{{ $stats['agencies'] }}</strong><span>Agencies</span></div><div><strong>{{ $mediaLibrary->where('status','processing')->count() }}</strong><span>Processing videos</span></div><div><strong>2 GiB</strong><span>Maximum video file</span></div><a class="as-cta" href="{{ route('national.admin.v2.dashboard',['tab'=>'library']) }}">Media library & versions</a><a href="/national-government" target="_blank" rel="noopener noreferrer">Preview public page ↗</a></div>

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
                        <button onclick="this.closest('.border').querySelector('form.hidden')?.classList.toggle('hidden')" class="text-xs px-2 py-1 rounded border border-white/10 text-zinc-400 hover:text-white">Edit</button>
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
@include('experience.admin.national-media-widget',['ownerType'=>'national_page','ownerId'=>1,'slot'=>'national_hero_video','label'=>'National Government Hero Video','help'=>'Published on /national-government. Uploading a draft never overwrites the current live hero.','liveAsset'=>$nationalHero])
    @elseif($tab === 'media')
<div class="nm-toolbar"><label>Find a ministry<input data-nm-search placeholder="Search ministry name"></label><p>Each ministry has its own video and animated-flag controls. National-only administration applies to every upload and publication.</p></div>
@foreach($ministries as $m)
<div data-nm-ministry="{{ strtolower($m->name) }}"><h2>{{ $m->name }}</h2>
@include('experience.admin.national-media-widget',['ownerType'=>\App\Models\Ministry::class,'ownerId'=>$m->id,'slot'=>'ministry_video_'.$m->slug,'label'=>$m->name.' — Ministry Video','help'=>'Dedicated ministry media; publishes only to this ministry’s slot.','liveAsset'=>$ministryMedia[$m->id]['video']??null])
@include('experience.admin.national-media-widget',['ownerType'=>\App\Models\Ministry::class,'ownerId'=>$m->id,'slot'=>'ministry_flag_video','label'=>$m->name.' — Animated Flag','help'=>'Fallback flag for this ministry.','liveAsset'=>$ministryMedia[$m->id]['flag']??null])
</div>
@endforeach
    @elseif($tab === 'flag')
@include('experience.admin.national-media-widget',['ownerType'=>'national_page','ownerId'=>1,'slot'=>'national_flag_video','label'=>'National Animated Flag','help'=>'National fallback animation. Draft, preview and publish separately.','liveAsset'=>$nationalFlag])
    @elseif($tab === 'library')
<section class="nm-card"><h2>National media library</h2><p>Original files and versions are retained. Publishing replaces the logical slot only after the new stream is ready.</p><div class="nm-table"><table><thead><tr><th>Title</th><th>Destination</th><th>State</th><th>Size</th><th>Manage</th></tr></thead><tbody>
@forelse($mediaLibrary as $a)
<tr><td>{{ $a->alt_text?:$a->original_name }}</td><td>{{ $a->metadata['target_slot']??$a->slot }}</td><td>{{ $a->status }} · {{ $a->metadata['publication']??'legacy live' }}</td><td>{{ number_format($a->size_bytes/1048576,1) }} MiB</td><td><a href="{{ route('national.admin.v2.dashboard',['tab'=>$a->owner_type===\App\Models\Ministry::class?'media':(str_contains($a->slot,'flag')?'flag':'hero')]) }}">Preview & manage</a></td></tr>
@empty
<tr><td colspan="5">No national videos yet.</td></tr>
@endforelse
</tbody></table></div></section>
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
<script defer src="/js/national-media-upload.js?v=national-resumable-v1"></script>
@endpush


@endsection