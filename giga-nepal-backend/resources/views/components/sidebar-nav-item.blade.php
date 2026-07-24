@props([
    'icon',
    'label',
    'href',
    'active' => false,
    'group' => null,
    'method' => 'GET',
    'loop' => null,
    'portal' => [],
    'isFirstInGroup' => false,
    'isLastInGroup' => false,
])

@if($group && $isFirstInGroup)
    <div class="nav-group" data-group="{{ $group }}">
        <button type="button" class="nav-group-header" onclick="this.parentElement.classList.toggle('is-collapsed')">
            <span class="nav-group-label">{{ $group }}</span>
            <svg class="nav-group-toggle" width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="nav-group-items">
@endif

@if($group)
    <a href="{{ $href }}"
       class="ng-navitem{{ $active ? ' is-active' : '' }}"
       @if ($active) aria-current="page" @endif
       @if($method !== 'GET') onclick="event.preventDefault(); this.closest('form')?.submit() || fetch('{{ $href }}', {method: '{{ $method }}', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content}})" @endif>
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

@if($group && $isLastInGroup)
        </div>
    </div>
@endif
