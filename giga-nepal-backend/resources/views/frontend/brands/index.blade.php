@extends('frontend.layout')
@section('title', 'Brands - NeoGiga Engineering Marketplace')
@section('description', 'Browse active engineering manufacturers and brands available in this NeoGiga marketplace.')
@section('content')
@php($publicBase = '/'.(array_key_exists(strtolower((string) request()->segment(1)), config('neogiga_global.prefixes', [])) ? strtolower((string) request()->segment(1)) : config('neogiga_global.default_prefix', 'en')))
<div class="wrap section">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ $publicBase }}">Home</a> › <span>Brands</span></nav>
    <div class="section-head"><div><p class="eyebrow">Manufacturers</p><h1 class="section-title">Brands available in this marketplace</h1></div></div>
    @if($brands->isNotEmpty())<div class="category-grid">@foreach($brands as $brand)<a class="category-card" href="{{ $publicBase }}/brands/{{ $brand->slug }}">@if($brand->logo_path)<img src="{{ $brand->logo_path }}" alt="{{ $brand->name }} logo" style="height:42px;width:auto;object-fit:contain;margin-bottom:12px">@else<div class="cat-icon">{{ strtoupper(substr($brand->name, 0, 1)) }}</div>@endif<h2>{{ $brand->name }}</h2><p class="sub">{{ $brand->short_description ?: 'Explore products, specifications and RFQ options.' }}</p></a>@endforeach</div>@else<div class="panel" style="padding:32px"><h1 class="section-title">Brands are being configured</h1><p class="sub">Browse the catalog or request a sourced part through RFQ.</p><a class="btn btn-primary" href="{{ $publicBase }}/products">Browse products</a></div>@endif
</div>
@endsection
