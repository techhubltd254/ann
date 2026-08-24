<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-white">Production Chain</h1><p class="text-zinc-500 text-sm">Stage-by-stage story of your product journey</p></div>
    </div>
    <div class="glass-card rounded-2xl p-5">
        <form method="POST" action="{{ route('institution.admin.production', $institution->slug) }}" class="space-y-3">
            @csrf
            <div id="chainRows">
                @forelse(($institution->production_chain ?? []) as $ci => $step)
                <div class="grid md:grid-cols-3 gap-3 mb-3 chain-row">
                    <input name="production_chain[{{ $ci }}][step]" value="{{ $step['step'] }}" placeholder="Stage name">
                    <textarea name="production_chain[{{ $ci }}][description]" rows="1" placeholder="What happens at this stage?" class="md:col-span-2">{{ $step['description'] }}</textarea>
                </div>
                @empty
                <div class="grid md:grid-cols-3 gap-3 mb-3 chain-row">
                    <input name="production_chain[0][step]" placeholder="Stage name">
                    <textarea name="production_chain[0][description]" rows="1" placeholder="What happens at this stage?" class="md:col-span-2"></textarea>
                </div>
                @endforelse
            </div>
            <button type="button" onclick="addChainRow()" class="btn-ghost text-xs">+ Add Stage</button>
            <button class="btn-primary block mt-4">Save Production Chain</button>
        </form>
    </div>
</div>
<script>
function addChainRow() {
    var r = document.getElementById('chainRows');
    var c = r.querySelectorAll('.chain-row').length;
    var d = document.createElement('div'); d.className = 'grid md:grid-cols-3 gap-3 mb-3 chain-row';
    d.innerHTML = '<input name="production_chain['+c+'][step]" placeholder="Stage name"><textarea name="production_chain['+c+'][description]" rows="1" placeholder="What happens?" class="md:col-span-2"></textarea>';
    r.appendChild(d);
}
</script>