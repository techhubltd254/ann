{{-- Packages strip — the blueprint's "everywhere you click, see a package" --}}
@php
    $stripPlans = \App\Models\SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
@endphp
@if($stripPlans->isNotEmpty())
<div class="bg-[#0B0B0B] rounded-2xl overflow-hidden">
    <div class="px-6 pt-6 pb-2 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="{{ media('kicc/kicc-logo.png') }}" alt="KICC" class="h-6 w-auto">
            <span class="text-[#FFCD05] text-[10px] font-black uppercase tracking-[0.2em]">Exhibitor Packages</span>
        </div>
        <a href="{{ route('packages.index') }}" class="text-gray-400 hover:text-gray-900 text-xs font-semibold transition-colors">All packages &nearr;</a>
    </div>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-px bg-gray-100 mt-4">
        @foreach($stripPlans as $p)
        <a href="{{ route('packages.index') }}" class="bg-[#0B0B0B] p-5 hover:bg-[#0B0B0B] transition-colors group">
            <div class="text-[10px] font-bold uppercase tracking-widest {{ $p->slug === 'exhibitor-pro' ? 'text-[#FFCD05]' : 'text-gray-400' }} mb-1">{{ $p->name }}</div>
            <div class="text-gray-900 font-black">KES {{ number_format($p->price) }}<span class="text-[10px] font-medium text-gray-400">/mo</span></div>
            <div class="text-[10px] text-gray-400 mt-1 group-hover:text-gray-500 transition-colors">{{ $p->max_booths >= 999 ? 'Unlimited' : $p->max_booths }} booth{{ $p->max_booths > 1 ? 's' : '' }} &nearr;</div>
        </a>
        @endforeach
    </div>
</div>
@endif
