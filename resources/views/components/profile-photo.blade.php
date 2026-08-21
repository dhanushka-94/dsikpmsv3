@props([
    'url',
    'class' => 'h-28 w-28',
    'rounded' => 'rounded-3xl',
    'ring' => 'ring-4 ring-brand-50',
    'alt' => 'Profile photo',
])

@if($url)
    <span
        role="button"
        tabindex="0"
        {{ $attributes->class(['group relative inline-flex shrink-0 cursor-zoom-in items-center justify-center overflow-hidden bg-slate-100', $class, $rounded, $ring]) }}
        @click.prevent.stop="$dispatch('open-lightbox', { src: @js($url), alt: @js($alt) })"
        @keydown.enter.prevent.stop="$dispatch('open-lightbox', { src: @js($url), alt: @js($alt) })"
        title="View photo"
    >
        <img
            src="{{ $url }}"
            alt="{{ $alt }}"
            class="h-full w-full object-contain transition group-hover:opacity-90"
        >
    </span>
@endif
