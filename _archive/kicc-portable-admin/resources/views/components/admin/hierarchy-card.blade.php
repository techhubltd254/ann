@props(['items' => []])
<div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
    <h3 class="font-bold text-white text-sm mb-5">Admin Hierarchy</h3>
    <div class="space-y-0">
        @foreach($items as $i => $item)
        <div class="flex items-center gap-4 py-3 {{ $i > 0 ? 'border-t border-white/5' : '' }}">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background: {{ $item['color'] }}20;">
                <span style="color:{{ $item['color'] }}">{!! $item['icon'] !!}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-white">{{ $item['label'] }}</div>
                <div class="text-xs" style="color: {{ $item['color'] }}">{{ $item['count'] }} {{ $item['sublabel'] }}</div>
            </div>
            <div class="text-xs text-white/30 shrink-0">{{ $item['tag'] ?? '' }}</div>
        </div>
        @endforeach
    </div>
</div>
