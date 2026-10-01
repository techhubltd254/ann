<div
    id="kicc-shader-container"
    data-shader-backdrop="{{ $variant ?? 'ribbon' }}"
    data-shader-palette="{{ $palette ?? '' }}"
    class="fixed inset-0 z-[-1] pointer-events-none"
    aria-hidden="true"
></div>

@push('scripts')
<script type="module" src="{{ asset('js/kicc-shader-bg.js') }}?v={{ filemtime(public_path('js/kicc-shader-bg.js')) }}"></script>
@endpush