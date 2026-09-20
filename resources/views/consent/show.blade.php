@extends('layouts.app')
@section('title', $form->title)
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
    <div class="flex items-center gap-3 mb-3">
        <div class="h-px w-8 bg-[#FFCD05]"></div>
        <span class="text-[#FFCD05] text-xs font-bold tracking-[0.2em] uppercase">Consent</span>
        <span class="text-xs text-gray-400 ml-2">{{ $form->language === 'sw' ? 'Idhini' : 'Consent Form' }}</span>
    </div>
    <h1 class="text-3xl md:text-4xl font-black text-gray-900 leading-tight mb-2">{{ $form->title }}</h1>
    <p class="text-gray-500 text-sm mb-8">{{ $form->language === 'sw' ? 'Tafadhali soma na kukubali sheria na masharti hapa chini.' : 'Please read and agree to the terms below.' }}</p>

    <div class="flex gap-3 mb-6">
        <a href="{{ route('consent.physical') }}" target="_blank" class="inline-flex items-center gap-2 text-xs text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4"/></svg>
            Print Physical Form
        </a>
    </div>

    <form method="POST" action="{{ route('consent.sign', $form->slug) }}" class="bg-white rounded-2xl border border-gray-200 p-6 md:p-10">
        @csrf

        {{-- Legal Text --}}
        <div class="prose prose-sm max-w-none mb-8 p-6 bg-gray-50 rounded-xl border border-gray-200 max-h-96 overflow-y-auto">
            @if($form->language === 'sw' && $form->content_sw)
                {!! nl2br(e($form->content_sw)) !!}
            @else
                {!! nl2br(e($form->content_en ?? $form->content_sw)) !!}
            @endif
        </div>

        {{-- Signer Details --}}
        <div class="grid md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">@lang('Full Name') *</label>
                <input name="signer_name" required class="border border-gray-300 rounded-xl w-full px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">@lang('ID / Passport Number') *</label>
                <input name="signer_id_number" required class="border border-gray-300 rounded-xl w-full px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">@lang('Phone Number') *</label>
                <input name="signer_phone" type="tel" required class="border border-gray-300 rounded-xl w-full px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">@lang('Email Address') *</label>
                <input name="signer_email" type="email" required class="border border-gray-300 rounded-xl w-full px-4 py-2.5 text-sm">
            </div>
        </div>

        {{-- Specific Agreements --}}
        <div class="space-y-3 mb-6 p-4 bg-amber-50 rounded-xl border border-amber-200">
            <p class="text-sm font-bold text-amber-800 mb-2">@lang('Specific Consents')</p>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="agree_photo" required class="mt-1">
                <span class="text-sm text-gray-700">@lang('I consent to my photograph being taken during KICC events and activities.')</span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="agree_video" required class="mt-1">
                <span class="text-sm text-gray-700">@lang('I consent to video recording of my participation in KICC events.')</span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="agree_publish" required class="mt-1">
                <span class="text-sm text-gray-700">@lang('I consent to my image and video being published on the KICC National Exhibition Platform, social media, and promotional materials.')</span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="agree_retention" required class="mt-1">
                <span class="text-sm text-gray-700">@lang('I understand that my data will be retained for 10 years for exhibition archival purposes, after which it will be securely deleted.')</span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="agree_terms" required class="mt-1">
                <span class="text-sm text-gray-700">@lang('I have read and understood the terms above. I understand that I can withdraw consent at any time by contacting dpo@kicc.go.ke.')</span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="agree_third_party" class="mt-1">
                <span class="text-sm text-gray-700">@lang('(Optional) I consent to my image being shared with partner organizations for trade promotion purposes.')</span>
            </label>
        </div>

        {{-- Legal Notice --}}
        <div class="text-xs text-gray-400 mb-6 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <p class="font-semibold text-gray-500 mb-1">@lang('Legal Basis')</p>
            <p>@lang('Kenya Data Protection Act 2019 §30(a) — Consent. Biometric data (photographs, video) is processed under §44 (sensitive data). GDPR Art. 6(1)(a), Art. 9(2)(a). Data Controller: Kenyatta International Convention Centre, P.O. Box 30746-00100, Nairobi. DPO: dpo@kicc.go.ke')</p>
        </div>

        <button type="submit" class="w-full bg-[#901C1E] text-white py-3.5 rounded-xl font-bold text-sm hover:bg-[#7b1618] transition-all active:scale-[0.98]">
            {{ $form->language === 'sw' ? 'Saini Idhini' : 'Sign Consent' }}
        </button>

        <p class="text-center text-xs text-gray-400 mt-3">@lang('By signing, you agree to the above terms. Your digital signature is legally binding under Kenyan law. Reference will be provided upon completion.')</p>
    </form>
</div>
@endsection