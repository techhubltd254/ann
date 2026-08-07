@props(['label', 'value', 'change' => null, 'accent' => '#FFCD05', 'icon' => null])
<div class="bg-[#0D1220] border border-white/8 rounded-2xl p-6">
    <div class="flex items-start justify-between mb-2">
        <span class="text-white/40 text-xs font-medium uppercase tracking-wide">{{ $label }}</span>
        @if($icon)<span class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: {{ $accent }}15;">{!! $icon !!}</span>@endif
    </div>
    <div class="text-3xl font-black text-white">{{ $value }}</div>
    @if($change)<div class="text-xs mt-1 font-semibold" style="color: {{ str_starts_with($change, '+') ? '#2D6A4F' : '#901C1E' }}">{{ $change }}</div>@endif
</div>