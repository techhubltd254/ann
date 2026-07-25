@extends('layouts.app')

@section('title', 'My Exhibitions')
@section('description', 'Manage your exhibitions on KICC.')

@section('content')
<div class="bg-[#0D1220] border-b border-white/8 py-12">
    <div class="max-w-7xl mx-auto px-5" data-reveal>
        <a href="{{ route('dashboard.index') }}" class="text-kicc-gold hover:underline text-sm mb-4 inline-block">&larr; Dashboard</a>
        <h1 class="text-3xl md:text-4xl font-black text-white mb-2">My Exhibitions</h1>
        <p class="text-white/50">Exhibitions you've organized or manage.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-5 py-10">
    @if($exhibitions->count() > 0)
    <div class="bg-[#0D1220] rounded-2xl border border-white/8 overflow-hidden" data-reveal>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-white/40 text-[10px] uppercase tracking-wider border-b border-white/8 bg-white/[0.02]">
                        <th class="text-left px-6 py-3">Name</th>
                        <th class="text-left px-6 py-3">Dates</th>
                        <th class="text-left px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($exhibitions as $exhibition)
                    <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4">
                            <a href="{{ route('exhibitions.show', $exhibition->slug) }}" class="font-bold text-kicc-gold hover:underline">{{ $exhibition->name }}</a>
                        </td>
                        <td class="px-6 py-4 text-white/60 text-xs">{{ $exhibition->start_date->format('M d, Y') }} - {{ $exhibition->end_date->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            <span class="text-[10px] font-bold px-2.5 py-1 rounded-full border {{ $exhibition->status === 'published' ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25' : 'text-white/40 bg-white/5 border-white/10' }}">
                                {{ ucfirst($exhibition->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">{{ $exhibitions->links() }}</div>
    @else
    <div class="text-center py-16 bg-[#0D1220] rounded-2xl border border-white/8" data-reveal>
        <div class="text-5xl mb-4">🏛️</div>
        <h3 class="text-xl font-bold text-white mb-2">No exhibitions yet</h3>
        <p class="text-white/40 text-sm">Contact the admin to create your first exhibition.</p>
    </div>
    @endif
</div>
@endsection
