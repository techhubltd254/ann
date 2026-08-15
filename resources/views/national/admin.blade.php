@extends('layouts.app')
@section('title', 'National Government Management — KICC Admin')
@section('content')
<div class="pt-24 max-w-7xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-2">National Government Management</h1>
    <p class="text-gray-500 text-sm mb-6">Manage ministries, agencies, and national pages.</p>

    @if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>@endif

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- MINISTRIES --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-gray-900">Ministries ({{ $stats['ministries'] }})</h2>
                <button onclick="document.getElementById('addMinistryForm').classList.toggle('hidden')" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-[#046bd2] text-white">+ Add</button>
            </div>
            <form id="addMinistryForm" method="POST" action="{{ route('national.admin.v2.ministry.store') }}" class="hidden space-y-2 mb-4 p-4 border border-gray-200 rounded-xl">@csrf
                <input type="text" name="name" required placeholder="Ministry name" class="w-full h-9 px-3 rounded-lg border border-gray-200 text-sm">
                <div class="grid grid-cols-2 gap-2"><input type="text" name="code" placeholder="Code (e.g. MITI)" class="h-9 px-3 rounded-lg border border-gray-200 text-sm"><input type="text" name="color" placeholder="Color hex (e.g. #046bd2)" class="h-9 px-3 rounded-lg border border-gray-200 text-sm"></div>
                <input type="url" name="website" placeholder="Website URL" class="w-full h-9 px-3 rounded-lg border border-gray-200 text-sm">
                <textarea name="description" rows="2" placeholder="Description" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm"></textarea>
                <button class="h-9 px-4 rounded-lg bg-[#046bd2] text-white text-xs font-bold">Create</button>
            </form>
            <div class="space-y-3 max-h-[500px] overflow-y-auto">
                @foreach($ministries as $m)
                <div class="border border-gray-200 rounded-xl p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-black text-white" style="background: {{ $m->color ?: '#1890D7' }}">{{ $m->code ?? substr($m->name, 0, 3) }}</div>
                            <div><div class="font-bold text-gray-900 text-sm">{{ $m->name }}</div><div class="text-xs text-gray-400">{{ $m->agencies->count() }} agencies</div></div>
                        </div>
                        <div class="flex gap-1">
                            <button onclick="this.nextElementSibling.classList.toggle('hidden')" class="text-xs px-2 py-1 rounded border border-gray-200 hover:bg-gray-50">✏️</button>
                            <a href="{{ route('national.admin.v2.ministry.delete', $m->id) }}" class="text-xs px-2 py-1 rounded border border-red-200 text-red-500 hover:bg-red-50" onclick="return confirm('Delete?')">×</a>
                        </div>
                    </div>
                    @if($m->description)<p class="text-xs text-gray-500 mt-2">{{ Str::limit($m->description, 120) }}</p>@endif
                    <form method="POST" action="{{ route('national.admin.v2.ministry.update', $m->id) }}" class="hidden mt-3 space-y-2">@csrf
                        <input type="text" name="name" value="{{ $m->name }}" class="w-full h-8 px-3 rounded-lg border border-gray-200 text-xs">
                        <input type="text" name="code" value="{{ $m->code }}" class="w-full h-8 px-3 rounded-lg border border-gray-200 text-xs">
                        <button class="h-8 px-3 rounded-lg bg-[#046bd2] text-white text-[10px] font-bold">Save</button>
                    </form>
                </div>
                @endforeach
            </div>
        </div>

        {{-- AGENCIES --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-gray-900">Agencies ({{ $stats['agencies'] }})</h2>
                <button onclick="document.getElementById('addAgencyForm').classList.toggle('hidden')" class="text-xs font-bold px-3 py-1.5 rounded-lg bg-[#046bd2] text-white">+ Add</button>
            </div>
            <form id="addAgencyForm" method="POST" action="{{ route('national.admin.v2.agency.store') }}" class="hidden space-y-2 mb-4 p-4 border border-gray-200 rounded-xl">@csrf
                <select name="ministry_id" required class="w-full h-9 px-3 rounded-lg border border-gray-200 text-sm">@foreach($ministries as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select>
                <input type="text" name="name" required placeholder="Agency name" class="w-full h-9 px-3 rounded-lg border border-gray-200 text-sm">
                <input type="text" name="code" placeholder="Code" class="w-full h-9 px-3 rounded-lg border border-gray-200 text-sm">
                <button class="h-9 px-4 rounded-lg bg-[#046bd2] text-white text-xs font-bold">Create</button>
            </form>
            <div class="space-y-2 max-h-[500px] overflow-y-auto">
                @foreach($agencies as $a)
                <div class="flex items-center justify-between border border-gray-200 rounded-xl p-3">
                    <div><span class="font-semibold text-gray-900 text-sm">{{ $a->name }}</span><div class="text-xs text-gray-400">{{ $a->ministry?->name }}</div></div>
                    <a href="{{ route('national.admin.v2.agency.delete', $a->id) }}" class="text-xs px-2 py-1 rounded border border-red-200 text-red-500 hover:bg-red-50" onclick="return confirm('Delete?')">×</a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection