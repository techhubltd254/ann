@extends('layouts.app')
@section('title', 'Verify Consent')
@section('content')
<div class="pt-20 max-w-xl mx-auto px-5 py-10">
    <h1 class="text-3xl font-black text-gray-900 mb-6">@lang('Verify Consent Record')</h1>

    <form method="GET" action="{{ route('consent.verify') }}" class="flex gap-3 mb-8">
        <input name="reference" placeholder="@lang('Enter Reference Number (e.g. KICC-CS-000001)')" class="border border-gray-300 rounded-xl flex-1 px-4 py-2.5 text-sm" value="{{ request('reference') }}">
        <button class="bg-[#0B1E57] text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-[#0D2A7A]">@lang('Verify')</button>
    </form>

    @if(isset($record))
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="w-3 h-3 rounded-full bg-green-500"></span>
            <span class="font-bold text-green-700">@lang('Verified — This consent record is authentic')</span>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">@lang('Reference')</span><p class="font-semibold">KICC-CS-{{ str_pad($record->id, 6, '0', STR_PAD_LEFT) }}</p></div>
            <div><span class="text-gray-500">@lang('Signer')</span><p class="font-semibold">{{ $record->signer_name }}</p></div>
            <div><span class="text-gray-500">@lang('ID Number')</span><p class="font-semibold">{{ $record->signer_id_number }}</p></div>
            <div><span class="text-gray-500">@lang('Signed At')</span><p class="font-semibold">{{ $record->signed_at->format('d M Y H:i:s') }}</p></div>
            <div><span class="text-gray-500">@lang('Form')</span><p class="font-semibold">{{ $record->consentForm?->title }}</p></div>
            <div><span class="text-gray-500">@lang('Consent Version')</span><p class="font-semibold">{{ $record->consentForm?->slug }}</p></div>
        </div>
        <div class="mt-4 text-xs text-gray-400 border-t pt-3">
            <p>@lang('This verification confirms the consent record exists and has not been tampered with. The timestamp, IP address, and agreement data are stored immutably.')</p>
        </div>
    </div>
    @endif
</div>
@endsection