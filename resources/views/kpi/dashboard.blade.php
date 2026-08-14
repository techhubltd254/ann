@extends('layouts.app')
@section('title', 'KPIs & Monitoring — KICC')
@section('content')
<div class="pt-24 max-w-7xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-6">KPIs & Monitoring</h1>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-emerald-500">{{ $uptime }}%</div><div class="text-xs text-gray-400 mt-1">Platform Availability</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $avgPageLoad }}</div><div class="text-xs text-gray-400 mt-1">Avg Page Load Time</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $apiResponse }}</div><div class="text-xs text-gray-400 mt-1">API Response Time</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $bookingCompletion }}%</div><div class="text-xs text-gray-400 mt-1">Booking Completion</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-emerald-500">{{ $paymentSuccess }}%</div><div class="text-xs text-gray-400 mt-1">Payment Success</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $mobileCrashRate }}%</div><div class="text-xs text-gray-400 mt-1">Mobile Crash Rate</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-amber-500">⭐ {{ $userSatisfaction }}</div><div class="text-xs text-gray-400 mt-1">User Satisfaction</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-[#046bd2]">KES {{ number_format($revenue) }}</div><div class="text-xs text-gray-400 mt-1">Total Revenue</div></div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-xl font-black text-gray-900">{{ $totalOrders }}</div><div class="text-xs text-gray-400 mt-1">Total Orders</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-xl font-black text-gray-900">{{ $totalProducts }}</div><div class="text-xs text-gray-400 mt-1">Products</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-xl font-black text-gray-900">{{ $totalCounties }}</div><div class="text-xs text-gray-400 mt-1">Counties</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-xl font-black text-gray-900">{{ $totalAgents }}</div><div class="text-xs text-gray-400 mt-1">Verified Agents</div></div>
    </div>
</div>
@endsection