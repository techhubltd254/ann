@props(['type','items','label','icon','fields'=>[]])
<div class="flex items-center justify-between mb-6">
    <h3 class="font-bold text-white text-sm">{{ $label }} ({{ $items->count() }})</h3>
    <button @click="document.getElementById('{{ $type }}-form').classList.toggle('hidden')" class="bg-[#901C1E] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#7b1618] transition-all">+ Add {{ $type }}</button>
</div>
<div id="{{ $type }}-form" class="hidden mb-6 bg-[#0D1220] border border-white/8 rounded-2xl p-6">
    <form method="POST" action="{{ route('county.admin.' . $type, $county->slug) }}">
        @csrf
        <div class="grid grid-cols-3 gap-4">
            @foreach($fields as $label => $field)
            <div><label class="text-[10px] font-bold text-white/30 uppercase block mb-1">{{ $label }}</label><input name="{{ $field }}" required class="w-full bg-[#141B2E] border border-white/10 rounded-xl px-4 py-2 text-sm text-white" @if($loop->first) placeholder="Name" @endif></div>
            @endforeach
        </div>
        <button class="mt-4 bg-[#901C1E] text-white px-6 py-2 rounded-xl text-sm font-bold">Save {{ $type }}</button>
    </form>
</div>
@if($items->count() > 0)
<div class="bg-[#0D1220] border border-white/8 rounded-2xl overflow-hidden">
    <table class="w-full">
        <thead><tr class="bg-[#141B2E]">
            @foreach($fields as $label => $field)
            <th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-left">{{ $label }}</th>
            @endforeach
            <th class="text-[10px] font-bold text-white/30 uppercase px-4 py-3 text-right">Actions</th>
        </tr></thead>
        <tbody class="divide-y divide-white/5">
            @foreach($items as $item)
            <tr class="hover:bg-white/2">
                @foreach($fields as $label => $field)
                <td class="px-4 py-3 text-sm text-white">{{ $item->$field ?? '—' }}</td>
                @endforeach
                <td class="px-4 py-3 text-right">
                    <form method="POST" action="{{ route('county.admin.' . $type . '.delete', [$county->slug, $item->id]) }}" onsubmit="return confirm('Delete?')">
                        @csrf @method('DELETE')
                        <button class="text-xs text-white/30 hover:text-[#901C1E] font-bold">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="text-center py-12 text-white/30"><span class="text-4xl block mb-3">{{ $icon }}</span><p class="text-sm">No {{ strtolower($label) }} listed yet.</p></div>
@endif
