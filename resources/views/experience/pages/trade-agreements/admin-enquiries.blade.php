@extends('layouts.app')

@section('title', 'Trade Enquiries — Admin')
@section('description', 'Manage export applications')

@section('content')
<div class="pt-24 max-w-7xl mx-auto px-5 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Export Enquiries</h1>
            <p class="text-gray-500 text-sm mt-1">Export applications submitted from the platform.</p>
        </div>
        <div class="flex gap-2">
            @foreach(['submitted','reviewing','approved','rejected','shipped'] as $s)
            <a href="{{ route('trade.admin.enquiries', $s !== 'submitted' ? ['status' => $s] : []) }}" class="px-3 py-1.5 rounded-full text-[10px] font-bold {{ ($status ?? 'submitted') === $s ? 'bg-[#0B0B0B] text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">{{ ucfirst($s) }}</a>
            @endforeach
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
    @endif

    @if($enquiries->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-12 text-center">
        <div class="text-4xl mb-3"></div>
        <h3 class="font-bold text-gray-900 mb-1">No enquiries yet</h3>
        <p class="text-gray-500 text-sm">Export applications will appear here as users submit them.</p>
    </div>
    @else
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-left text-xs text-gray-500 uppercase tracking-wider">
                    <th class="p-4">Reference</th>
                    <th class="p-4">Company</th>
                    <th class="p-4">Product</th>
                    <th class="p-4">Agreement / Bloc</th>
                    <th class="p-4">Destination</th>
                    <th class="p-4">Value (KES)</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($enquiries as $e)
                <tr class="border-b border-gray-50 hover:bg-gray-50/50">
                    <td class="p-4 font-mono text-xs text-[#0B0B0B]">{{ $e->reference }}</td>
                    <td class="p-4">
                        <div class="font-bold text-gray-900">{{ $e->company_name }}</div>
                        <div class="text-xs text-gray-400">{{ $e->contact_name }} · {{ $e->contact_email }}</div>
                    </td>
                    <td class="p-4 text-gray-600">{{ $e->product_name ?: '—' }}</td>
                    <td class="p-4">
                        @if($e->agreement)<div class="text-xs text-gray-600">{{ $e->agreement->title }}</div>@endif
                        @if($e->bloc)<div class="text-[10px] text-[#0B0B0B] font-bold">{{ $e->bloc->name }}</div>@endif
                    </td>
                    <td class="p-4 text-gray-600">{{ $e->destination ?: '—' }}</td>
                    <td class="p-4 text-gray-600">{{ $e->estimated_value ? number_format($e->estimated_value) : '—' }}</td>
                    <td class="p-4">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold
                            {{ $e->status === 'approved' ? 'bg-emerald-100 text-emerald-700' :
                               ($e->status === 'rejected' ? 'bg-red-100 text-red-700' :
                               ($e->status === 'shipped' ? 'bg-sky-100 text-sky-700' :
                               ($e->status === 'reviewing' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600'))) }}">{{ ucfirst($e->status) }}</span>
                    </td>
                    <td class="p-4">
                        <details class="relative">
                            <summary class="cursor-pointer text-xs font-bold text-[#0B0B0B] hover:underline">Manage</summary>
                            <form method="POST" action="{{ route('trade.admin.enquiry.status', $e->id) }}" class="absolute right-0 z-20 mt-2 bg-white border border-gray-200 rounded-xl p-3 shadow-lg w-56 space-y-2">
                                @csrf
                                <select name="status" class="w-full h-9 rounded-lg border border-gray-200 text-sm">
                                    @foreach(['submitted','reviewing','approved','rejected','shipped'] as $s)
                                    <option value="{{ $s }}" {{ $e->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                                <textarea name="admin_note" rows="2" placeholder="Admin note…" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-xs">{{ $e->admin_note }}</textarea>
                                <button class="w-full h-9 rounded-lg bg-[#0B0B0B] text-white text-xs font-bold">Update</button>
                            </form>
                        </details>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $enquiries->links() }}</div>
    @endif
</div>
@endsection