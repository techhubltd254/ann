<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-white">Profile</h1><p class="text-zinc-500 text-sm">Your public-facing identity</p></div>
    </div>
    <div class="grid lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 glass-card rounded-2xl p-5">
            <form method="POST" action="{{ route('institution.admin.profile', $institution->slug) }}" class="space-y-4">
                @csrf
                <div class="grid md:grid-cols-2 gap-3">
                    <input name="name" value="{{ $institution->name }}" required placeholder="Institution name">
                    <input name="type" value="{{ $institution->type }}" placeholder="Type">
                    <input name="headquarters" value="{{ $institution->headquarters }}" placeholder="Headquarters">
                    <input name="founded_year" value="{{ $institution->founded_year }}" type="number" placeholder="Founded year">
                    <input name="phone" value="{{ $institution->phone }}" placeholder="Phone">
                    <input name="email" value="{{ $institution->email }}" type="email" placeholder="Email">
                    <input name="website" value="{{ $institution->website }}" placeholder="Website">
                    <input name="location" value="{{ $institution->location }}" placeholder="Location">
                </div>
                <textarea name="description" rows="3" placeholder="Short description">{{ $institution->description }}</textarea>
                <textarea name="story" rows="8" placeholder="Full story — the complete writeup">{{ $institution->story }}</textarea>
                <button class="btn-primary">Save Profile</button>
            </form>
        </div>
        <div class="space-y-4">
            <div class="glass-card rounded-2xl p-5">
                <h3 class="text-xs font-semibold text-zinc-300 mb-4">Logo & Cover</h3>
                @if($institution->logo_url)
                <img src="{{ $institution->logo_url }}" class="w-24 h-24 rounded-xl object-cover mb-3 bg-white/5">
                @endif
                @if($institution->cover_image_url)
                <img src="{{ $institution->cover_image_url }}" class="w-full h-32 object-cover rounded-xl mb-3 bg-white/5">
                @endif
                <form method="POST" action="{{ route('institution.admin.logo', $institution->slug) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input type="file" name="logo" accept="image/*">
                    <input type="file" name="cover" accept="image/*">
                    <button class="btn-primary w-full">Upload Images</button>
                </form>
            </div>
            <div class="glass-card rounded-2xl p-5">
                <h3 class="text-xs font-semibold text-zinc-300 mb-1">Hero Video</h3>
                <p class="text-[10px] text-zinc-500 mb-3">Plays everywhere this institution appears — county page, marketplace, sector cards.</p>
                @php
                $heroAsset = \App\Models\MediaAsset::resolveSlot(\App\Models\CountyInstitution::class, $institution->id, 'hero_video');
                @endphp
                @if($heroAsset)
                <div class="aspect-video bg-black rounded-xl overflow-hidden mb-3">
                    <video autoplay muted loop playsinline preload="metadata" class="w-full h-full object-cover">
                        <source src="{{ $heroAsset->mp4Url() ?? $heroAsset->url() }}" type="video/mp4">
                    </video>
                </div>
                @else
                <div class="aspect-video bg-[#0B0D11] rounded-xl mb-3 flex items-center justify-center">
                    <svg class="w-8 h-8 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </div>
                @endif
                <form method="POST" action="{{ route('institution.admin.hero-video', $institution->slug) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <input name="video" type="file" accept="video/mp4,video/webm">
                    <button class="btn-primary w-full">Upload Hero Video</button>
                </form>
            </div>
        </div>
    </div>
</div>