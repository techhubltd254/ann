@extends('layouts.app')
@section('title', 'Messages — KICC')
@section('content')
<div class="pt-24 max-w-4xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-6">Messages</h1>
    @if($conversations->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center"><div class="text-4xl mb-3">💬</div><h3 class="font-bold text-gray-900 mb-1">No messages</h3><p class="text-gray-500 text-sm">Start a conversation with a vendor from any product page.</p></div>
    @else
    <div class="space-y-2">
        @foreach($conversations as $c)
        <a href="{{ route('messaging.show', $c->id) }}" class="block bg-white border border-gray-200 rounded-xl p-4 hover:border-[#046bd2]/40 transition-all card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-bold text-gray-900 text-sm">{{ $c->subject ?? 'Inquiry' }}</div>
                    <div class="text-xs text-gray-400">with {{ $c->vendor?->name ?? $c->user?->name ?? '—' }}</div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-400">{{ $c->last_message_at?->diffForHumans() }}</div>
                    @php $unread = $c->messages->where('user_id', '!=', Auth::id())->where('is_read', false)->count(); @endphp
                    @if($unread > 0)<span class="inline-block w-5 h-5 rounded-full bg-[#046bd2] text-white text-[10px] font-bold leading-5 text-center">{{ $unread }}</span>@endif
                </div>
            </div>
        </a>
        @endforeach
    </div>
    <div class="mt-6">{{ $conversations->links() }}</div>
    @endif
</div>
@endsection