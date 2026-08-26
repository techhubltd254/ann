@props([
    'type' => 'card', // card, text, title, avatar, image, chart
    'class' => '',
])
<div class="skeleton
    @if($type === 'card') skeleton-card @endif
    @if($type === 'text') skeleton-text @endif
    @if($type === 'title') skeleton-title @endif
    @if($type === 'avatar') skeleton-avatar @endif
    @if($type === 'image') skeleton-image @endif
    @if($type === 'chart') rounded-xl h-48 w-full @endif
    {{ $class }}"
    aria-hidden="true"
    role="presentation">
</div>