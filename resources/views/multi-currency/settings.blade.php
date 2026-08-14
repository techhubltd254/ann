@extends('layouts.app')
@section('title', 'Multi-Currency — KICC')
@section('content')
<div class="pt-24 max-w-4xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-2">Multi-Currency Settings</h1>
    <p class="text-gray-500 text-sm mb-6">Supported currencies and exchange rates. Base: KES (1 KES = 1).</p>
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <table class="w-full text-sm"><thead><tr class="bg-gray-50 border-b border-gray-100 text-left text-xs text-gray-500 uppercase"><th class="p-4">Currency</th><th class="p-4">Rate (1 KES)</th><th class="p-4">Status</th></tr></thead>
            <tbody>@foreach($currencies as $code => $rate)<tr class="border-b border-gray-50"><td class="p-4 font-bold text-gray-900">{{ $code }}</td><td class="p-4">{{ $rate }}</td><td class="p-4"><span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Active</span></td></tr>@endforeach</tbody>
        </table>
    </div>
    <div class="mt-6 bg-white border border-gray-200 rounded-2xl p-6">
        <h3 class="font-bold text-gray-900 text-sm mb-3">Currency Converter (demo)</h3>
        <form class="flex gap-3 items-end" x-data="{ amount: 1000, from: 'KES', to: 'USD', result: null }" @submit.prevent="fetch('/api/currency/convert',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({amount,from,to})}).then(r=>r.json()).then(d=>result=d.converted)">
            @csrf
            <div><input type="number" x-model="amount" class="w-32 h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"></div>
            <select x-model="from" class="h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm">@foreach($currencies as $code => $rate)<option value="{{ $code }}">{{ $code }}</option>@endforeach</select>
            <span>→</span>
            <select x-model="to" class="h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm">@foreach($currencies as $code => $rate)<option value="{{ $code }}">{{ $code }}</option>@endforeach</select>
            <button type="submit" class="h-11 px-6 rounded-xl bg-[#046bd2] text-white font-bold text-sm">Convert</button>
            <div x-show="result" x-text="`${amount} ${from} = ${result} ${to}`" class="text-sm font-bold text-gray-900"></div>
        </form>
    </div>
</div>
@endsection