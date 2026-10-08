@extends('layouts.app')
@section('title', $agent->business_name . ' — Agent Review')
@section('content')
<div class="pt-24 max-w-5xl mx-auto px-5 py-10">
    <a href="{{ route('agent.admin.index') }}" class="text-gray-500 hover:text-[#0B0B0B] text-sm mb-4 inline-block">← All Agents</a>
    <div class="bg-white border border-gray-200 rounded-2xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-black text-gray-900">{{ $agent->business_name }}</h1>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $agent->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($agent->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($agent->status) }}</span>
            </div>
            @if($agent->status === 'pending')
            <div class="flex gap-2">
                <form method="POST" action="{{ route('agent.admin.approve', $agent->id) }}" class="inline">
                    @csrf
                    <input type="hidden" name="commission_rate" value="{{ $agent->commission_rate }}">
                    <button class="h-10 px-5 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700">Approve</button>
                </form>
                <form method="POST" action="{{ route('agent.admin.reject', $agent->id) }}" class="inline" onsubmit="return confirm('Reject this agent?')">
                    @csrf
                    <button class="h-10 px-5 rounded-xl bg-red-600 text-white text-sm font-bold hover:bg-red-700">Reject</button>
                </form>
            </div>
            @endif
        </div>
        <div class="grid sm:grid-cols-2 gap-6">
            <div><h3 class="font-bold text-gray-900 text-sm mb-3">Business Details</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-400">Registration #</dt><dd class="font-semibold">{{ $agent->registration_number ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">License #</dt><dd class="font-semibold">{{ $agent->license_number ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">Tax ID</dt><dd class="font-semibold">{{ $agent->tax_id ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">County</dt><dd class="font-semibold">{{ $agent->county?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">Type</dt><dd class="font-semibold">{{ ucfirst($agent->agent_type) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">Commission</dt><dd class="font-semibold">{{ $agent->commission_rate }}%</dd></div>
                </dl>
            </div>
            <div><h3 class="font-bold text-gray-900 text-sm mb-3">Contact</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-400">Email</dt><dd class="font-semibold">{{ $agent->contact_email }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">Phone</dt><dd class="font-semibold">{{ $agent->contact_phone ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">Website</dt><dd class="font-semibold">{{ $agent->website ?: '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-400">Address</dt><dd class="font-semibold">{{ $agent->address ?: '—' }}</dd></div>
                </dl>
            </div>
        </div>
        @if($agent->description)
        <div class="mt-6"><h3 class="font-bold text-gray-900 text-sm mb-2">Description</h3><p class="text-gray-600 text-sm">{{ $agent->description }}</p></div>
        @endif
        @if($agent->service_types)
        <div class="mt-6"><h3 class="font-bold text-gray-900 text-sm mb-2">Services</h3>
            <div class="flex flex-wrap gap-2">@foreach($agent->service_types as $s)<span class="px-3 py-1 rounded-full bg-gray-100 text-xs font-bold text-gray-600">{{ str_replace('_',' ',$s) }}</span>@endforeach</div>
        </div>
        @endif
        @if($agent->documents->isNotEmpty())
        <div class="mt-6"><h3 class="font-bold text-gray-900 text-sm mb-3">Documents</h3>
            <div class="space-y-2">@foreach($agent->documents as $d)
                <div class="flex items-center justify-between border border-gray-200 rounded-xl p-3">
                    <div><div class="text-sm font-semibold text-gray-900">{{ $d->original_name }}</div><div class="text-xs text-gray-400">{{ str_replace('_',' ',$d->document_type) }} · {{ $d->status }}</div></div>
                    <div class="flex gap-2">
                        <a href="{{ asset('storage/'.$d->file_path) }}" target="_blank" class="text-xs font-bold text-[#0B0B0B] hover:underline">View</a>
                        @if($d->status !== 'verified')
                        <form method="POST" action="{{ route('agent.admin.document.verify', $d->id) }}" class="inline">@csrf<button class="text-xs font-bold text-emerald-600 hover:underline">Verify</button></form>
                        @endif
                    </div>
                </div>
            @endforeach</div>
        </div>
        @endif
    </div>
</div>
@endsection