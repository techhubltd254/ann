@extends('layouts.app')
@section('title', 'Consent Signed')
@section('content')
<div class="pt-20 max-w-2xl mx-auto px-5 py-10 text-center">
    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-3xl font-black text-gray-900 mb-2">@lang('Consent Signed')</h1>
    <p class="text-gray-500 mb-6">@lang('Your consent has been recorded successfully.')</p>

    <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-8 text-left">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">@lang('Reference No.')</span><p class="font-semibold">KICC-CS-{{ str_pad($record->id, 6, '0', STR_PAD_LEFT) }}</p></div>
            <div><span class="text-gray-500">@lang('Signer')</span><p class="font-semibold">{{ $record->signer_name }}</p></div>
            <div><span class="text-gray-500">@lang('Email')</span><p class="font-semibold">{{ $record->signer_email }}</p></div>
            <div><span class="text-gray-500">@lang('Signed At')</span><p class="font-semibold">{{ $record->signed_at->format('d M Y H:i:s') }}</p></div>
            <div><span class="text-gray-500">@lang('Form')</span><p class="font-semibold">{{ $form->title }}</p></div>
            <div><span class="text-gray-500">@lang('IP Address')</span><p class="font-semibold">{{ $record->ip_address }}</p></div>
        </div>
    </div>

    <div class="text-xs text-gray-400 mb-8 p-4 bg-gray-50 rounded-xl border border-gray-200 text-left">
        <p class="font-semibold text-gray-500 mb-1">@lang('Your Rights')</p>
        <ul class="list-disc list-inside space-y-1">
            <li>@lang('You may withdraw consent at any time by emailing dpo@kicc.go.ke')</li>
            <li>@lang('You have the right to access, rectify, or erase your data under Kenya DPA 2019 §24-27')</li>
            <li>@lang('You may lodge a complaint with the Office of the Data Protection Commissioner (ODPC)')</li>
        </ul>
    </div>

    <div class="flex gap-4 justify-center">
        <a href="{{ route('consent.verify', ['reference' => $record->id]) }}" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-bold text-sm hover:bg-gray-200">@lang('Verify This Consent')</a>
        <a href="/" class="bg-[#0B1E57] text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-[#0D2A7A]">@lang('Continue to Site')</a>
    </div>
</div>
@endsection