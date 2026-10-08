@extends('layouts.app')

@section('title', 'Create 3D Room - KICC')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10" data-reveal>
    <a href="{{ route('room3d.index') }}" class="text-kicc-gold hover:underline text-sm mb-6 inline-block" data-magnetic>&larr; Back to Rooms</a>

    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden card-hover">
        <div class="p-6 md:p-8 border-b border-gray-200">
            <h1 class="text-2xl font-black text-gray-900" data-split>Create a 3D Room</h1>
            <p class="text-gray-900/45 mt-2 text-sm leading-relaxed">Upload photos of a room, booth, or venue from different angles. We'll build an interactive 3D experience you can explore on any phone — tilt to look around, drag to orbit.</p>
        </div>

        @if($errors->any())
        <div class="mx-6 md:mx-8 mt-6 bg-[#B3261E]/15 border border-[#B3261E]/30 text-[#B3261E] rounded-xl px-5 py-4 text-sm">
            <div class="font-bold mb-1">Please fix the following:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('room3d.store') }}" enctype="multipart/form-data"
              x-data="{ previews: [], dragging: false }"
              class="p-6 md:p-8 space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">Room Title <span class="text-kicc-gold">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       placeholder="e.g. Tsavo Hall Exhibition Booth"
                       class="w-full h-12 px-4 rounded-xl bg-[#FFFFFF] border border-gray-200 text-gray-900 placeholder:text-[#0B0B0B] outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">Description</label>
                <textarea name="description" rows="3"
                          placeholder="What is this space? What should visitors know?"
                          class="w-full px-4 py-3 rounded-xl bg-[#FFFFFF] border border-gray-200 text-gray-900 placeholder:text-[#0B0B0B] outline-none focus:ring-2 focus:ring-kicc-gold/60 transition-all">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-900 mb-2">Room Photos <span class="text-kicc-gold">*</span>
                    <span class="font-normal text-gray-400">(2-20 photos, different angles)</span>
                </label>
                <div @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                     @drop.prevent="dragging = false; $refs.photos.files = $event.dataTransfer.files; $refs.photos.dispatchEvent(new Event('change'))"
                     :class="dragging ? 'border-kicc-gold bg-kicc-gold/5' : 'border-gray-200'"
                     class="border-2 border-dashed rounded-2xl p-8 text-center transition-all cursor-pointer"
                     @click="$refs.photos.click()">
                    <input type="file" name="photos[]" multiple accept="image/*" required class="hidden" x-ref="photos"
                           @change="previews = []; Array.from($event.target.files).slice(0, 20).forEach(f => { const r = new FileReader(); r.onload = e => previews.push(e.target.result); r.readAsDataURL(f); })">
                    <div class="text-4xl mb-3"></div>
                    <div class="text-gray-900 font-semibold text-sm">Drop photos here or click to browse</div>
                    <div class="text-gray-400 text-xs mt-1">JPG, PNG or WebP &middot; Max 10MB each &middot; More angles = better 3D</div>
                </div>

                <div x-show="previews.length" class="grid grid-cols-3 sm:grid-cols-4 gap-3 mt-4">
                    <template x-for="(src, i) in previews" :key="i">
                        <div class="aspect-square rounded-lg overflow-hidden bg-[#FFFFFF] border border-gray-200">
                            <img :src="src" class="w-full h-full object-cover">
                        </div>
                    </template>
                </div>
                <div x-show="previews.length" class="text-[#0B0B0B] text-xs mt-2" x-text="previews.length + ' photo(s) ready'"></div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" data-magnetic
                        class="inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-8 text-base h-14 rounded-xl bg-kicc-gold text-[#0B0B0B] hover:bg-[#FFCD05] active:scale-[0.97] animate-pulse-glow">
                    Build 3D Room
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </button>
                <a href="{{ route('room3d.index') }}" class="text-[#0B0B0B] hover:text-gray-900 text-sm transition-colors">Cancel</a>
            </div>
        </form>
    </div>

    <div class="mt-8 grid sm:grid-cols-3 gap-4" data-reveal data-reveal-delay="150">
        @foreach([['','Phone Ready','Tilt your phone to look around — gyroscope-powered 3D.'],['','Orbit Mode','Drag to rotate, pinch to zoom on any device.'],['','VR Mode','Drop into Google Cardboard for full immersion.']] as $f)
        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
            <div class="text-2xl mb-2">{{ $f[0] }}</div>
            <div class="font-bold text-gray-900 text-sm">{{ $f[1] }}</div>
            <div class="text-[#0B0B0B] text-xs mt-1 leading-relaxed">{{ $f[2] }}</div>
        </div>
        @endforeach
    </div>
</div>
@endsection
