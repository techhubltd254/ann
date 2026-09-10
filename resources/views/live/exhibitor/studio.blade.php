@extends('layouts.app')
@section('title', 'Studio — ' . $booth->name)
@section('content')
<div class="min-h-screen" style="background: var(--kicc-navy); color: white;">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-xl font-bold">{{ $booth->name }}</h1>
                <span class="text-sm opacity-70">Studio — {{ $auth->status === 'AUTHORIZED' ? 'Authorized' : 'Locked' }}</span>
            </div>
            <div class="flex space-x-3">
                <span class="px-3 py-1 rounded-full text-sm" style="background: rgba(5,150,105,0.2); color: #34D399;">
                    @if($session->stream_status === 'live') ● LIVE @elseif($session->stream_status === 'paused') ■ PAUSED @else ○ OFFLINE @endif
                </span>
                @if($auth->status === 'AUTHORIZED')
                <button id="goLiveBtn" class="px-4 py-2 rounded-lg font-medium" style="background: var(--kicc-red); color: white;">Go Live</button>
                <button id="endStreamBtn" class="px-4 py-2 rounded-lg font-medium" style="background: #DC2626; color: white;">End Stream</button>
                @endif
            </div>
        </div>

        @if($auth->status !== 'AUTHORIZED')
        <div class="card-kicc p-8 text-center" style="background: rgba(220,38,38,0.1); border-color: rgba(220,38,38,0.3);">
            <h2 class="text-2xl font-bold text-red-400 mb-2">Authorization Required</h2>
            <p class="text-gray-400">Your booth is not authorized. Contact Super Admin to enable streaming.</p>
        </div>
        @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <div class="rounded-lg overflow-hidden mb-4" style="background: #000;">
                    <div class="aspect-video flex items-center justify-center" id="previewPane">
                        <p class="text-gray-500">Preview — Connected to ingest</p>
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-2">
                    <button class="p-2 rounded text-xs" style="background: rgba(255,255,255,0.1);">Scene 1</button>
                    <button class="p-2 rounded text-xs" style="background: rgba(255,255,255,0.1);">Scene 2</button>
                    <button class="p-2 rounded text-xs" style="background: rgba(255,255,255,0.1);">Lower Third</button>
                    <button class="p-2 rounded text-xs" style="background: rgba(255,255,255,0.1);">Cut to Slate</button>
                </div>
            </div>
            <div>
                <div class="rounded-lg p-4 mb-4" style="background: rgba(255,255,255,0.05);">
                    <h3 class="font-medium mb-2">Stream Health</h3>
                    <div class="text-sm space-y-1 opacity-70">
                        <p>Bitrate: --</p>
                        <p>Viewers: {{ $booth->liveStreams->first()?->viewer_count ?? 0 }}</p>
                        <p>Heartbeat: Active (every 5s)</p>
                    </div>
                </div>
                <div class="rounded-lg p-4" style="background: rgba(255,255,255,0.05);">
                    <h3 class="font-medium mb-2">Booth Settings</h3>
                    <form method="POST" action="{{ route('live.studio.update-booth', $booth) }}" class="space-y-2 text-sm">
                        @csrf
                        <input name="name" value="{{ $booth->name }}" class="w-full p-2 rounded text-black" placeholder="Booth name">
                        <textarea name="description" class="w-full p-2 rounded text-black" rows="2" placeholder="Description">{{ $booth->description }}</textarea>
                        <button type="submit" class="w-full p-2 rounded" style="background: var(--kicc-gold); color: var(--kicc-navy);">Save</button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.getElementById('goLiveBtn')?.addEventListener('click', async () => {
    const res = await fetch('{{ route("live.studio.go-live", $booth) }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'} });
    const data = await res.json();
    if(data.status === 'live') location.reload();
});
document.getElementById('endStreamBtn')?.addEventListener('click', async () => {
    const res = await fetch('{{ route("live.studio.end-stream", $booth) }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'} });
    location.reload();
});
</script>
@endpush
@endsection