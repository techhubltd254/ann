@extends('layouts.app')
@section('title', 'Apply — ' . $job->title)
@section('content')
<div class="pt-20 max-w-2xl mx-auto px-5 py-10">
    <a href="{{ route('kicc.jobs') }}" class="text-gray-500 hover:text-[#0B0B0B] text-sm mb-4 inline-block">← All Jobs</a>
    <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6">
        <h1 class="text-xl font-black text-gray-900">{{ $job->title }}</h1>
        @if($job->department)<div class="text-xs text-gray-400 mt-1">{{ $job->department }}</div>@endif
        @if($job->description)<p class="text-gray-600 text-sm mt-3">{{ $job->description }}</p>@endif
    </div>
    <form method="POST" action="{{ route('kicc.jobs.apply', $job->id) }}" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-4">
        @csrf
        <div class="grid sm:grid-cols-2 gap-4"><div><label class="block text-xs font-bold text-gray-500 mb-1">Name *</label><input type="text" name="name" required class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm"></div>
        <div><label class="block text-xs font-bold text-gray-500 mb-1">Email *</label><input type="email" name="email" required class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm"></div></div>
        <div><label class="block text-xs font-bold text-gray-500 mb-1">Phone</label><input type="tel" name="phone" class="w-full h-11 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm"></div>
        <div><label class="block text-xs font-bold text-gray-500 mb-1">Cover Letter</label><textarea name="cover_letter" rows="4" class="w-full px-4 py-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-sm"></textarea></div>
        <div><label class="block text-xs font-bold text-gray-500 mb-1">CV (PDF/DOC)</label><input type="file" name="cv" accept=".pdf,.doc,.docx" class="w-full"></div>
        <button type="submit" class="w-full h-12 rounded-xl bg-[#0B0B0B] text-white font-bold hover:bg-[#0B0B0B]">Submit Application</button>
    </form>
</div>
@endsection