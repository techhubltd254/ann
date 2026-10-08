@extends('layouts.app')

@section('title', 'Upload Media — KICC Pipeline')

@section('content')
<div class="pt-20">
    <div class="max-w-3xl mx-auto px-5 py-10">
        <a href="{{ route('media.library') }}" class="inline-flex items-center gap-1.5 text-[#5A6480] hover:text-gray-900 text-sm mb-6 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Library
        </a>

        <div data-reveal>
            <h1 class="text-3xl md:text-4xl font-black text-gray-900 tracking-tight" data-split>Upload Media</h1>
            <p class="text-[#5A6480] mt-2 text-sm">Images go through the cinematic pipeline (video / 3D). Videos and models are stored as-is, ready to attach.</p>
        </div>

        {{-- Upload card — Fitts's Law: huge dropzone target --}}
        <div class="mt-8 bg-white rounded-2xl border-2 border-dashed border-gray-300 hover:border-kicc-gold/60 transition-colors p-10 text-center" id="dropzone" data-reveal="zoom">
            <div class="text-5xl mb-3"></div>
            <p class="font-black text-gray-900">Drag &amp; drop files here</p>
            <p class="text-[#5A6480] text-sm mt-1">or click to browse — max 10 files, 50MB each</p>
            <button type="button" id="pick-files" class="mt-6 inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 h-14 rounded-xl bg-[#b3261e] text-white hover:bg-[#7b1618] active:scale-[0.97]" data-magnetic>
                Choose Files
            </button>
            <div class="hidden mt-6" id="file-list"></div>

            <form method="POST" action="{{ route('media.store') }}" enctype="multipart/form-data" id="upload-form" class="hidden">
                @csrf
                <input type="file" name="files[]" multiple id="file-input" class="hidden" accept="image/*,video/*,.glb,.gltf">
                <input type="text" name="alt_text" placeholder="Default alt text (optional)" class="mt-4 w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-1 focus:ring-kicc-gold placeholder:text-gray-400">
                <button type="submit" id="submit-upload" class="mt-4 w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 h-14 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#FFCD05] active:scale-[0.97]" data-magnetic>
                    Upload {{ count(request()->old('files', [])) }} file(s)
                </button>
            </form>
        </div>

        {{-- Progress (skeleton loader while uploading — no blocking, no waiting) --}}
        <div class="hidden mt-6 bg-white rounded-2xl border border-gray-200 p-6" id="upload-progress-wrap">
            <div class="flex items-center justify-between mb-3">
                <div class="font-black text-gray-900 text-sm">Uploading…</div>
                <div class="text-xs font-bold text-kicc-gold" id="upload-progress-text">0%</div>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-kicc-gold to-[#b3261e] transition-all" id="upload-progress-bar" style="width: 0%"></div>
            </div>
            <div class="flex gap-2 mt-4" id="file-progress-list"></div>
        </div>
    </div>
</div>

<script src="{{ asset('js/pipeline-upload.js') }}"></script>
@endsection
