@extends('layouts.app')
@section('title', 'National Government Exhibition')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold" style="color: var(--kicc-navy);">National Government Exhibition</h1>
        <p class="text-lg mt-2" style="color: var(--kicc-text);">Showcasing Kenya's development pillars across Muranga and Mombasa Counties</p>
        <div class="flex justify-center gap-4 mt-4">
            <span class="badge-kicc-green px-3 py-1 rounded-full">{{ $stats['live'] }} Live Now</span>
            <span class="badge-kicc-blue px-3 py-1 rounded-full">{{ $stats['authorized'] }} Authorized</span>
            <span class="badge-kicc-red px-3 py-1 rounded-full">{{ $stats['total'] }} Pillars</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($pillars as $booth)
        <a href="{{ route('national.show', $booth->slug) }}" class="card-kicc overflow-hidden group hover:shadow-lg transition-all duration-300">
            <div class="aspect-video relative" style="background: var(--kicc-navy);">
                <div class="absolute inset-0 flex items-center justify-center">
                    @if($booth->stream_status === 'live')
                    <span class="px-3 py-1 rounded-full text-sm font-medium" style="background: rgba(11,11,11,0.2); color: #0B0B0B;">
                        <span class="animate-pulse">●</span> LIVE
                    </span>
                    @else
                    <span class="text-gray-400 text-sm">{{ $booth->name }}</span>
                    @endif
                </div>
                <div class="absolute top-2 right-2 flex gap-1">
                    <span class="inline-block w-3 h-3 rounded-full" style="background: {{ match($booth->heartbeat_color ?? 'gray') { 'green' => '#0B0B0B', 'amber' => '#B3261E', 'red' => '#B3261E', default => '#FFFFFF' } }}"></span>
                </div>
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-lg" style="color: var(--kicc-navy);">{{ $booth->name }}</h3>
                <p class="text-sm mt-1" style="color: var(--kicc-text);">{{ Str::limit($booth->description, 100) }}</p>
                <div class="flex justify-between items-center mt-3">
                    <span class="text-xs" style="color: var(--kicc-text-light);">
                        @if($booth->authorization?->status === 'AUTHORIZED') Authorized
                        @elseif($booth->authorization?->status === 'STOPPED') Terminated
                        @else Pending
                        @endif
                    </span>
                    <span class="group-hover:translate-x-1 transition-transform text-sm font-medium" style="color: var(--kicc-red);">Explore →</span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endsection