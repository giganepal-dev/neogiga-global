@props([
    'icon',
    'label',                 // required accessible name + tooltip
    'href' => null,
    'variant' => 'default',  // default | primary | success | danger
    'confirm' => null,       // destructive actions: JS confirm text
    'type' => 'button',
])

@php $tag = $href ? 'a' : 'button'; @endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    class="ng-tact ng-tact--{{ $variant }}"
    aria-label="{{ $label }}" title="{{ $label }}"
    @if ($confirm) data-confirm="{{ $confirm }}" onclick="return confirm(@js($confirm))" @endif
    {{ $attributes }}>
    <x-icon :name="$icon" :size="16" />
</{{ $tag }}>
