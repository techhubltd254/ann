@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="pt-24 max-w-3xl mx-auto px-5 py-10">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-black text-gray-900">Notifications</h1>
        @if($notifications->where('is_read', false)->count() > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}" class="inline">@csrf
            <button class="text-sm font-bold text-[#0B0B0B] hover:underline">Mark all as read</button>
        </form>
        @endif
    </div>
    @if($notifications->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center"><div class="text-4xl mb-3"></div><h3 class="font-bold text-gray-900 mb-1">No notifications</h3></div>
    @else
    <div class="space-y-2">
        @foreach($notifications as $n)
        <div class="bg-white border border-gray-200 rounded-xl p-4 {{ !$n->is_read ? 'ring-2 ring-[#0B0B0B]/20' : '' }}">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold text-[#0B0B0B] uppercase">{{ $n->type }}</span>
                        @if(!$n->is_read)<span class="w-2 h-2 rounded-full bg-[#0B0B0B]"></span>@endif
                    </div>
                    <div class="font-semibold text-gray-900 text-sm mt-1">{{ $n->title }}</div>
                    @if($n->body)<p class="text-gray-500 text-xs mt-0.5">{{ $n->body }}</p>@endif
                    <div class="text-xs text-gray-400 mt-2">{{ $n->created_at->diffForHumans() }}</div>
                </div>
                <div class="flex gap-2 shrink-0">
                    @if($n->action_url)<a href="{{ $n->action_url }}" class="text-xs font-bold text-[#0B0B0B] hover:underline">View</a>@endif
                    @if(!$n->is_read)
                    <form method="POST" action="{{ route('notifications.read', $n->id) }}" class="inline">@csrf
                        <button class="text-xs font-bold text-gray-400 hover:text-gray-600">Dismiss</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection