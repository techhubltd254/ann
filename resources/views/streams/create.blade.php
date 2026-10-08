@extends('layouts.app')

@section('title', 'Start a Stream — KICC')
@section('content')
<div class="pt-20 max-w-2xl mx-auto px-5 py-10">
    <a href="{{ route('streams.index') }}" class="inline-flex items-center gap-1.5 text-gray-400 hover:text-gray-900 text-sm mb-6 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        All Streams
    </a>

    <div class="bg-white border border-gray-200 rounded-2xl p-6 md:p-8">
        <h1 class="text-2xl font-black text-gray-900 mb-2">Start a Live Stream</h1>
        <p class="text-gray-400 text-sm mb-6">Create a stream for your exhibition or event. After creation, share the RTMPS URL with your broadcaster.</p>

        <form method="POST" action="{{ route('streams.store') }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Stream Name</label>
                    <input type="text" name="name" required maxlength="255" placeholder="e.g. KICC Trade Fair 2026"
                           class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Description (optional)</label>
                    <textarea name="description" rows="3" maxlength="1000" placeholder="What is this stream about?"
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05]"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Exhibition (optional)</label>
                    <select name="exhibition_id"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-1 focus:ring-[#FFCD05]">
                        <option value="">None</option>
                        @foreach($exhibitions as $ex)
                        <option value="{{ $ex->id }}">{{ $ex->name }} ({{ $ex->start_date?->format('M d') }})</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full h-12 rounded-xl bg-[#b3261e] text-white font-bold text-sm hover:bg-[#7b1618] transition-all">
                    Create Stream
                </button>
            </div>
        </form>
    </div>
</div>
@endsection