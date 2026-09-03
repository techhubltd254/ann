@extends('layouts.app')

@section('title', 'Manage Live Events — Admin')
@section('content')
<div class="pt-20 max-w-7xl mx-auto px-5 py-10">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black text-gray-900">Manage Live Events</h1>
            <p class="text-gray-400 text-sm mt-1">Create, start, end, and manage live streams for events and exhibitions.</p>
        </div>
        <a href="{{ route('streams.create') }}" class="inline-flex items-center gap-2 font-bold text-sm h-11 px-5 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618]">Start a Stream</a>
    </div>

    {{-- Stats bar --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-gray-200 rounded-2xl p-4">
            <div class="text-2xl font-black text-gray-900">{{ $streams->total() }}</div>
            <div class="text-xs text-gray-400 mt-1">Total Streams</div>
        </div>
        <div class="bg-white border border-red-200 rounded-2xl p-4">
            <div class="text-2xl font-black text-red-500">{{ $streams->where('status','live')->count() }}</div>
            <div class="text-xs text-gray-400 mt-1">Live Now</div>
        </div>
        <div class="bg-white border border-amber-200 rounded-2xl p-4">
            <div class="text-2xl font-black text-amber-500">{{ $streams->where('status','idle')->count() }}</div>
            <div class="text-xs text-gray-400 mt-1">Scheduled</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-4">
            <div class="text-2xl font-black text-gray-500">{{ $streams->where('status','ended')->count() }}</div>
            <div class="text-xs text-gray-400 mt-1">Ended</div>
        </div>
    </div>

    {{-- Streams table --}}
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                        <th class="px-4 py-3">Stream</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Exhibition</th>
                        <th class="px-4 py-3">Viewers</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($streams as $stream)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3">
                            <div class="font-bold text-gray-900">{{ $stream->name }}</div>
                            @if($stream->description)
                            <div class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $stream->description }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($stream->isLive())
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-600 flex items-center gap-1.5 w-fit">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>LIVE
                            </span>
                            @elseif($stream->status === 'idle')
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-600">Scheduled</span>
                            @else
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Ended</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $stream->exhibition?->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-bold text-gray-700">{{ number_format($stream->viewer_count) }}</td>
                        <td class="px-4 py-3 text-gray-400 text-xs">{{ $stream->created_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <a href="{{ route('streams.show', $stream) }}" class="text-[10px] font-bold px-2 py-1 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200">View</a>
                                @if($stream->isLive())
                                <form method="POST" action="{{ route('streams.end', $stream) }}" onsubmit="return confirm('End this stream?')">
                                    @csrf
                                    <button class="text-[10px] font-bold px-2 py-1 rounded-lg bg-red-100 text-red-600 hover:bg-red-200">End</button>
                                </form>
                                @elseif($stream->status === 'idle')
                                <form method="POST" action="{{ route('streams.go-live', $stream) }}">
                                    @csrf
                                    <button class="text-[10px] font-bold px-2 py-1 rounded-lg bg-emerald-100 text-emerald-600 hover:bg-emerald-200">Go Live</button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('streams.destroy', $stream) }}" onsubmit="return confirm('Delete this stream permanently?')">
                                    @csrf @method('DELETE')
                                    <button class="text-[10px] font-bold px-2 py-1 rounded-lg bg-gray-100 text-gray-500 hover:bg-red-100 hover:text-red-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">No streams yet. Create one to start broadcasting.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $streams->links() }}
    </div>
</div>
@endsection