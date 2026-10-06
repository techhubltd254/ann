<div class="bg-white rounded-2xl border border-gray-200 overflow-hidden mb-4 hover:border-[#FFCD05]/30 transition-all group card-hover">
    <div class="flex flex-col md:flex-row">
        <div class="md:w-64 h-44 bg-[#F9FAFB] flex items-center justify-center relative overflow-hidden shrink-0">
            @php $videoPath = $screen->immersive_url; @endphp
            @if($videoPath)<video src="{{ $videoPath }}" class="w-full h-full object-cover opacity-70 group-hover:opacity-100 transition-opacity" autoplay muted loop playsinline
                   onerror="this.outerHTML='<div class=\'flex items-center justify-center w-full h-full\'><svg class=\'w-10 h-10 text-gray-900/20\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z\'/><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M21 12a9 9 0 11-18 0 9 9 0 0118 0z\'/></svg></div>'"></video>@endif
            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <div class="w-12 h-12 bg-[#901C1E]/80 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-900" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                </div>
            </div>
        </div>
        <div class="p-5 flex-1 flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-bold text-kicc-gold uppercase tracking-widest">{{ $screen->location ?? 'KICC' }}</span>
                    <span class="text-xs text-[#5A6480]">{{ $screen->id }}</span>
                </div>
                <h3 class="font-black text-gray-900 text-lg">{{ $screen->label }}</h3>
                @if($screen->description)
                <p class="text-[#5A6480] text-sm mt-1">{{ $screen->description }}</p>
                @endif
                <div class="flex items-center gap-4 mt-2 text-xs text-[#5A6480]">
                    @if($screen->video_exists)
                    <span class="text-emerald-400"> Video ready ({{ $screen->video_size_mb }} MB)</span>
                    @else
                    <span class="text-[#5A6480]"> Video pending</span>
                    @endif
                    @if(isset($screen->image_count))
                    <span>{{ $screen->image_count }} images</span>
                    @endif
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <a href="{{ route('screens.show', $screen->id) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-kicc-gold text-[#07090F] text-xs font-bold hover:bg-[#FFCD05] transition-colors">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                    Play Showcase
                </a>
            </div>
        </div>
    </div>
</div>