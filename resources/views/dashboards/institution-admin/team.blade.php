<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-white">Team</h1><p class="text-zinc-500 text-sm">People managing {{ $institution->name }}</p></div>
    </div>
    <div class="glass-card rounded-2xl p-5 max-w-2xl">
        <form method="POST" action="{{ route('institution.admin.team.add', $institution->slug) }}" class="grid md:grid-cols-3 gap-3 mb-5">
            @csrf
            <input name="name" required placeholder="Full name">
            <input name="email" type="email" required placeholder="Email">
            <button class="btn-primary">+ Add Member</button>
        </form>
        @php $team = \App\Models\User::where('institution_id', $institution->id)->get(); @endphp
        <div class="space-y-1">
            @forelse($team as $m)
            <div class="flex items-center justify-between py-2.5 border-b border-white/5 last:border-0">
                <div>
                    <div class="font-medium text-sm text-zinc-200">{{ $m->name }}</div>
                    <div class="text-[11px] text-zinc-500">{{ $m->email }}</div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $m->id === auth()->id() ? 'You' : 'Admin' }}</span>
                    @if($m->id !== auth()->id())
                    <form method="POST" action="{{ route('institution.admin.team.remove', [$institution->slug, $m->id]) }}" onsubmit="return confirm('Remove this member?')">
                        @csrf
                        <button class="text-[10px] text-red-400 hover:text-red-300">Remove</button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <p class="text-zinc-500 text-xs py-8 text-center">No team members.</p>
            @endforelse
        </div>
    </div>
</div>