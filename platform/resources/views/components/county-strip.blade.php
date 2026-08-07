@props(['counties' => []])

@php $regions = ['All', 'Central', 'Coast', 'Eastern', 'Nyanza', 'North Eastern', 'Rift Valley', 'Western']; @endphp

<div x-data="{ q: '', region: 'All' }" class="overflow-hidden">
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1 max-w-sm">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#5A6480]" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input x-model="q" placeholder="Search county name…"
                class="w-full pl-10 pr-4 h-11 rounded-xl bg-white border border-gray-200 text-gray-900 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05] placeholder:text-[#5A6480]/50 transition-all">
        </div>
        <div class="flex gap-1.5 overflow-x-auto pb-1 flex-wrap sm:flex-nowrap">
            @foreach($regions as $r)
            <button @click="region = '{{ $r }}'"
                class="shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-bold transition-all cursor-pointer"
                :class="region === '{{ $r }}' ? 'bg-[#901C1E] text-white' : 'bg-white text-[#5A6480] border border-gray-200 hover:border-[#901C1E]/30'" data-magnetic>{{ $r }}</button>
            @endforeach
        </div>
    </div>

    <div class="flex items-center justify-between mb-3">
        <span class="text-[#5A6480] text-xs font-semibold" x-text="
            document.querySelectorAll('.county-card:not([style*=\'display: none\'])').length + ' counties'
        "></span>
        <div class="flex gap-2">
            <button @click="document.getElementById('county-strip').scrollBy({left: -320, behavior: 'smooth'})" class="w-8 h-8 rounded-full border border-gray-200 text-[#5A6480] hover:border-[#FFCD05] hover:text-[#FFCD05] flex items-center justify-center transition-all cursor-pointer" data-magnetic>&larr;</button>
            <button @click="document.getElementById('county-strip').scrollBy({left: 320, behavior: 'smooth'})" class="w-8 h-8 rounded-full border border-gray-200 text-[#5A6480] hover:border-[#FFCD05] hover:text-[#FFCD05] flex items-center justify-center transition-all cursor-pointer" data-magnetic>&rarr;</button>
        </div>
    </div>

    <div class="relative">
        <div class="max-w-7xl mx-auto px-0">
            <div id="county-strip" class="flex gap-4 overflow-x-auto pb-4 px-5 scrollbar-hide">
                @foreach($counties as $c)
                <a href="{{ route('counties.show', $c->slug) }}"
                   x-show="(q === '' || '{{ strtolower($c->name) }}'.includes(q.toLowerCase())) && (region === 'All' || '{{ $c->former_province ?? '' }}' === region)"
                   class="county-card shrink-0 group relative overflow-hidden rounded-2xl block bg-white border border-gray-200 hover:border-[#FFCD05]/40 transition-all" style="width: 200px; height: 280px;">
                     <img src="{{ media('counties/' . $c->slug . '/hero.jpeg') }}" alt="{{ $c->name }}"
                          class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                          loading="lazy" decoding="async"
                          onerror="this.remove()">
                     <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 p-4">
                        <div class="text-white font-black text-base leading-tight">{{ $c->name }}</div>
                        <div class="text-white/60 text-[11px] mt-1">{{ $c->tagline ?? Str::limit($c->description ?? '', 40) }}</div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
