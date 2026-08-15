<div class="relative bg-black rounded-2xl overflow-hidden group" x-data="{ playing: false }">
    <video 
        x-ref="video"
        src="{{ $src }}"
        poster="{{ $poster ?? '' }}"
        class="w-full h-full object-cover"
        @click="playing ? $refs.video.pause() : $refs.video.play(); playing = !playing"
        @ended="playing = false"
        loop
        muted
        playsinline
    ></video>
    <div class="absolute inset-0 flex items-center justify-center" x-show="!playing">
        <button @click="$refs.video.play(); playing = true" class="w-16 h-16 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center hover:bg-white/30 transition-all">
            <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        </button>
    </div>
    <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
        <div class="text-white text-xs font-bold">{{ $title ?? '4D View' }}</div>
        <div class="text-white/50 text-[10px]">{{ $subtitle ?? '' }}</div>
    </div>
</div>