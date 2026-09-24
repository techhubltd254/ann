@extends('layouts.app')

@section('title', $venue->name . ' — KICC Venues')
@section('description', Str::limit($venue->description ?? '', 160))

@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10">
    @if(session('success'))
    <div class="bg-emerald-500/15 border border-emerald-500/25 text-emerald-400 rounded-xl px-5 py-4 mb-6 text-sm font-semibold" data-reveal>{{ session('success') }}</div>
    @endif
    <a href="{{ route('venues.index') }}" class="inline-flex items-center gap-1.5 text-[#5A6480] hover:text-gray-900 text-sm mb-6 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        All Venues
    </a>

    <div class="grid lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="rounded-2xl overflow-hidden h-80 bg-[#F9FAFB]">
                @php $img = $venue->cover_image ? media($venue->cover_image) : media("kicc/{$venue->slug}.jpg"); @endphp
                <img src="{{ $img }}" alt="{{ $venue->name }}" class="w-full h-full object-cover"
                     onerror="this.style.display='none';this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-6xl font-black text-gray-900/20\'>{{ $venue->name[0] }}</div>'">
            </div>
            <div class="mt-8">
                <h1 class="text-3xl font-black text-gray-900" data-split>{{ $venue->name }}</h1>
                <div class="flex flex-wrap items-center gap-3 mt-3">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border bg-[#FFCD05]/15 text-[#FFCD05] border-[#FFCD05]/30 capitalize">{{ $venue->venue_type }}</span>
                    @if($venue->capacity)
                    <span class="text-sm text-[#5A6480]">{{ $venue->capacity }} capacity</span>
                    @endif
                    @if($venue->city)
                    <span class="flex items-center gap-1.5 text-sm text-[#5A6480]">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $venue->city }}, Kenya
                    </span>
                    @endif
                </div>
                @if($venue->description)
                <p class="mt-5 text-[#5A6480] leading-relaxed text-sm">{{ $venue->description }}</p>
                @endif
            </div>
        </div>
        <div class="lg:col-span-1">
            <div class="bg-white border border-gray-200 rounded-2xl p-6 sticky top-24">
                <div class="font-black text-kicc-gold text-xl">{{ $venue->venue_type }}</div>
                <div class="text-gray-400 text-sm">Venue</div>

                {{-- Booking package (kicc.co.ke venue hire model) --}}
                <div class="mt-5 bg-[#901C1E]/5 border border-[#901C1E]/15 rounded-xl p-4">
                    <div class="text-[10px] font-black uppercase tracking-widest text-[#901C1E] mb-2">Booking Package</div>
                    <div class="space-y-1.5 text-xs text-[#5A6480]">
                        <div class="flex justify-between"><span>Full-day hire</span><span class="font-bold text-gray-900">KES {{ number_format(max(($venue->capacity ?? 50) * 350, 25000)) }}</span></div>
                        <div class="flex justify-between"><span>Half-day hire</span><span class="font-bold text-gray-900">KES {{ number_format(max(($venue->capacity ?? 50) * 200, 15000)) }}</span></div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-[#901C1E]/10 text-[10px] text-[#5A6480] leading-relaxed">
                        Includes: PA system · AV equipment · security · technical support · internet. Catering quoted per event.
                    </div>
                </div>

                <div class="mt-4">
                    @include('components.pipeline-badge', [
                        'pipelineCode' => $venue->pipelineCode(),
                        'feeRate' => $venue->feeRate() . '%',
                        'isLocked' => \Illuminate\Support\Facades\DB::table('pipeline_registrations')
                            ->where('code', $venue->pipelineCode())->value('earning_locked') ?? false,
                    ])
                </div>

                <div class="mt-6 space-y-3" id="kicc-booking-modal">
                    <button onclick="document.getElementById('kicc-booking-overlay').style.display='flex'; document.body.style.overflow='hidden';" class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-[#901C1E] text-gray-900 hover:bg-[#7b1618] active:scale-[0.97]" data-magnetic>Request Booking</button>
                    <a href="{{ route('packages.index') }}" class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-4 text-xs h-11 rounded-xl border border-[#901C1E]/30 text-[#901C1E] hover:bg-[#901C1E]/5" data-magnetic>See Exhibitor Packages</a>

                    {{-- Booking inquiry modal --}}
                    <div id="kicc-booking-overlay" class="fixed inset-0 z-[100] items-center justify-center p-5" style="display:none; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);">
                        <div class="bg-white border border-gray-300/12 rounded-2xl p-6 w-full max-w-md shadow-2xl" style="max-height:90vh; overflow-y:auto">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="font-black text-gray-900 text-lg">Request Booking</h3>
                                    <p class="text-[#5A6480] text-xs mt-1">{{ $venue->name }} · {{ ucfirst($venue->venue_type ?? '') }}</p>
                                </div>
                                <button onclick="document.getElementById('kicc-booking-overlay').style.display='none'; document.body.style.overflow='';" class="text-[#5A6480] hover:text-gray-900 p-1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('venues.inquire', $venue->slug) }}" class="space-y-3">
                                @csrf
                                <input type="text" name="name" required placeholder="Your name"
                                       class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 text-sm placeholder:text-[#5A6480] outline-none focus:ring-2 focus:ring-kicc-gold/60">
                                <input type="email" name="email" required placeholder="Email address"
                                       class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 text-sm placeholder:text-[#5A6480] outline-none focus:ring-2 focus:ring-kicc-gold/60">
                                <input type="tel" name="phone" placeholder="Phone (optional)"
                                       class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 text-sm placeholder:text-[#5A6480] outline-none focus:ring-2 focus:ring-kicc-gold/60">
                                <div class="grid grid-cols-2 gap-2">
                                    <select name="event_type" required class="h-11 px-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 text-sm outline-none focus:ring-2 focus:ring-kicc-gold/60">
                                        <option value="">Event type…</option>
                                        @foreach(['Conference','Exhibition','Banquet / Gala','Concert','Wedding','Product Launch','Board Meeting','Training / Workshop'] as $et)
                                        <option value="{{ $et }}">{{ $et }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="guests" min="1" placeholder="Est. guests"
                                           class="h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 text-sm placeholder:text-[#5A6480] outline-none focus:ring-2 focus:ring-kicc-gold/60">
                                </div>
                                <input type="date" name="event_date" required
                                       class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 text-sm outline-none focus:ring-2 focus:ring-kicc-gold/60 [color-scheme:dark]">
                                <textarea name="message" rows="3" placeholder="Tell us about your event (requirements, catering, AV…)"
                                          class="w-full px-4 py-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-gray-900 text-sm placeholder:text-[#5A6480] outline-none focus:ring-2 focus:ring-kicc-gold/60"></textarea>
                                <button type="submit" class="w-full h-12 rounded-xl bg-[#901C1E] text-gray-900 font-bold text-sm hover:bg-[#7b1618] transition-colors active:scale-[0.98]" data-magnetic>
                                    Send Booking Inquiry
                                </button>
                                <p class="text-[#5A6480] text-[11px] text-center">Same process as kicc.co.ke — our events team responds within 24 hours.</p>
                            </form>
                        </div>
                    </div>
                </div>

                @if($venue->amenities)
                @php $amenities = is_array($venue->amenities) ? $venue->amenities : (json_decode($venue->amenities ?? '[]', true) ?? []); @endphp
                @if(count($amenities) > 0)
                <div class="mt-6 pt-5 border-t border-gray-200">
                    <h4 class="text-xs font-bold text-[#5A6480] uppercase tracking-wider mb-3">Amenities</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($amenities as $a)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold tracking-wide border bg-sky-50 text-[#5A6480] border-gray-200">{{ $a }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @endif

                <div class="mt-6 pt-5 border-t border-gray-200 space-y-3">
                    @if($venue->city)<div class="flex items-center gap-3 text-sm text-[#5A6480]"><svg class="w-3.5 h-3.5 text-kicc-gold shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>{{ $venue->city }}, Kenya</span></div>@endif
                </div>
            </div>
        </div>
    </div>
</div>
@endSection