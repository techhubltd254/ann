@extends('layouts.admin')
@section('title', 'Venue — ' . $venue->name)

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold">{{ $venue->name }}</h1>
            <p class="text-sm text-gray-500">#{{ $venue->id }} · {{ $venue->slug }}</p>
        </div>
        <a href="{{ route('admin.venues.index') }}" class="text-sm text-gray-600 hover:underline">← All venues</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded border border-green-300 bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded border border-red-300 bg-red-50 text-red-800 px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($venue->source_details['official_source']??null)
    <section class="border rounded-xl bg-white p-5 mb-5" data-venue-source><h2>Official venue source</h2><a href="{{ $venue->source_details['official_source']['source_url'] }}" target="_blank" rel="noopener noreferrer">KICC venue page</a> · <a href="https://kicc.co.ke/pricing-guideline/" target="_blank" rel="noopener noreferrer">Published pricing guideline</a><p>Rack rates are reference data, not a confirmed bookable quote. Verify the period, setup, capacity and current rate with KICC.</p>@foreach($venue->source_details['official_source']['source_conflicts']??[] as $conflict)<p role="alert">{{ $conflict }}</p>@endforeach</section>
    @endif
    <div class="grid md:grid-cols-2 gap-6">
        {{-- ─── COVER IMAGE ─── --}}
        <section class="border rounded-xl bg-white p-5">
            <h2 class="font-semibold text-lg mb-3">Cover image</h2>

            <div class="mb-4">
                @if($cover)
                    <img src="{{ media($cover->path) }}" alt="{{ $venue->name }} cover"
                         class="w-full h-44 object-cover rounded-lg border bg-gray-100">
                    <p class="text-xs text-gray-500 mt-2">asset #{{ $cover->id }} · {{ $cover->disk }} · {{ number_format($cover->size_bytes / 1024, 0) }} KB · {{ $cover->status }}</p>
                @elseif($venue->cover_image)
                    <img src="{{ media($venue->cover_image) }}" alt="{{ $venue->name }} cover"
                         class="w-full h-44 object-cover rounded-lg border bg-gray-100">
                    <p class="text-xs text-amber-700 mt-2">legacy value — replace it to move onto the media library</p>
                @else
                    <div class="w-full h-44 rounded-lg border border-dashed flex items-center justify-center text-sm text-gray-400">no cover image yet</div>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.venues.upload-cover', $venue->id) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="file" name="cover" accept="image/jpeg,image/png,image/webp,image/avif" required
                       class="block w-full text-sm border rounded px-3 py-2">
                <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Upload / replace cover</button>
            </form>

            <form method="POST" action="{{ route('admin.venues.delete-cover', $venue->id) }}" class="mt-3"
                  onsubmit="return confirm('Delete the cover image for {{ $venue->name }}?')">
                @csrf
                <button class="bg-red-600 text-white px-4 py-2 rounded text-sm">Delete cover</button>
            </form>
        </section>

        {{-- ─── HERO VIDEO ─── --}}
        <section class="border rounded-xl bg-white p-5">
            <h2 class="font-semibold text-lg mb-3">Hero video</h2><p data-venue-video-brief>{{ $venue->source_details['expected_video_description']??'Record the actual venue, layout and confirmed facilities. Obtain participant consent.' }}</p>

            <div class="mb-4">
                @if($video)
                    <video src="{{ media($video->path) }}" class="w-full h-44 object-cover rounded-lg border bg-black"
                           controls muted playsinline preload="metadata"></video>
                    <p class="text-xs text-gray-500 mt-2">asset #{{ $video->id }} · {{ $video->disk }} · {{ number_format($video->size_bytes / 1024 / 1024, 2) }} MB · {{ $video->status }}</p>
                @elseif($venue->hero_video_url)
                    <video src="{{ $venue->hero_video_url }}" class="w-full h-44 object-cover rounded-lg border bg-black"
                           controls muted playsinline preload="metadata"></video>
                    <p class="text-xs text-amber-700 mt-2">legacy value — replace it to move onto the media library</p>
                @else
                    <div class="w-full h-44 rounded-lg border border-dashed flex items-center justify-center text-sm text-gray-400">no hero video yet</div>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.venues.upload-video', $venue->id) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="file" name="video" accept="video/mp4,video/webm,video/quicktime" required
                       class="block w-full text-sm border rounded px-3 py-2">
                <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Upload / replace video</button>
                <p class="text-xs text-gray-500">For resumable video uploads up to 2 GiB, use the scoped uploader: choose Venue, then {{ $venue->name }}.</p><a href="/admin/uploads" class="block px-4 py-3 border rounded" data-safe-venue-upload>Open resumable admin uploader →</a>
            </form>

            <form method="POST" action="{{ route('admin.venues.delete-video', $venue->id) }}" class="mt-3"
                  onsubmit="return confirm('Delete the hero video for {{ $venue->name }}?')">
                @csrf
                <button class="bg-red-600 text-white px-4 py-2 rounded text-sm">Delete video</button>
            </form>

            {{-- direct-to-R2 for large files --}}
            <div class="mt-5 pt-4 border-t">
                <h3 class="font-medium text-sm mb-2">Large file (direct to R2, bypasses the 100 MB edge limit)</h3>
                <input type="file" id="r2file-{{ $venue->id }}" accept="video/mp4,video/webm,video/quicktime"
                       class="block w-full text-sm border rounded px-3 py-2">
                <button type="button" id="r2go-{{ $venue->id }}" class="mt-2 bg-gray-900 text-white px-4 py-2 rounded text-sm">Upload large video</button>
                <p id="r2msg-{{ $venue->id }}" class="text-xs mt-2 text-gray-500"></p>
            </div>
        </section>
    </div>

    {{-- ─── DETAILS ─── --}}
    <section class="border rounded-xl bg-white p-5 mt-6">
        <h2 class="font-semibold text-lg mb-3">Details</h2>
        <form method="POST" action="{{ route('admin.venues.update', $venue->id) }}" class="grid md:grid-cols-2 gap-4">
            @csrf
            <label class="text-sm">Name
                <input name="name" value="{{ old('name', $venue->name) }}" class="mt-1 block w-full border rounded px-3 py-2" required>
            </label>
            <label class="text-sm">Type
                <input name="venue_type" value="{{ old('venue_type', $venue->venue_type) }}" class="mt-1 block w-full border rounded px-3 py-2">
            </label>
            <label class="text-sm">City
                <input name="city" value="{{ old('city', $venue->city) }}" class="mt-1 block w-full border rounded px-3 py-2">
            </label>
            <label class="text-sm">County
                <input name="county" value="{{ old('county', $venue->county) }}" class="mt-1 block w-full border rounded px-3 py-2">
            </label>
            <label class="text-sm">Address
                <input name="address" value="{{ old('address', $venue->address) }}" class="mt-1 block w-full border rounded px-3 py-2">
            </label>
            <label class="text-sm">Capacity
                <input name="capacity" type="number" value="{{ old('capacity', $venue->capacity) }}" class="mt-1 block w-full border rounded px-3 py-2">
            </label>
            <label class="text-sm">Conference rate (KES)
                <input name="conference_rate" type="number" step="0.01" value="{{ old('conference_rate', $venue->conference_rate) }}" class="mt-1 block w-full border rounded px-3 py-2">
            </label>
            <label class="text-sm">Exhibition rate (KES)
                <input name="exhibition_rate" type="number" step="0.01" value="{{ old('exhibition_rate', $venue->exhibition_rate) }}" class="mt-1 block w-full border rounded px-3 py-2">
            </label>
            <label class="text-sm md:col-span-2">Description
                <textarea name="description" rows="4" class="mt-1 block w-full border rounded px-3 py-2">{{ old('description', $venue->description) }}</textarea>
            </label>
            <label class="text-sm md:col-span-2">Expected video — filming brief<textarea name="expected_video_description" maxlength="2000" rows="3" class="block w-full border rounded px-3 py-2">{{ old('expected_video_description',$venue->source_details['expected_video_description']??null) }}</textarea></label>
            <label class="text-sm flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $venue->is_active))> Active
            </label>
            <div class="md:col-span-2">
                <button class="bg-gray-900 text-white px-5 py-2 rounded text-sm">Save details</button>
            </div>
        </form>
    </section>
</div>

<script>
(function () {
  const venueId = {{ $venue->id }};
  const input = document.getElementById('r2file-' + venueId);
  const btn   = document.getElementById('r2go-' + venueId);
  const msg   = document.getElementById('r2msg-' + venueId);
  if (!btn) return;

  btn.addEventListener('click', async function () {
    const file = input.files && input.files[0];
    if (!file) { msg.textContent = 'Choose a file first.'; return; }
    btn.disabled = true;
    try {
      msg.textContent = 'Requesting upload URL…';
      const pre = await fetch("{{ route('admin.venues.r2-presigned') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': "{{ csrf_token() }}",
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          venue_id: venueId,
          slot: 'hero_video',
          mime: file.type || 'video/mp4',
          original_name: file.name
        })
      });
      if (!pre.ok) throw new Error('presign HTTP ' + pre.status);
      const p = await pre.json();
      const putUrl = p.url || p.upload_url || p.presigned_url;
      if (!putUrl) throw new Error('no presigned URL returned');

      msg.textContent = 'Uploading ' + (file.size / 1048576).toFixed(1) + ' MB straight to R2…';
      const put = await fetch(putUrl, { method: 'PUT', body: file, headers: { 'Content-Type': file.type || 'video/mp4' } });
      if (!put.ok) throw new Error('R2 PUT HTTP ' + put.status);

      msg.textContent = 'Confirming…';
      const conf = await fetch("{{ route('admin.venues.r2-confirm') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': "{{ csrf_token() }}",
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          venue_id: venueId,
          slot: 'hero_video',
          path: p.path,
          mime: file.type || 'video/mp4',
          size_bytes: file.size,
          original_name: file.name
        })
      });
      if (!conf.ok) throw new Error('confirm HTTP ' + conf.status);
      msg.textContent = 'Done — reloading…';
      location.reload();
    } catch (e) {
      msg.textContent = 'Upload failed: ' + e.message;
      btn.disabled = false;
    }
  });
})();
</script>
@endsection
