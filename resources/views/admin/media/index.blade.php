@extends('layouts.app')

@section('title', 'Media Library — KICC Pipeline')
@section('description', 'Upload, process and attach cinematic media across the platform — nothing hardcoded, everything admin-controlled.')

@section('content')
<div class="pt-20">
    {{-- Header — Hick's Law: 1 primary action (Upload), everything else secondary --}}
    <div class="bg-white border-b border-gray-200 py-10">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div data-reveal>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="h-px w-8 bg-kicc-gold"></div>
                        <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Media Pipeline</span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight" data-split>Media Library</h1>
                    <p class="text-[#5A6480] mt-2 text-sm max-w-xl">Every image, video and 3D model on the platform lives here. Upload once, then choose which cinematic pipeline to run — or attach straight to a page.</p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a href="{{ route('media.upload') }}" data-magnetic class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 text-sm h-12 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618] active:scale-[0.97]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        Upload Media
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-8">
        {{-- Stat chips — Miller's Law: chunked summary counts --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-8">
            @php $chips = [
                ['l' => 'All Assets', 'v' => $counts['all'], 'c' => 'text-gray-900'],
                ['l' => 'Images', 'v' => $counts['image'], 'c' => 'text-sky-600'],
                ['l' => 'Videos', 'v' => $counts['video'], 'c' => 'text-kicc-gold'],
                ['l' => '3D Models', 'v' => $counts['model'], 'c' => 'text-emerald-600'],
                ['l' => 'Processing', 'v' => $counts['processing'], 'c' => 'text-[#901C1E]'],
            ]; @endphp
            @foreach($chips as $i => $chip)
            <div class="bg-white border border-gray-200 rounded-2xl p-4 card-hover" data-reveal data-reveal-delay="{{ $i * 50 }}">
                <div class="text-2xl font-black {{ $chip['c'] }}"><span data-count="{{ $chip['v'] }}">0</span></div>
                <div class="text-[11px] font-bold text-gray-400 uppercase tracking-widest mt-1">{{ $chip['l'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Filters (searchable, descriptive placeholder — component guideline 4) --}}
        <form method="GET" class="flex flex-wrap items-center gap-2 mb-6">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search by filename, e.g. mombasa_hero..." 
                   class="flex-1 min-w-[220px] h-11 px-4 rounded-xl bg-white border border-gray-200 text-sm outline-none focus:ring-1 focus:ring-kicc-gold placeholder:text-gray-400">
            <select name="kind" class="h-11 px-3 rounded-xl bg-white border border-gray-200 text-sm outline-none focus:ring-1 focus:ring-kicc-gold">
                <option value="">All kinds</option>
                <option value="image" @selected($kind === 'image')>Images</option>
                <option value="video" @selected($kind === 'video')>Videos</option>
                <option value="model" @selected($kind === 'model')>3D Models</option>
            </select>
            <select name="status" class="h-11 px-3 rounded-xl bg-white border border-gray-200 text-sm outline-none focus:ring-1 focus:ring-kicc-gold">
                <option value="">Any status</option>
                <option value="ready" @selected($status === 'ready')>Ready</option>
                <option value="processing" @selected($status === 'processing')>Processing</option>
                <option value="uploaded" @selected($status === 'uploaded')>Uploaded</option>
                <option value="failed" @selected($status === 'failed')>Failed</option>
            </select>
            <button type="submit" class="h-11 px-5 rounded-xl bg-gray-100 border border-gray-200 text-sm font-bold text-gray-700 hover:bg-gray-200 transition-all">Filter</button>
            <a href="{{ route('media.library') }}" class="h-11 px-4 rounded-xl text-sm font-bold text-[#5A6480] hover:text-gray-900 inline-flex items-center">Clear</a>
        </form>

        {{-- Grid — cards, chunked, big touch targets (Fitts's Law) --}}
        @if($assets->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($assets as $asset)
            <a href="{{ route('media.show', $asset) }}" class="group bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-kicc-gold/50 transition-all card-hover" data-reveal>
                <div class="aspect-video bg-[#F9FAFB] relative overflow-hidden">
                    @if($asset->kind === 'video')
                    <img src="{{ $asset->posterUrl() ?? $asset->thumbnailUrl() ?? $asset->url() }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <span class="absolute top-2 right-2 bg-black/70 text-white text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest">Video</span>
                    @elseif($asset->kind === 'model')
                    <img src="{{ $asset->thumbnailUrl() ?? $asset->url() }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <span class="absolute top-2 right-2 bg-emerald-600 text-white text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-widest">3D</span>
                    @else
                    <img src="{{ $asset->thumbnailUrl() ?? $asset->url() }}" alt="{{ $asset->alt_text }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    @endif
                    @if($asset->status === 'processing')
                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                        <div class="w-8 h-8 border-2 border-kicc-gold border-t-transparent rounded-full animate-spin"></div>
                    </div>
                    @endif
                </div>
                <div class="p-3.5">
                    <div class="font-bold text-gray-900 text-xs truncate">{{ $asset->original_name }}</div>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-[10px] font-bold uppercase tracking-widest 
                            {{ $asset->status === 'ready' ? 'text-emerald-600' : ($asset->status === 'processing' ? 'text-kicc-gold' : ($asset->status === 'failed' ? 'text-[#901C1E]' : 'text-gray-400')) }}">
                            {{ ucfirst($asset->status) }}
                        </span>
                        <span class="text-[10px] text-gray-400">{{ round($asset->size_bytes / 1024) }} KB</span>
                    </div>
                    @php $latestJob = $asset->pipelineJobs->first(); @endphp
                    @if($latestJob && $latestJob->status === 'running')
                    <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-kicc-gold to-[#901C1E] transition-all" style="width: {{ $latestJob->progress }}%"></div>
                    </div>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
        <div class="mt-8">{{ $assets->links() }}</div>
        @else
        <div class="text-center py-20 bg-white rounded-2xl border border-gray-200" data-reveal="zoom">
            <div class="text-5xl mb-4">🗂️</div>
            <h3 class="text-lg font-black text-gray-900 mb-1">No media yet</h3>
            <p class="text-[#5A6480] text-sm mb-5">Upload your first image to start the cinematic pipeline.</p>
            <a href="{{ route('media.upload') }}" class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 h-12 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618]">Upload Media</a>
        </div>
        @endif
    </div>
</div>
@endsection
