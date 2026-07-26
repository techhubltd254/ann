@extends('layouts.app')

@section('title', 'National Government Admin')

@section('content')
<div class="max-w-7xl mx-auto px-5 py-10" x-data="{ tab: 'overview' }">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black text-[#0B1E57]">National Government Admin</h1>
            <p class="text-[#5A6480] mt-1">Manage ministries, agencies, and national content</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.portal') }}" class="px-4 py-2 text-sm font-semibold text-[#5A6480] hover:text-[#901C1E] rounded-lg hover:bg-gray-100 transition-colors">← Portal</a>
        </div>
    </div>

    {{-- Tab Nav --}}
    <div class="flex gap-1 bg-white border border-[#0B1E57]/8 rounded-xl p-1 mb-8 overflow-x-auto">
        @foreach(['overview' => 'Overview', 'ministries' => 'Ministries', 'agencies' => 'Agencies', 'sectors' => 'Sectors', 'content' => 'Content'] as $key => $label)
        <button @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-[#0B1E57] text-white' : 'text-[#5A6480] hover:text-[#0B1E57]'" class="px-4 py-2 text-sm font-bold rounded-lg transition-all shrink-0">{{ $label }}</button>
        @endforeach
    </div>

    {{-- OVERVIEW --}}
    <div x-show="tab === 'overview'" x-cloak>
        @php
            $totalMinistries = \App\Models\Ministry::count();
            $totalAgencies = \App\Models\Agency::count();
            $totalSectors = \App\Models\Sector::count();
            $totalCounties = \App\Models\County::count();
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl border border-[#0B1E57]/8 p-6">
                <div class="text-3xl font-black text-[#0B1E57]">{{ $totalMinistries }}</div>
                <div class="text-[#5A6480] text-sm mt-1">Ministries</div>
            </div>
            <div class="bg-white rounded-2xl border border-[#0B1E57]/8 p-6">
                <div class="text-3xl font-black text-[#0B1E57]">{{ $totalAgencies }}</div>
                <div class="text-[#5A6480] text-sm mt-1">Agencies</div>
            </div>
            <div class="bg-white rounded-2xl border border-[#0B1E57]/8 p-6">
                <div class="text-3xl font-black text-[#0B1E57]">{{ $totalSectors }}</div>
                <div class="text-[#5A6480] text-sm mt-1">Sectors</div>
            </div>
            <div class="bg-white rounded-2xl border border-[#0B1E57]/8 p-6">
                <div class="text-3xl font-black text-[#0B1E57]">{{ $totalCounties }}</div>
                <div class="text-[#5A6480] text-sm mt-1">Counties</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-[#0B1E57]/8 p-6">
            <h3 class="font-bold text-[#0B1E57] mb-4">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <a href="/admin/ministries" class="px-4 py-2 text-sm font-bold bg-[#901C1E] text-white rounded-xl hover:bg-[#7a181a] transition-colors">Manage Ministries</a>
                <a href="/admin/agencies" class="px-4 py-2 text-sm font-bold bg-[#0B1E57] text-white rounded-xl hover:bg-[#0a1a4a] transition-colors">Manage Agencies</a>
                <a href="/admin/sectors" class="px-4 py-2 text-sm font-bold bg-[#FFCD05] text-[#0B1E57] rounded-xl hover:bg-[#e6b904] transition-colors">Manage Sectors</a>
                <a href="/admin/counties" class="px-4 py-2 text-sm font-bold border border-[#0B1E57]/20 text-[#0B1E57] rounded-xl hover:bg-[#0B1E57]/5 transition-colors">View Counties</a>
            </div>
        </div>
    </div>

    {{-- MINISTRIES --}}
    <div x-show="tab === 'ministries'" x-cloak>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-black text-[#0B1E57]">Ministries</h2>
            <a href="/admin/ministries/create" class="px-4 py-2 text-sm font-bold bg-[#901C1E] text-white rounded-xl hover:bg-[#7a181a] transition-colors">+ Add Ministry</a>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach(\App\Models\Ministry::with('agencies')->get() as $ministry)
            <div class="bg-white rounded-2xl border border-[#0B1E57]/8 p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-black text-lg" style="background: {{ $ministry->color }}">{{ $ministry->code[0] }}</div>
                    <div class="flex gap-1">
                        <a href="/admin/ministries/{{ $ministry->id }}/edit" class="p-1.5 text-[#5A6480] hover:text-[#901C1E] hover:bg-gray-100 rounded-lg transition-colors" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <form method="POST" action="/admin/ministries/{{ $ministry->id }}" onsubmit="return confirm('Delete {{ $ministry->name }}?')">
                            @csrf @method('DELETE')
                            <button class="p-1.5 text-[#5A6480] hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                <h3 class="font-black text-[#0B1E57] text-sm">{{ $ministry->name }}</h3>
                <div class="text-[#5A6480] text-xs mt-1">{{ $ministry->code }}</div>
                @if($ministry->description)
                <p class="text-[#5A6480] text-xs mt-2 line-clamp-2">{{ $ministry->description }}</p>
                @endif
                <div class="mt-3 pt-3 border-t border-[#0B1E57]/8 flex items-center justify-between">
                    <span class="text-xs text-[#5A6480]">{{ $ministry->agencies->count() }} agencies</span>
                    <span class="text-xs font-bold {{ $ministry->is_active ? 'text-emerald-500' : 'text-red-500' }}">{{ $ministry->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- AGENCIES --}}
    <div x-show="tab === 'agencies'" x-cloak>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-black text-[#0B1E57]">Agencies</h2>
            <a href="/admin/agencies/create" class="px-4 py-2 text-sm font-bold bg-[#0B1E57] text-white rounded-xl hover:bg-[#0a1a4a] transition-colors">+ Add Agency</a>
        </div>
        <div class="bg-white rounded-2xl border border-[#0B1E57]/8 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#F9FAFB] border-b border-[#0B1E57]/8">
                    <tr>
                        <th class="text-left px-5 py-3 font-bold text-[#0B1E57]">Agency</th>
                        <th class="text-left px-5 py-3 font-bold text-[#0B1E57]">Ministry</th>
                        <th class="text-left px-5 py-3 font-bold text-[#0B1E57]">Code</th>
                        <th class="text-left px-5 py-3 font-bold text-[#0B1E57]">Status</th>
                        <th class="text-right px-5 py-3 font-bold text-[#0B1E57]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#0B1E57]/5">
                    @foreach(\App\Models\Agency::with('ministry')->get() as $agency)
                    <tr class="hover:bg-[#F9FAFB] transition-colors">
                        <td class="px-5 py-3 font-semibold text-[#0B1E57]">{{ $agency->name }}</td>
                        <td class="px-5 py-3 text-[#5A6480]">{{ $agency->ministry?->name }}</td>
                        <td class="px-5 py-3"><span class="px-2 py-1 bg-[#0B1E57]/10 text-[#0B1E57] text-xs font-bold rounded">{{ $agency->code }}</span></td>
                        <td class="px-5 py-3"><span class="text-xs font-bold {{ $agency->is_active ? 'text-emerald-500' : 'text-red-500' }}">{{ $agency->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="px-5 py-3 text-right">
                            <a href="/admin/agencies/{{ $agency->id }}/edit" class="text-[#5A6480] hover:text-[#901C1E] text-sm font-semibold">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- SECTORS --}}
    <div x-show="tab === 'sectors'" x-cloak>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-black text-[#0B1E57]">Sectors</h2>
            <a href="/admin/sectors/create" class="px-4 py-2 text-sm font-bold bg-[#FFCD05] text-[#0B1E57] rounded-xl hover:bg-[#e6b904] transition-colors">+ Add Sector</a>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach(\App\Models\Sector::withCount('counties')->get() as $sector)
            <div class="bg-white rounded-2xl border border-[#0B1E57]/8 p-5 hover:shadow-lg transition-shadow">
                <div class="text-2xl mb-3">{{ $sector->icon ?? '📊' }}</div>
                <h3 class="font-black text-[#0B1E57] text-sm">{{ $sector->name }}</h3>
                <div class="text-[#5A6480] text-xs mt-1">{{ $sector->counties_count }} counties</div>
                <div class="mt-3 flex gap-1">
                    <a href="/admin/sectors/{{ $sector->id }}/edit" class="text-xs text-[#5A6480] hover:text-[#901C1E] font-semibold">Edit</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- CONTENT --}}
    <div x-show="tab === 'content'" x-cloak>
        <div class="bg-white rounded-2xl border border-[#0B1E57]/8 p-6">
            <h2 class="text-xl font-black text-[#0B1E57] mb-6">National Content Management</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="/admin/counties" class="p-5 bg-[#F9FAFB] rounded-2xl border border-[#0B1E57]/8 hover:border-[#901C1E]/30 transition-all group">
                    <div class="text-2xl mb-3">🏛️</div>
                    <h3 class="font-bold text-[#0B1E57] group-hover:text-[#901C1E]">Counties</h3>
                    <p class="text-[#5A6480] text-xs mt-1">Manage all 47 county profiles</p>
                </a>
                <a href="/admin/exhibitions" class="p-5 bg-[#F9FAFB] rounded-2xl border border-[#0B1E57]/8 hover:border-[#901C1E]/30 transition-all group">
                    <div class="text-2xl mb-3">📅</div>
                    <h3 class="font-bold text-[#0B1E57] group-hover:text-[#901C1E]">Exhibitions</h3>
                    <p class="text-[#5A6480] text-xs mt-1">Manage national exhibitions</p>
                </a>
                <a href="/admin/venues" class="p-5 bg-[#F9FAFB] rounded-2xl border border-[#0B1E57]/8 hover:border-[#901C1E]/30 transition-all group">
                    <div class="text-2xl mb-3">🏢</div>
                    <h3 class="font-bold text-[#0B1E57] group-hover:text-[#901C1E]">Venues</h3>
                    <p class="text-[#5A6480] text-xs mt-1">Manage KICC facilities</p>
                </a>
                <a href="/admin/screens" class="p-5 bg-[#F9FAFB] rounded-2xl border border-[#0B1E57]/8 hover:border-[#901C1E]/30 transition-all group">
                    <div class="text-2xl mb-3">📺</div>
                    <h3 class="font-bold text-[#0B1E57] group-hover:text-[#901C1E]">Screens</h3>
                    <p class="text-[#5A6480] text-xs mt-1">Manage digital screens</p>
                </a>
                <a href="/admin/products" class="p-5 bg-[#F9FAFB] rounded-2xl border border-[#0B1E57]/8 hover:border-[#901C1E]/30 transition-all group">
                    <div class="text-2xl mb-3">📦</div>
                    <h3 class="font-bold text-[#0B1E57] group-hover:text-[#901C1E]">Products</h3>
                    <p class="text-[#5A6480] text-xs mt-1">Manage marketplace products</p>
                </a>
                <a href="/admin/bookings" class="p-5 bg-[#F9FAFB] rounded-2xl border border-[#0B1E57]/8 hover:border-[#901C1E]/30 transition-all group">
                    <div class="text-2xl mb-3">🎫</div>
                    <h3 class="font-bold text-[#0B1E57] group-hover:text-[#901C1E]">Bookings</h3>
                    <p class="text-[#5A6480] text-xs mt-1">View all bookings</p>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
