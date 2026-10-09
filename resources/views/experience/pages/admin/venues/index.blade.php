@extends('layouts.admin')
@section('title', 'Venues')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-bold">Venues ({{ $venues->total() }})</h1>
            <p class="text-sm text-gray-500 mt-1">Every cover image and hero video below is served from the media library — upload, replace or delete and the public site changes immediately.</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name, city, county…"
                   class="border rounded px-3 py-2 text-sm w-64">
            <button class="bg-gray-900 text-white px-4 py-2 rounded text-sm">Search</button>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded border border-green-300 bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="overflow-x-auto border rounded-xl bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Venue</th>
                    <th class="px-4 py-3">Type / Location</th>
                    <th class="px-4 py-3">Cover image</th>
                    <th class="px-4 py-3">Hero video</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
            @forelse($venues as $venue)
                @php $m = $media[$venue->id] ?? []; @endphp
                <tr class="align-top">
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-900">{{ $venue->name }}</div>
                        <div class="text-xs text-gray-500">#{{ $venue->id }} · {{ $venue->slug }}</div>
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        <div>{{ $venue->venue_type ?: '—' }}</div>
                        <div class="text-xs">{{ $venue->city ?: '—' }}@if($venue->county), {{ $venue->county }}@endif</div>
                        <div class="text-xs text-gray-400">Cap. {{ number_format((int) $venue->capacity) }}</div>
                    </td>
                    <td class="px-4 py-3">
                        @if(!empty($m['cover']))
                            <img src="{{ $m['cover'] }}" alt="{{ $venue->name }} cover"
                                 class="h-16 w-28 object-cover rounded-lg border bg-gray-100">
                            <div class="mt-1 text-[11px] {{ ($m['cover_source'] ?? '') === 'admin' ? 'text-green-700' : 'text-amber-700' }}">
                                {{ ($m['cover_source'] ?? '') === 'admin' ? '● admin-uploaded' : '● legacy value' }}
                            </div>
                        @else
                            <span class="inline-flex h-16 w-28 items-center justify-center rounded-lg border border-dashed text-[11px] text-gray-400">no image</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if(!empty($m['video']))
                            <video src="{{ $m['video'] }}" class="h-16 w-28 object-cover rounded-lg border bg-black"
                                   muted playsinline preload="metadata"></video>
                            <div class="mt-1 text-[11px] {{ ($m['video_source'] ?? '') === 'admin' ? 'text-green-700' : 'text-amber-700' }}">
                                {{ ($m['video_source'] ?? '') === 'admin' ? '● admin-uploaded' : '● legacy value' }}
                            </div>
                        @else
                            <span class="inline-flex h-16 w-28 items-center justify-center rounded-lg border border-dashed text-[11px] text-gray-400">no video</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('admin.venues.edit', $venue->id) }}"
                           class="inline-block bg-gray-900 text-white px-3 py-1.5 rounded text-xs">Manage media</a>
                        <form method="POST" action="{{ route('admin.venues.delete-video', $venue->id) }}" class="inline"
                              onsubmit="return confirm('Remove the hero video for {{ $venue->name }}?')">
                            @csrf
                            <button class="inline-block bg-red-600 text-white px-3 py-1.5 rounded text-xs">Delete video</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No venues matched.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $venues->links() }}</div>
</div>
@endsection
