@extends('layouts.app')
@section('title', 'Report Incident — KICC Safety')
@section('content')
<div class="pt-24 max-w-2xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-2">Report an Incident</h1>
    <p class="text-gray-500 text-sm mb-6">Submit a safety or security report. Your report is sent to the relevant authorities.</p>
    <form method="POST" action="{{ route('safety.report.submit') }}" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-4">
        @csrf
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="block text-xs font-bold text-gray-500 mb-1">Type *</label>
                <select name="type" required class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
                    <option value="">Select type</option><option value="theft">Theft</option><option value="accident">Accident</option><option value="harassment">Harassment</option><option value="medical">Medical Emergency</option><option value="lost">Lost / Missing</option><option value="other">Other</option>
                </select>
            </div>
            <div><label class="block text-xs font-bold text-gray-500 mb-1">County</label>
                <select name="county_id" class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
                    <option value="">— Select —</option>@foreach($counties as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div><label class="block text-xs font-bold text-gray-500 mb-1">Location</label>
            <input type="text" name="location" class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm">
        </div>
        <div><label class="block text-xs font-bold text-gray-500 mb-1">Description *</label>
            <textarea name="description" required rows="5" class="w-full px-4 py-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm" placeholder="Describe what happened, when, and any relevant details."></textarea>
        </div>
        <button type="submit" class="w-full h-12 rounded-xl bg-[#0B0B0B] text-white font-bold hover:bg-[#0B0B0B] transition-all">Submit Report</button>
    </form>
</div>
@endsection