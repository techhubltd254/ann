@extends('layouts.app')

@section('title', 'Digital Screens — KICC Advertising')
@section('description', 'Advertise on 40+ premium digital screens across KICC exhibition halls, county pavilions, and the national government hall.')

@section('content')
<div class="pt-20">
    <div class="bg-white border-b border-gray-200 py-12">
        <div class="max-w-7xl mx-auto px-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-px w-8 bg-kicc-gold"></div>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">Digital Screens</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-gray-900 tracking-tight" data-split>Advertise on <span class="text-kicc-gold">Kenya's Most</span> Iconic Screens</h1>
            <p class="text-[#5A6480] mt-3 text-base max-w-xl">40+ premium screens across exhibition halls, county pavilions and the national government hall — book your slot now.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-5 py-10">
        @php $grouped = collect($screens ?? [])->groupBy(fn($s) => $s->type_tag); @endphp

        {{-- National Government Hall --}}
        @if(($grouped['national'] ?? collect())->isNotEmpty() || ($grouped['national_sector'] ?? collect())->isNotEmpty() || ($grouped['agency'] ?? collect())->isNotEmpty())
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-[#2a2a2a]"></span>
                <span class="text-[#2a2a2a] text-xs font-bold tracking-[0.2em] uppercase">National Pavilion</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            @foreach(['national', 'national_sector', 'agency'] as $g)
            @foreach($grouped[$g] ?? [] as $screen)
            @include('screens._card', ['screen' => $screen])
            @endforeach
            @endforeach
        </div>
        @endif

        {{-- County Screens --}}
        @if(($grouped['county'] ?? collect())->isNotEmpty() || ($grouped['sector'] ?? collect())->isNotEmpty())
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-kicc-gold"></span>
                <span class="text-kicc-gold text-xs font-bold tracking-[0.2em] uppercase">County Pavilions</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            @foreach(['county', 'sector'] as $g)
            @foreach($grouped[$g] ?? [] as $screen)
            @include('screens._card', ['screen' => $screen])
            @endforeach
            @endforeach
        </div>
        @endif

        {{-- Hero + Hallway + Others --}}
        @foreach(['hero', 'hallway', 'other'] as $g)
        @if(($grouped[$g] ?? collect())->isNotEmpty())
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-6">
                <span class="h-px w-8 bg-gray-300"></span>
                <span class="text-gray-500 text-xs font-bold tracking-[0.2em] uppercase">{{ ucfirst($g) }} Screens</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </div>
            @foreach($grouped[$g] as $screen)
            @include('screens._card', ['screen' => $screen])
            @endforeach
        </div>
        @endif
        @endforeach
    </div>
</div>
@endSection