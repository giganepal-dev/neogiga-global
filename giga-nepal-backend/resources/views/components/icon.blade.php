@props([
    'name',           // registry key, e.g. "cart"
    'size' => 20,     // px (standard button 18-20, nav 20-24, tables/badges 16)
    'stroke' => 1.8,  // consistent Lucide stroke weight
    'label' => null,  // accessible name; when null the icon is decorative
])

@php
    $paths = config('icons.map.'.$name);
    $viewbox = config('icons.viewbox', '0 0 24 24');
@endphp

@if ($paths)
    <svg {{ $attributes->merge(['class' => 'ng-icon']) }}
        width="{{ $size }}" height="{{ $size }}" viewBox="{{ $viewbox }}"
        fill="none" stroke="currentColor" stroke-width="{{ $stroke }}"
        stroke-linecap="round" stroke-linejoin="round"
        @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif
        style="flex:none;vertical-align:middle;{{ $attributes->get('style') }}"
    >@if ($label)<title>{{ $label }}</title>@endif{!! $paths !!}</svg>
@else
    {{-- Unknown icon "{{ $name }}" — add it to config/icons.php --}}
@endif
