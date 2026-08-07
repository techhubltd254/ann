@extends('layouts.app')

@section('title', $asset->original_name . ' — Media Pipeline')

@section('content')
<div class="pt-20">
    <div class="max-w-7xl mx-auto px-5 py-8">
        <a href="{{ route('media.library') }}" class="inline-flex items-center gap-1.5 text-[#5A6480] hover:text-gray-900 text-sm mb-6 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Library
        </a>

        <div class="grid lg:grid-cols-5 gap-8">
            {{-- LEFT: Preview — progressive blur glassmorphism over the media --}}
            <div class="lg:col-span-3 space-y-6">
                <div data-reveal>
                    <h1 class="text-2xl md:text-3xl font-black text-gray-900 tracking-tight" data-split>{{ $asset->original_name }}</h1>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest border bg-sky-50 text-[#5A6480] border-gray-200">{{ $asset->kind }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest border bg-emerald-50 text-emerald-600 border-emerald-200">{{ $asset->status }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest border bg-gray-50 text-[#5A6480] border-gray-200">{{ round($asset->size_bytes / 1024) }} KB</span>
                        @if($asset->width && $asset->height)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest border bg-gray-50 text-[#5A6480] border-gray-200">{{ $asset->width }}×{{ $asset->height }}</span>
                        @endif
                    </div>
                </div>

                {{-- Preview with glass frame (bottom-half progressive blur) --}}
                <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-[#07090F] aspect-video" data-reveal="zoom">
                    @if($asset->kind === 'video')
                    <video src="{{ $asset->url() }}" controls class="w-full h-full object-cover"></video>
                    @elseif($asset->kind === 'model')
                    <div class="w-full h-full flex items-center justify-center text-white/40 text-4xl">🧊 {{ pathinfo($asset->original_name, PATHINFO_EXTENSION) }}</div>
                    @else
                    <img src="{{ $asset->url() }}" alt="{{ $asset->alt_text }}" class="w-full h-full object-cover">
                    @endif

                    {{-- Progressive blur frame — mask bottom 40%, blur 0→90 --}}
                    <div class="absolute bottom-0 inset-x-0 px-5 pt-10 pb-4 glass-frame">
                        <div class="text-white text-xs font-bold uppercase tracking-widest mb-1">Original asset</div>
                        <div class="text-white/80 text-sm truncate">{{ $asset->path }}</div>
                    </div>
                </div>

                {{-- Derivatives — chunked (Miller's Law) --}}
                @if($asset->derivatives->count() > 0)
                <div class="bg-white rounded-2xl border border-gray-200 p-5" data-reveal>
                    <h3 class="font-black text-gray-900 text-sm mb-4 uppercase tracking-wider">Generated Derivatives</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($asset->derivatives as $d)
                        <a href="{{ media($d->path) }}" target="_blank" class="group bg-[#F9FAFB] border border-gray-200 rounded-xl px-3 py-2.5 hover:border-kicc-gold/50 transition-all">
                            <div class="text-[10px] font-bold uppercase tracking-widest text-kicc-gold">{{ $d->kind }}</div>
                            <div class="text-[10px] text-gray-400 truncate mt-0.5">{{ $d->variant ?? pathinfo($d->path, PATHINFO_EXTENSION) }} · {{ round($d->size_bytes / 1024) }} KB</div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Job history --}}
                @if($asset->pipelineJobs->count() > 0)
                <div class="bg-white rounded-2xl border border-gray-200 p-5" data-reveal>
                    <h3 class="font-black text-gray-900 text-sm mb-4 uppercase tracking-wider">Pipeline History</h3>
                    <div class="space-y-3">
                        @foreach($asset->pipelineJobs as $job)
                        <div class="flex items-center justify-between p-3 bg-[#F9FAFB] rounded-xl border border-gray-100" x-data="{ poll: null }" x-init="
                            if ('{{ $job->status }}' === 'running' || '{{ $job->status }}' === 'queued') {
                                poll = setInterval(async () => {
                                    const r = await fetch('{{ route('media.job.status', $job) }}');
                                    const d = await r.json();
                                    $el.querySelector('[data-job-progress]').style.width = d.progress + '%';
                                    $el.querySelector('[data-job-status]').textContent = d.status;
                                    $el.querySelector('[data-job-stage]').textContent = d.stage ?? '';
                                    if (d.status === 'completed' || d.status === 'failed') { clearInterval(poll); window.location.reload(); }
                                }, 3000);
                            }
                        ">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black text-gray-900">{{ $job->engine }}</span>
                                    <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full {{ $job->status === 'completed' ? 'bg-emerald-50 text-emerald-600' : ($job->status === 'failed' ? 'bg-red-50 text-[#901C1E]' : 'bg-kicc-gold/15 text-kicc-gold') }}">{{ $job->status }}</span>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-1">{{ $job->pipeline }} · {{ $job->created_at->diffForHumans() }}</div>
                                <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-kicc-gold to-[#901C1E] transition-all" data-job-progress style="width: {{ $job->progress }}%"></div>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-1" data-job-stage>{{ $job->stage ?? '' }}</div>
                                @if($job->error)
                                <div class="mt-2 text-[11px] text-[#901C1E] bg-red-50 border border-red-100 rounded-lg px-3 py-2">{{ $job->error }}</div>
                                @endif
                            </div>
                            <div class="flex flex-col items-end gap-1 shrink-0 ml-3">
                                <span class="text-xs font-black text-kicc-gold" data-job-progress-label>{{ $job->progress }}%</span>
                                @if(in_array($job->status, ['queued', 'running']))
                                <form method="POST" action="{{ route('media.job.cancel', $job) }}">
                                    @csrf
                                    <button type="submit" class="text-[10px] font-bold text-[#5A6480] hover:text-[#901C1E] transition-colors">Cancel</button>
                                </form>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- RIGHT: Pipeline wizard — progressive disclosure (Hick's Law) --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Step indicator — multi-step form guideline 5 --}}
                <div class="bg-white rounded-2xl border border-gray-200 p-5" x-data="pipelineWizard()" data-reveal>
                    <div class="flex items-center gap-2 mb-6">
                        @php $steps = ['Pipeline', 'Engine', 'Options']; @endphp
                        @foreach($steps as $i => $s)
                        <template x-if="true">
                            <div class="flex items-center gap-2 flex-1">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-black transition-all"
                                     :class="step >= {{ $i }} ? 'bg-[#901C1E] text-white' : 'bg-gray-100 text-gray-400'">
                                    <span x-show="step > {{ $i }}">✓</span>
                                    <span x-show="step <= {{ $i }}">{{ $i + 1 }}</span>
                                </div>
                                <div class="text-[10px] font-bold uppercase tracking-widest" :class="step >= {{ $i }} ? 'text-gray-900' : 'text-gray-400'">{{ $s }}</div>
                                <div class="h-px flex-1 bg-gray-100" x-show="{{ $i }} < 2"></div>
                            </div>
                        </template>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('media.pipeline.dispatch', $asset) }}" id="pipeline-form">
                        @csrf
                        <input type="hidden" name="pipeline" :value="pipeline">
                        <input type="hidden" name="engine" :value="engine">

                        {{-- Step 1: Choose pipeline (2 options, radio — guideline 2) --}}
                        <div x-show="step === 1" x-transition.opacity>
                            <h4 class="font-black text-gray-900 text-sm mb-3">What should we generate?</h4>
                            <div class="space-y-3">
                                <label class="block">
                                    <input type="radio" value="cinematic_video" x-model="pipeline" class="sr-only">
                                    <div class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                         :class="pipeline === 'cinematic_video' ? 'border-[#901C1E] bg-[#901C1E]/5' : 'border-gray-200 hover:border-gray-300'">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg bg-kicc-gold/15 shrink-0">🎬</div>
                                        <div>
                                            <div class="font-bold text-gray-900 text-sm">Cinematic Video</div>
                                            <div class="text-[11px] text-[#5A6480] mt-0.5">Camera motion, lighting & depth from your image</div>
                                        </div>
                                    </div>
                                </label>
                                <label class="block">
                                    <input type="radio" value="image_to_3d" x-model="pipeline" class="sr-only">
                                    <div class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                                         :class="pipeline === 'image_to_3d' ? 'border-[#901C1E] bg-[#901C1E]/5' : 'border-gray-200 hover:border-gray-300'">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg bg-emerald-500/15 shrink-0">🧊</div>
                                        <div>
                                            <div class="font-bold text-gray-900 text-sm">3D Model (.glb)</div>
                                            <div class="text-[11px] text-[#5A6480] mt-0.5">Rotatable web-ready mesh, compressed & LOD-ready</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <button type="button" @click="next()" class="mt-5 w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 h-12 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618]" data-magnetic>Continue</button>
                        </div>

                        {{-- Step 2: Choose engine (recommended first — Hick's Law) --}}
                        <div x-show="step === 2" x-transition.opacity>
                            <h4 class="font-black text-gray-900 text-sm mb-3">Choose engine</h4>
                            <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                                @foreach($engines as $group => $groupEngines)
                                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mt-3 {{ $loop->first ? '' : 'pt-3 border-t border-gray-100' }}">{{ $group }}</div>
                                @foreach($groupEngines as $e)
                                <label class="block" x-show="pipeline === '{{ $e['pipeline'][0] ?? '' }}'">
                                    <input type="radio" :value="'{{ $e['key'] }}'" x-model="engine" value="{{ $e['key'] }}" class="sr-only">
                                    <div class="p-4 rounded-xl border-2 cursor-pointer transition-all relative"
                                         :class="engine === '{{ $e['key'] }}' ? 'border-[#901C1E] bg-[#901C1E]/5' : 'border-gray-200 hover:border-gray-300'">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="font-bold text-gray-900 text-sm">{{ $e['label'] }}</div>
                                            <span class="text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-full {{ $e['cost'] === 'free' ? 'bg-emerald-50 text-emerald-600' : ($e['cost'] === 'local' ? 'bg-sky-50 text-sky-600' : 'bg-kicc-gold/15 text-kicc-gold') }}">{{ $e['cost'] }}</span>
                                        </div>
                                        <p class="text-[11px] text-[#5A6480] mt-1 leading-relaxed">{{ $e['description'] }}</p>
                                        @if(!$e['available'])
                                        <p class="text-[10px] mt-1.5 text-[#901C1E]">⚠ {{ $e['note'] }}</p>
                                        @endif
                                    </div>
                                </label>
                                @endforeach
                                @endforeach
                            </div>
                            <div class="flex gap-2 mt-5">
                                <button type="button" @click="step = 1" class="h-12 px-4 rounded-xl border border-gray-200 text-sm font-bold text-[#5A6480] hover:text-gray-900 transition-all">Back</button>
                                <button type="button" @click="next()" class="flex-1 inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 h-12 rounded-xl bg-[#901C1E] text-white hover:bg-[#7b1618]" data-magnetic>Continue</button>
                            </div>
                        </div>

                        {{-- Step 3: Options + dispatch (recommended defaults pre-filled) --}}
                        <div x-show="step === 3" x-transition.opacity>
                            <h4 class="font-black text-gray-900 text-sm mb-3">Options</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-[11px] font-bold text-gray-500 uppercase tracking-widest block mb-1.5">Prompt (what should the AI do?)</label>
                                    <textarea name="prompt" rows="3" class="w-full px-4 py-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-1 focus:ring-kicc-gold placeholder:text-gray-400" placeholder="e.g. Cinematic drone fly-over, golden hour, coastal cliffs, smooth pan...">Cinematic aerial shot, smooth camera motion, golden hour light, ultra realistic</textarea>
                                </div>
                                <div>
                                    <label class="text-[11px] font-bold text-gray-500 uppercase tracking-widest block mb-1.5">Camera path (ffmpeg engine)</label>
                                    <select name="camera_path" class="w-full h-11 px-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-1 focus:ring-kicc-gold">
                                        <option value="zoom_in">Zoom In</option>
                                        <option value="zoom_out">Zoom Out</option>
                                        <option value="pan_lr">Pan Left → Right</option>
                                        <option value="pan_rl">Pan Right → Left</option>
                                        <option value="tilt_up">Tilt Up</option>
                                        <option value="tilt_down">Tilt Down</option>
                                        <option value="orbit">Orbit</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-widest block mb-1.5">Duration (sec)</label>
                                        <select name="duration" class="w-full h-11 px-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-1 focus:ring-kicc-gold">
                                            @foreach([6, 8, 10, 15, 20] as $d)
                                            <option value="{{ $d }}" @selected($d === 8)>{{ $d }}s</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[11px] font-bold text-gray-500 uppercase tracking-widest block mb-1.5">Aspect ratio</label>
                                        <select name="aspect_ratio" class="w-full h-11 px-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-1 focus:ring-kicc-gold">
                                            <option value="16:9">16:9 — Landscape</option>
                                            <option value="9:16">9:16 — Story</option>
                                            <option value="1:1">1:1 — Square</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2 mt-5">
                                <button type="button" @click="step = 2" class="h-12 px-4 rounded-xl border border-gray-200 text-sm font-bold text-[#5A6480] hover:text-gray-900 transition-all">Back</button>
                                <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 h-12 rounded-xl bg-kicc-gold text-[#07090F] hover:bg-[#FFCD05]" data-magnetic>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    Run Pipeline
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Attach to a page — nothing hardcoded, everything admin-controlled --}}
                <div class="bg-white rounded-2xl border border-gray-200 p-5" x-data="attachPanel()" data-reveal>
                    <h3 class="font-black text-gray-900 text-sm mb-1 uppercase tracking-wider">Where should this appear?</h3>
                    <p class="text-[11px] text-[#5A6480] mb-4">Attach this asset to a page slot — the hero video plays automatically with no code changes.</p>

                    @if($asset->owner_type && $asset->owner_id && $asset->slot)
                    <div class="flex items-center justify-between gap-3 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-9 h-9 rounded-xl bg-emerald-500/15 flex items-center justify-center text-sm shrink-0">🔗</span>
                            <div class="min-w-0">
                                <div class="text-xs font-black text-emerald-700 truncate">{{ $asset->owner->name ?? '—' }}</div>
                                <div class="text-[10px] text-emerald-600/80">{{ config('pipeline.attachments.' . $asset->owner_type . '.' . $asset->slot, $asset->slot) }}</div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('media.detach', $asset) }}">
                            @csrf
                            <button type="submit" class="text-[10px] font-bold text-emerald-700/70 hover:text-[#901C1E] transition-colors shrink-0">Detach</button>
                        </form>
                    </div>
                    @else
                    <form method="POST" action="{{ route('media.attach', $asset) }}">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1.5">Page type</label>
                                <select name="entity_type" x-model="entityType" class="w-full h-11 px-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-1 focus:ring-kicc-gold">
                                    @foreach($attachmentOptions as $class => $def)
                                    <option value="{{ $class }}">{{ $def['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1.5">Placement slot</label>
                                <select name="slot" x-model="slot" class="w-full h-11 px-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-1 focus:ring-kicc-gold">
                                    <template x-for="(label, key) in slotOptions()" :key="key">
                                        <option :value="key" x-text="label"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1.5">Which one?</label>
                            <select name="entity_id" class="w-full h-11 px-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-1 focus:ring-kicc-gold">
                                <template x-for="(name, id) in entityOptions()" :key="id">
                                    <option :value="id" x-text="name"></option>
                                </template>
                            </select>
                        </div>
                        <button type="submit" class="mt-4 w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 px-6 h-11 rounded-xl bg-[#07090F] text-white hover:bg-gray-900" data-magnetic>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Attach to page
                        </button>
                    </form>
                    @endif
                </div>

                {{-- Note: async processing — Fitts's Law sized, friendly microcopy --}}
                <div class="bg-[#0D1220] rounded-2xl p-5 border border-gray-200/10" data-reveal>
                    <div class="flex gap-3">
                        <div class="text-xl shrink-0">⚡</div>
                        <div>
                            <div class="font-bold text-white text-sm">Processing runs in the background</div>
                            <p class="text-white/60 text-xs mt-1 leading-relaxed">You can keep working — this page auto-refreshes progress. Results are compressed to modern web formats (WebM + MP4) and delivered poster-first so pages never wait on video.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function pipelineWizard() {
        return {
            step: 1,
            pipeline: 'cinematic_video',
            engine: '',
            next() {
                if (this.step === 2 && !this.engine) return;
                this.step++;
            }
        };
    }

    function attachPanel() {
        const registry = @json($slots);
        const entities = @json($entities);
        return {
            entityType: Object.keys(registry)[0],
            slot: '',
            slotOptions() {
                return registry[this.entityType] ?? {};
            },
            entityOptions() {
                return entities[this.entityType] ?? {};
            }
        };
    }
</script>
@endsection
