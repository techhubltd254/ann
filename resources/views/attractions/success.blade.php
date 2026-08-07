@extends('layouts.app')

@section('title', 'Booking Confirmed — ' . $attraction->name)

@section('content')
<div class="pt-20 min-h-screen flex items-center justify-center px-5 py-12">
    <div class="w-full max-w-lg text-center" data-reveal>
        <div class="w-20 h-20 rounded-full bg-[#11820B]/15 border-2 border-[#11820B] flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-[#11820B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-3xl font-black text-gray-900 mb-2" data-split>Booking Confirmed!</h1>
        <p class="text-gray-400 text-sm mb-8">Your trip is reserved. Payment confirmation follows shortly via M-Pesa.</p>

        <div class="bg-white border border-gray-200 rounded-2xl p-6 text-left">
            <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-4">
                <span class="text-gray-400 text-xs uppercase tracking-widest">Reference</span>
                <span class="text-[#FFCD05] font-black text-lg">{{ $reference }}</span>
            </div>
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between"><span class="text-gray-400">Attraction</span><span class="text-gray-900 font-bold">{{ $attraction->name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">County</span><span class="text-gray-600">{{ $attraction->county->name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Visit date</span><span class="text-gray-600">{{ $visitDate }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Guests</span><span class="text-gray-600">{{ $guests }} × KES {{ number_format($entryFee) }}</span></div>
                @foreach($addons as $a)
                <div class="flex justify-between"><span class="text-gray-400">+ {{ $a['label'] }}</span><span class="text-gray-600">KES {{ number_format($a['cost']) }}</span></div>
                @endforeach
            </div>
            <div class="flex justify-between items-center pt-4 mt-4 border-t border-gray-100">
                <span class="text-gray-900 font-bold">Total</span>
                <span class="text-[#11820B] font-black text-xl">KES {{ number_format($total) }}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-6">
            <a href="{{ route('counties.sector', [$attraction->county->slug, 'tourism']) }}" class="h-11 inline-flex items-center justify-center rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition-all">More in {{ $attraction->county->name }}</a>
            <a href="{{ route('travel.index') }}" class="h-11 inline-flex items-center justify-center rounded-xl bg-[#901C1E] text-gray-900 text-sm font-bold hover:bg-[#7b1618] transition-all" data-magnetic>Plan Full Trip</a>
        </div>
    </div>
</div>
@endsection
