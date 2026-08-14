@extends('layouts.app')
@section('title', 'About KICC')
@section('content')
<div class="pt-20 max-w-5xl mx-auto px-5 py-10">
    <div class="bg-gradient-to-r from-[#046bd2] to-[#045cb4] rounded-2xl p-8 mb-8">
        <h1 class="text-3xl font-black text-white">About Kenyatta International Convention Centre</h1>
        <p class="text-white/70 mt-2">Africa's Premier Meeting Venue — A national icon since 1973.</p>
    </div>
    <div class="grid md:grid-cols-2 gap-4 mb-8">
        <a href="{{ route('kicc.mission') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-[#046bd2]/40 transition-all card-hover"><div class="text-2xl mb-2">🎯</div><h3 class="font-bold text-gray-900">Mission, Vision & Mandate</h3><p class="text-gray-500 text-sm mt-1">Our purpose, direction and core mandate.</p></a>
        <a href="{{ route('kicc.board') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-[#046bd2]/40 transition-all card-hover"><div class="text-2xl mb-2">👥</div><h3 class="font-bold text-gray-900">KICC Board</h3><p class="text-gray-500 text-sm mt-1">Our board of directors and leadership.</p></a>
        <a href="{{ route('kicc.management') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-[#046bd2]/40 transition-all card-hover"><div class="text-2xl mb-2">👤</div><h3 class="font-bold text-gray-900">KICC Management</h3><p class="text-gray-500 text-sm mt-1">Meet the management team.</p></a>
        <a href="{{ route('kicc.history') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-[#046bd2]/40 transition-all card-hover"><div class="text-2xl mb-2">📜</div><h3 class="font-bold text-gray-900">KICC History</h3><p class="text-gray-500 text-sm mt-1">Our journey since 1973.</p></a>
        <a href="{{ route('kicc.org-structure') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-[#046bd2]/40 transition-all card-hover"><div class="text-2xl mb-2">🏛️</div><h3 class="font-bold text-gray-900">Organisation Structure</h3><p class="text-gray-500 text-sm mt-1">How we are organised.</p></a>
    </div>
    @include('kicc-website._contact_strip')
</div>
@endsection