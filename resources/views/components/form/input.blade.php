{{-- Reusable form input with label, error state, 44px touch target --}}
@props(['name', 'label', 'type' => 'text', 'required' => false, 'placeholder' => '', 'value' => null])

<div>
    @if($label)
    <label for="{{ $name }}" class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">
        {{ $label }}
    </label>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}"
           value="{{ $value ?? old($name) }}"
           @if($required) required @endif
           placeholder="{{ $placeholder }}"
           class="w-full bg-[#F9FAFB] border {{ $errors->has($name) ? 'border-red-300' : 'border-gray-200' }} focus:border-[#F59E0B]/60 rounded-xl px-4 py-2.5 text-sm text-gray-900 placeholder:text-[#5A6480]/50 outline-none transition-colors touch-target">
    @error($name)
    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>