@props([
    'text' => '',
    'lines' => null,
    'as' => 'p',
    'href' => null,
])

@php
    $text = trim((string) $text);
    $tag = $href ? 'a' : $as;
    $baseClass = 'min-w-0 break-words [overflow-wrap:anywhere]';
@endphp

@if($text === '')
    <{{ $tag }} {{ $attributes->class([$baseClass]) }}>{{ $slot->isEmpty() ? '—' : $slot }}</{{ $tag }}>
@elseif($lines)
    <div
        class="min-w-0"
        x-data="{ open: false, canToggle: false }"
        x-init="$nextTick(() => { canToggle = $refs.body.scrollHeight > $refs.body.clientHeight + 1 })"
    >
        <{{ $tag }}
            @if($href) href="{{ $href }}" @endif
            {{ $attributes->class([$baseClass, 'leading-snug']) }}
            x-ref="body"
            :class="open ? '' : 'line-clamp-{{ (int) $lines }}'"
            title="{{ $text }}"
        >{{ $text }}</{{ $tag }}>
        <button
            type="button"
            class="mt-1 text-xs font-bold text-brand-700 hover:underline"
            x-show="canToggle || open"
            x-cloak
            @click.stop="open = !open; $nextTick(() => { if (!open) canToggle = $refs.body.scrollHeight > $refs.body.clientHeight + 1 })"
            x-text="open ? 'Show less' : 'Show more'"
        ></button>
    </div>
@else
    <{{ $tag }}
        @if($href) href="{{ $href }}" @endif
        {{ $attributes->class([$baseClass]) }}
        title="{{ $text }}"
    >{{ $text }}</{{ $tag }}>
@endif
