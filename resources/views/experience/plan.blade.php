@extends('layouts.app')
@section('title', "Build Your {$anchor->name} Experience")
@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10" x-data="experienceBuilder()">
    <div class="flex items-center gap-3 mb-3">
        <div class="h-px w-8 bg-[#FFCD05]"></div>
        <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">Build Your Experience</span>
    </div>
    <h1 class="text-3xl md:text-5xl font-black text-gray-900 leading-tight mb-2">{{ $anchor->name }}</h1>
    <p class="text-gray-500 mb-8">{{ $context['county']?->name ?? '' }} · {{ $context['anchor_type'] ?? 'attraction' }}</p>

    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
            {{-- Anchor attraction card --}}
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                @if(method_exists($anchor, 'image_url') && $anchor->image_url)
                <div class="aspect-video bg-gray-100 relative">
                    <img src="{{ $anchor->image_url }}" alt="{{ $anchor->name }}" class="w-full h-full object-cover">
                </div>
                @endif
                <div class="p-6">
                    <h2 class="text-xl font-bold">{{ $anchor->name }}</h2>
                    <p class="text-gray-500 mt-2">{{ $anchor->description ?? Str::limit($anchor->short_description ?? '', 200) }}</p>
                    <div class="flex items-center gap-4 mt-4 text-sm">
                        <span class="text-gray-500">Entry: <strong>KES {{ number_format($anchor->entry_fee ?? $context['estimated_total'] ?? 500) }}</strong></span>
                        @if(method_exists($anchor, 'opening_hours') && $anchor->opening_hours)
                        <span class="text-gray-500">🕐 {{ $anchor->opening_hours }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- You May Also Like: Places to Visit --}}
            @if(!empty($correlations['places_to_visit']))
            <div>
                <h3 class="text-lg font-bold mb-4">📍 More Places You'll Love</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($correlations['places_to_visit'] as $place)
                    <label class="bg-white rounded-xl border border-gray-200 p-4 flex gap-4 cursor-pointer hover:border-blue-300 transition-colors @if(!empty($place['selected'])) border-blue-500 bg-blue-50 @endif">
                        <input type="checkbox" class="mt-1" @checked(!empty($place['selected'])) @change="toggleItem('visit', {{ $place['id'] }}, '{{ addslashes($place['name']) }}', {{ $place['entry_fee'] ?? 0 }})">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm">{{ $place['name'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit($place['description'] ?? '', 80) }}</p>
                            @if(!empty($place['entry_fee']) && $place['entry_fee'] > 0)
                            <p class="text-xs font-medium text-blue-600 mt-1">KES {{ number_format($place['entry_fee']) }}</p>
                            @endif
                            @if(!empty($place['distance_km']))
                            <p class="text-xs text-gray-400">{{ $place['distance_km'] }} km away</p>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Places to Stay --}}
            @if(!empty($correlations['places_to_stay']))
            <div>
                <h3 class="text-lg font-bold mb-4">🏨 Places to Stay</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($correlations['places_to_stay'] as $hotel)
                    <label class="bg-white rounded-xl border border-gray-200 p-4 flex gap-4 cursor-pointer hover:border-green-300 transition-colors">
                        <input type="checkbox" @change="toggleItem('stay', {{ $hotel['id'] }}, '{{ addslashes($hotel['name']) }}', {{ $hotel['price_per_night'] ?? 0 }})">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm">{{ $hotel['name'] }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ Str::limit($hotel['description'] ?? '', 80) }}</p>
                            @if(!empty($hotel['price_per_night']))
                            <p class="text-xs font-medium text-green-600 mt-1">From KES {{ number_format($hotel['price_per_night']) }}/night</p>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Transport Options --}}
            @if(!empty($correlations['transport']))
            <div>
                <h3 class="text-lg font-bold mb-4">🚗 Getting There</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($correlations['transport'] as $transport)
                    <label class="bg-white rounded-xl border border-gray-200 p-4 flex gap-4 cursor-pointer hover:border-purple-300 transition-colors">
                        <input type="checkbox" @change="toggleItem('transport', {{ $transport['id'] ?? 0 }}, '{{ addslashes($transport['name'] ?? ($transport['type'] ?? 'Transport')) }}', {{ $transport['price'] ?? 0 }})">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm">{{ $transport['name'] ?? ($transport['type'] ?? 'Transport option') }}</p>
                            @if(!empty($transport['price']))
                            <p class="text-xs font-medium text-purple-600 mt-1">KES {{ number_format($transport['price']) }}</p>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Add-ons --}}
            @if(!empty($addonOptions))
            <div>
                <h3 class="text-lg font-bold mb-4">✨ Enhance Your Trip</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($addonOptions as $key => $addon)
                    <label class="bg-white rounded-xl border border-gray-200 p-4 flex gap-4 cursor-pointer hover:border-yellow-300 transition-colors">
                        <input type="checkbox" @change="toggleAddon('{{ $key }}', '{{ addslashes($addon['label']) }}', {{ $addon['price'] }}, {{ $addon['per_guest'] ? 'true' : 'false' }})">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm">{{ $addon['label'] }}</p>
                            <p class="text-xs font-medium text-yellow-600 mt-1">KES {{ number_format($addon['price']) }}{{ $addon['per_guest'] ? '/person' : '' }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar: Trip Summary --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-gray-200 p-6 sticky top-24" x-show="selectedCount > 0">
                <h3 class="font-bold text-lg mb-4">Your Trip</h3>
                <div class="text-sm text-gray-500 mb-4">
                    <span x-text="selectedCount"></span> items selected
                </div>

                <div class="space-y-3 mb-6" x-show="items.length > 0">
                    <template x-for="(item, i) in items" :key="i">
                        <div class="flex justify-between items-center text-sm">
                            <span class="truncate mr-2" x-text="item.name"></span>
                            <span class="font-medium whitespace-nowrap" x-text="'KES ' + numberFormat(item.price)"></span>
                        </div>
                    </template>
                </div>

                <div class="space-y-3 mb-6" x-show="addons.length > 0">
                    <template x-for="(addon, i) in addons" :key="'a'+i">
                        <div class="flex justify-between items-center text-sm text-yellow-700">
                            <span class="truncate mr-2" x-text="addon.label"></span>
                            <span class="font-medium whitespace-nowrap" x-text="'KES ' + numberFormat(addon.price * (addon.perGuest ? guestCount : 1))"></span>
                        </div>
                    </template>
                </div>

                {{-- Guest count --}}
                <div class="mb-4">
                    <label class="text-sm font-medium">Number of guests</label>
                    <input type="number" x-model="guestCount" min="1" max="20" class="border rounded w-full px-3 py-2 mt-1">
                </div>

                {{-- Trip duration --}}
                <div class="mb-4">
                    <label class="text-sm font-medium">Trip duration (days)</label>
                    <input type="number" x-model="tripDays" min="1" max="14" class="border rounded w-full px-3 py-2 mt-1">
                </div>

                {{-- Discount badge --}}
                <div x-show="selectedCount >= 3" class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4 text-sm">
                    🎉 <strong x-text="discountPercent + '%'"></strong> package discount applied!
                </div>

                {{-- Total --}}
                <div class="border-t pt-4">
                    <div class="flex justify-between items-center text-lg font-bold">
                        <span>Total</span>
                        <span x-text="'KES ' + numberFormat(grandTotal)"></span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Entry + selections + add-ons</p>
                </div>

                <form method="POST" action="{{ route('experience.build', ['type' => $context['anchor_type'] ?? 'attraction', 'id' => $anchor->id]) }}" class="mt-6">
                    @csrf
                    <input type="hidden" name="selections" :value='JSON.stringify(allSelections)'>
                    <input type="hidden" name="days" :value="tripDays">
                    <input type="hidden" name="start_date" :value="startDate">
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition-colors">
                        Book This Experience
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function experienceBuilder() {
    return {
        items: [],
        addons: [],
        guestCount: 1,
        tripDays: {{ config('experience.itinerary.default_days', 3) }},
        startDate: '{{ now()->addDay()->toDateString() }}',
        entryFee: {{ $estimatedTotal ?? 500 }},
        selectedCount: 0,

        toggleItem(type, id, name, price) {
            const key = type + '-' + id;
            const idx = this.items.findIndex(i => i.key === key);
            if (idx >= 0) {
                this.items.splice(idx, 1);
            } else {
                this.items.push({ key, type, id, name, price: price || 0 });
            }
            this.selectedCount = this.items.length;
        },

        toggleAddon(key, label, price, perGuest) {
            const idx = this.addons.findIndex(a => a.key === key);
            if (idx >= 0) {
                this.addons.splice(idx, 1);
            } else {
                this.addons.push({ key, label, price, perGuest });
            }
        },

        get allSelections() {
            return [
                ...this.items.map(i => ({ type: i.type, id: i.id, name: i.name, price: i.price })),
                ...this.addons.map(a => ({ type: 'addon', name: a.label, price: a.price * (a.perGuest ? this.guestCount : 1) }))
            ];
        },

        get discountPercent() {
            const counts = {{ json_encode(config('experience.pricing.package_discounts', [])) }};
            let pct = 0;
            const total = this.items.length + this.addons.length;
            Object.entries(counts).forEach(([threshold, percent]) => {
                if (total >= parseInt(threshold)) pct = percent;
            });
            return pct;
        },

        get subtotal() {
            return this.items.reduce((s, i) => s + i.price, 0) + this.entryFee;
        },

        get addonsTotal() {
            return this.addons.reduce((s, a) => s + a.price * (a.perGuest ? this.guestCount : 1), 0);
        },

        get grandTotal() {
            const base = (this.subtotal + this.addonsTotal) * this.guestCount;
            return Math.round(base * (1 - this.discountPercent / 100));
        },

        numberFormat(n) {
            return Number(n || 0).toLocaleString('en-KE');
        }
    }
}
</script>
@endsection