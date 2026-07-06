@extends('frontend.layout')
@section('title','All Categories — NeoGiga Engineering Marketplace')
@section('description','Browse '.number_format($total).' engineering categories: semiconductors, electronics, IoT, robotics, batteries, power, automation and tools.')

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>url('/')],
        ['@type'=>'ListItem','position'=>2,'name'=>'Categories','item'=>url('/categories')],
    ],
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a><span>/</span><strong style="color:var(--soft)">Categories</strong></nav>

<h1>Browse by category</h1>
<p class="lead">Explore {{ number_format($total) }} categories across the engineering supply chain — from silicon to finished robotics. Every branch links straight into the NeoGiga catalog.</p>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
    @foreach ($roots as $root)
        <section style="background:rgba(13,34,64,.55);border:1px solid var(--line);border-radius:var(--r);padding:18px">
            <a href="/categories/{{ $root->slug }}" style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                <strong style="font-size:1.05rem">{{ $root->name }}</strong>
                <span aria-hidden="true" style="color:var(--cyan)">→</span>
            </a>
            @if ($root->children->isNotEmpty())
                <ul style="list-style:none;margin:12px 0 0;padding:0;display:flex;flex-wrap:wrap;gap:6px">
                    @foreach ($root->children->take(7) as $child)
                        <li><a href="/categories/{{ $child->slug }}" style="display:inline-block;padding:4px 10px;border:1px solid var(--line);border-radius:999px;font-size:.8rem;color:var(--muted)">{{ $child->name }}</a></li>
                    @endforeach
                    @if ($root->children->count() > 7)
                        <li><a href="/categories/{{ $root->slug }}" style="display:inline-block;padding:4px 10px;font-size:.8rem;color:var(--cyan)">+{{ $root->children->count() - 7 }} more</a></li>
                    @endif
                </ul>
            @endif
        </section>
    @endforeach
</div>
@endsection
