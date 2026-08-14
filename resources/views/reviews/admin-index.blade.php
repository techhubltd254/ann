@extends('layouts.app')
@section('title', 'Review Moderation — KICC Admin')
@section('content')
<div class="pt-24 max-w-7xl mx-auto px-5 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-black text-gray-900">Review Moderation</h1>
        <div class="flex gap-2">
            @foreach(['pending','approved','rejected'] as $s)
            <a href="{{ route('review.admin.index', $s !== 'pending' ? ['status' => $s] : []) }}" class="px-3 py-1.5 rounded-full text-[10px] font-bold {{ ($status ?? 'pending') === $s ? 'bg-[#046bd2] text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">{{ ucfirst($s) }}</a>
            @endforeach
        </div>
    </div>
    @if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>@endif
    @if($reviews->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center"><div class="text-4xl mb-3">⭐</div><h3 class="font-bold text-gray-900 mb-1">No reviews</h3></div>
    @else
    <div class="space-y-4">
        @foreach($reviews as $r)
        <div class="bg-white border border-gray-200 rounded-2xl p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-amber-400 text-sm">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</span>
                        <span class="text-xs text-gray-400">by {{ $r->user?->name ?? 'Anonymous' }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $r->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($r->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($r->status) }}</span>
                    </div>
                    <div class="text-xs text-gray-400 mb-1">{{ class_basename($r->reviewable_type) }} #{{ $r->reviewable_id }}</div>
                    @if($r->content)<p class="text-gray-600 text-sm">{{ $r->content }}</p>@endif
                    @if($r->photos && count($r->photos) > 0)
                    <div class="flex gap-2 mt-2">@foreach($r->photos as $p)<img src="{{ asset('storage/'.$p) }}" class="w-16 h-16 rounded-lg object-cover">@endforeach</div>
                    @endif
                    @if($r->vendor_response)<div class="mt-3 pl-4 border-l-2 border-gray-200"><div class="text-xs text-gray-400">Vendor response</div><p class="text-sm text-gray-600">{{ $r->vendor_response }}</p></div>@endif
                </div>
                @if($r->status === 'pending')
                <div class="flex gap-2 shrink-0">
                    <form method="POST" action="{{ route('review.admin.approve', $r->id) }}" class="inline">@csrf<button class="h-8 px-4 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700">Approve</button></form>
                    <form method="POST" action="{{ route('review.admin.reject', $r->id) }}" class="inline">@csrf<button class="h-8 px-4 rounded-lg bg-red-600 text-white text-xs font-bold hover:bg-red-700">Reject</button></form>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $reviews->links() }}</div>
    @endif
</div>
@endsection