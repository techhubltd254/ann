@props([
    'action' => route('review.store'),
    'reviewableType' => '',
    'reviewableId' => 0,
    'useProductReviews' => false,
    'productId' => null,
])

<div class="bg-white border border-gray-200 rounded-2xl p-5">
    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-4">Write a Review</h3>

    <form method="POST" action="{{ $useProductReviews ? route('product.review.store') : $action }}" enctype="multipart/form-data" x-data="{ rating: 5 }">
        @csrf
        @if($useProductReviews)
        <input type="hidden" name="product_id" value="{{ $productId }}">
        @else
        <input type="hidden" name="reviewable_type" value="{{ $reviewableType }}">
        <input type="hidden" name="reviewable_id" value="{{ $reviewableId }}">
        @endif

        {{-- Star picker --}}
        <div class="flex items-center gap-1 mb-4">
            <span class="text-xs text-gray-500 mr-2">Your rating:</span>
            <template x-for="i in 5" :key="i">
                <button type="button" @click="rating = i"
                        class="transition-transform hover:scale-110"
                        :class="i <= rating ? 'text-[#FFCD05]' : 'text-gray-300'">
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 20 20"><path d="M10 15.27L16.18 19l-1.64-7.03L20 7.24l-7.19-.61L10 0 7.19 6.63 0 7.24l5.46 4.73L3.82 19z"/></svg>
                </button>
            </template>
            <input type="hidden" name="rating" :value="rating" value="5">
        </div>

        @if($useProductReviews)
        <input type="text" name="title" placeholder="Review title" maxlength="120"
               class="w-full mb-2 px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 text-sm focus:ring-1 focus:ring-[#FFCD05] outline-none">
        @endif

        <textarea name="{{ $useProductReviews ? 'body' : 'content' }}" rows="3" placeholder="Share your experience…" required maxlength="2000"
                  class="w-full mb-3 px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 text-sm focus:ring-1 focus:ring-[#FFCD05] outline-none"></textarea>

        @if($useProductReviews)
        <div class="grid grid-cols-2 gap-2 mb-3">
            <input type="text" name="pros" placeholder="Pros (optional)" maxlength="300"
                   class="px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 text-sm focus:ring-1 focus:ring-[#FFCD05] outline-none">
            <input type="text" name="cons" placeholder="Cons (optional)" maxlength="300"
                   class="px-3 py-2 rounded-xl bg-gray-50 border border-gray-200 text-sm focus:ring-1 focus:ring-[#FFCD05] outline-none">
        </div>
        <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp"
               class="block w-full text-xs text-gray-400 mb-3 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-[#0B1E57] file:text-white file:text-xs">
        @endif

        <button type="submit" class="w-full h-10 rounded-xl bg-[#0B1E57] text-white text-sm font-bold hover:bg-[#16275f] transition-all">
            Submit Review
        </button>
        <p class="text-[10px] text-gray-400 mt-2 text-center">Reviews appear after moderation.</p>
    </form>
</div>