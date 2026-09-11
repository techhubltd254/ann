@extends('layouts.app')
@section('title', 'Screen Broadcast Control')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold" style="color: var(--kicc-navy);">📺 Screen Broadcast Control</h1>
            <p class="text-sm mt-1" style="color: var(--kicc-text);">Route live streams to screens across Kenya — KICC Nairobi, Elite Sounds, Digital Mara, President Town Hall</p>
        </div>
        <a href="{{ route('live.screens.broadcast') }}?refresh=1" class="btn-kicc-outline px-3 py-1.5 rounded-lg text-sm">Refresh Status</a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Live Streams Available --}}
    <div class="card-kicc p-4 mb-6">
        <h3 class="font-semibold mb-3" style="color: var(--kicc-navy);">Active Live Streams</h3>
        @if($liveStreams->isEmpty())
        <p class="text-sm" style="color: var(--kicc-text-light);">No active live streams. Start a stream from an exhibitor studio first.</p>
        @else
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach($liveStreams as $stream)
            <div class="p-3 rounded-lg text-sm" style="background: rgba(5,150,105,0.08); border: 1px solid rgba(5,150,105,0.2);">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="font-medium">{{ $stream->title ?? $stream->booth?->name ?? 'Stream #'.$stream->id }}</span>
                </div>
                <span class="text-xs" style="color: var(--kicc-text-light);">ID: {{ $stream->id }} · {{ $stream->viewer_count ?? 0 }} viewers</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Quick Route by Venue --}}
    <div class="card-kicc p-4 mb-6">
        <h3 class="font-semibold mb-3" style="color: var(--kicc-navy);">Route to Venue</h3>
        @if($liveStreams->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            @php $venues = [
                'kicc-nairobi' => 'KICC Nairobi — All Screens',
                'elite-sounds' => 'Elite Sounds',
                'digital-mara' => 'Digital Mara — Full Block',
                'town-hall' => 'President Town Hall',
            ]; @endphp
            @foreach($venues as $key => $label)
            <div class="p-4 rounded-lg" style="background: var(--kicc-bg-alt);">
                <h4 class="font-medium text-sm mb-2" style="color: var(--kicc-navy);">{{ $label }}</h4>
                <form method="POST" action="{{ route('live.screens.route-venue') }}" class="space-y-2">
                    @csrf
                    <input type="hidden" name="venue" value="{{ $key }}">
                    <select name="live_stream_id" class="w-full text-xs p-1.5 border rounded" style="border-color: var(--kicc-border);">
                        @foreach($liveStreams as $s)
                        <option value="{{ $s->id }}">{{ $s->title ?? 'Stream #'.$s->id }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <button name="action" value="assign" class="flex-1 text-xs py-1.5 rounded font-medium" style="background: var(--kicc-crimson); color: white;">Route →</button>
                        <button name="action" value="remove" class="flex-1 text-xs py-1.5 rounded font-medium bg-gray-200" style="color: var(--kicc-navy);">Remove</button>
                    </div>
                </form>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm" style="color: var(--kicc-text-light);">No active streams to route. Go Live from an exhibitor studio first.</p>
        @endif
    </div>

    {{-- Route to All Screens --}}
    <div class="card-kicc p-4 mb-6">
        <h3 class="font-semibold mb-3" style="color: var(--kicc-navy);">Broadcast to ALL Screens</h3>
        @if($liveStreams->isNotEmpty())
        <div class="flex gap-3 items-center">
            <form method="POST" action="{{ route('live.screens.route-all') }}" class="inline">
                @csrf
                <input type="hidden" name="live_stream_id" value="{{ $liveStreams->first()->id }}">
                <button name="action" value="assign" class="px-4 py-2 rounded-lg text-sm font-medium" style="background: var(--kicc-crimson); color: white;">
                    Broadcast {{ $liveStreams->first()->title ?? 'Stream' }} → ALL Screens
                </button>
                <button name="action" value="remove" class="px-4 py-2 rounded-lg text-sm font-medium ml-2 bg-gray-200" style="color: var(--kicc-navy);">
                    Remove from ALL
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Screen Status Grid --}}
    <div class="card-kicc">
        <div class="p-4 border-b" style="border-color: var(--kicc-border);">
            <h3 class="font-semibold" style="color: var(--kicc-navy);">Screen Status by Location</h3>
        </div>
        @if($screens->isEmpty())
        <div class="p-8 text-center text-sm" style="color: var(--kicc-text-light);">
            No screens configured. Add screens via the admin panel.
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-left" style="background: var(--kicc-bg-alt);">
                    <th class="p-3 font-medium">Screen</th>
                    <th class="p-3 font-medium">Location</th>
                    <th class="p-3 font-medium">Group</th>
                    <th class="p-3 font-medium">Live Feed</th>
                    <th class="p-3 font-medium">Status</th>
                </tr></thead>
                <tbody>
                @foreach($screens as $screen)
                <tr class="border-t" style="border-color: var(--kicc-border);">
                    <td class="p-3 font-medium">{{ $screen->label }}</td>
                    <td class="p-3" style="color: var(--kicc-text);">{{ $screen->location ?? '—' }}</td>
                    <td class="p-3" style="color: var(--kicc-text);">{{ $screen->group?->name ?? '—' }}</td>
                    <td class="p-3">
                        @if($screen->liveFeed)
                        <span class="badge-kicc-green px-2 py-0.5 rounded text-xs">{{ $screen->liveFeed?->title ?? 'Stream #'.$screen->liveFeed->id }}</span>
                        @else
                        <span class="text-xs" style="color: var(--kicc-text-light);">No feed</span>
                        @endif
                    </td>
                    <td class="p-3">
                        <span class="inline-block w-2 h-2 rounded-full {{ $screen->live_feed_id ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Regions --}}
    @foreach($regions as $region => $regionScreens)
    <div class="card-kicc p-4 mt-4">
        <h4 class="font-semibold text-sm mb-3" style="color: var(--kicc-navy);">{{ $region ?: 'Unassigned' }} ({{ $regionScreens->count() }})</h4>
        <div class="flex flex-wrap gap-2">
            @foreach($regionScreens as $s)
            <span class="px-2 py-1 rounded text-xs {{ $s->live_feed_id ? 'badge-kicc-green' : 'bg-gray-100' }}">{{ $s->label }}</span>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection