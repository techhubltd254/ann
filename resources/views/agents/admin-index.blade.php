@extends('layouts.app')
@section('title', 'Agent Management — KICC Admin')
@section('content')
<div class="pt-24 max-w-7xl mx-auto px-5 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-black text-gray-900">Agent Onboarding</h1>
        <div class="flex gap-2">
            @foreach(['pending','approved','rejected'] as $s)
            <a href="{{ route('agent.admin.index', $s !== 'pending' ? ['status' => $s] : []) }}" class="px-3 py-1.5 rounded-full text-[10px] font-bold {{ ($status ?? 'pending') === $s ? 'bg-[#046bd2] text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">{{ ucfirst($s) }}</a>
            @endforeach
        </div>
    </div>
    @if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>@endif
    @if($agents->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center"><div class="text-4xl mb-3"></div><h3 class="font-bold text-gray-900 mb-1">No agents</h3><p class="text-gray-500 text-sm">No agent applications with this status.</p></div>
    @else
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-gray-50 border-b border-gray-100 text-left text-xs text-gray-500 uppercase tracking-wider">
                <th class="p-4">Business</th><th class="p-4">Contact</th><th class="p-4">Services</th><th class="p-4">Type</th><th class="p-4">Documents</th><th class="p-4">Status</th><th class="p-4">Action</th>
            </tr></thead>
            <tbody>
                @foreach($agents as $a)
                <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                    <td class="p-4"><div class="font-bold text-gray-900">{{ $a->business_name }}</div><div class="text-xs text-gray-400">{{ $a->registration_number ?: '—' }}</div></td>
                    <td class="p-4"><div class="text-sm">{{ $a->contact_email }}</div><div class="text-xs text-gray-400">{{ $a->contact_phone }}</div></td>
                    <td class="p-4"><div class="flex flex-wrap gap-1">@foreach($a->service_types ?? [] as $s)<span class="px-2 py-0.5 rounded bg-gray-100 text-[10px] font-bold text-gray-600">{{ str_replace('_',' ',$s) }}</span>@endforeach</div></td>
                    <td class="p-4"><span class="text-xs font-bold {{ $a->agent_type === 'international' ? 'text-[#046bd2]' : 'text-gray-600' }}">{{ ucfirst($a->agent_type) }}</span></td>
                    <td class="p-4"><span class="text-xs font-bold">{{ $a->documents->count() }} files</span></td>
                    <td class="p-4"><span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $a->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($a->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($a->status) }}</span></td>
                    <td class="p-4"><a href="{{ route('agent.admin.show', $a->id) }}" class="text-xs font-bold text-[#046bd2] hover:underline">Review →</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $agents->links() }}</div>
    @endif
</div>
@endsection