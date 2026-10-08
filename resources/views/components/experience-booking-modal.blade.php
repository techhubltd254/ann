@props([
    'destinationType' => 'institution',
    'destinationId' => 0,
    'destinationName' => '',
    'destinationLat' => null,
    'destinationLng' => null,
    'countyId' => null,
])

<div
    x-data="experienceBookingForm({
        destinationType: '{{ $destinationType }}',
        destinationId: {{ $destinationId }},
        destinationName: '{{ $destinationName }}',
        destinationLat: {{ $destinationLat ?? 'null' }},
        destinationLng: {{ $destinationLng ?? 'null' }},
        countyId: {{ $countyId ?? 'null' }},
    })"
    x-cloak
>
    {{-- Trigger button --}}
    <button type="button" @click="open = true"
            class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-sm h-11 rounded-xl bg-[#0b0b0b] text-white hover:bg-[#16275f] active:scale-[0.97]">
         Book as Experience
    </button>

    {{-- Modal overlay --}}
    <template x-teleport="body">
        <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition.opacity.duration.200ms>
            <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
                 @click.stop>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-lg font-black text-gray-900">Book as Experience</h2>
                        <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-700 p-1">&times;</button>
                    </div>

                    <p class="text-sm text-gray-600 mb-5">
                        Plan your trip to <strong x-text="destinationName"></strong> with transport, dates, and pricing tailored to you.
                    </p>

                    {{-- Origin --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Where are you coming from?</label>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[10px] text-gray-400 mb-1 block">County (optional)</label>
                                <select x-model="originCountyId"
                                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-1 focus:ring-[#FFCD05] outline-none">
                                    <option value="">Select county</option>
                                    <template x-for="c in counties" :key="c.id">
                                        <option :value="c.id" x-text="c.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] text-gray-400 mb-1 block">Specific location</label>
                                <input type="text" x-model="originLocation" placeholder="Town or landmark"
                                       class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-1 focus:ring-[#FFCD05] outline-none">
                            </div>
                        </div>
                    </div>

                    {{-- Dates --}}
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Departure</label>
                            <input type="date" x-model="departureDate" :min="today"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-1 focus:ring-[#FFCD05] outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Return</label>
                            <input type="date" x-model="returnDate" :min="departureDate || today"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-1 focus:ring-[#FFCD05] outline-none">
                        </div>
                    </div>

                    {{-- Guests --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Number of Guests</label>
                        <select x-model="guestCount"
                                class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-1 focus:ring-[#FFCD05] outline-none">
                            <template x-for="i in 20" :key="i">
                                <option :value="i" x-text="i"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Transport mode --}}
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Transport Mode</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="transportMode='road'"
                                    class="flex flex-col items-center gap-1 py-2.5 rounded-xl border-2 transition-all"
                                    :class="transportMode==='road' ? 'border-[#0b0b0b] bg-[#0b0b0b]/5' : 'border-gray-200 hover:border-gray-300'">
                                <span class="text-lg"></span>
                                <span class="text-xs font-bold" :class="transportMode==='road' ? 'text-[#0b0b0b]' : 'text-gray-500'">Road</span>
                            </button>
                            <button type="button" @click="transportMode='train'"
                                    class="flex flex-col items-center gap-1 py-2.5 rounded-xl border-2 transition-all"
                                    :class="transportMode==='train' ? 'border-[#0b0b0b] bg-[#0b0b0b]/5' : 'border-gray-200 hover:border-gray-300'">
                                <span class="text-lg"></span>
                                <span class="text-xs font-bold" :class="transportMode==='train' ? 'text-[#0b0b0b]' : 'text-gray-500'">Train</span>
                            </button>
                            <button type="button" @click="transportMode='air+rail'"
                                    class="flex flex-col items-center gap-1 py-2.5 rounded-xl border-2 transition-all"
                                    :class="transportMode==='air+rail' ? 'border-[#0b0b0b] bg-[#0b0b0b]/5' : 'border-gray-200 hover:border-gray-300'">
                                <span class="text-lg"></span>
                                <span class="text-xs font-bold" :class="transportMode==='air+rail' ? 'text-[#0b0b0b]' : 'text-gray-500'">Air/Rail</span>
                            </button>
                        </div>
                    </div>

                    {{-- Loading spinner + price preview --}}
                    <div x-show="pricingLoading" class="flex items-center justify-center py-4">
                        <svg class="w-6 h-6 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    </div>

                    <div x-show="!pricingLoading && pricePreview" class="bg-gray-50 rounded-xl p-4 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-500">Base price</span>
                            <span class="text-sm font-bold text-gray-900" x-text="'KES ' + pricePreview.base_price.toLocaleString()"></span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-500">Rating multiplier</span>
                            <span class="text-sm text-gray-900" x-text="pricePreview.rating_multiplier + '×'"></span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-500">Season multiplier</span>
                            <span class="text-sm text-gray-900" x-text="pricePreview.season_multiplier + '×'"></span>
                        </div>
                        <div x-show="pricePreview.distance_multiplier !== 1" class="flex items-center justify-between mb-2">
                            <span class="text-xs text-gray-500">Distance multiplier</span>
                            <span class="text-sm text-gray-900" x-text="pricePreview.distance_multiplier + '×'"></span>
                        </div>
                        <div class="border-t border-gray-200 pt-2 mt-2 flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-700">Estimated total</span>
                            <span class="text-lg font-black text-[#0b0b0b]" x-text="'KES ' + (pricePreview.final_price * guestCount).toLocaleString()"></span>
                        </div>
                    </div>

                    {{-- Transport options --}}
                    <div x-show="transportMode && transportOptions.length > 0" class="mb-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Choose transport</label>
                        <div class="space-y-2">
                            <template x-for="(t, i) in transportOptions" :key="i">
                                <label class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                                       :class="selectedTransportId === t.id ? 'border-[#0b0b0b] bg-[#0b0b0b]/5' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" :value="t.id" x-model="selectedTransportId" class="hidden">
                                    <span class="text-lg shrink-0" x-text="t.type_emoji || ''"></span>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-bold text-gray-900" x-text="t.name"></div>
                                        <div class="text-[10px] text-gray-500" x-text="t.type_label + (t.distance_km ? ' · ' + t.distance_km + ' km' : '') + (t.eta_minutes ? ' · ' + t.eta_minutes + ' min' : '')"></div>
                                    </div>
                                    <div x-show="t.price" class="text-sm font-black text-[#0b0b0b]" x-text="'KES ' + t.price.toLocaleString()"></div>
                                    <div x-show="!t.price" class="text-xs text-gray-400">Enquire</div>
                                </label>
                            </template>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="button" @click="submitBooking()" :disabled="submitting || !originLocation"
                            class="w-full h-12 rounded-xl font-bold tracking-wide transition-all text-sm"
                            :class="submitting ? 'bg-gray-200 text-gray-500' : 'bg-[#b3261e] text-white hover:bg-[#7b1618]'">
                        <span x-show="!submitting"> Add to Experience Cart</span>
                        <span x-show="submitting" class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            Booking...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
function experienceBookingForm(config) {
    return {
        open: false,
        destinationType: config.destinationType,
        destinationId: config.destinationId,
        destinationName: config.destinationName,
        destinationLat: config.destinationLat,
        destinationLng: config.destinationLng,
        countyId: config.countyId,

        today: new Date().toISOString().split('T')[0],
        departureDate: '',
        returnDate: '',
        guestCount: 1,
        originLocation: '',
        originCountyId: '',
        transportMode: null,
        selectedTransportId: null,

        counties: [],
        transportOptions: [],
        pricePreview: null,
        pricingLoading: false,
        submitting: false,

        init() {
            fetch('/experience/counties')
                .then(r => r.json())
                .then(data => { this.counties = data; })
                .catch(() => {});
        },

        fetchPricing() {
            if (!this.departureDate || !this.originLocation) return;
            this.pricingLoading = true;
            fetch('/api/experience/pricing-preview', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: JSON.stringify({
                    destination_type: this.destinationType,
                    destination_id: this.destinationId,
                    departure_date: this.departureDate,
                    origin_location: this.originLocation,
                    origin_county_id: this.originCountyId || null,
                })
            })
            .then(r => r.json())
            .then(data => {
                this.pricePreview = data.preview;
                this.pricingLoading = false;
            })
            .catch(() => { this.pricingLoading = false; });
        },

        fetchTransport() {
            if (!this.transportMode) return;
            fetch('/experience/transport-options?county_id=' + (this.countyId || '') + '&lat=' + (this.destinationLat || 0) + '&lng=' + (this.destinationLng || 0))
                .then(r => r.json())
                .then(data => { this.transportOptions = data || []; })
                .catch(() => {});
        },

        submitBooking() {
            if (this.submitting || !this.originLocation) return;
            this.submitting = true;

            fetch('/experience/create', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''},
                body: JSON.stringify({
                    destination_type: this.destinationType,
                    destination_id: this.destinationId,
                    origin_location: this.originLocation,
                    origin_county_id: this.originCountyId || null,
                    departure_date: this.departureDate,
                    return_date: this.returnDate,
                    guest_count: this.guestCount,
                    transport_mode: this.transportMode,
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.open = false;
                    window.location.href = '/cart';
                } else {
                    alert('Error creating booking');
                    this.submitting = false;
                }
            })
            .catch(() => { this.submitting = false; });
        }
    };
}
</script>
@endpush