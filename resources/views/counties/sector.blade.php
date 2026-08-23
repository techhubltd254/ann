@extends('layouts.app')

@section('title', $sectorInfo['title'] . ' — ' . $county->name . ' County')
@section('description', 'Explore ' . $sectorInfo['title'] . ' in ' . $county->name . ' County')

@section('content')
<div class="pt-20">
    @if($fourDVideo)
    <div class="relative h-[40vh] md:h-[50vh] overflow-hidden bg-black">
        <video autoplay muted loop playsinline class="w-full h-full object-cover">
            <source src="{{ $fourDVideo }}" type="video/mp4">
        </video>
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-5 pb-8">
            <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-white/60 hover:text-white text-sm mb-3 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ $county->name }} County
            </a>
            <h1 class="text-3xl md:text-5xl font-black text-white" data-split>{{ $sectorInfo['title'] }}</h1>
            <p class="text-white/70 text-sm mt-2">{{ $sectorInfo['desc'] }}</p>
        </div>
    </div>
    @else
    <div class="bg-white border-b border-gray-200 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <a href="{{ route('counties.show', $county->slug) }}" class="inline-flex items-center gap-1.5 text-[#5A6480] hover:text-gray-900 text-sm mb-4 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ $county->name }} County
            </a>
            <div class="flex items-center gap-5">
                <div class="w-16 h-16 bg-[#901C1E]/20 border border-[#901C1E]/30 rounded-2xl flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7 text-[#901C1E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-gray-900" data-split>{{ $sectorInfo['title'] }}</h1>
                    <p class="text-[#5A6480] mt-1 text-sm">{{ $items->count() }} registered entities · {{ $county->name }} County</p>
                </div>
            </div>
            <p class="text-[#5A6480] text-sm mt-4 max-w-xl">{{ $sectorInfo['desc'] }}</p>
        </div>
    </div>
    @endif
    <div class="max-w-7xl mx-auto px-5 py-10">
        @if($items->count() > 0)
        {{-- Scroll-driven cinematic sections for entities with 4D videos --}}
        @php $scrollItems = $items->filter(fn($e) => isset($entityVideos[$e->id])); @endphp
        @foreach($scrollItems as $e)
        @php $ev = $entityVideos[$e->id]; $ep = $entityPosters[$e->id] ?? null; @endphp
        <x-scroll-video
            :videoUrl="$ev"
            :posterUrl="$ep"
            :title="$e->name"
            :subtitle="$sectorInfo['title']"
            :description="$e->description"
            :price="$e->entry_fee ?? null"
            ctaText="Learn More"
            :ctaUrl="route('attractions.show', $e->id) ?? '#'"
        />
        @endforeach

        {{-- Grid cards for remaining entities (no video) --}}
        @php $gridItems = $items->filter(fn($e) => !isset($entityVideos[$e->id])); @endphp
        @if($gridItems->count() > 0)
        <h2 class="text-2xl font-black text-gray-900 mb-6 mt-10">More in {{ $sectorInfo['title'] }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach($gridItems as $e)
            <div class="group bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/40 hover:shadow-md transition-all">
                <div class="h-40 overflow-hidden bg-gradient-to-br from-[#F9FAFB] to-gray-100 relative">
                    <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-[#0A1024] to-[#901C1E]/50">
                        <svg class="w-10 h-10 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    @if($e->category)
                    <span class="absolute top-2.5 left-3 text-[10px] font-bold px-2 py-0.5 rounded-full bg-black/40 text-white/90 backdrop-blur-sm capitalize">{{ $e->category }}</span>
                    @endif
                </div>
                <div class="p-4">
                    <div class="font-bold text-gray-900 text-sm leading-snug">{{ $e->name }}</div>
                    @if($e->description)
                    <p class="text-gray-500 text-xs leading-relaxed mt-1.5 line-clamp-2">{{ $e->description }}</p>
                    @endif
                    <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2.5 text-[11px] text-gray-400">
                        @if($e->location)<span>📍 {{ $e->location }}</span>@endif
                        @if(!empty($e->entry_fee))<span class="font-bold text-kicc-gold">KES {{ number_format($e->entry_fee) }}</span>@endif
                        @if(!empty($e->contact))<span>☎ {{ $e->contact }}</span>@endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
        <div class="mt-8">
            {{ $items->links() }}
        </div>
        @else
<div class="text-center py-16 text-[#5A6480]">
        <span class="text-4xl block mb-3">📂</span>
        <p class="text-sm">No entities registered in this sector yet.</p>
    </div>
    @endif

    @if($services->count() > 0)
    <div class="mt-16 pt-10 border-t border-gray-200">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-black text-gray-900">Bookable Services</h2>
                <p class="text-[#5A6480] text-sm mt-1">Add services to your cart and checkout securely.</p>
            </div>
            <a href="{{ route('cart.index') }}" class="text-sm font-bold text-kicc-gold hover:text-[#FFCD05] transition-colors">
                View Cart →
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($services as $product)
            <div class="bg-white rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/40 hover:shadow-md transition-all">
                <div class="h-36 bg-gradient-to-br from-[#F9FAFB] to-gray-100 flex items-center justify-center">
                    <img src="https://placehold.co/400x300/1a1a2e/FFCD05?text=Service" alt="" class="w-full h-full object-cover" loading="lazy">
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-gray-900 text-sm leading-snug">{{ $product->name }}</h3>
                    <p class="text-gray-500 text-xs leading-relaxed mt-1.5 line-clamp-2">{{ $product->short_description ?? $product->description }}</p>
                    <div class="mt-3 space-y-1.5">
                        @foreach($product->variants->where('is_active', true) as $variant)
                        <form method="POST" action="{{ route('cart.add') }}" class="flex items-center justify-between gap-2 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                            @csrf
                            <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-700 truncate">{{ $variant->name }}</div>
                                <div class="text-xs font-bold text-kicc-gold">KES {{ number_format($variant->price) }}</div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <select name="quantity" class="text-xs border border-gray-200 rounded-lg px-1.5 py-1 w-14 bg-white">
                                    @for($q = 1; $q <= min(10, $variant->stock ?? 10); $q++)
                                    <option value="{{ $q }}">{{ $q }}</option>
                                    @endfor
                                </select>
                                <button type="submit" class="text-[10px] font-bold px-2.5 py-1.5 rounded-lg bg-[#901C1E] text-white hover:bg-[#7b1618] transition-colors whitespace-nowrap">
                                    Add to Cart
                                </button>
                            </div>
                        </form>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
</div>
@endsection