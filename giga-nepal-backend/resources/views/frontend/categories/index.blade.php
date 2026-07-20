@extends('frontend.layout')
@section('title','All Categories — NeoGiga Engineering Marketplace')
@section('description','Browse NeoGiga engineering product families: semiconductors, electronics, IoT, robotics, batteries, power, automation and tools.')
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
        ['@type'=>'ListItem','position'=>2,'name'=>'Categories','item'=>url($publicBase.'/categories')],
    ],
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'NeoGiga engineering categories',
    'numberOfItems' => $roots->count(),
    'itemListElement' => $roots->values()->map(fn($category, $index) => [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $category->name,
        'url' => url($publicBase.'/categories/'.$category->slug),
        'image' => $category->image_path ? url($category->image_path) : url('/images/brand/neogiga-icon-512.png'),
    ])->all(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
<style>
    .catalog-category-page{padding:30px 0 72px}
    .category-directory-heading{max-width:760px;margin-bottom:30px}
    .category-directory-heading h1{display:flex;align-items:center;gap:10px;margin:0 0 10px;color:var(--on);font-size:clamp(1.9rem,3vw,2.7rem);line-height:1.15}
    .category-directory-heading .lead{margin:0;color:var(--muted);max-width:68ch}
    .category-directory{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:16px}
    .category-directory-card{display:flex;flex-direction:column;min-height:178px;padding:18px;background:#fff;border:1px solid var(--line);border-radius:8px;box-shadow:0 8px 22px rgba(23,43,77,.05);transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease}
    .category-directory-card:hover{border-color:rgba(15,98,230,.46);box-shadow:0 14px 30px rgba(23,43,77,.11);transform:translateY(-2px)}
    .category-directory-head{display:flex;align-items:center;justify-content:space-between;gap:12px;color:var(--on)}
    .category-directory-title{display:flex;align-items:center;gap:11px;min-width:0;font-weight:750;font-size:1rem;line-height:1.35}
    .category-directory-title span:last-child{overflow-wrap:anywhere}
    .category-directory-icon{width:42px;height:42px;display:grid;place-items:center;flex:none;border:1px solid #c6dafc;border-radius:8px;background:#eaf2ff;color:var(--cyan)}
    .category-directory-icon img{width:100%;height:100%;object-fit:contain;border-radius:7px;background:#fff}
    .category-directory-arrow{display:grid;place-items:center;width:28px;height:28px;flex:none;border-radius:50%;background:#f1f6ff;color:var(--cyan)}
    .category-directory-links{display:flex;flex-wrap:wrap;gap:7px;margin:16px 0 0;padding:0;list-style:none}
    .category-directory-links a{display:inline-flex;align-items:center;min-height:28px;padding:4px 9px;border:1px solid #d7e2ef;border-radius:6px;background:#f8fafc;color:#40536a;font-size:.78rem;line-height:1.25;transition:border-color .15s ease,color .15s ease,background .15s ease}
    .category-directory-links a:hover{border-color:rgba(15,98,230,.45);background:#eaf2ff;color:var(--cyan)}
    .category-directory-links .more-link{border-color:transparent;background:transparent;color:var(--cyan);font-weight:700}
    @media(max-width:620px){.catalog-category-page{padding:22px 0 54px}.category-directory{grid-template-columns:1fr;gap:12px}.category-directory-card{min-height:0}.category-directory-heading{margin-bottom:22px}}
</style>
@endpush

@section('content')
<section class="catalog-category-page">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ $publicBase }}">Home</a><span><x-icon name="chevron-right" size="12"/></span><strong>Categories</strong></nav>
    <header class="category-directory-heading">
        <h1><x-icon name="categories" size="24"/> Browse by category</h1>
        <p class="lead">Explore {{ number_format($total) }} curated product families across the engineering supply chain, from silicon to finished robotics. Every branch links straight into the NeoGiga catalog.</p>
        <form method="get" action="{{ request()->url() }}" style="display:flex;gap:8px;margin-top:12px;max-width:480px">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search categories (e.g. microcontroller, sensor, battery)" aria-label="Search categories" style="flex:1;padding:10px 14px;border:1px solid var(--line);border-radius:10px;background:var(--s1);color:var(--on);font:inherit">
            <button type="submit" class="btn btn-ghost"><x-icon name="search" size="16"/> Search</button>
        </form>
    </header>

    <div class="category-directory">
        @foreach ($roots as $root)
            <section class="category-directory-card">
                <a class="category-directory-head" href="{{ $publicBase }}/categories/{{ $root->slug }}">
                    <span class="category-directory-title">
                        <span class="category-directory-icon" aria-hidden="true">
                            @if ($root->image_path)
                                <img src="{{ $root->image_path }}" alt="" width="42" height="42" loading="lazy">
                            @else
                                <x-icon name="categories" size="20"/>
                            @endif
                        </span>
                        <span>{{ $root->name }}</span>
                    </span>
                    <span class="category-directory-arrow" aria-hidden="true"><x-icon name="chevron-right" size="16"/></span>
                </a>
                @if ($root->children->isNotEmpty())
                    <ul class="category-directory-links">
                        @foreach ($root->children->take(7) as $child)
                            <li><a href="{{ $publicBase }}/categories/{{ $child->slug }}">{{ $child->name }}</a></li>
                        @endforeach
                        @if ($root->children->count() > 7)
                            <li><a class="more-link" href="{{ $publicBase }}/categories/{{ $root->slug }}">+{{ $root->children->count() - 7 }} more</a></li>
                        @endif
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
</section>
@endsection
