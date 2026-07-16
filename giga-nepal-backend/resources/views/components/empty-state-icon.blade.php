@props([
    'icon' => 'inventory',
    'title' => 'Nothing here yet',
    'message' => null,
])

<div class="ng-empty" role="status" {{ $attributes }}>
    <span class="ng-empty__ico"><x-icon :name="$icon" :size="40" :stroke="1.4" /></span>
    <p class="ng-empty__title">{{ $title }}</p>
    @if ($message)
        <p class="ng-empty__msg">{{ $message }}</p>
    @endif
    {{ $slot }}
</div>
