@props(['title' => 'Dashboard', 'role' => 'User', 'accent' => '#901C1E', 'initials' => 'JK', 'navItems' => []])
<div class="flex min-h-screen bg-[#F9FAFB] pt-0">
    <div x-data="{ collapsed: false }" class="flex">
        <div :style="'width: ' + (collapsed ? '64px' : '230px')" class="bg-[#0EA5E9] border-r border-gray-200 flex flex-col overflow-hidden shrink-0 transition-all duration-300" x-init="$el.style.width='230px'">
            <div class="flex items-center justify-between px-4 border-b border-gray-200 h-16 shrink-0">
                <div x-show="!collapsed" class="flex items-center gap-2">
                    <div class="rounded-lg bg-[#901C1E] px-2 py-1 flex items-center justify-center">
                        <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-6 w-auto">
                    </div>
                    <div class="text-[#FFCD05] text-[9px] font-black tracking-[0.15em] uppercase leading-tight">Global<br>Exhibition</div>
                </div>
                <button @click="collapsed = !collapsed" class="text-[#5A6480] hover:text-gray-900 p-1 ml-auto">
                    <svg class="w-4 h-4" :class="collapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
            <div class="flex-1 py-3 overflow-y-auto">
                @foreach($navItems as $item)
                <a href="{{ $item['route'] ?? '#' }}"
                   class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold transition-all"
                   style="{{ ($item['active'] ?? false) ? 'background: ' . $accent . '22; border-right: 2px solid ' . $accent : '' }}"
                   :class="'{{ ($item['active'] ?? false) ? 'true' : '' }}'.includes('true') ? 'text-gray-900' : 'text-gray-400 hover:bg-sky-50 hover:text-gray-900'">
                    @if(isset($item['icon']))
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    @endif
                    <span x-show="!collapsed" class="truncate text-xs">{{ $item['label'] }}</span>
                </a>
                @endforeach
            </div>
            <a href="/" class="flex items-center gap-3 px-4 py-4 border-t border-gray-200 text-[#5A6480] hover:text-gray-900 text-xs transition-colors">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span x-show="!collapsed">Exit Dashboard</span>
            </a>
        </div>
    </div>
    <div class="flex-1 flex flex-col overflow-hidden">
        <div class="bg-[#0EA5E9] border-b border-gray-200 px-6 h-16 flex items-center justify-between shrink-0">
            <div>
                <div class="font-black text-gray-900 text-sm">{{ $title }}</div>
                <div class="text-[10px] font-bold uppercase tracking-widest" style="color: {{ $accent }}">{{ $role }}</div>
            </div>
            <div class="flex items-center gap-3">
                <button class="relative p-2 text-[#5A6480] hover:text-gray-900 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </button>
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-gray-900 font-black text-xs" style="background: {{ $accent }}">{{ $initials }}</div>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6">{{ $slot }}</div>
    </div>
</div>