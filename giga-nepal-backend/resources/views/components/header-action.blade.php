@props([
    'icon',
    'label',
    'href' => '#',
    'count' => null,      // optional badge (cart/wishlist/compare)
    'active' => false,
    'iconSize' => 20,
    'showLabel' => true,  // desktop: icon + label; compact: icon only (label stays accessible)
])

<a href="{{ $href }}"
   class="ng-haction{{ $active ? ' is-active' : '' }}"
   aria-label="{{ $label }}{{ !is_null($count) ? ' ('.$count.')' : '' }}"
   title="{{ $label }}"
   @if ($active) aria-current="page" @endif
   {{ $attributes }}>
    <span class="ng-haction__ico">
        <x-icon :name="$icon" :size="$iconSize" />
        @if (!is_null($count))
            <span class="ng-haction__badge" aria-hidden="true">{{ $count }}</span>
        @endif
    </span>
    @if ($showLabel)
        <span class="ng-haction__lbl">{{ $label }}</span>
    @endif
</a>
