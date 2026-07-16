@props([
    'icon',
    'label',
    'href',
    'active' => false,
])

<a href="{{ $href }}"
   class="ng-navitem{{ $active ? ' is-active' : '' }}"
   @if ($active) aria-current="page" @endif
   {{ $attributes }}>
    <x-icon :name="$icon" :size="20" />
    <span class="ng-navitem__lbl">{{ $label }}</span>
</a>
