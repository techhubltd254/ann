@extends('layouts.app')
@section('title','My Orders — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10"><h1 class="text-2xl font-black text-gray-900 mb-6">My Orders</h1>
@forelse($orders as $order)
<a href="{{ route('orders.track', $order->order_number) }}" class="block bg-white border border-gray-200 rounded-2xl p-5 mb-3 card-hover hover:border-[#0B0B0B]/30">
<div class="flex items-center justify-between"><div><span class="font-bold text-gray-900">{{ $order->order_number }}</span><span class="text-gray-400 text-xs ml-3">{{ $order->created_at->format('M d, Y') }}</span></div><span class="text-xs font-bold px-2 py-1 rounded-full {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $order->payment_status ?? 'pending' }}</span></div>
<div class="text-sm text-gray-500 mt-2">{{ $order->items->count() }} items · KES {{ number_format($order->grand_total) }}</div>
</a>
@empty
<div class="text-center py-20 text-gray-400"><p>No orders yet.</p><a href="{{ route('marketplace.index') }}" class="inline-block mt-4 text-[#0B0B0B] font-bold">Start shopping</a></div>
@endforelse
{{ $orders->links() }}</div>@endsection