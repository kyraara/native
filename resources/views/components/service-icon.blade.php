@props(['icon'])
@php
    $symbol = match(true) {
        str_contains($icon, 'code'), str_contains($icon, 'laptop') => 'code',
        str_contains($icon, 'powerpoint'), str_contains($icon, 'display'), str_contains($icon, 'desktop'), str_contains($icon, 'presentation') => 'slides',
        str_contains($icon, 'file'), str_contains($icon, 'chart'), str_contains($icon, 'clipboard') => 'report',
        str_contains($icon, 'book'), str_contains($icon, 'scroll') => 'research',
        str_contains($icon, 'pen'), str_contains($icon, 'pencil'), str_contains($icon, 'graduation') => 'brief',
        str_contains($icon, 'ellipsis'), str_contains($icon, 'layer'), str_contains($icon, 'shapes') => 'services',
        default => null,
    };
@endphp
@if($symbol)<x-public-icon :name="$symbol" :class="$attributes->get('class', '')"/>@else<i {{ $attributes->class([$icon]) }} aria-hidden="true"></i>@endif
