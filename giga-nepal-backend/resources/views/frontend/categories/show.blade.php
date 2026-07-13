@extends('frontend.layout')
@php
    $seo = $category->seo_meta ?? [];
    $metaTitle = $seo['title'] ?? ($category->name.' — NeoGiga');
    $metaDesc = $seo['description'] ?? ('Shop '.$category->name.' on NeoGiga — genuine parts, regional stock and engineering support.');
@endphp
@section('title', $metaTitle)
@section('description', \Illuminate\Support\Str::limit(strip_tags($metaDesc), 158))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($breadcrumb)->values()->map(fn($b,$i)=>[
        '@type'=>'ListItem','position'=>$i+1,'name'=>$b['name'],'item'=>$b['url'],
    ])->all(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<nav class="crumbs" aria-label="Breadcrumb">
    @foreach ($breadcrumb as $i => $b)
        @if ($i === count($breadcrumb)-1)
            <strong style="color:var(--soft)">{{ $b['name'] }}</strong>
        @else
            <a href="{{ $b['url'] }}">{{ $b['name'] }}</a><span>/</span>
        @endif
    @endforeach
</nav>

<div class="category-hero" style="display:flex;align-items:center;gap:18px;margin-bottom:12px">
    @if ($category->image_path)
        <img src="{{ $category->image_path }}" alt="" width="112" height="112" loading="lazy" style="width:112px;height:112px;object-fit:contain;border:1px solid var(--line);border-radius:12px;background:#fff;flex:none">
    @endif
    <div><h1 style="margin:0 0 8px">{{ $category->name }}</h1><p class="lead" style="margin:0">{{ $metaDesc }}</p></div>
</div>

@if ($children->isNotEmpty())
    <h2 style="font-size:1.05rem;margin:8px 0 12px;color:var(--soft)">Subcategories</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:34px">
        @foreach ($children as $child)
            <a href="/categories/{{ $child->slug }}" style="display:flex;align-items:center;justify-content:space-between;gap:10px;background:rgba(13,34,64,.5);border:1px solid var(--line);border-radius:10px;padding:14px 16px">
                <span>{{ $child->name }}</span>
                <span aria-hidden="true" style="color:var(--cyan)">→</span>
            </a>
        @endforeach
    </div>
@endif

<h2 style="font-size:1.05rem;margin:8px 0 12px;color:var(--soft)">Products in {{ $category->name }}</h2>
@if ($products->isEmpty())
    <div style="background:rgba(13,34,64,.5);border:1px solid var(--line);border-radius:var(--r);padding:40px 22px;text-align:center;color:var(--muted)">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="none" style="opacity:.6;margin-bottom:8px"><path d="M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8" stroke="#5b7a99" stroke-width="1.4" stroke-linejoin="round"/></svg>
        <h3 style="color:var(--soft);margin:0 0 4px">Catalog coming soon</h3>
        <p style="margin:0 auto;max-width:44ch">Products for this category are being onboarded. Meanwhile, try the <a href="/#ai" style="color:var(--cyan)">AI BOM Builder</a> or <a href="/#vendors" style="color:var(--cyan)">list your products</a> as a vendor.</p>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px">
        @foreach ($products as $p)
            <article style="background:rgba(13,34,64,.55);border:1px solid var(--line);border-radius:var(--r);padding:16px">
                <strong>{{ $p->name }}</strong>
                <div style="color:var(--muted);font-size:.82rem" class="mono">{{ $p->sku }}</div>
            </article>
        @endforeach
    </div>
@endif
@endsection
