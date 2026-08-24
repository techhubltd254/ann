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
    </div>
</div>