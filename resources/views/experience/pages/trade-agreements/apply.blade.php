@extends('layouts.app')

@section('title', 'Apply to Export — ' . $agreement->title)
@section('description', 'Submit an export application under ' . $agreement->title)

@section('content')
<div class="pt-28 pb-16">
    <div class="max-w-2xl mx-auto px-5">
        <a href="{{ route('trade.agreements.show', $agreement->slug) }}" class="inline-flex items-center gap-1.5 text-gray-500 hover:text-[#0B0B0B] text-sm mb-6 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to agreement
        </a>

        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-[#0B0B0B] to-[#0B0B0B] px-6 py-5">
                <span class="text-[#FFCD05] text-xs font-black uppercase tracking-widest">{{ $agreement->bloc?->name ?? $agreement->agreement_type }}</span>
                <h1 class="text-xl font-black text-white mt-1">{{ $agreement->title }}</h1>
            </div>
        </div>

        <form method="POST" action="{{ route('trade.enquiry.store') }}" class="bg-white border border-gray-200 rounded-2xl p-6" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            <input type="hidden" name="trade_agreement_id" value="{{ $agreement->id }}">
            <h2 class="font-bold text-gray-900 mb-4">Export application form</h2>
            <div class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Company Name *</label>
                        <input type="text" name="company_name" required value="{{ old('company_name') }}" class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Product Name</label>
                        <input type="text" name="product_name" value="{{ old('product_name') }}" placeholder="e.g. Kericho black tea" class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40">
                    </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Product Category</label>
                        <input type="text" name="product_category" value="{{ old('product_category') }}" placeholder="e.g. Agriculture, Tea" class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Destination Market</label>
                        <input type="text" name="destination" value="{{ old('destination') }}" placeholder="e.g. United Kingdom" class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40">
                    </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Contact Name *</label>
                        <input type="text" name="contact_name" required value="{{ old('contact_name') }}" class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Contact Email *</label>
                        <input type="email" name="contact_email" required value="{{ old('contact_email') }}" class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40">
                    </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Phone</label>
                        <input type="tel" name="contact_phone" value="{{ old('contact_phone') }}" placeholder="+254 7XX XXX XXX" class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Estimated Export Value (KES)</label>
                        <input type="number" name="estimated_value" min="0" step="0.01" value="{{ old('estimated_value') }}" class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Message / Requirements</label>
                    <textarea name="message" rows="3" class="w-full px-4 py-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#0B0B0B]/40" placeholder="Tell us about your export plans, certifications, volumes…"></textarea>
                </div>
            </div>
            <button type="submit" :disabled="submitting" class="mt-6 w-full h-12 rounded-xl bg-[#0B0B0B] text-white font-black text-sm hover:bg-[#0B0B0B] transition-all disabled:opacity-60">
                <span x-text="submitting ? 'Submitting…' : 'Submit Export Application'"></span>
            </button>
            <p class="text-gray-400 text-[11px] text-center mt-3">Your enquiry is sent to the trade desk. A KICC trade officer will contact you.</p>
        </form>
    </div>
</div>
@endsection