@extends('layouts.app')

@section('title', 'Export Application Submitted')
@section('description', 'Your export application has been received.')

@section('content')
<div class="pt-28 pb-16">
    <div class="max-w-lg mx-auto px-5 text-center">
        <div class="w-16 h-16 rounded-full bg-[#046bd2]/10 flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-[#046bd2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl font-black text-gray-900">Export application received</h1>
        <p class="text-gray-500 mt-2">Your enquiry has been sent to the KICC trade desk. A trade officer will contact you within 2 business days.</p>

        <div class="mt-8 bg-white rounded-2xl border border-gray-200 p-6 text-left">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-100">
                <span class="text-xs text-gray-400 uppercase tracking-wider font-bold">Reference</span>
                <span class="text-[#046bd2] font-black text-lg">{{ $enquiry->reference }}</span>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-400">Company</span><span class="font-semibold text-gray-900">{{ $enquiry->company_name }}</span></div>
                @if($enquiry->product_name)
                <div class="flex justify-between"><span class="text-gray-400">Product</span><span class="font-semibold text-gray-900">{{ $enquiry->product_name }}</span></div>
                @endif
                @if($enquiry->destination)
                <div class="flex justify-between"><span class="text-gray-400">Destination</span><span class="font-semibold text-gray-900">{{ $enquiry->destination }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-gray-400">Status</span><span class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 text-xs font-bold">Submitted</span></div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-6">
            <a href="{{ route('trade.agreements.index') }}" class="h-11 inline-flex items-center justify-center rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition-all">More Agreements</a>
            <a href="/" class="h-11 inline-flex items-center justify-center rounded-xl bg-[#046bd2] text-white text-sm font-bold hover:bg-[#045cb4] transition-all">Back to Home</a>
        </div>
    </div>
</div>
@endsection