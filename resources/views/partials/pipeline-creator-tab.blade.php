@if($tab === 'pipeline-creator')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Pipeline Creator</h2>
            <p class="text-zinc-400 text-sm mt-1">Dynamically add new revenue pipelines — no coding required</p>
        </div>
        <span class="text-xs text-zinc-500">{{ $dynamicPipelineTotal }} dynamic pipelines</span>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="glass-card rounded-xl p-3">
            <div class="text-2xl font-bold text-white">{{ $pipelineTotal }}</div>
            <div class="text-[10px] text-zinc-400">Total Pipelines</div>
        </div>
        <div class="glass-card rounded-xl p-3 border-l-2 border-emerald-500">
            <div class="text-2xl font-bold text-emerald-400">{{ $dynamicPipelines->where('is_active', true)->count() }}</div>
            <div class="text-[10px] text-zinc-400">Active Dynamic</div>
        </div>
        <div class="glass-card rounded-xl p-3 border-l-2 border-sky-500">
            <div class="text-2xl font-bold text-sky-400">{{ $dynamicPipelines->where('sector', 'milk-dairy')->count() }}</div>
            <div class="text-[10px] text-zinc-400">Milk & Dairy</div>
        </div>
        <div class="glass-card rounded-xl p-3 border-l-2 border-amber-500">
            <div class="text-2xl font-bold text-amber-400">{{ $pipelineStatusBreakdown->where('status', 'licence_gated')->first()?->c ?? 0 }}</div>
            <div class="text-[10px] text-zinc-400">Licence-Gated</div>
        </div>
        <div class="glass-card rounded-xl p-3 border-l-2 border-rose-500">
            <div class="text-2xl font-bold text-rose-400">{{ $pipelineStatusBreakdown->where('status', 'partial')->first()?->c ?? 0 }}</div>
            <div class="text-[10px] text-zinc-400">Partial (needs build)</div>
        </div>
    </div>

    {{-- Create new pipeline form --}}
    <div class="glass-card rounded-2xl p-5">
        <h3 class="font-bold text-white text-sm mb-4">+ Create New Pipeline</h3>
        <form method="POST" action="{{ route('kicc.admin.pipeline.store') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Pipeline Code</label>
                <input name="code" required placeholder="e.g. M1" maxlength="10"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder:text-zinc-600 outline-none focus:border-rose-500/50">
            </div>
            <div>
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Pipeline Name</label>
                <input name="name" required placeholder="e.g. Milk Collection"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder:text-zinc-600 outline-none focus:border-rose-500/50">
            </div>
            <div>
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Sector</label>
                <select name="sector" required
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-rose-500/50">
                    <option value="">Select sector…</option>
                    @foreach($dynamicSectors as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                    @endforeach
                    <option value="milk-dairy">Milk & Dairy</option>
                    <option value="other">Other (new sector)</option>
                </select>
            </div>
            <div>
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Money Mechanism</label>
                <select name="mechanism"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-rose-500/50">
                    <option value="commission">Commission (%)</option>
                    <option value="flat_fee">Flat Fee</option>
                    <option value="subscription">Subscription</option>
                    <option value="listing">Listing Fee</option>
                    <option value="escrow">Escrow Hold</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Description</label>
                <input name="description" placeholder="What does this pipeline do?"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder:text-zinc-600 outline-none focus:border-rose-500/50">
            </div>
            <div>
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Fee Rate (%)</label>
                <input name="fee_rate" type="number" step="0.01" value="4" min="0" max="100"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-rose-500/50">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30 text-xs font-bold transition-all">
                    + Create Pipeline
                </button>
            </div>
        </form>
    </div>

    {{-- Dynamic pipelines table --}}
    <div class="glass-card rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-white text-sm">{{ $dynamicPipelines->count() }} Dynamic Pipelines</h3>
            <div class="flex gap-2 text-[10px]">
                <input type="text" id="pipeline-search" placeholder="Search dynamic pipelines..." 
                    class="bg-white/5 border border-white/10 rounded-lg px-3 py-1.5 text-white placeholder:text-zinc-600 outline-none w-48">
            </div>
        </div>
        <div class="overflow-x-auto" style="max-height:500px; overflow-y:auto;">
            <table class="w-full text-xs">
                <thead><tr class="text-zinc-500 border-b border-white/5">
                    <th class="text-left py-2 pr-3 font-semibold">Code</th>
                    <th class="text-left py-2 pr-3 font-semibold">Name</th>
                    <th class="text-left py-2 pr-3 font-semibold">Sector</th>
                    <th class="text-left py-2 pr-3 font-semibold">Mechanism</th>
                    <th class="text-left py-2 pr-3 font-semibold">Fee Rate</th>
                    <th class="text-left py-2 pr-3 font-semibold">Status</th>
                    <th class="text-left py-2 font-semibold">Created</th>
                </tr></thead>
                <tbody class="divide-y divide-white/5">
                @forelse($dynamicPipelines as $p)
                <tr class="hover:bg-white/5 transition">
                    <td class="py-2.5 pr-3 font-mono text-zinc-300 font-bold">{{ $p->code }}</td>
                    <td class="py-2.5 pr-3 text-white">{{ $p->name }}</td>
                    <td class="py-2.5 pr-3 text-zinc-400">{{ $p->sector }}</td>
                    <td class="py-2.5 pr-3 text-zinc-500">{{ $p->mechanism ?? 'commission' }}</td>
                    <td class="py-2.5 pr-3 text-zinc-300">{{ $p->fee_rate ?? 4 }}%</td>
                    <td class="py-2.5 pr-3">
                        @if($p->is_active)
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">ACTIVE</span>
                        @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-zinc-500/20 text-zinc-400">INACTIVE</span>
                        @endif
                    </td>
                    <td class="py-2.5 text-zinc-500">{{ $p->created_at?->format('d M') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-8 text-center text-zinc-500">No dynamic pipelines yet. Create one above.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick pipeline ideas --}}
    <div class="glass-card rounded-2xl p-5">
        <h3 class="font-bold text-white text-sm mb-4">Suggested New Pipelines</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach($suggestedPipelines as $sp)
            <div class="rounded-xl bg-white/5 border border-white/10 p-4 hover:border-rose-500/30 transition-all">
                <div class="font-bold text-white text-sm">{{ $sp['code'] }}</div>
                <div class="text-xs text-zinc-300 mt-0.5">{{ $sp['name'] }}</div>
                <div class="text-[10px] text-zinc-500 mt-1">{{ $sp['description'] }}</div>
                <div class="mt-2 flex gap-2 text-[10px]">
                    <span class="px-2 py-0.5 rounded-full bg-sky-500/10 text-sky-400">{{ $sp['sector'] }}</span>
                    <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400">{{ $sp['fee'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif