@extends('layouts.app')
@section('title', 'Capacity Building — KICC LMS')
@section('content')
<div class="pt-24 max-w-7xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-2">Capacity Building</h1>
    <p class="text-gray-500 text-sm mb-6">Tourism training courses, certifications, and e-learning resources.</p>
    @if($courses->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center"><div class="text-4xl mb-3">📚</div><h3 class="font-bold text-gray-900 mb-1">No courses yet</h3></div>
    @else
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">@foreach($courses as $c)
        <a href="{{ route('lms.show', $c->id) }}" class="bg-white border border-gray-200 rounded-2xl p-5 hover:border-[#046bd2]/40 transition-all card-hover">
            <span class="text-[10px] font-bold text-[#046bd2] uppercase">{{ $c->category ?? 'General' }}</span>
            <h3 class="font-bold text-gray-900 text-sm mt-1">{{ $c->title }}</h3>
            <p class="text-gray-500 text-xs mt-1 line-clamp-2">{{ $c->description }}</p>
            <div class="flex items-center justify-between mt-3 text-xs text-gray-400"><span>{{ $c->level }}</span><span>{{ $c->duration_hours ?? '—' }}h</span></div>
        </a>
    @endforeach</div>
    <div class="mt-6">{{ $courses->links() }}</div>
    @endif
</div>
@endsection