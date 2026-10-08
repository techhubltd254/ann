@extends('layouts.app')
@section('title','Order '.$order->order_number.' — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10">
<h1 class="text-2xl font-black text-gray-900 mb-2">Order {{ $order->order_number }}</h1>
<div class="flex items-center gap-3 mb-6"><span class="text-xs px-2 py-1 rounded-full {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $order->payment_status ?? 'pending' }}</span><span class="text-gray-400 text-sm">{{ $order->created_at->format('M d, Y H:i') }}</span></div>

<div class="grid md:grid-cols-3 gap-6">
<div class="md:col-span-2">
<div class="bg-white border border-gray-200 rounded-2xl p-5 mb-4"><h2 class="font-bold text-gray-900 mb-3">Items</h2>
@foreach($order->items as $item)
<div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"><div><div class="font-semibold text-sm text-gray-900">{{ $item->product_name }}</div><div class="text-xs text-gray-400">{{ $item->variant_name }} × {{ $item->quantity }}</div></div><span class="font-bold text-sm">KES {{ number_format($item->total) }}</span></div>
@endforeach
<div class="flex justify-between pt-3 font-bold">Total<span>KES {{ number_format($order->grand_total) }}</span></div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl p-5"><h2 class="font-bold text-gray-900 mb-3">Status Timeline</h2>
@forelse($order->statusHistory as $h)
<div class="flex gap-3 py-2 border-b border-gray-100 last:border-0"><div class="w-2 h-2 rounded-full bg-[#0B0B0B] mt-2 shrink-0"></div><div><div class="text-sm font-medium text-gray-900 capitalize">{{ $h->status_to }}</div><div class="text-xs text-gray-400">{{ $h->notes }} · {{ $h->created_at->format('d M H:i') }}</div></div></div>
@empty
<p class="text-gray-400 text-sm">No updates yet.</p>
@endforelse
</div>

@if($returns->count())
<div class="bg-white border border-gray-200 rounded-2xl p-5 mt-4"><h2 class="font-bold text-gray-900 mb-3">Returns</h2>
@foreach($returns as $r)
<div class="flex justify-between py-1 text-sm"><span class="text-gray-600">{{ $r->return_number }}</span><span class="{{ $r->status === 'refunded' ? 'text-green-600' : 'text-yellow-600' }} capitalize">{{ $r->status }}</span></div>
@endforeach
</div>
@endif
</div>

<div class="bg-white border border-gray-200 rounded-2xl p-5 h-fit"><h2 class="font-bold text-gray-900 mb-3">Need Help?</h2>
@foreach($order->items as $item)
<form method="POST" action="{{ route('orders.return', $order->order_number) }}" class="mb-3 border-b border-gray-100 pb-3 last:border-0">
@csrf
<input type="hidden" name="order_item_id" value="{{ $item->id }}">
<p class="text-xs text-gray-500 mb-2">{{ $item->product_name }} ({{ $item->variant_name }})</p>
<textarea name="reason" rows="2" class="w-full border border-gray-200 rounded-xl p-2 text-xs mb-2" placeholder="Reason for return..." required></textarea>
<button type="submit" class="text-xs font-bold text-[#0B0B0B] hover:underline">Request Return</button>
</form>
@endforeach
</div>
</div>
</div>
@if(session('success'))<script>alert('{{ session('success') }}');</script>@endif
@endsection