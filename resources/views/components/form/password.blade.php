{{-- Password input with visibility toggle + 44px touch target --}}
@props(['name' => 'password', 'label' => 'Password', 'required' => true])

<div x-data="{ show: false }">
    @if($label)
    <label for="{{ $name }}" class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">
        {{ $label }}
    </label>
    @endif
    <div class="relative">
        <input :type="show ? 'text' : 'password'" name="{{ $name }}" id="{{ $name }}"
               @if($required) required @endif
               class="w-full bg-[#FFFFFF] border {{ $errors->has($name) ? 'border-red-300' : 'border-gray-200' }} focus:border-[#FFCD05]/60 rounded-xl px-4 py-2.5 pr-10 text-sm text-gray-900 placeholder:text-[#0B0B0B]/50 outline-none transition-colors touch-target">
        <button type="button" @click="show = !show"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors touch-target"
                aria-label="Toggle password visibility">
            <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg x-show="show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
            </svg>
        </button>
    </div>
    @error($name)
    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>