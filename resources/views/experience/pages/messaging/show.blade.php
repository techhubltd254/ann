@extends('layouts.app')
@section('title', 'Messages — KICC')
@section('content')
<div class="pt-24 max-w-4xl mx-auto px-5 py-10">
    <a href="{{ route('messaging.inbox') }}" class="text-gray-500 hover:text-[#0B0B0B] text-sm mb-4 inline-block">← All messages</a>
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <div class="font-bold text-gray-900 text-sm">{{ $conversation->subject ?? 'Inquiry' }}</div>
            <div class="text-xs text-gray-400">with {{ $conversation->vendor?->name ?? $conversation->user?->name ?? '—' }}</div>
        </div>
        <div class="px-5 py-4 space-y-4 max-h-96 overflow-y-auto" id="message-list">
            @forelse($messages as $m)
            <div class="flex {{ $m->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[75%] {{ $m->user_id === Auth::id() ? 'bg-[#0B0B0B] text-white' : 'bg-gray-100 text-gray-900' }} rounded-2xl px-4 py-2.5 text-sm">
                    <p>{{ $m->body }}</p>
                    <div class="text-[10px] mt-1 {{ $m->user_id === Auth::id() ? 'text-white/60' : 'text-gray-400' }}">{{ $m->created_at->format('g:i A') }} · {{ $m->is_read ? 'Read' : 'Sent' }}</div>
                </div>
            </div>
            @empty
            <p class="text-center text-gray-400 py-8">No messages yet. Send the first message.</p>
            @endforelse
        </div>
        <div class="px-5 py-4 border-t border-gray-100">
            <form method="POST" action="{{ route('messaging.send', $conversation->id) }}" class="flex gap-3" x-data="{ text: '' }" @submit="text = ''">
                @csrf
                <input type="text" name="body" x-model="text" required placeholder="Type your message…" class="flex-1 h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40">
                <button type="submit" class="h-11 px-6 rounded-xl bg-[#0B0B0B] text-white font-bold text-sm hover:bg-[#0B0B0B] transition-all">Send</button>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const list = document.getElementById('message-list');
    if (list) list.scrollTop = list.scrollHeight;
});
</script>
@endsection