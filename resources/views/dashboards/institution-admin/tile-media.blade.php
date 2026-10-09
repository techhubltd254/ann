@php
    /**
     * Universal tile media console — one row per tile slot.
     * Every slot below is a media_assets row: upload, replace or delete it here
     * and the public site serves the change on the next request.
     */
    $resolver = app(\App\Services\TileMediaResolver::class);
    $tileSlots = $resolver->slotsFor(\App\Models\CountyInstitution::class, (int) $institution->id);
    $products   = \App\Models\Marketplace\Product::where('institution_id', $institution->id)->orderBy('name')->get(['id', 'name']);
@endphp

<div class="space-y-6" x-data="tileMediaConsole()">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white">Tile Media</h1>
            <p class="text-zinc-400 text-sm">
                Every tile on the public site — hero, product, venue, brand mark, editorial plates.
                Upload, replace or delete here; the site serves it immediately.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-[10px] uppercase tracking-widest text-zinc-500" x-text="status"></span>
            <button type="button" class="btn-ghost text-xs" @click="reload()">Refresh</button>
        </div>
    </div>

    {{-- Upload --}}
    <div class="glass-card rounded-2xl p-5">
        <form method="POST" enctype="multipart/form-data"
              :action="uploadAction" x-ref="uploadForm" @submit.prevent="submitUpload($event)">
            @csrf
            <input type="hidden" name="tile_type" :value="target.type">
            <input type="hidden" name="tile_id" :value="target.id">

            <div class="grid md:grid-cols-4 gap-3">
                <label class="block">
                    <span class="text-[10px] uppercase tracking-widest text-zinc-500">Tile</span>
                    <select name="slot" x-model="slot" class="w-full text-xs mt-1">
                        @foreach($resolver->labels() as $key => $meta)
                        <option value="{{ $key }}">{{ $meta[0] }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-[10px] uppercase tracking-widest text-zinc-500">Applies to</span>
                    <select x-model="targetKey" class="w-full text-xs mt-1" @change="onTargetChange()">
                        <option value="institution">This institution</option>
                        <option value="county">This county ({{ $institution->county?->name ?? 'county' }})</option>
                        <optgroup label="Products of this institution">
                            @foreach($products as $p)
                            <option value="product:{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </label>

                <label class="block md:col-span-2">
                    <span class="text-[10px] uppercase tracking-widest text-zinc-500">File — image or video</span>
                    <input type="file" name="file" required
                           accept="image/*,video/mp4,video/webm,video/quicktime"
                           class="w-full text-xs mt-1" x-ref="fileInput">
                </label>

                <label class="block md:col-span-3">
                    <span class="text-[10px] uppercase tracking-widest text-zinc-500">Alt text (accessibility)</span>
                    <input name="alt_text" class="w-full text-xs mt-1" placeholder="Describe the media">
                </label>

                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full" :disabled="busy">
                        <span x-text="busy ? 'Uploading…' : 'Upload & publish'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Result banner --}}
    <template x-if="message">
        <div class="px-5 py-3 rounded-xl text-sm"
             :class="error ? 'bg-red-500/10 border border-red-500/20 text-red-300' : 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-300'"
             x-text="message"></div>
    </template>

    {{-- Slot grid: current winner + source --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="s in slots" :key="s.slot">
            <div class="glass-card rounded-2xl overflow-hidden flex flex-col">
                <div class="relative aspect-video bg-black/60">
                    <template x-if="s.url && s.kind === 'video'">
                        <video class="absolute inset-0 w-full h-full object-cover"
                               :src="s.url" autoplay muted loop playsinline preload="metadata"></video>
                    </template>
                    <template x-if="s.url && s.kind !== 'video'">
                        <img class="absolute inset-0 w-full h-full object-cover" :src="s.url" :alt="s.alt || s.label" loading="lazy">
                    </template>
                    <template x-if="!s.url">
                        <div class="absolute inset-0 flex items-center justify-center text-zinc-500 text-[11px]">No media published</div>
                    </template>
                    <span class="absolute top-2 left-2 text-[9px] font-bold px-2 py-0.5 rounded-full"
                          :class="s.source === 'admin-upload' ? 'bg-emerald-500/80 text-black' : (s.source === 'ai-default' ? 'bg-[#FFCD05] text-black' : 'bg-black/60 text-white/70')"
                          x-text="s.source"></span>
                    <span class="absolute top-2 right-2 text-[9px] font-bold px-2 py-0.5 rounded-full bg-black/60 text-white/70"
                          x-text="s.kind"></span>
                </div>

                <div class="p-3 flex-1 flex flex-col gap-2">
                    <div class="text-[11px] font-semibold text-zinc-200" x-text="s.label"></div>
                    <div class="text-[10px] font-mono text-zinc-500 truncate" x-text="s.slot"></div>

                    <div class="mt-auto flex items-center gap-2 pt-1">
                        <template x-if="s.asset_id">
                            <div class="flex items-center gap-1.5 w-full">
                                <label class="flex-1">
                                    <span class="sr-only">Replace</span>
                                    <input type="file" class="hidden" :accept="'image/*,video/*'"
                                           @change="replace(s, $event.target.files[0])">
                                    <span class="btn-ghost text-[10px] cursor-pointer inline-flex w-full justify-center"
                                          @click="$event.currentTarget.parentElement.querySelector('input').click()">Replace</span>
                                </label>
                                <button type="button" class="btn-ghost text-[10px] text-red-300"
                                        @click="remove(s)">Delete</button>
                            </div>
                        </template>
                        <template x-if="!s.asset_id && s.default_asset_id">
                            <div class="flex items-center gap-1.5 w-full">
                                <button type="button" class="btn-ghost text-[10px] w-full"
                                        @click="pickFor(s)">Upload for this tile</button>
                            </div>
                        </template>
                        <template x-if="!s.asset_id && !s.default_asset_id">
                            <button type="button" class="btn-ghost text-[10px] w-full" @click="pickFor(s)">Upload</button>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function tileMediaConsole() {
    return {
        busy: false,
        message: '',
        error: false,
        status: '',
        slot: '{{ array_key_first($resolver->labels()) }}',
        targetKey: 'institution',
        target: { type: 'institution', id: '{{ $institution->id }}' },
        slots: @js($tileSlots),
        base: '{{ url('/institution-admin/' . $institution->slug . '/tile-media') }}',
        csrf: document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',

        get uploadAction() { return this.base + '/upload'; },

        onTargetChange() {
            const [t, id] = this.targetKey.split(':');
            this.target = { type: t, id: id || '' };
        },

        pickFor(s) {
            this.slot = s.slot;
            this.$nextTick(() => this.$refs.fileInput?.focus());
            this.flash('Tile "' + s.label + '" selected — choose a file and press Upload.', false);
        },

        async reload() {
            try {
                const r = await fetch(this.base, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                const j = await r.json();
                this.slots = j.slots || this.slots;
                this.status = 'live';
            } catch (e) { this.status = 'offline'; }
        },

        async submitUpload(e) {
            const fd = new FormData(e.target);
            await this.send(fd, 'Uploaded and live.');
        },

        async replace(s, file) {
            if (!file) return;
            const fd = new FormData();
            fd.append('file', file);
            fd.append('_token', this.csrf);
            await this.send(fd, 'Replaced.', this.base + '/' + s.asset_id + '/replace');
        },

        async remove(s) {
            if (!confirm('Delete this tile media? The default or placeholder shows again.')) return;
            const fd = new FormData();
            fd.append('_token', this.csrf);
            await this.send(fd, 'Deleted.', this.base + '/' + s.asset_id + '/delete');
        },

        async send(fd, okMsg, url) {
            this.busy = true; this.message = '';
            try {
                const r = await fetch(url || this.uploadAction, {
                    method: 'POST', body: fd, credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const j = await r.json().catch(() => ({}));
                if (!r.ok) throw new Error(j.message || ('HTTP ' + r.status));
                if (j.slots) this.slots = j.slots;
                this.flash(okMsg || j.message || 'Done.', false);
                this.status = 'live';
            } catch (err) {
                this.flash(err.message || 'Failed', true);
            } finally {
                this.busy = false;
            }
        },

        flash(msg, isErr) { this.message = msg; this.error = !!isErr; setTimeout(() => this.message = '', 6000); }
    };
}
</script>
