@props(['title', 'value', 'growth' => '', 'label' => '', 'accent' => false])

<div class="bg-[#18181B] border border-zinc-800/80 rounded-xl p-4 flex justify-between items-center">
    <div>
        <p class="text-[10px] font-medium text-zinc-500 uppercase tracking-wider">{{ $title }}</p>
        <p class="text-xl font-bold text-white mt-1">{{ $value }}</p>
        @if($growth)
        <p class="text-[10px] text-emerald-400 mt-2 font-medium">
            {{ $growth }} <span class="text-zinc-500">{{ $label }}</span>
        </p>
        @endif
    </div>
    <div class="flex items-end gap-1 h-8">
        <div class="w-1.5 h-3 bg-zinc-700 rounded-sm"></div>
        <div class="w-1.5 h-5 bg-zinc-700 rounded-sm"></div>
        <div class="w-1.5 h-4 bg-zinc-700 rounded-sm"></div>
        <div class="w-1.5 h-7 {{ $accent ? 'bg-[#FFCD05]' : 'bg-orange-500' }} rounded-sm"></div>
    </div>
</div>
