@props([
    'icon',
    'label',
    'href' => null,
    'size' => 18,
    'variant' => 'primary',  // primary | secondary | ghost | danger
    'type' => 'button',
])

@php $tag = $href ? 'a' : 'button'; @endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    class="ng-lbtn ng-lbtn--{{ $variant }}"
    {{ $attributes }}>
    <x-icon :name="$icon" :size="$size" />
    <span>{{ $label }}</span>
</{{ $tag }}>
