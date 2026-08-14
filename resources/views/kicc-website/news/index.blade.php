@extends('layouts.app')
@section('title', 'KICC News & Events')
@section('content')
<div class="pt-20">
    <div class="bg-gradient-to-r from-[#046bd2] to-[#045cb4] py-16">
        <div class="max-w-7xl mx-auto px-5">
            <h1 class="text-4xl md:text-5xl font-black text-white leading-tight" data-split>KICC <span class="text-[#FFCD05]">News & Events</span></h1>
            <p class="text-white/70 text-lg mt-3 max-w-2xl">Latest updates, press releases, and announcements from Africa's premier convention centre.</p>
        </div>
    </div>
    <div class="max-w-7xl mx-auto px-5 py-12">
        @if($featured)
        <div class="mb-12 bg-white border border-gray-200 rounded-2xl overflow-hidden card-hover">
            <div class="md:flex">
                @if($featured->featured_image)<div class="md:w-1/2 h-64 bg-gray-100"><img src="{{ $featured->featured_image }}" class="w-full h-full object-cover"></div>@endif
                <div class="p-6 md:w-1/2 flex flex-col justify-center">
                    <span class="text-[10px] font-bold text-[#046bd2] uppercase">{{ $featured->category }}</span>
                    <h2 class="text-2xl font-black text-gray-900 mt-2">{{ $featured->title }}</h2>
                    <p class="text-gray-500 text-sm mt-2">{{ $featured->excerpt }}</p>
                    <a href="{{ route('kicc.news.show', $featured->slug) }}" class="mt-4 text-[#046bd2] font-bold hover:underline">Read more →</a>
                </div>
            </div>
        </div>
        @endif
        <div class="flex flex-wrap gap-2 mb-8">
            <a href="{{ route('kicc.news') }}" class="px-4 py-1.5 rounded-full text-xs font-bold bg-[#046bd2] text-white">All</a>
            @foreach($categories as $c)<a href="{{ route('kicc.news', ['category' => $c]) }}" class="px-4 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600 hover:bg-gray-200">{{ ucfirst(str_replace('_', ' ', $c)) }}</a>@endforeach
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($articles as $a)
            <a href="{{ route('kicc.news.show', $a->slug) }}" class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:border-[#046bd2]/40 transition-all card-hover group">
                @if($a->featured_image)<div class="h-44 bg-gray-100 overflow-hidden"><img src="{{ $a->featured_image }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"></div>@endif
                <div class="p-5">
                    <span class="text-[10px] font-bold text-[#046bd2] uppercase">{{ $a->category }}</span>
                    <h3 class="font-bold text-gray-900 text-sm mt-1 leading-snug">{{ $a->title }}</h3>
                    @if($a->excerpt)<p class="text-gray-500 text-xs mt-1 line-clamp-2">{{ $a->excerpt }}</p>@endif
                    <div class="text-xs text-gray-400 mt-3">{{ $a->published_at->format('M d, Y') }}</div>
                </div>
            </a>
            @empty
            <div class="col-span-3 text-center py-12 text-gray-400">No articles published yet.</div>
            @endforelse
        </div>
        <div class="mt-8">{{ $articles->links() }}</div>
    </div>
</div>
@endsection