{{-- Submit button with loading state — 44px touch target, KICC red --}}
@props(['label' => 'Submit', 'loading' => false])

<button type="submit" :disabled="{{ $loading ? 'true' : 'false' }}"
        class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base rounded-xl bg-[#B3261E] text-white hover:bg-[#B3261E] disabled:opacity-60 touch-target">
    @if($loading)
    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
    </svg>
    @endif
    <span>{{ $label }}</span>
</button>