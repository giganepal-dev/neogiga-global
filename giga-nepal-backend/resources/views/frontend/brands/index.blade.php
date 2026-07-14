@extends('frontend.layout')
@section('title', 'All Brands — NeoGiga Engineering Marketplace')
@section('description', 'Browse '.number_format($brands->total()).' electronic component brands on NeoGiga. Find authorized distributors for Arduino, Espressif, Texas Instruments, STMicroelectronics and more.')
@php
    $activePrefix = strtolower((string) request()->segment(1));
    $activePrefix = array_key_exists($activePrefix, config('neogiga_global.prefixes', []))
        ? $activePrefix
        : config('neogiga_global.default_prefix', 'en');
    $publicBase = '/'.$activePrefix;
@endphp

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>url($publicBase)],
        ['@type'=>'ListItem','position'=>2,'name'=>'Brands','item'=>url($publicBase.'/brands')],
    ],
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="{{ $publicBase }}">Home</a><span>/</span><strong style="color:var(--soft)">Brands</strong></nav>

<h1>Browse by Brand</h1>
<p class="lead">Discover {{ number_format($brands->total()) }} trusted brands in electronics, semiconductors, IoT, robotics and engineering components. Shop from authorized distributors with regional availability.</p>

{{-- Search and filters --}}
<form method="GET" action="{{ url($publicBase.'/brands') }}" style="margin-bottom:24px;display:flex;gap:12px;flex-wrap:wrap">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search brands..." aria-label="Search brands" style="flex:1;min-width:200px;padding:10px 14px;border:1px solid var(--line);border-radius:var(--r);background:rgba(13,34,64,.55);color:#fff">
    <label style="display:flex;align-items:center;gap:8px;font-size:.9rem;color:var(--muted)">
        <input type="checkbox" name="featured" value="1" @checked(request('featured') === '1') style="accent-color:var(--cyan)">
        Featured only
    </label>
    <button type="submit" class="btn btn-primary">Filter</button>
</form>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
    @forelse ($brands as $brand)
        <section style="background:rgba(13,34,64,.55);border:1px solid var(--line);border-radius:var(--r);padding:18px;transition:transform .15s ease,border-color .15s ease">
            <a href="{{ $publicBase }}/brands/{{ $brand->slug }}" style="display:block;text-decoration:none">
                @if($brand->logo_path)
                    <img src="{{ asset($brand->logo_path) }}" alt="{{ $brand->name }}" style="width:80px;height:80px;object-fit:contain;margin-bottom:12px">
                @else
                    <div style="width:80px;height:80px;display:grid;place-items:center;background:rgba(40,216,251,.08);border:1px solid rgba(40,216,251,.3);border-radius:var(--r);margin-bottom:12px;color:var(--cyan);font-weight:700;font-size:1.5rem">{{ substr($brand->name, 0, 2) }}</div>
                @endif
                <h3 style="font-size:1.1rem;margin:0 0 6px;color:#fff">{{ $brand->name }}</h3>
                @if($brand->country)
                    <p style="font-size:.85rem;color:var(--muted);margin:0 0 8px">{{ $brand->country->name }}</p>
                @endif
                @if($brand->description)
                    <p style="font-size:.9rem;color:var(--muted);margin:0;line-clamp:2;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ Str::limit($brand->description, 100) }}</p>
                @endif
                @if($brand->is_featured)
                    <span style="display:inline-block;margin-top:10px;padding:4px 10px;background:rgba(249,189,44,.15);border:1px solid rgba(249,189,44,.4);border-radius:999px;font-size:.75rem;color:var(--gold);font-weight:600">Featured</span>
                @endif
            </a>
        </section>
    @empty
        <div style="grid-column:1/-1;text-align:center;padding:60px 20px">
            <p style="font-size:1.2rem;color:var(--muted)">No brands found matching your criteria.</p>
            <a href="{{ url($publicBase.'/brands') }}" class="btn btn-ghost" style="margin-top:16px">Clear filters</a>
        </div>
    @endforelse
</div>

{{-- Pagination --}}
@if ($brands->hasPages())
    <div style="margin-top:32px;display:flex;justify-content:center">
        {{ $brands->links('vendor.pagination.tailwind') }}
    </div>
@endif
@endsection
