@extends('frontend.layout')
@section('title', ($category->name ?? 'Products').' — NeoGiga Engineering Marketplace')
@section('description','Browse engineering components and tools: semiconductors, electronics, IoT, robotics, batteries, power and automation. Search by part number, SKU or keyword.')

@section('content')
<style>
    .plist-head{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;margin:24px 0 16px}
    .plist-head h1{margin:0;font-size:1.5rem}
    .psearch{display:flex;gap:8px;flex-wrap:wrap}
    .psearch input,.psearch select{padding:10px 12px;border:1px solid rgba(15,23,42,.2);border-radius:9px;font:inherit;min-width:200px}
    .pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin:16px 0 32px}
    .pcard{border:1px solid rgba(15,23,42,.12);border-radius:12px;padding:18px;background:#fff;display:flex;flex-direction:column;gap:8px}
    .pcard h2{font-size:1rem;margin:0;line-height:1.35}
    .pcard a{color:#0F172A;text-decoration:none}.pcard a:hover{color:#0369A1}
    .pmeta{color:#64748B;font-size:.82rem}
    .ptag{display:inline-block;background:#ECFEFF;color:#155E75;border-radius:999px;padding:2px 10px;font-size:.75rem;font-weight:600}
    .pstock{font-size:.8rem;font-weight:600}.in{color:#065F46}.out{color:#991B1B}
    .pempty{border:1px dashed rgba(15,23,42,.2);border-radius:12px;padding:48px;text-align:center;color:#475569;margin:24px 0}
</style>

<div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="/">Home</a> › <span>Products</span>@if($category) › <span>{{ $category->name }}</span>@endif</nav>

    <div class="plist-head">
        <h1>{{ $category->name ?? 'All Products' }}</h1>
        <form class="psearch" method="get" action="/products">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search part number, SKU, keyword…" aria-label="Search products">
            <select name="category" aria-label="Category">
                <option value="">All categories</option>
                @foreach ($rootCategories as $c)
                    <option value="{{ $c->slug }}" @selected(($category->slug ?? '') === $c->slug)>{{ $c->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
    </div>

    @if ($products->count())
        <div class="pmeta">{{ number_format($products->total()) }} product(s)</div>
        <div class="pgrid">
            @foreach ($products as $p)
                <div class="pcard">
                    @if($p->brand)<span class="ptag">{{ $p->brand->name }}</span>@endif
                    <h2><a href="/products/{{ $p->slug }}">{{ $p->name }}</a></h2>
                    <div class="pmeta">
                        @if($p->mpn)MPN: <strong>{{ $p->mpn }}</strong> · @endif
                        SKU: {{ $p->sku ?? '—' }}
                        @if($p->category) · {{ $p->category->name }}@endif
                    </div>
                    @if($p->track_inventory)
                        <span class="pstock {{ $p->stock_quantity > 0 ? 'in' : 'out' }}">{{ $p->stock_quantity > 0 ? 'In stock' : 'Out of stock' }}</span>
                    @endif
                    <div style="margin-top:auto"><a class="btn btn-ghost" href="/products/{{ $p->slug }}">View specs &amp; RFQ</a></div>
                </div>
            @endforeach
        </div>
        @if ($products->hasPages())<div style="margin:16px 0">{{ $products->links() }}</div>@endif
    @else
        <div class="pempty">
            <h2>No products match yet</h2>
            <p>The catalog is being loaded. Meanwhile, browse <a href="/categories">all categories</a> or <a href="/sell-on-neogiga">sell on NeoGiga</a>.</p>
        </div>
    @endif
</div>
@endsection
