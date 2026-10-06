@extends('layouts.app')

@section('title', 'National Government of Kenya — KICC')
@section('description', 'Ministries and agencies of the National Government of Kenya on the KICC Digital Economy Platform.')

@section('content')
<div class="pt-20">
    {{-- Hero --}}
    <div class="bg-[#0A1024] text-white">
        <div class="max-w-7xl mx-auto px-5 py-14 md:py-20">
            <div class="flex items-center gap-3 mb-6">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-9 w-auto" style="filter: brightness(0) invert(1);">
                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/40 border-l border-white/20 pl-3">National Pavilion</span>
            </div>
            <h1 class="text-3xl md:text-5xl font-black leading-tight" data-split>National Government <span class="text-[#FFCD05]">of Kenya</span></h1>
            <p class="text-white/60 mt-3 max-w-2xl text-sm md:text-base leading-relaxed">
                Ministries and their agencies, organized in one place — each with its own national pavilion
                on the KICC Digital Economy Platform.
            </p>
            <div class="flex flex-wrap gap-4 mt-6 text-xs text-white/50">
                <span> {{ $ministries->count() }} {{ Str::plural('ministry', $ministries->count()) }}</span>
                <span> {{ $ministries->sum('agencies_count') }} {{ Str::plural('agency', $ministries->sum('agencies_count')) }}</span>
                <span> Republic of Kenya</span>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-12">
        <div class="flex items-center gap-3 mb-8">
            <span class="h-px w-8 bg-kicc-gold"></span>
            <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Ministries</span>
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>

        @if($ministries->isEmpty())
        <div class="bg-white border border-gray-200 rounded-2xl p-14 text-center text-gray-400">
            <span class="text-4xl block mb-3"></span>
            <p class="text-sm">No ministries published yet.</p>
        </div>
        @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($ministries as $m)
            @php $color = $m->color ?: '#901C1E'; @endphp
            <a href="{{ route('national.site', $m->slug) }}"
               class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all block">
                <div class="h-2" style="background: {{ $color }}"></div>
                <div class="p-5">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl shrink-0 flex items-center justify-center text-white font-black text-sm overflow-hidden"
                             style="background: {{ $color }}">
                            @if($m->logo)
                            <img src="{{ $m->logo }}" alt="" class="w-full h-full object-cover" loading="lazy">
                            @else
                            {{ $m->code }}
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h2 class="font-bold text-gray-900 text-sm leading-snug group-hover:text-[#901C1E] transition-colors">{{ $m->name }}</h2>
                            <div class="text-xs text-gray-400 mt-1">
                                {{ $m->agencies_count }} {{ Str::plural('agency', $m->agencies_count) }}
                            </div>
                        </div>
                    </div>
                    @if($m->description)
                    <p class="text-xs text-gray-500 leading-relaxed mt-3 line-clamp-2">{{ $m->description }}</p>
                    @endif
                    <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
                        <span class="text-xs font-bold text-[#901C1E] group-hover:underline">Visit pavilion</span>
                        <span class="text-gray-300 group-hover:text-[#901C1E] group-hover:translate-x-0.5 transition-all">&rarr;</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif

        {{-- Economic sectors, organized by major group --}}
        @if(($sectorGroups ?? collect())->isNotEmpty())
        <div class="mt-16">
            <div class="flex items-center gap-3 mb-3">
                <span class="h-px w-8 bg-[#11820B]"></span>
                <span class="text-[#11820B] text-xs font-bold tracking-[0.2em] uppercase">Economic Sectors</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            <p class="text-sm text-gray-500 mb-8 max-w-2xl">Every county department across the 47 counties, organized into its major national group. <a href="{{ route('national.sectors') }}" class="text-kicc-gold hover:underline font-medium">View all sectors →</a></p>

            <div class="space-y-10">
                @foreach($sectorGroups as $g)
                <section id="group-{{ $g['key'] }}" class="scroll-mt-24">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-[#11820B]/10 flex items-center justify-center text-xl">{{ $g['icon'] }}</div>
                        <div>
                            <h2 class="font-bold text-gray-900">{{ $g['name'] }}</h2>
                            <div class="text-xs text-gray-400">{{ $g['sectors']->count() }} county {{ Str::plural('department', $g['sectors']->count()) }}</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($g['sectors'] as $s)
                        <a href="{{ route('national.sector.show', $s->slug) }}" class="bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-sm text-gray-700 font-medium hover:border-[#11820B]/30 hover:bg-white transition-all block">
                            {{ $s->name }}
                        </a>
                        @endforeach
                    </div>
                </section>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Cross-links --}}
        <div class="grid md:grid-cols-3 gap-4 mt-14">
            <a href="{{ route('counties.index') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all flex items-center justify-between group">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Explore</div>
                    <div class="font-bold text-gray-900">47 County Websites</div>
                </div><span class="text-gray-300 text-xl group-hover:text-[#901C1E] transition-colors">&nearr;</span>
            </a>
            <a href="{{ route('exhibitions.index') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all flex items-center justify-between group">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Showcasing at</div>
                    <div class="font-bold text-gray-900">KICC Exhibitions</div>
                </div><span class="text-gray-300 text-xl group-hover:text-[#901C1E] transition-colors">&nearr;</span>
            </a>
            <a href="{{ route('marketplace.index') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:shadow-md transition-all flex items-center justify-between group">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Trade on</div>
                    <div class="font-bold text-gray-900">National Marketplace</div>
                </div><span class="text-gray-300 text-xl group-hover:text-[#901C1E] transition-colors">&nearr;</span>
            </a>
        </div>
    </div>
</div>
@endsection
