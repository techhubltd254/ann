@extends('layouts.app')

@section('title', $product->name . ' — KICC Marketplace')
@section('description', $product->short_description ?? Str::limit($product->description, 150))

@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10">
    <a href="{{ route('marketplace.index') }}" class="inline-flex items-center gap-1.5 text-[#5A6480] hover:text-gray-900 text-sm mb-6 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Marketplace
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
    {{-- Left: Video / Media --}}
    <div>
    @php $allVideos = collect(array_merge(
        $product->video_url ? [$product->video_url] : [],
        is_array($product->videos) ? $product->videos : []
    ))->unique()->values(); @endphp

    @if($allVideos->isNotEmpty())
    <div class="space-y-2">
<div class="rounded-2xl overflow-hidden aspect-video relative" id="main-video-wrapper" style="background: transparent; z-index: 1;">
    {{-- Tab bar for switching between video and 3D --}}
    @if($product->hasModel())
    <div class="absolute top-3 left-3 z-30 flex gap-1.5">
        <button onclick="switchMediaTab('video')" id="tab-video-btn" class="px-3 py-1.5 rounded-full text-[10px] font-bold transition-all bg-white/90 text-gray-800 shadow-sm">Video</button>
        <button onclick="switchMediaTab('model')" id="tab-model-btn" class="px-3 py-1.5 rounded-full text-[10px] font-bold transition-all bg-white/20 text-white/80 hover:bg-white/40">3D Model</button>
    </div>
    @endif
    <div id="media-video" class="w-full h-full three-video-container"
         data-video="{{ $allVideos->first() }}"
         data-depth=""
         data-mode="parallax"
         style="background: transparent;">
    </div>
    @if($product->hasModel())
    <div id="media-model" class="w-full h-full absolute inset-0" style="display:none;">
        <div id="product-viewer-container" class="w-full h-full"></div>
    </div>
    @endif
</div>
        @if($allVideos->count() > 1)
        <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
            @foreach($allVideos as $v)
            <button onclick="
                var player = document.getElementById('product-video-{{ $product->id }}');
                player.src = '{{ $v }}';
                player.load();
                player.play();
            " class="shrink-0 w-24 h-14 rounded-xl overflow-hidden border-2 border-gray-200 hover:border-indigo-500 transition-all bg-black">
                <video muted playsinline preload="auto" class="w-full h-full object-cover">
                    <source src="{{ $v }}" type="video/mp4">
                </video>
            </button>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    <div class="rounded-2xl overflow-hidden h-80 bg-[#F9FAFB] {{ $allVideos->isNotEmpty() ? 'hidden' : '' }}" id="product-image-container">
        @if($product->video_description)
        <div class="w-full h-full bg-gradient-to-br from-[#0B1E57] to-[#1a1a2e] p-6 flex flex-col justify-center">
            <span class="text-[10px] font-bold text-[#FFCD05] uppercase tracking-widest mb-2"> Video being produced</span>
            <p class="text-white/90 text-sm leading-relaxed">{{ $product->video_description }}</p>
        </div>
        @else
        @php $productImage = $product->image_url; @endphp
        @if($productImage && !str_contains($productImage, 'svg'))
        <x-fast-image :src="$productImage" :alt="$product->name" :width="960" :quality="80" class="w-full h-full" />
        @elseif($product->images->first()->url ?? null)
        <x-fast-image :src="$product->images->first()->url" :alt="$product->name" :width="960" :quality="80" class="w-full h-full" />
        @else
        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#0B1E57] to-[#1a1a2e]">
            <span class="text-white/30 text-8xl font-black">{{ strtoupper(substr($product->name, 0, 2)) }}</span>
        </div>
        @endif
        @endif
    </div>
            @if($product->images->count() > 1)
            <div class="flex gap-2 mt-3">
                @foreach($product->images->take(4) as $img)
                <div class="w-20 h-14 rounded-xl overflow-hidden border-2 shrink-0 border-gray-200 card-hover">
                    <x-fast-image :src="$img->url" :alt="$product->name" :width="160" :quality="70" class="w-full h-full" />
                </div>
                @endforeach
            </div>
            @endif
    </div>

    {{-- Right: Product Info + Purchase --}}
    <div>
                <div class="flex items-center gap-2 mb-2">
                    @if($product->county)
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/30">{{ $product->county->name }} County</span>
                    @endif
                    @if($product->category)
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border bg-sky-100 text-[#5A6480] border-gray-200">{{ $product->category->name }}</span>
                    @endif
                    @if(isset($flashSaleProduct))
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide bg-red-100 text-red-600 border border-red-200"> -{{ $flashSaleProduct->pivot->flashSale->discount_percent ?? 0 }}% Flash</span>
                    @endif
                </div>
                <h1 class="text-3xl font-black text-gray-900" data-split>{{ $product->name }}</h1>
                <div class="flex items-center gap-3 mt-2">
                    @auth
                    <button data-wishlist-btn data-type="{{ get_class($product) }}" data-id="{{ $product->id }}" class="text-gray-300 hover:text-red-500 transition-colors text-lg" title="Add to wishlist"></button>
                    @endauth
                    <label class="flex items-center gap-1 text-xs text-gray-400 cursor-pointer">
                        <input type="checkbox" class="compare-checkbox accent-[#046bd2]" value="{{ $product->id }}" onchange="updateCompare(this)">
                        Compare
                    </label>
                </div>
                @if($product->short_description)
                <p class="text-[#5A6480] leading-relaxed text-sm mt-4">{{ $product->short_description }}</p>
                @endif
            </div>
        </div>

        <div>
            <div class="bg-white border border-gray-200 rounded-2xl p-6 sticky-sidebar sticky top-24">
                <div class="font-black text-kicc-gold text-2xl">KES {{ number_format($product->price ?? 0) }}</div>
                <div class="text-gray-400 text-sm">per {{ $product->unit ?? 'unit' }}</div>

                <div class="mt-6 space-y-3">
                    @foreach($product->variants->where('is_active', true) as $i => $variant)
                    <label class="flex items-center justify-between bg-[#F9FAFB] border border-gray-200 rounded-xl px-4 py-3 cursor-pointer transition-all has-[:checked]:border-kicc-gold has-[:checked]:bg-[#FFCD05]/5">
                        <span class="flex items-center gap-3">
                            <input type="radio" name="variant" value="{{ $variant->id }}" {{ $i === 0 ? 'checked' : '' }} class="accent-kicc-gold">
                            <span class="text-sm font-semibold text-gray-900">{{ $variant->name }}</span>
                        </span>
                        <span class="font-bold text-kicc-gold">KES {{ number_format($variant->price) }}</span>
                    </label>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('cart.add') }}" class="mt-6 space-y-3">
                    @csrf
                    <input type="hidden" name="variant_id" value="{{ $product->variants->first()->id ?? '' }}">
                    <input type="number" name="quantity" value="1" min="1" max="99"
                           class="w-full h-11 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-700 text-center outline-none focus:ring-1 focus:ring-kicc-gold">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-gray-900 hover:bg-[#7b1618] active:scale-[0.97]" data-magnetic>
                        Add to Cart
                    </button>
                </form>

                @if($product->county)
                <div class="mt-2">
                    <x-experience-booking-modal
                        destination-type="product"
                        :destination-id="$product->id"
                        destination-name="{{ $product->name }}"
                        :county-id="$product->county_id"
                    />
                </div>
                @endif

                @if($product->description)
                <div class="mt-6 pt-5 border-t border-gray-200">
                    <div class="text-xs text-gray-400">Description</div>
                    <p class="text-[#5A6480] text-sm mt-2 leading-relaxed">{{ $product->description }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if(isset($related) && $related->count() > 0)
    <div class="mt-20">
        <h2 class="text-2xl font-black text-gray-900 mb-6" data-split>You may also like</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($related as $rel)
            <a href="{{ route('marketplace.show', $rel->slug) }}" class="group bg-[#F9FAFB] rounded-2xl overflow-hidden border border-gray-200 hover:border-[#FFCD05]/30 transition-all">
                <div class="aspect-square overflow-hidden bg-white">
                    @if($rel->images->first()->url ?? null)
                    <x-fast-image :src="$rel->images->first()->url" :alt="$rel->name" :width="400" :quality="70" class="w-full h-full" />
                    @else
                    <img src="{{ $rel->image_url }}" alt="{{ $rel->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                         onerror="this.src='{{ asset('storage/kicc/kicc-logo.png') }}'">
                    @endif
                </div>
                <div class="p-4">
                    <h3 class="font-bold text-gray-900 text-sm line-clamp-2">{{ $rel->name }}</h3>
                    <div class="font-black text-kicc-gold text-sm mt-1">KES {{ number_format($rel->price ?? 0) }}</div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    @if(isset($tripRecommendations) && (!empty($tripRecommendations['places_to_visit']) || !empty($tripRecommendations['places_to_stay']) || !empty($tripRecommendations['transport'])))
    <div class="mt-20" x-data="correlationLoader('product', {{ $product->id }})">
        <div class="flex items-center gap-3 mb-6">
            <span class="h-px w-8 bg-kicc-gold"></span>
            <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Plan Your Trip</span>
            <span class="text-gray-400 text-xs">from {{ $product->county->name ?? 'here' }}</span>
            <span class="h-px flex-1 bg-gray-200"></span>
        </div>

        {{-- Places to visit --}}
        @if(!empty($tripRecommendations['places_to_visit']))
        <div class="mb-8">
            <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                <span> Places to Visit</span>
                <span class="text-[10px] font-normal text-gray-400">nearby</span>
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($tripRecommendations['places_to_visit'] as $rec)
                <a href="{{ route('counties.institution', $rec['slug']) }}" class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-[#FFCD05]/40 transition-all card-hover">
                    <div class="h-32 bg-gray-100 overflow-hidden relative">
                        @if($rec['image_url'])
                        <img src="{{ $rec['image_url'] }}" alt="{{ $rec['name'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                             onerror="this.style.display='none'">
                        @endif
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur rounded-full px-2 py-0.5 text-[10px] font-bold text-gray-600 shadow">
                            {{ $rec['type_emoji'] }} {{ $rec['type_label'] ?? '' }}
                        </div>
                        @if($rec['distance_km'])
                        <div class="absolute bottom-2 left-2 bg-black/60 backdrop-blur rounded-full px-2 py-0.5 text-[10px] font-bold text-white shadow">
                            {{ $rec['distance_km'] }} km
                        </div>
                        @endif
                    </div>
                    <div class="p-3">
                        <h4 class="font-bold text-gray-900 text-sm line-clamp-1">{{ $rec['name'] }}</h4>
                        @if($rec['description'])
                        <p class="text-gray-400 text-xs line-clamp-2 mt-1">{{ Str::limit($rec['description'], 80) }}</p>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Places to stay --}}
        @if(!empty($tripRecommendations['places_to_stay']))
        <div class="mb-8">
            <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                <span> Places to Stay</span>
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($tripRecommendations['places_to_stay'] as $rec)
                <a href="{{ route('counties.institution', $rec['slug']) }}" class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-[#FFCD05]/40 transition-all card-hover">
                    <div class="h-28 bg-gray-100 overflow-hidden relative">
                        @if($rec['image_url'])
                        <img src="{{ $rec['image_url'] }}" alt="{{ $rec['name'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                             onerror="this.style.display='none'">
                        @endif
                        @if($rec['distance_km'])
                        <div class="absolute bottom-2 left-2 bg-black/60 backdrop-blur rounded-full px-2 py-0.5 text-[10px] font-bold text-white shadow">
                            {{ $rec['distance_km'] }} km
                        </div>
                        @endif
                    </div>
                    <div class="p-3">
                        <h4 class="font-bold text-gray-900 text-sm line-clamp-1">{{ $rec['name'] }}</h4>
                        @if($rec['description'])
                        <p class="text-gray-400 text-xs line-clamp-2 mt-1">{{ Str::limit($rec['description'], 80) }}</p>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Transport options --}}
        @if(!empty($tripRecommendations['transport']))
        <div>
            <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                <span> Transport Options</span>
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($tripRecommendations['transport'] as $t)
                <div class="bg-white border border-gray-200 rounded-2xl p-4 flex flex-col items-start gap-2 card-hover hover:border-[#FFCD05]/40 transition-all">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ $t['type_emoji'] ?? '' }}</span>
                        <div>
                            <h4 class="font-bold text-gray-900 text-sm">{{ $t['name'] }}</h4>
                            @if($t['type_label'])
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $t['type_label'] }}</span>
                            @endif
                        </div>
                    </div>
                    @if($t['price'])
                    <div class="font-black text-kicc-gold text-sm">KES {{ number_format($t['price']) }}/{{ $t['unit'] ?? 'trip' }}</div>
                    @endif
                    @if($t['booking_url'])
                    <a href="{{ $t['booking_url'] }}" target="_blank" rel="noopener"
                       class="w-full mt-1 text-center py-1.5 rounded-lg bg-[#0B1E57] text-white text-xs font-bold hover:bg-[#16275f] transition-all">
                        Book Now
                    </a>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- AJAX more link --}}
        <div class="mt-4 text-center" x-show="!loaded && !loading" x-cloak>
            <button @click="loadMore()" class="inline-flex items-center gap-2 text-xs font-bold text-[#0B1E57] hover:text-[#901C1E] transition-colors">
                <span x-show="!loading">Load more recommendations</span>
                <span x-show="loading" class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    Loading...
                </span>
            </button>
        </div>
        <div x-show="loaded" x-cloak>
            <div x-html="html"></div>
        </div>
    </div>
    @endif

@push('scripts')
<script>
function correlationLoader(type, id) {
    return {
        loading: false,
        loaded: false,
        html: '',
        loadMore() {
            this.loading = true;
            fetch('/api/correlations/' + type + '/' + id)
                .then(r => r.json())
                .then(data => {
                    this.html = this.renderMore(data);
                    this.loaded = true;
                    this.loading = false;
                })
                .catch(() => { this.loading = false; });
        },
        renderMore(data) {
            var h = '';
            if (data.places_to_visit && data.places_to_visit.length > 0) {
                h += '<div class="mb-6"><h4 class="text-sm font-bold text-gray-900 mb-3"> More Places</h4><div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
                data.places_to_visit.forEach(function(r) {
                    h += '<a href="/counties/institution/' + r.slug + '" class="bg-white border border-gray-200 rounded-xl p-3 hover:border-amber-300 transition-all"><div class="font-bold text-sm">' + r.name + '</div><div class="text-xs text-gray-500">' + (r.distance_km || '') + ' km · ' + (r.type_label || '') + '</div></a>';
                });
                h += '</div></div>';
            }
            if (data.places_to_stay && data.places_to_stay.length > 0) {
                h += '<div class="mb-6"><h4 class="text-sm font-bold text-gray-900 mb-3"> More Places to Stay</h4><div class="grid grid-cols-2 md:grid-cols-3 gap-4">';
                data.places_to_stay.forEach(function(r) {
                    h += '<a href="/counties/institution/' + r.slug + '" class="bg-white border border-gray-200 rounded-xl p-3 hover:border-amber-300 transition-all"><div class="font-bold text-sm">' + r.name + '</div><div class="text-xs text-gray-500">' + (r.distance_km || '') + ' km</div></a>';
                });
                h += '</div></div>';
            }
            if (data.transport && data.transport.length > 0) {
                h += '<div><h4 class="text-sm font-bold text-gray-900 mb-3"> More Transport</h4><div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
                data.transport.forEach(function(t) {
                    var priceHtml = '';
                    if (t.price) priceHtml = '<div class="font-bold text-amber-600 text-sm">KES ' + t.price.toLocaleString() + '</div>';
                    h += '<div class="bg-white border border-gray-200 rounded-xl p-3"><div class="font-bold text-sm">' + t.name + '</div>' + priceHtml + '<div class="text-xs text-gray-500">' + (t.type_label || '') + '</div></div>';
                });
                h += '</div></div>';
            }
            return h;
        }
    };
}

function switchMediaTab(tab) {
    var video = document.getElementById('media-video');
    var model = document.getElementById('media-model');
    var videoBtn = document.getElementById('tab-video-btn');
    var modelBtn = document.getElementById('tab-model-btn');
    if (tab === 'video') {
        video.style.display = '';
        model.style.display = 'none';
        videoBtn.classList.remove('bg-white/20', 'text-white/80');
        videoBtn.classList.add('bg-white/90', 'text-gray-800');
        modelBtn.classList.remove('bg-white/90', 'text-gray-800');
        modelBtn.classList.add('bg-white/20', 'text-white/80');
    } else {
        video.style.display = 'none';
        model.style.display = '';
        modelBtn.classList.remove('bg-white/20', 'text-white/80');
        modelBtn.classList.add('bg-white/90', 'text-gray-800');
        videoBtn.classList.remove('bg-white/90', 'text-gray-800');
        videoBtn.classList.add('bg-white/20', 'text-white/80');
        // Initialize product viewer if not already done
        if (!window._productViewer && document.getElementById('product-viewer-container')) {
            window._productViewer = new KiccProductViewer(
                'product-viewer-container',
                '{{ $product->model_url ?? "" }}',
                '{{ $product->image_url ?? "" }}'
            );
        }
    }
}
</script>
@endpush

    {{--  REVIEWS  --}}
    <div class="mt-20 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-review-widget
            :reviews="$productReviews ?? collect([])"
            :average="$reviewScore['average'] ?? 0"
            :count="$reviewScore['count'] ?? 0"
            :seed-source="$reviewScore['seed_source'] ?? null"
            :seed-url="$reviewSeed->external_url ?? null"
        />
        <x-review-form
            :use-product-reviews="true"
            :product-id="$product->id"
        />
    </div>

    @if(isset($tradeAgreements) && $tradeAgreements->isNotEmpty())
    <div class="mt-20">
        <h2 class="text-2xl font-black text-gray-900 mb-2" data-split>Export This Product</h2>
        <p class="text-gray-500 text-sm mb-6">Trade agreements that open foreign markets for this product category.</p>
        <div class="grid sm:grid-cols-3 gap-4">
            @foreach($tradeAgreements as $a)
            <a href="{{ route('trade.agreements.show', $a->slug) }}" class="bg-white border border-gray-200 rounded-2xl p-5 hover:border-[#046bd2]/40 transition-all card-hover">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#046bd2]/10 text-[#046bd2]">{{ $a->bloc?->name ?? $a->agreement_type }}</span>
                <h3 class="font-bold text-gray-900 text-sm mt-2 leading-snug">{{ $a->title }}</h3>
                <p class="text-gray-400 text-xs mt-1 line-clamp-2">{{ $a->summary }}</p>
                <span class="mt-3 inline-flex items-center gap-1 text-[#046bd2] text-xs font-bold">Learn more →</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Q&A Section --}}
    <div class="mt-20 max-w-3xl">
        <h2 class="text-2xl font-black text-gray-900 mb-6" data-split>Questions & Answers</h2>
        @auth
        <form method="POST" action="{{ route('product-questions.ask', $product->id) }}" class="flex gap-3 mb-6">
            @csrf
            <input type="text" name="question" placeholder="Ask a question about this product..." class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm" required>
            <button type="submit" class="bg-[#046bd2] text-white font-bold px-5 py-2.5 rounded-xl text-sm">Ask</button>
        </form>
        @else
        <p class="text-gray-400 text-sm mb-6"><a href="{{ route('login') }}" class="text-[#046bd2] font-bold">Sign in</a> to ask a question.</p>
        @endauth
        <div class="space-y-4">
            @forelse($questions as $q)
            <div class="bg-white border border-gray-200 rounded-2xl p-4">
                <div class="flex items-start justify-between"><div class="text-sm font-semibold text-gray-900">{{ $q->user->name ?? 'Anonymous' }}</div><div class="text-xs text-gray-400">{{ $q->created_at->diffForHumans() }}</div></div>
                <p class="text-sm text-gray-600 mt-1">{{ $q->question }}</p>
                <div class="mt-2 pl-4 border-l-2 border-[#046bd2]/30"><p class="text-sm text-gray-500">Answer: {{ $q->answer }}</p></div>
            </div>
            @empty
            <p class="text-gray-400 text-sm">No questions yet.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
function updateCompare(cb) {
    let checked = Array.from(document.querySelectorAll('.compare-checkbox:checked')).map(c => c.value);
    if (checked.length >= 2) {
        window.location.href = '{{ route('marketplace.compare') }}?ids=' + checked.join(',');
    }
}
// Track recently viewed
fetch('{{ route('recently-viewed.track') }}', {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},body:JSON.stringify({viewable_type:'{{ str_replace('\\','\\\\',get_class($product)) }}',viewable_id:{{ $product->id }}})});
</script>
@push('styles')
<style>
/* Responsive touch targets */
@media (max-width: 640px) {
    .nav-link { padding: 0.625rem 0.75rem; font-size: 0.75rem; }
    .h1-responsive { font-size: 1.75rem !important; line-height: 1.2 !important; }
    .h2-responsive { font-size: 1.5rem !important; }
    .section-padding { padding-top: 2.5rem !important; padding-bottom: 2.5rem !important; }
    .sticky-sidebar { position: relative !important; top: auto !important; }
    .mobile-full { width: 100% !important; }
    .touch-target { min-height: 44px; min-width: 44px; }
}
@media (max-width: 768px) {
    .md-hidden { display: none !important; }
    .mobile-stack { flex-direction: column !important; }
    .mobile-text-center { text-align: center !important; }
}
</style>
@endpush
@endSection