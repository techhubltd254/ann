<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-bold text-white">Sector Mapping</h1><p class="text-zinc-500 text-sm">Map your institution to county sectors</p></div>
    </div>
    <div class="glass-card rounded-2xl p-5">
        @php $sectors = $institution->county?->sectors ?? collect(); @endphp
        <form method="POST" action="{{ route('institution.admin.sectors', $institution->slug) }}" class="space-y-3">
            @csrf
            <div id="sectorRows">
                @forelse(($institution->sector_mappings ?? []) as $si => $m)
                <div class="grid md:grid-cols-5 gap-3 mb-3 sector-row">
                    <select name="sector_mappings[{{ $si }}][sector_slug]">
                        @foreach($sectors as $s)
                        <option value="{{ $s->slug }}" @selected(($m['sector_slug']??'')==$s->slug)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <input name="sector_mappings[{{ $si }}][entry_name]" value="{{ $m['entry_name'] }}" placeholder="Entry name">
                    <input name="sector_mappings[{{ $si }}][entry_type]" value="{{ $m['entry_type'] ?? '' }}" placeholder="Type">
                    <input name="sector_mappings[{{ $si }}][entry_fee]" value="{{ $m['entry_fee'] }}" type="number" min="0" placeholder="Fee KES">
                    <input name="sector_mappings[{{ $si }}][location]" value="{{ $m['location'] ?? '' }}" placeholder="Location">
                    <textarea name="sector_mappings[{{ $si }}][description]" rows="1" placeholder="Description" class="md:col-span-5">{{ $m['description'] ?? '' }}</textarea>
                </div>
                @empty
                <div class="grid md:grid-cols-5 gap-3 mb-3 sector-row">
                    <select name="sector_mappings[0][sector_slug]">@foreach($sectors as $s)<option value="{{ $s->slug }}">{{ $s->name }}</option>@endforeach</select>
                    <input name="sector_mappings[0][entry_name]" placeholder="Entry name">
                    <input name="sector_mappings[0][entry_type]" placeholder="Type">
                    <input name="sector_mappings[0][entry_fee]" type="number" min="0" placeholder="Fee KES">
                    <input name="sector_mappings[0][location]" placeholder="Location">
                    <textarea name="sector_mappings[0][description]" rows="1" placeholder="Description" class="md:col-span-5"></textarea>
                </div>
                @endforelse
            </div>
            <button type="button" onclick="addSectorRow()" class="btn-ghost text-xs">+ Add Mapping</button>
            <div class="flex gap-3 mt-4">
                <button class="btn-primary">Save Mappings</button>
                <a href="{{ route('institution.admin.sync', $institution->slug) }}" class="btn-success">Sync Now</a>
            </div>
        </form>
    </div>
</div>
<script>
function addSectorRow() {
    var r = document.getElementById('sectorRows');
    var c = r.querySelectorAll('.sector-row').length;
    var sel = r.querySelector('select');
    var opts = sel ? sel.innerHTML : '';
    var d = document.createElement('div'); d.className = 'grid md:grid-cols-5 gap-3 mb-3 sector-row';
    d.innerHTML = '<select name="sector_mappings['+c+'][sector_slug]">'+opts+'</select><input name="sector_mappings['+c+'][entry_name]" placeholder="Entry name"><input name="sector_mappings['+c+'][entry_type]" placeholder="Type"><input name="sector_mappings['+c+'][entry_fee]" type="number" min="0" placeholder="Fee KES"><input name="sector_mappings['+c+'][location]" placeholder="Location"><textarea name="sector_mappings['+c+'][description]" rows="1" placeholder="Description" class="md:col-span-5"></textarea>';
    r.appendChild(d);
}
</script>