@extends('layouts.app')
@section('title', 'KICC Management')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6 prose prose-sm max-w-none">{!! $page->content ?? '<p>Management information coming soon.</p>' !!}</div>
    <div class="grid md:grid-cols-2 gap-4">
        @forelse($members as $m)
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><h3 class="font-bold text-gray-900">{{ $m->name }}</h3>
            @if($m->title)<div class="text-sm text-[#046bd2] font-semibold">{{ $m->title }}</div>@endif
        </div>
        @empty <p class="text-gray-400 col-span-2">No management team listed yet.</p>
        @endforelse
    </div>
</div>
@endsection