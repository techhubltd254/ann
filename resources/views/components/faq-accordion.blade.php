@props(['faqs' => []])

<div class="space-y-3">
    @foreach($faqs as $i => $f)
    <div class="bg-white border border-gray-200 rounded-2xl">
        <button onclick="var el=this.nextElementSibling; var icon=this.querySelector('.faq-icon'); el.style.display=el.style.display==='block'?'none':'block'; icon.style.transform=el.style.display==='block'?'rotate(180deg)':'';"
                class="w-full flex items-center justify-between p-5 text-left">
            <span class="font-bold text-gray-900 text-sm pr-4">{{ $f->question }}</span>
            <svg class="faq-icon w-4 h-4 text-gray-400 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="px-5 pb-5 text-sm text-gray-600 leading-relaxed" style="display:none">{{ $f->answer }}</div>
    </div>
    @endforeach
</div>