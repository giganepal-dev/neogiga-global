@props([
    'icon',
    'label',
    'href',
    'active' => false,
    'group' => null,
    'method' => 'GET',
])

@if($group && !isset($seenGroups[$group]))
    @php $seenGroups[$group] = true; @endphp
    <div class="nav-group" data-group="{{ $group }}">
        <div class="nav-group-header">
            <span class="nav-group-label">{{ $group }}</span>
        </div>
        <div class="nav-group-items">
@endif

@if($group)
    <a href="{{ $href }}"
       class="ng-navitem{{ $active ? ' is-active' : '' }}"
       @if ($active) aria-current="page" @endif>
        <x-icon :name="$icon" :size="20" />
        <span class="ng-navitem__lbl">{{ $label }}</span>
    </a>
@else
    <a href="{{ $href }}"
       class="ng-navitem{{ $active ? ' is-active' : '' }}"
       @if ($active) aria-current="page" @endif
       {{ $attributes }}>
        <x-icon :name="$icon" :size="20" />
        <span class="ng-navitem__lbl">{{ $label }}</span>
    </a>
@endif

@if($group && ($loop->last || (isset($portal['nav'][$loop->index + 1]) && ($portal['nav'][$loop->index + 1]['group'] ?? null) !== $group)))
        </div>
    </div>
@endif
