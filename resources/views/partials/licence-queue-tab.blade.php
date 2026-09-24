@if($tab === 'licence-queue')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Licence Queue</h2>
            <p class="text-zinc-400 text-sm mt-1">Upload and approve regulatory licences — approved licences auto-unlock pipeline earning</p>
        </div>
        <span class="text-xs text-zinc-500">{{ count($licences ?? []) }} licences · {{ collect($licences ?? [])->where('status','pending')->count() }} pending</span>
    </div>

    {{-- Upload form --}}
    <div class="glass-card rounded-2xl p-5">
        <h3 class="font-bold text-white text-sm mb-4">Upload New Licence</h3>
        <form method="POST" action="{{ route('kicc.admin.licence.upload') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Pipeline Code</label>
                <select name="pipeline_code" required
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-rose-500/50">
                    <option value="">Select pipeline…</option>
                    @foreach($gatedPipelineCodes ?? [] as $gc)
                    <option value="{{ $gc }}">{{ $gc }} {{ \Illuminate\Support\Str::title(str_replace('-', ' ', \Illuminate\Support\Str::slug($gc))) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Licence Type</label>
                <select name="licence_type" required
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-rose-500/50">
                    <option value="cbk">CBK (Central Bank)</option>
                    <option value="ifmis">IFMIS (Treasury)</option>
                    <option value="ardhisasa">Ardhisasa (Lands)</option>
                    <option value="regulatory">Other Regulatory</option>
                    <option value="legal">Legislative</option>
                </select>
            </div>
            <div>
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Reference Number</label>
                <input name="reference_number" placeholder="e.g. CBK/LIC/2026/001"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder:text-zinc-600 outline-none focus:border-rose-500/50">
            </div>
            <div>
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Issuing Authority</label>
                <input name="issuing_authority" placeholder="e.g. Central Bank of Kenya"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder:text-zinc-600 outline-none focus:border-rose-500/50">
            </div>
            <div>
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Document (PDF/Image)</label>
                <input type="file" name="document" accept=".pdf,.png,.jpg,.jpeg"
                    class="w-full text-[10px] text-zinc-400 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-rose-500/20 file:text-rose-400 file:text-[10px] file:font-bold">
            </div>
            <div>
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Expires</label>
                <input type="date" name="expires_at"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white outline-none focus:border-rose-500/50">
            </div>
            <div class="md:col-span-2">
                <label class="text-[9px] font-semibold text-zinc-500 uppercase tracking-widest block mb-1">Notes</label>
                <input name="notes" placeholder="Any additional information about this licence…"
                    class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white placeholder:text-zinc-600 outline-none focus:border-rose-500/50">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30 text-xs font-bold transition-all">
                    + Upload Licence
                </button>
            </div>
        </form>
    </div>

    {{-- Licence table --}}
    <div class="glass-card rounded-2xl p-5">
        <h3 class="font-bold text-white text-sm mb-4">All Licences</h3>
        <div class="overflow-x-auto" style="max-height:500px; overflow-y:auto;">
            <table class="w-full text-xs">
                <thead><tr class="text-zinc-500 border-b border-white/5">
                    <th class="text-left py-2 pr-3 font-semibold">Pipeline</th>
                    <th class="text-left py-2 pr-3 font-semibold">Type</th>
                    <th class="text-left py-2 pr-3 font-semibold">Reference</th>
                    <th class="text-left py-2 pr-3 font-semibold">Authority</th>
                    <th class="text-left py-2 pr-3 font-semibold">Status</th>
                    <th class="text-left py-2 pr-3 font-semibold">Uploaded</th>
                    <th class="text-left py-2 font-semibold">Actions</th>
                </tr></thead>
                <tbody class="divide-y divide-white/5">
                @forelse($licences as $l)
                <tr class="hover:bg-white/5 transition">
                    <td class="py-2.5 pr-3 font-mono font-bold text-[#FFCD05]">{{ $l->pipeline_code }}</td>
                    <td class="py-2.5 pr-3">
                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ $l->licence_type === 'cbk' ? 'bg-sky-500/20 text-sky-400' : ($l->licence_type === 'ifmis' ? 'bg-purple-500/20 text-purple-400' : ($l->licence_type === 'ardhisasa' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-zinc-500/20 text-zinc-400')) }}">
                            {{ strtoupper($l->licence_type) }}
                        </span>
                    </td>
                    <td class="py-2.5 pr-3 text-zinc-300">{{ $l->reference_number ?? '—' }}</td>
                    <td class="py-2.5 pr-3 text-zinc-400">{{ $l->issuing_authority ?? '—' }}</td>
                    <td class="py-2.5 pr-3">
                        @if($l->status === 'approved')
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">APPROVED</span>
                        @elseif($l->status === 'rejected')
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400">REJECTED</span>
                        @else
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400">PENDING</span>
                        @endif
                    </td>
                    <td class="py-2.5 pr-3 text-zinc-500">{{ $l->created_at?->format('d M Y') }}</td>
                    <td class="py-2.5">
                        <div class="flex gap-2">
                            @if($l->status === 'pending')
                            <form method="POST" action="{{ route('kicc.admin.licence.approve', $l->id) }}" class="inline" onsubmit="return confirm('Approve this licence? Pipeline {{ $l->pipeline_code }} will be UNLOCKED for earning.');">
                                @csrf
                                <button class="text-[10px] font-bold px-2 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('kicc.admin.licence.reject', $l->id) }}" class="inline" onsubmit="return confirm('Reject this licence?');">
                                @csrf
                                <button class="text-[10px] font-bold px-2 py-1 rounded-lg bg-rose-500/20 text-rose-400 hover:bg-rose-500/30">Reject</button>
                            </form>
                            @endif
                            @if($l->document_path)
                            <a href="{{ asset('storage/' . $l->document_path) }}" target="_blank" class="text-[10px] font-bold px-2 py-1 rounded-lg bg-white/10 text-zinc-300 hover:bg-white/20">View</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-8 text-center text-zinc-500">No licences uploaded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Regulatory summary --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($regulatorMap ?? [] as $key => $reg)
        <div class="glass-card rounded-xl p-4 border-l-4 {{ $key === 'cbk' ? 'border-sky-500' : ($key === 'ifmis' ? 'border-purple-500' : ($key === 'ardhisasa' ? 'border-emerald-500' : 'border-amber-500')) }}">
            <div class="font-bold text-white text-sm">{{ $reg['label'] }}</div>
            <div class="text-[10px] text-zinc-400 mt-1">{{ $reg['description'] }}</div>
            <div class="mt-2 flex flex-wrap gap-1">
                @foreach($reg['pipelines'] as $pc)
                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-white/5 text-zinc-400">{{ $pc }}</span>
                @endforeach
            </div>
            <div class="mt-2 text-[9px] text-zinc-500">
                @if($reg['data_source'])
                Source: <span class="text-zinc-300">{{ strtoupper($reg['data_source']) }}</span>
                @else
                <span class="text-amber-400">External (legislation)</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif