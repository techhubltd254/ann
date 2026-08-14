@extends('layouts.app')
@section('title', 'Commission Log — KICC Admin')
@section('content')
<div class="pt-24 max-w-7xl mx-auto px-5 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-gray-900">Commission & Licensing</h1>
            <p class="text-gray-500 text-sm mt-1">Pending: KES {{ number_format($totalPending) }} · Settled: KES {{ number_format($totalSettled) }}</p>
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-gray-50 border-b border-gray-100 text-left text-xs text-gray-500 uppercase tracking-wider">
                <th class="p-4">Agent</th><th class="p-4">Order</th><th class="p-4">Item Total</th><th class="p-4">Rate</th><th class="p-4">Commission</th><th class="p-4">Type</th><th class="p-4">Status</th>
            </tr></thead>
            <tbody>
                @forelse($commissions as $c)
                <tr class="border-b border-gray-50">
                    <td class="p-4"><div class="font-semibold text-gray-900">{{ $c->agent?->business_name ?? '—' }}</div></td>
                    <td class="p-4"><span class="font-mono text-xs">{{ $c->order?->order_number ?? '—' }}</span></td>
                    <td class="p-4">KES {{ number_format($c->item_total) }}</td>
                    <td class="p-4">{{ $c->commission_rate }}%</td>
                    <td class="p-4 font-bold text-gray-900">KES {{ number_format($c->commission_amount) }}</td>
                    <td class="p-4"><span class="text-xs font-bold {{ $c->commission_type === 'licensing' ? 'text-[#046bd2]' : 'text-gray-600' }}">{{ ucfirst($c->commission_type) }}</span></td>
                    <td class="p-4"><span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $c->status === 'settled' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($c->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" class="p-10 text-center text-gray-400">No commission records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $commissions->links() }}</div>
</div>
@endsection