@extends('layouts.app')
@section('title', 'Application Submitted')
@section('content')
<div class="pt-28 pb-16">
    <div class="max-w-lg mx-auto px-5 text-center">
        <div class="w-16 h-16 rounded-full bg-[#0B0B0B]/10 flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-[#0B0B0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl font-black text-gray-900">Application Submitted</h1>
        <p class="text-gray-500 mt-2">Your business <strong class="text-gray-900">{{ $agent->business_name }}</strong> has been registered. A KICC trade officer will review your documents and notify you once approved.</p>
        <div class="mt-8 bg-white border border-gray-200 rounded-2xl p-6 text-left">
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-400">Business Name</span><span class="font-semibold">{{ $agent->business_name }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Status</span><span class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 text-xs font-bold">Pending Review</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Services</span><span class="font-semibold">{{ implode(', ', $agent->service_types ?? []) }}</span></div>
            </div>
        </div>
        <a href="/" class="inline-block mt-6 h-11 px-6 rounded-xl bg-[#0B0B0B] text-white text-sm font-bold hover:bg-[#0B0B0B] transition-all">Back to Home</a>
    </div>
</div>
@endsection