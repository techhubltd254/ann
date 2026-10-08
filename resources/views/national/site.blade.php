@extends('layouts.app')
@section('title', 'National Government of Kenya')
@section('content')
<div class="min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg, #0b0b0b 0%, #A6192E 50%, #0b0b0b 100%);">
    <div class="text-center px-8 py-16 max-w-lg">
        <div class="text-6xl mb-4">🇰🇪</div>
        <h1 class="text-3xl font-bold text-white mb-3">{{ $ministry->name }}</h1>
        <p class="text-white/70 mb-6">{{ $ministry->description }}</p>
        @if($ministry->agencies->isNotEmpty())
        <div class="space-y-2">
            @foreach($ministry->agencies as $a)
            <div class="bg-white/10 backdrop-blur rounded-lg p-3 text-white text-sm">{{ $a->name }}</div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection