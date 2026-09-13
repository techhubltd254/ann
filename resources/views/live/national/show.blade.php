@extends('layouts.app')
@section('title', $booth->name . ' — National Exhibition')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <nav class="flex items-center gap-2 text-sm mb-6" style="color: var(--kicc-text);">
        <a href="{{ route('national.index') }}" class="hover:underline">National Exhibition</a>
        <span>/</span>
        <span style="color: var(--kicc-navy); font-weight: 500;">{{ $booth->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="card-kicc overflow-hidden">
                <div class="aspect-video relative" style="background: #000;">
                    @if($booth->stream_status === 'live' && $booth->liveStreams->first())
                    <x-hls-player :hls-url="$booth->liveStreams->first()->hls_url ?? ''" />
                    @elseif($booth->stream_status === 'offline' && $booth->authorization?->status === 'AUTHORIZED')
                    <div class="flex items-center justify-center h-full flex-col">
                        <p class="text-2xl mb-2">📡</p>
                        <p class="text-gray-400">Booth is authorized — stream will appear when live</p>
                    </div>
                    @elseif($booth->authorization?->status === 'STOPPED')
                    <div class="flex items-center justify-center h-full flex-col" style="background: rgba(220,38,38,0.1);">
                        <p class="text-xl font-bold text-red-400">Stream Terminated</p>
                    </div>
                    @else
                    <div class="flex items-center justify-center h-full">
                        <p class="text-gray-500">Awaiting authorization</p>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-kicc p-4 mt-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold" style="color: var(--kicc-navy);">{{ $booth->name }}</h1>
                        <p class="mt-2" style="color: var(--kicc-text);">{{ $booth->description }}</p>
                    </div>
                    <div class="flex gap-2">
                        @auth
                        <button id="favBtn" data-booth="{{ $booth->id }}" class="btn-kicc-outline px-3 py-1.5 rounded-lg text-sm">
                            {{ $isFavourite ? '♥ Favourited' : '♡ Favourite' }}
                        </button>
                        @endauth
                        <span class="px-3 py-1.5 rounded-lg text-sm font-medium {{
                            $booth->stream_status === 'live' ? 'badge-kicc-green' : ($booth->stream_status === 'paused' ? 'badge-kicc-gold' : 'bg-gray-100 text-gray-500')
                        }}">{{ $booth->stream_status === 'live' ? '● LIVE' : strtoupper($booth->stream_status ?? 'OFFLINE') }}</span>
                    </div>
                </div>
            </div>

            @if($booth->gps_lat && $booth->gps_lng)
            <div class="card-kicc p-4 mt-4">
                <h3 class="font-semibold mb-2" style="color: var(--kicc-navy);">📍 Physical Location</h3>
                <p class="text-sm" style="color: var(--kicc-text);">
                    {{ $booth->physical_address ?? 'GPS coordinates available' }}
                </p>
                <p class="text-xs mt-1" style="color: var(--kicc-text-light);">
                    {{ $booth->gps_lat }}, {{ $booth->gps_lng }}
                </p>
            </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="card-kicc p-4">
                <h3 class="font-semibold mb-3" style="color: var(--kicc-navy);">Contact</h3>
                @if($booth->contact_phone)<p class="text-sm mb-1">📞 {{ $booth->contact_phone }}</p>@endif
                @if($booth->contact_email)<p class="text-sm mb-1">✉️ {{ $booth->contact_email }}</p>@endif
                @if($booth->whatsapp)<p class="text-sm">💬 <a href="https://wa.me/{{ $booth->whatsapp }}" class="hover:underline" style="color: var(--kicc-red);">{{ $booth->whatsapp }}</a></p>@endif
            </div>

            <div class="card-kicc p-4">
                <h3 class="font-semibold mb-3" style="color: var(--kicc-navy);">📅 Book a Meeting</h3>
                @auth
                <form id="meetingForm" class="space-y-2 text-sm">
                    <input type="hidden" name="booth_id" value="{{ $booth->id }}">
                    <input type="text" name="visitor_name" placeholder="Your Name" required class="w-full p-2 border rounded" style="border-color: var(--kicc-border);">
                    <input type="email" name="visitor_email" placeholder="Your Email" required class="w-full p-2 border rounded" style="border-color: var(--kicc-border);">
                    <input type="tel" name="visitor_phone" placeholder="Phone (optional)" class="w-full p-2 border rounded" style="border-color: var(--kicc-border);">
                    <input type="datetime-local" name="slot_start" required class="w-full p-2 border rounded" style="border-color: var(--kicc-border);">
                    <button type="submit" class="w-full btn-kicc-primary py-2 rounded-lg">Request Meeting</button>
                </form>
                @else
                <p class="text-sm" style="color: var(--kicc-text);"><a href="{{ route('login') }}" class="hover:underline" style="color: var(--kicc-red);">Login</a> to book a meeting</p>
                @endauth
            </div>

            <div class="card-kicc p-4 text-sm" style="color: var(--kicc-text);">
                <h3 class="font-semibold mb-2" style="color: var(--kicc-navy);">Booth Health</h3>
                <p>Status: <span class="font-medium">{{ $booth->authorization?->status ?? 'N/A' }}</span></p>
                <p>Heartbeat: <span class="inline-block w-2 h-2 rounded-full" style="background: {{ match($health) { 'healthy' => '#059669', 'warning' => '#D97706', 'dead' => '#DC2626', default => '#9CA3AF' } }}"></span> {{ $health }}</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('favBtn')?.addEventListener('click', async () => {
    const res = await fetch('{{ route("api.live.favourite") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'},
        body: JSON.stringify({ booth_id: {{ $booth->id }} })
    });
    const data = await res.json();
    location.reload();
});
document.getElementById('meetingForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));
    const res = await fetch('{{ route("api.live.book-meeting") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    const result = await res.json();
    if(result.booking_id) {
        alert('Meeting request submitted! Check your calendar for confirmation.');
        form.reset();
    }
});
</script>
@endpush
@endsection