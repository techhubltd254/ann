@props(['title','items','fields'=>[],'storeRoute','deleteRoute','formFields'=>[]])
<div class="bg-[#0D1220] border border-white/8 rounded-2xl overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-white/8">
        <h3 class="font-bold text-white text-sm">{{ $title }} ({{ $items->count() }})</h3>
        <button @click="document.getElementById('form-{{ Str::slug($title) }}').classList.toggle('hidden')" class="bg-[#901C1E] text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#7b1618] transition-all">+ Add</button>
    </div>

    <div id="form-{{ Str::slug($title) }}" class="hidden bg-[#141B2E] p-6 border-b border-white/8">
        <form method="POST" action="{{ route($storeRoute) }}">
            @csrf
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                @foreach($formFields as $f)
                <div>
                    <label class="text-[10px] font-bold text-white/30 uppercase block mb-1">{{ $f['label'] }}</label>
                    @if(($f['type'] ?? 'text') === 'select' && !empty($f['options']))
                    <select name="{{ $f['name'] }}" class="w-full bg-[#0D1220] border border-white/10 rounded-xl px-4 py-2 text-sm text-white" {{ ($f['required'] ?? false) ? 'required' : '' }}>
                        <option value="">Select...</option>
                        @foreach($f['options'] as $val=>$label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @elseif(($f['type'] ?? 'text') === 'textarea')
                    <textarea name="{{ $f['name'] }}" rows="2" class="w-full bg-[#0D1220] border border-white/10 rounded-xl px-4 py-2 text-sm text-white"></textarea>
                    @else
                    <input type="{{ $f['type'] ?? 'text' }}" name="{{ $f['name'] }}" class="w-full bg-[#0D1220] border border-white/10 rounded-xl px-4 py-2 text-sm text-white" {{ ($f['required'] ?? false) ? 'required' : '' }}>
                    @endif
                </div>
                @endforeach
            </div>
            <button class="mt-4 bg-[#901C1E] text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-[#7b1618] transition-all">Save {{ Str::singular($title) }}</button>
        </form>
    </div>

    @if($items->count())
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
                <td class="px-4 py-3 text-sm {{ is_bool($item->$field) ? ($item->$field ? 'text-emerald-400' : 'text-red-400') : 'text-white' }}">
                    {{ is_bool($item->$field) ? ($item->$field ? '✓' : '✗') : $item->$field ?? '—' }}
                </td>
                @endforeach
                <td class="px-4 py-3 text-right">
                    <form method="POST" action="{{ route($deleteRoute, $item->id) }}" onsubmit="return confirm('Delete?')">
                        @csrf @method('DELETE')
                        <button class="text-xs text-white/30 hover:text-[#901C1E] font-bold">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="text-center py-12 text-white/30">
        <div class="text-3xl mb-2">📋</div>
        <p class="text-sm">No {{ strtolower($title) }} yet. Add one above.</p>
    </div>
    @endif
</div>
