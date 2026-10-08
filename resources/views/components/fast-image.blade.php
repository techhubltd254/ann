@props([
    'src' => null,
    'alt' => '',
    'class' => '',
    'width' => 640,
    'quality' => 75,
    'blur' => null,
    'placeholderColor' => '#FFFFFF',
    'aspect' => null,
    'priority' => false,
])

@php
    $aspectClass = $aspect ?? '';
    $optimizedSrc = $src ? url('/api/optimize-image?url=' . urlencode($src) . '&w=' . $width . '&q=' . $quality) : '';
    $blur = $blur ?? image_blur($src);
@endphp

<div class="relative overflow-hidden {{ $aspectClass }} {{ $class }}"
     style="{{ $aspectClass ? '' : 'background-color:' . $placeholderColor }}"
     x-data="fastImage('{{ $optimizedSrc }}', '{{ $blur ?? '' }}')">

    {{-- Blur-up placeholder (real base64 WebP from ingestion, else flat colour) --}}
    <template x-if="!loaded && blurUrl">
        <img :src="blurUrl" alt="" aria-hidden="true"
             class="absolute inset-0 w-full h-full object-cover blur-lg scale-110"
             style="filter: blur(14px); transform: scale(1.1);">
    </template>

    {{-- Skeleton shimmer while the optimized image downloads --}}
    <template x-if="!loaded && !blurUrl">
        <div class="absolute inset-0 animate-pulse bg-gradient-to-r from-slate-200 via-slate-100 to-slate-200"></div>
    </template>

    {{-- Optimized image — fades in once buffered --}}
    <img :src="src"
         alt="{{ $alt }}"
         @if(!$priority) loading="lazy" @endif
         decoding="async"
         class="absolute inset-0 w-full h-full object-cover transition-all duration-500 ease-out"
         :class="loaded ? 'opacity-100 scale-100 blur-0' : 'opacity-0 scale-105'"
         x-ref="img"
         x-on:load="loaded = true"
         x-on:error="loaded = true">
</div>

@push('scripts')
<script>
function fastImage(src, blurUrl) {
    return {
        src: src,
        blurUrl: blurUrl,
        loaded: false,
    };
}
</script>
@endpush