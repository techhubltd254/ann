@if($tab === 'pipeline-settings')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Pipeline Settings</h2>
            <p class="text-zinc-400 text-sm mt-1">Edit pipeline configuration — status, earning_locked flag, regulators, economics. All changes take effect immediately.</p>
        </div>
        <div class="flex gap-2 text-xs text-zinc-500">
            <span>{{ count($pipelineSettings ?? []) }} pipelines</span>
            <span>·</span>
            <span>{{ collect($pipelineSettings ?? [])->where('earning_locked', 1)->count() }} locked</span>
            <span>·</span>
            <span>{{ collect($pipelineSettings ?? [])->where('earning_locked', 0)->count() }} earning</span>
        </div>
    </div>

    {{-- Quick stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="glass-card rounded-xl p-3">
            <div class="text-lg font-bold text-white">{{ collect($pipelineSettings ?? [])->count() }}</div>
            <div class="text-[10px] text-zinc-400">Total Registered</div>
        </div>
        <div class="glass-card rounded-xl p-3 border-l-2 border-emerald-500">
            <div class="text-lg font-bold text-emerald-400">{{ collect($pipelineSettings ?? [])->where('earning_locked', 0)->count() }}</div>
            <div class="text-[10px] text-zinc-400">Earning (unlocked)</div>
        </div>
        <div class="glass-card rounded-xl p-3 border-l-2 border-amber-500">
            <div class="text-lg font-bold text-amber-400">{{ collect($pipelineSettings ?? [])->where('earning_locked', 1)->count() }}</div>
            <div class="text-[10px] text-zinc-400">Locked (awaiting data)</div>
        </div>
        <div class="glass-card rounded-xl p-3 border-l-2 border-rose-500">
            <div class="text-lg font-bold text-rose-400">{{ collect($pipelineSettings ?? [])->where('status', 'blocked')->count() }}</div>
            <div class="text-[10px] text-zinc-400">Blocked</div>
        </div>
    </div>

    {{-- Search + filter --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <input type="text" id="pipeline-settings-search" placeholder="Search pipeline code or sector…"
            class="flex-1 bg-zinc-900/60 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-white placeholder:text-zinc-500 outline-none focus:border-kicc-gold">
        <select id="pipeline-locked-filter" class="bg-zinc-900/60 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-white outline-none">
            <option value="">All statuses</option>
            <option value="unlocked">Earning (unlocked)</option>
            <option value="locked">Locked (earning_locked)</option>
            <option value="blocked">Blocked</option>
            <option value="licence_gated">Licence-gated</option>
        </select>
    </div>

    {{-- Pipeline config table --}}
    <div class="glass-card rounded-2xl p-5">
        <div class="overflow-x-auto" style="max-height:600px; overflow-y:auto;">
            <table class="w-full text-xs" id="pipeline-settings-table">
                <thead><tr class="text-zinc-500 border-b border-white/5">
                    <th class="text-left py-2 pr-3 font-semibold">Code</th>
                    <th class="text-left py-2 pr-3 font-semibold">Sector</th>
                    <th class="text-left py-2 pr-3 font-semibold">Status</th>
                    <th class="text-left py-2 pr-3 font-semibold">Earning Locked</th>
                    <th class="text-left py-2 pr-3 font-semibold">Regulators</th>
                    <th class="text-left py-2 pr-3 font-semibold">Economics</th>
                    <th class="text-left py-2 font-semibold">Edit</th>
                </tr></thead>
                <tbody class="divide-y divide-white/5">
                @forelse($pipelineSettings ?? [] as $ps)
                <tr class="hover:bg-white/5 transition">
                    <td class="py-2.5 pr-3 font-mono font-bold text-[#FFCD05]">{{ $ps->code }}</td>
                    <td class="py-2.5 pr-3 text-zinc-400">{{ $ps->sector ?? '—' }}</td>
                    <td class="py-2.5 pr-3">
                        @php $sc = $ps->status ?? 'partial'; @endphp
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full
                            {{ $sc === 'built' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                            {{ $sc === 'partial' ? 'bg-sky-500/20 text-sky-400' : '' }}
                            {{ $sc === 'licence_gated' ? 'bg-amber-500/20 text-amber-400' : '' }}
                            {{ $sc === 'blocked' ? 'bg-rose-500/20 text-rose-400' : '' }}">
                            {{ strtoupper($sc) }}
                        </span>
                    </td>
                    <td class="py-2.5 pr-3">
                        @if($ps->earning_locked)
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400">LOCKED</span>
                        @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">EARNING</span>
                        @endif
                    </td>
                    <td class="py-2.5 pr-3 text-zinc-400 text-[10px]">
                        @php
                            $regs = json_decode($ps->regulators ?? '[]', true);
                            echo $regs ? implode(', ', array_map('ucfirst', $regs)) : '—';
                        @endphp
                    </td>
                    <td class="py-2.5 pr-3 text-zinc-400 text-[10px]">
                        @php
                            $eco = json_decode($ps->economics ?? '{}', true);
                            echo $eco['take_rate'] ?? ($eco['model'] ?? '—');
                        @endphp
                    </td>
                    <td class="py-2.5">
                        <button onclick="toggleEditForm('{{ $ps->code }}')"
                            class="text-[10px] font-bold px-2 py-1 rounded-lg bg-white/10 text-zinc-300 hover:bg-white/20">Edit</button>
                    </td>
                </tr>
                <tr id="edit-form-{{ $ps->code }}" style="display:none;">
                    <td colspan="7" class="py-3 px-4 bg-white/5">
                        <form method="POST" action="{{ route('kicc.admin.pipeline.config', $ps->code) }}" class="grid grid-cols-2 md:grid-cols-5 gap-3">
                            @csrf
                            <div>
                                <label class="text-[8px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Status</label>
                                <select name="status" class="w-full bg-zinc-900 border border-zinc-700 rounded px-2 py-1 text-xs text-white">
                                    <option value="built" {{ $ps->status === 'built' ? 'selected' : '' }}>Built</option>
                                    <option value="partial" {{ $ps->status === 'partial' ? 'selected' : '' }}>Partial</option>
                                    <option value="licence_gated" {{ $ps->status === 'licence_gated' ? 'selected' : '' }}>Licence-gated</option>
                                    <option value="blocked" {{ $ps->status === 'blocked' ? 'selected' : '' }}>Blocked</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[8px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Earning Locked</label>
                                <select name="earning_locked" class="w-full bg-zinc-900 border border-zinc-700 rounded px-2 py-1 text-xs text-white">
                                    <option value="0" {{ !$ps->earning_locked ? 'selected' : '' }}>Unlocked (earning)</option>
                                    <option value="1" {{ $ps->earning_locked ? 'selected' : '' }}>Locked</option>
                                </select>
                            </div>
                            <div class="md:col-span-3">
                                <label class="text-[8px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Regulators (JSON array)</label>
                                <input name="regulators" value="{{ $ps->regulators ?? '[]' }}"
                                    class="w-full bg-zinc-900 border border-zinc-700 rounded px-2 py-1 text-xs text-white font-mono">
                            </div>
                            <div class="md:col-span-3">
                                <label class="text-[8px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Economics (JSON)</label>
                                <input name="economics" value="{{ $ps->economics ?? '{}' }}"
                                    class="w-full bg-zinc-900 border border-zinc-700 rounded px-2 py-1 text-xs text-white font-mono">
                            </div>
                            <div class="md:col-span-2 flex items-end">
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30 text-[10px] font-bold">Save</button>
                                <button type="button" onclick="toggleEditForm('{{ $ps->code }}')" class="ml-2 px-3 py-1.5 rounded-lg bg-white/10 text-zinc-400 hover:bg-white/20 text-[10px]">Cancel</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-8 text-center text-zinc-500">No pipelines found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleEditForm(code) {
    var el = document.getElementById('edit-form-' + code);
    if (el) el.style.display = el.style.display === 'none' ? '' : 'none';
}
</script>
@endif