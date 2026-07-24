@extends('layouts.app')

@section('title', $room3d->title . ' - 3D Room - KICC')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <a href="{{ route('room3d.index') }}" class="text-amber-600 hover:text-amber-700 mb-4 inline-block">&larr; Back to Rooms</a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @if($room3d->coverUrl())
        <div class="aspect-video bg-gray-100">
            <img src="{{ $room3d->coverUrl() }}" alt="{{ $room3d->title }}" class="w-full h-full object-cover">
        </div>
        @endif
        <div class="p-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ $room3d->title }}</h1>
            @if($room3d->description)
            <p class="text-gray-600 mt-2">{{ $room3d->description }}</p>
            @endif

            <div class="flex flex-wrap gap-4 mt-4 text-sm text-gray-500">
                <span>{{ count($room3d->images()) }} photos</span>
                <span>Pipeline: {{ str_replace('_', ' ', $room3d->pipeline) }}</span>
                <span>Status: {{ ucfirst($room3d->status) }}</span>
            </div>

            @if($room3d->isReady())
            <div class="mt-6 flex gap-3">
                <a href="{{ route('room3d.viewer', $room3d) }}"
                   class="btn-amber flex items-center gap-2">
                    <span>🔍</span> Open 3D Viewer
                </a>
                <a href="{{ route('room3d.viewer', $room3d) }}?mode=gyro"
                   class="btn-outline flex items-center gap-2">
                    <span>📱</span> Phone Gyro Mode
                </a>
            </div>
            @endif

            <div class="mt-6">
                <h3 class="font-semibold text-gray-900 mb-3">Photos ({{ count($room3d->images()) }})</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    @foreach($room3d->images() as $image)
                    <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                        <img src="{{ url('storage/' . $image) }}" alt="" class="w-full h-full object-cover hover:scale-105 transition-transform">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-amber { @apply bg-amber-500 text-white px-6 py-2.5 rounded-xl font-semibold hover:bg-amber-600 shadow-lg shadow-amber-500/25 transition-all inline-block; }
.btn-outline { @apply bg-white/10 backdrop-blur-sm text-gray-700 px-6 py-2.5 rounded-xl font-semibold border border-gray-300 hover:bg-gray-100 transition-all inline-block; }
</style>
@endsection