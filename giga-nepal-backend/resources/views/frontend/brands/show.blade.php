@extends('frontend.layout')
@section('title', $brand->seo_title ?: $brand->name.' Products - NeoGiga')
@section('description', $brand->seo_description ?: ($brand->short_description ?: 'Explore '.$brand->name.' products on NeoGiga.'))
@section('content')
@php($publicBase = '/'.(array_key_exists(strtolower((string) request()->segment(1)), config('neogiga_global.prefixes', [])) ? strtolower((string) request()->segment(1)) : config('neogiga_global.default_prefix', 'en')))
<div class="wrap section">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ $publicBase }}">Home</a> › <a href="{{ $publicBase }}/brands">Brands</a> › <span>{{ $brand->name }}</span></nav>
    <section class="panel" style="padding:28px;margin:20px 0 28px">@if($brand->banner_path)<img src="{{ $brand->banner_path }}" alt="{{ $brand->name }}" style="width:100%;max-height:260px;object-fit:cover;border-radius:10px;margin-bottom:20px">@endif<div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">@if($brand->logo_path)<img src="{{ $brand->logo_path }}" alt="{{ $brand->name }} logo" style="max-width:150px;max-height:72px;object-fit:contain">@endif<div><p class="eyebrow">Brand catalog</p><h1 class="section-title">{{ $brand->name }}</h1><p class="sub">{{ $brand->description ?: $brand->short_description }}</p>@if($brand->website_url)<a class="btn btn-ghost" href="{{ $brand->website_url }}" rel="nofollow noopener" target="_blank">Manufacturer site</a>@endif</div></div></section>
    <div class="section-head"><h2>Products</h2><a class="btn btn-ghost" href="{{ $publicBase }}/products?brand_id={{ $brand->id }}">View filtered catalog</a></div>
    @if($products->isNotEmpty())<div class="category-grid">@foreach($products as $product)<a class="product-card" href="{{ $publicBase }}/products/{{ $product->slug }}"><span class="badge b-info">{{ $product->mpn ?: $product->sku }}</span><h2>{{ $product->name }}</h2><p class="sub">{{ $product->category?->name }}{{ $product->short_description ? ' · '.$product->short_description : '' }}</p></a>@endforeach</div>@else<div class="panel" style="padding:28px"><p class="sub">No public products are currently listed for this brand in this marketplace.</p></div>@endif
    @if($products->hasPages())<div style="margin-top:24px">{{ $products->links() }}</div>@endif
</div>
@endsection
