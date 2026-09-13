@extends('layouts.app')
@section('title', $booth->name . ' — Live')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="card-kicc overflow-hidden">
        <div class="aspect-video relative" style="background: #000;">
            @if($booth->liveStreams->where('isLive', true)->first())
            <x-hls-player :hls-url="$booth->liveStreams->where('isLive', true)->first()->hls_url" />
            @else
            <div class="flex items-center justify-center h-full text-gray-500">Stream offline</div>
            @endif
        </div>
        <div class="p-4">
            <h1 class="text-xl font-bold">{{ $booth->name }}</h1>
            @auth
            <button id="favBtn" class="btn-kicc-outline px-3 py-1 rounded-lg text-sm mt-2">
                {{ $isFavourite ? '♥ Favourited' : '♡ Favourite' }}
            </button>
            @endauth
        </div>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('favBtn')?.addEventListener('click', async () => {
    await fetch('{{ route("api.live.favourite") }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'}, body: JSON.stringify({booth_id:{{$booth->id}}}) });
    location.reload();
});
</script>
@endpush
@endsection