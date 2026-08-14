@extends('layouts.app')
@section('title', $article->title)
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <a href="{{ route('kicc.news') }}" class="text-gray-500 hover:text-[#046bd2] text-sm mb-4 inline-block">← All News</a>
    <article class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        @if($article->featured_image)<img src="{{ $article->featured_image }}" class="w-full h-64 md:h-80 object-cover">@endif
        <div class="p-6 md:p-8">
            <span class="text-[10px] font-bold text-[#046bd2] uppercase">{{ $article->category }}</span>
            <h1 class="text-3xl font-black text-gray-900 mt-2 leading-tight">{{ $article->title }}</h1>
            <div class="flex items-center gap-3 text-xs text-gray-400 mt-3"><span>{{ $article->published_at->format('M d, Y') }}</span>@if($article->author)<span>· {{ $article->author }}</span>@endif</div>
            @if($article->excerpt)<p class="text-gray-500 text-base mt-4 italic">{{ $article->excerpt }}</p>@endif
            <div class="mt-6 prose prose-sm max-w-none text-gray-600 leading-relaxed">{!! $article->content !!}</div>
        </div>
    </article>
    @if($related->isNotEmpty())
    <h2 class="text-xl font-black text-gray-900 mt-10 mb-4">Related Articles</h2>
    <div class="grid sm:grid-cols-3 gap-4">@foreach($related as $r)<a href="{{ route('kicc.news.show', $r->slug) }}" class="bg-white border border-gray-200 rounded-2xl p-4 hover:border-[#046bd2]/40 transition-all"><span class="text-[10px] font-bold text-[#046bd2]">{{ $r->category }}</span><h3 class="font-bold text-gray-900 text-sm mt-1">{{ $r->title }}</h3></a>@endforeach</div>
    @endif
</div>
@endsection