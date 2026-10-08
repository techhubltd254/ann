@props([
    'reviews' => [],
    'average' => 0,
    'count' => 0,
    'seedSource' => null,
    'seedUrl' => null,
    'verifiedLabel' => null,
])

<div class="bg-white border border-gray-200 rounded-2xl p-5">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest">Reviews</h3>
        @if($average > 0)
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-0.5 text-[#FFCD05]">
                @for($i = 1; $i <= 5; $i++)
                <svg class="w-4 h-4 {{ $i <= round($average) ? 'fill-[#FFCD05]' : 'fill-gray-200' }}" viewBox="0 0 20 20"><path d="M10 15.27L16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z"/></svg>
                @endfor
            </span>
            <span class="text-sm font-bold text-gray-900">{{ number_format($average, 1) }}</span>
            <span class="text-xs text-gray-400">({{ number_format($count) }} reviews)</span>
        </div>
        @endif
    </div>

    @if($seedSource && $count > 0 && $reviews->isEmpty())
    <p class="text-[11px] text-gray-400 mb-3">
        Based on <span class="font-semibold text-gray-600">{{ number_format($count) }}</span> {{ $seedSource }}@if($seedUrl) — <a href="{{ $seedUrl }}" target="_blank" rel="noopener" class="text-kicc-gold hover:underline">view on source</a>@endif
    </p>
    @endif

    @if($reviews->count() > 0)
    <div class="space-y-4 max-h-[420px] overflow-y-auto pr-1">
        @foreach($reviews as $review)
        <div class="border-b border-gray-100 pb-3 last:border-0">
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-[#0b0b0b] text-white flex items-center justify-center text-[10px] font-bold">
                        {{ strtoupper(substr($review->user_name ?? ($review->user?->name ?? 'V'), 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-900">{{ $review->user_name ?? $review->user?->name ?? 'Visitor' }}</div>
                        <div class="text-[10px] text-gray-400">{{ $review->created_at?->diffForHumans() }}</div>
                    </div>
                </div>
                <span class="flex items-center gap-0.5">
                    @for($i = 1; $i <= 5; $i++)
                    <svg class="w-3 h-3 {{ $i <= (int) round($review->rating) ? 'fill-[#FFCD05]' : 'fill-gray-200' }}" viewBox="0 0 20 20"><path d="M10 15.27L16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z"/></svg>
                    @endfor
                </span>
            </div>
            @if($review->title ?? null)
            <div class="text-xs font-bold text-gray-800 mt-1">{{ $review->title }}</div>
            @endif
            <p class="text-xs text-gray-600 leading-relaxed mt-0.5">{{ $review->body ?? $review->content }}</p>
            @if($review->is_verified_purchase)
            <span class="inline-block mt-1.5 text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded"> Verified purchase</span>
            @endif
        </div>
        @endforeach
    </div>
    @elseif(!$seedSource || $count === 0)
    <p class="text-xs text-gray-400 py-3 text-center">No reviews yet — be the first to review.</p>
    @endif
</div>