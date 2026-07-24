@extends('layouts.app')

@section('title', '3D Room Explorer - KICC')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">3D Room Explorer</h1>
            <p class="text-gray-500 mt-1">Upload photos of a room and explore it in 3D on your phone</p>
        </div>
        <a href="{{ url('/admin/room3ds/create') }}" class="btn-amber">+ New Room</a>
    </div>

    @if($rooms->isEmpty())
    <div class="text-center py-20">
        <div class="text-6xl mb-4">🏗️</div>
        <h2 class="text-xl font-semibold text-gray-700 mb-2">No rooms yet</h2>
        <p class="text-gray-500 mb-6">Upload photos of a room to create your first 3D experience</p>
        <a href="{{ url('/admin/room3ds/create') }}" class="btn-amber">Create Your First Room</a>
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($rooms as $room)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
            <div class="aspect-video bg-gray-100 relative overflow-hidden">
                @if($room->coverUrl())
                <img src="{{ $room->coverUrl() }}" alt="{{ $room->title }}" class="w-full h-full object-cover">
                @else
                <div class="flex items-center justify-center h-full text-gray-400 text-4xl">🏠</div>
                @endif
                <div class="absolute top-2 right-2">
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                        @if($room->status === 'ready') bg-green-100 text-green-700
                        @elseif($room->status === 'processing') bg-yellow-100 text-yellow-700
                        @elseif($room->status === 'failed') bg-red-100 text-red-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ ucfirst($room->status) }}
                    </span>
                </div>
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-gray-900 truncate">{{ $room->title }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ count($room->images()) }} photos</p>
                <div class="flex gap-2 mt-3">
                    @if($room->isReady())
                    <a href="{{ route('room3d.viewer', $room) }}"
                       class="flex-1 text-center px-3 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 transition-colors">
                        View in 3D
                    </a>
                    @endif
                    <a href="{{ route('room3d.show', $room) }}"
                       class="flex-1 text-center px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                        Details
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-8">
        {{ $rooms->links() }}
    </div>
    @endif
</div>

<style>
.btn-amber {
    @apply bg-amber-500 text-white px-6 py-2.5 rounded-xl font-semibold hover:bg-amber-600 shadow-lg shadow-amber-500/25 transition-all inline-block;
}
</style>
@endsection