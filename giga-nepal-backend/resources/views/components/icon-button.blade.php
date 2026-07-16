@props([
    'icon',
    'label',                 // required accessible name (icon-only button)
    'href' => null,
    'size' => 18,
    'variant' => 'ghost',    // ghost | solid | subtle
    'type' => 'button',
])

@php $tag = $href ? 'a' : 'button'; @endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    class="ng-iconbtn ng-iconbtn--{{ $variant }}"
    aria-label="{{ $label }}" title="{{ $label }}"
    {{ $attributes }}>
    <x-icon :name="$icon" :size="$size" />
</{{ $tag }}>
