@extends('layouts.blank')

@section('title', 'Provider Portal — KICC')

@section('content')
@php $accent = '#0EA5E9'; @endphp
<div class="flex min-h-screen bg-[#F9FAFB]">
    {{-- Sidebar --}}
    <div class="w-56 bg-white border-r border-gray-200 flex flex-col shrink-0 min-h-screen">
        <div class="flex items-center gap-3 px-4 border-b border-gray-200 h-16">
            <a href="{{ route('admin.portal') }}" class="text-gray-400 hover:text-gray-700 text-xs">&larr; Portal</a>
            <div class="flex items-center gap-2">
                <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-7 w-auto" style="filter: brightness(0);">
                <div style="color: {{ $accent }}" class="text-[9px] font-black tracking-[0.15em] uppercase">Provider<br>Portal</div>
            </div>
        </div>
        <div class="flex-1 py-3 overflow-y-auto">
            @foreach($navItems as $item)
            <a href="{{ route('provider.admin', ['tab' => $item['tab']]) }}"
               class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all {{ $tab === $item['tab'] ? 'text-gray-900 border-r-2' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800' }}"
               style="{{ $tab === $item['tab'] ? 'background: '.$accent.'22; border-color: '.$accent : '' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                <span class="truncate text-xs">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </div>
        <a href="/" class="flex items-center gap-3 px-4 py-4 border-t border-gray-200 text-gray-400 hover:text-gray-700 text-xs transition-colors">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            <span>Exit</span>
        </a>
    </div>

    {{-- Main --}}
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-white border-b border-gray-200 px-6 h-16 flex items-center justify-between shrink-0">
            <div>
                <div class="font-black text-gray-900 text-sm">{{ $meta['company_name'] ?? $user->name }}</div>
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: {{ $accent }}">{{ ucfirst($type) }} Provider · {{ ($meta['approved'] ?? false) ? 'Government Certified ✓' : 'Pending Certification' }}</div>
            </div>
            @if($pendingCount > 0)
            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-600">{{ $pendingCount }} awaiting KICC approval</span>
            @endif
        </div>
        <div class="flex-1 overflow-y-auto p-6">

            @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-5 py-3 mb-6 text-sm">{{ session('success') }}</div>
            @endif
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-3 mb-6 text-sm">{{ $errors->first() }}</div>
            @endif

            {{-- ═══════ SERVICES ═══════ --}}
            @if($tab === 'services')
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">My Services &amp; Prices ({{ $services->count() }})</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left text-[10px] uppercase tracking-widest text-gray-400 border-b border-gray-100">
                            <th class="pb-3 pr-4">Service</th><th class="pb-3 pr-4">Detail</th><th class="pb-3 pr-4">Price (KES)</th><th class="pb-3 pr-4">Status</th><th class="pb-3">Edit Price</th>
                        </tr></thead>
                        <tbody>
                        @foreach($services as $s)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="py-2.5 pr-4 font-semibold text-gray-900">{{ $s->title }}</td>
                            <td class="py-2.5 pr-4 text-gray-400 text-xs">{{ $s->sub }}</td>
                            <td class="py-2.5 pr-4 font-bold text-gray-900">{{ number_format($s->price) }}</td>
                            <td class="py-2.5 pr-4">
                                @if($s->is_active)
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600">LIVE</span>
                                @else
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">PENDING APPROVAL</span>
                                @endif
                            </td>
                            <td class="py-2.5">
                                <form method="POST" action="{{ route('provider.admin.price') }}" class="flex gap-1.5">
                                    @csrf
                                    <input type="hidden" name="table_key" value="{{ $s->table_key }}">
                                    <input type="hidden" name="id" value="{{ $s->id }}">
                                    <input type="number" name="price" min="1" placeholder="New price" required class="w-24 h-8 px-2 rounded-lg border border-gray-200 text-xs">
                                    <button class="h-8 px-3 rounded-lg text-gray-900 text-[10px] font-bold" style="background: {{ $accent }}">SAVE</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ═══════ ADD SERVICE ═══════ --}}
            @if($tab === 'add')
            <div class="bg-white border border-gray-200 rounded-2xl p-6 max-w-xl">
                <h3 class="font-bold text-gray-900 mb-2">Add a Service</h3>
                <p class="text-xs text-gray-400 mb-5">New services go live after KICC certification review.</p>
                <form method="POST" action="{{ route('provider.admin.add') }}" class="space-y-3">
                    @csrf
                    @if($type === 'hotel')
                        <input name="name" required placeholder="Room name (e.g. Ocean View Suite)" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                        <select name="room_type" required class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm text-gray-600">
                            <option value="">Room type…</option>
                            @foreach(['standard','deluxe','suite','family'] as $rt)<option>{{ $rt }}</option>@endforeach
                        </select>
                        <input name="max_guests" type="number" min="1" required placeholder="Max guests" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                        <input name="price_per_night" type="number" min="1" required placeholder="Price per night (KES)" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                    @elseif($type === 'airline')
                        <div class="grid grid-cols-2 gap-2">
                            <input name="origin" required placeholder="From (e.g. NBO)" maxlength="3" class="h-11 px-4 rounded-xl border border-gray-200 text-sm uppercase">
                            <input name="destination" required placeholder="To (e.g. MBA)" maxlength="3" class="h-11 px-4 rounded-xl border border-gray-200 text-sm uppercase">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input name="departure_time" type="time" required class="h-11 px-4 rounded-xl border border-gray-200 text-sm">
                            <input name="arrival_time" type="time" required class="h-11 px-4 rounded-xl border border-gray-200 text-sm">
                        </div>
                        <input name="base_price" type="number" min="1" required placeholder="Base fare (KES)" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                    @else
                        <input name="airport" required placeholder="Airport (e.g. MBA)" maxlength="3" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm uppercase">
                        <select name="vehicle_type" required class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm text-gray-600">
                            <option value="">Vehicle type…</option>
                            @foreach(['economy','comfort','van','luxury','helicopter'] as $vt)<option>{{ $vt }}</option>@endforeach
                        </select>
                        <input name="capacity" type="number" min="1" required placeholder="Seats" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                        <input name="price" type="number" min="1" required placeholder="Price per transfer (KES)" class="w-full h-11 px-4 rounded-xl border border-gray-200 text-sm">
                    @endif
                    <button class="w-full h-12 rounded-xl text-gray-900 font-bold text-sm" style="background: {{ $accent }}">Submit for Certification</button>
                </form>
            </div>
            @endif

            {{-- ═══════ BOOKINGS ═══════ --}}
            @if($tab === 'bookings')
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-5">Bookings for My Services ({{ $bookings->count() }})</h3>
                @forelse($bookings as $b)
                <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                    <div>
                        <div class="font-semibold text-gray-900 text-sm">{{ $b->booking_reference }}</div>
                        <div class="text-xs text-gray-400">{{ $b->created_at }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-black text-gray-900">KES {{ number_format($b->total) }}</div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ in_array($b->status, ['confirmed','allocated']) ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">{{ strtoupper($b->status) }}</span>
                    </div>
                </div>
                @empty
                <p class="text-gray-400 text-sm py-6 text-center">No bookings yet.</p>
                @endforelse
            </div>
            @endif

            {{-- ═══════ MONEY ═══════ --}}
            @if($tab === 'money')
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">KES {{ number_format($money['total']) }}</div><div class="text-xs text-gray-400 mt-1">Gross bookings</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black" style="color: {{ $accent }}">KES {{ number_format($money['total'] * (1 - $money['commission'])) }}</div><div class="text-xs text-gray-400 mt-1">Your net (after {{ $money['commission']*100 }}% platform fee)</div></div>
                <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $money['count'] }}</div><div class="text-xs text-gray-400 mt-1">Total bookings</div></div>
            </div>
            <div class="bg-white border border-gray-200 rounded-2xl p-6">
                <h3 class="font-bold text-gray-900 mb-3">How money flows to you</h3>
                <div class="grid md:grid-cols-4 gap-3 text-xs">
                    <div class="rounded-xl p-4 border border-gray-100"><div class="font-bold text-gray-900 mb-1">1. Traveler pays</div><div class="text-gray-500">Unified payment intent (M-Pesa/card).</div></div>
                    <div class="rounded-xl p-4 border border-gray-100"><div class="font-bold text-gray-900 mb-1">2. Escrow holds</div><div class="text-gray-500">Funds held until service is delivered.</div></div>
                    <div class="rounded-xl p-4 border border-gray-100"><div class="font-bold text-gray-900 mb-1">3. You serve</div><div class="text-gray-500">Flight flown, room stayed, cab driven.</div></div>
                    <div class="rounded-xl p-4 border border-gray-100"><div class="font-bold text-gray-900 mb-1">4. Weekly payout</div><div class="text-gray-500">Net amount settled to your account.</div></div>
                </div>
            </div>
            @endif

            {{-- ═══════ CERTIFICATION ═══════ --}}
            @if($tab === 'status')
            <div class="bg-white border border-gray-200 rounded-2xl p-8 max-w-2xl">
                <h3 class="font-bold text-gray-900 mb-4">Government Certification</h3>
                <div class="flex items-center gap-3 mb-5">
                    @if($meta['approved'] ?? false)
                    <span class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center"><svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></span>
                    <div><div class="font-bold text-emerald-600">Certified Provider</div><div class="text-xs text-gray-400">Your services are live on the platform.</div></div>
                    @else
                    <span class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center"><svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg></span>
                    <div><div class="font-bold text-amber-600">Pending Certification</div><div class="text-xs text-gray-400">KICC reviews your services before they go live.</div></div>
                    @endif
                </div>
                <p class="text-xs text-gray-500 leading-relaxed">Certification requirement: every travel &amp; accommodation provider on the KICC platform must be government-certified. KICC's admin team verifies each new service and price change before it appears to travelers.</p>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
