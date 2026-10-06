<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-white">Videos</h1><p class="text-zinc-500 text-sm">Upload & manage unlimited videos</p></div>
    </div>
    <div class="glass-card rounded-2xl p-5">
        <form data-r2-upload method="POST" action="{{ route('institution.admin.videos.upload', $institution->slug) }}" enctype="multipart/form-data" class="grid md:grid-cols-3 gap-3">
            @csrf
            <input name="title" required placeholder="Video title">
            <select name="entity_key" class="text-xs">
                <option value="">Attach to institution</option>
                @foreach($sectorEntities as $se)
                <option value="{{ $se->sector?->slug }}">{{ $se->name }}</option>
                @endforeach
            </select>
            <input name="video" type="file" accept="video/mp4,video/webm" required>
            <textarea name="description" rows="2" placeholder="Description" class="md:col-span-3"></textarea>
            <button class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                Upload & Sync
            </button>
        </form>
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($videos as $v)
        <div class="glass-card rounded-2xl overflow-hidden">
            <video controls preload="metadata" class="w-full aspect-video bg-black">
                <source src="{{ media($v->path ?? '') }}" type="video/mp4">
            </video>
            <div class="p-4">
                <div class="font-medium text-sm text-zinc-200 truncate">{{ $v->original_name ?? 'Untitled' }}</div>
                @if(!empty($v->metadata['description'] ?? null))
                <p class="text-[11px] text-zinc-500 mt-1">{{ $v->metadata['description'] }}</p>
                @endif
            </div>
        </div>
        @empty
        <div class="md:col-span-3 text-center py-16 text-zinc-500 text-sm">No videos uploaded yet.</div>
        @endforelse
    </div>
</div>