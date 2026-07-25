@props(['counties' => []])

@php
$regions = ['All', 'Central', 'Coast', 'Eastern', 'Nyanza', 'North Eastern', 'Rift Valley', 'Western'];
$countyImages = ['nairobi','mombasa','kisumu','nakuru','kilifi','laikipia','kajiado','machakos','kiambu','muranga','nyeri','kirinyaga','tana-river','lamu','taita-taveta','garissa','wajir','turkana','kakamega','bungoma','baringo','narok','kericho','uashin-gishu','meru','nandi','siaya','homa-bay','kisii','nyamira','trans-nzoia','west-pokot','samburu','isiolo','marsabit','mandera','elgeyo-marakwet','kitui','makueni','kwale','vihiga','bomet','busia','tharaka-nithi','nyandarua','embe'];
@endphp
<div x-data="{ query: '', region: 'All', activeRegion: 'All' }"
     x-init="
        $watch('region', v => activeRegion = v)
     "
     class="py-20 overflow-hidden bg-[#07090F]">
    <div class="max-w-7xl mx-auto px-5">
        <div class="mb-10">
            <div class="flex items-center gap-3 mb-3">
                <div class="h-px w-8 bg-kicc-gold"></div>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Explore Kenya</span>
            </div>
            <h2 class="text-3xl md:text-4xl font-black text-white leading-[1.1]">Browse all <span class="text-kicc-gold">47 Counties</span></h2>
            <p class="text-white/40 mt-3 text-base max-w-xl leading-relaxed">Search by name or filter by region — then click to explore sectors and businesses.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <div class="relative flex-1 max-w-sm">
                <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-white/30" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input x-model="query" placeholder="Search county name…"
                    class="w-full pl-10 pr-4 h-11 rounded-xl bg-[#141B2E] border border-white/10 text-white/80 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] placeholder:text-white/25 transition-all">
                <button x-show="query" @click="query = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex gap-1.5 overflow-x-auto pb-1 flex-wrap sm:flex-nowrap">
                @foreach($regions as $r)
                <button @click="region = '{{ $r }}'"
                    class="shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all"
                    :class="region === '{{ $r }}' ? 'bg-[#901C1E] text-white' : 'bg-[#141B2E] text-white/40 border border-white/8 hover:border-white/20 hover:text-white'">
                    {{ $r }}
                </button>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between mb-3">
            <span class="text-white/30 text-xs font-semibold" x-text="filteredCount + ' county' + (filteredCount !== 1 ? 'ies' : '')"></span>
            <div class="flex gap-2">
                <button @click="scrollLeft()" class="w-8 h-8 rounded-full border border-white/20 text-white/60 hover:border-[#FFCD05] hover:text-[#FFCD05] flex items-center justify-center transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button @click="scrollRight()" class="w-8 h-8 rounded-full border border-white/20 text-white/60 hover:border-[#FFCD05] hover:text-[#FFCD05] flex items-center justify-center transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="relative">
        <div class="max-w-7xl mx-auto px-0">
            <div x-ref="strip" @scroll="updateScroll()" class="flex gap-4 overflow-x-auto pb-4 px-5 scrollbar-hide">
                @foreach($counties as $c)
                <a href="{{ route('counties.show', $c->slug) }}" x-show="
                    '{{ $c->name }}'.toLowerCase().includes(query.toLowerCase()) &&
                    (region === 'All' || '{{ $c->former_province ?? $c->slug }}'.includes(region))
                "
                   class="shrink-0 group relative overflow-hidden rounded-2xl cursor-pointer block" style="width: 200px; height: 280px;">
                    <img src="{{ media('counties/' . $c->slug . '/hero.jpeg') }}" alt="{{ $c->name }}"
                         class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                         onerror="this.style.display='none'">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/30 to-transparent"></div>
                    <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="w-6 h-6 bg-kicc-gold rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-[#07090F]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </div>
                    </div>
                    <div class="absolute inset-x-0 bottom-0 p-4">
                        <div class="text-white font-black text-base leading-tight">{{ $c->name }}</div>
                        <div class="text-white/50 text-[11px] mt-1 leading-snug">{{ $c->tagline ?? Str::limit($c->description ?? 'Kenya County', 40) }}</div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
