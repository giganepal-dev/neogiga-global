@extends('admin.layout')
@section('title','Brand: '.$b->name)
@section('crumb','Catalog / Brands / '.$b->name)
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Products</div><div class="v tnum">{{ number_format($productCount) }}</div><div class="s">using this brand</div></div>
    <div class="kpi"><div class="t">Status</div><div class="v tnum"><span class="badge {{ $b->is_active ? 'b-ok' : 'b-muted' }}">{{ $b->is_active ? 'Active' : 'Hidden' }}</span></div><div class="s">{{ $b->is_featured ? 'Featured' : 'Standard' }}</div></div>
    <div class="kpi"><div class="t">Slug</div><div class="v mono tnum" style="font-size:.8rem">{{ $b->slug }}</div><div class="s">URL identifier</div></div>
    <div class="kpi"><div class="t">Menu</div><div class="v tnum"><span class="badge {{ $b->is_menu_visible ? 'b-info' : 'b-muted' }}">{{ $b->is_menu_visible ? 'Visible' : 'Hidden' }}</span></div><div class="s">in navigation</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
    {{-- Brand Info --}}
    <section class="card" style="padding:20px">
        <div class="card-h"><h2>Brand Information</h2></div>
        <form class="form-stack" method="post" action="/admin/brands/{{ $b->id }}" enctype="multipart/form-data">
            @csrf
            <div class="field"><label>Name</label><input class="control" name="name" value="{{ old('name', $b->name) }}" required></div>
            <div class="field"><label>Website</label><input class="control" name="website_url" value="{{ old('website_url', $b->website_url) }}" placeholder="https://..."></div>
            <div class="field"><label>Short Description</label><textarea class="control" name="description" rows="3">{{ old('description', $b->description) }}</textarea></div>

            <div class="field"><label>Official Brand Logo</label>
                @if ($b->logo_path)
                    <div style="margin-bottom:8px"><img src="{{ asset('storage/'.$b->logo_path) }}" alt="{{ $b->name }} logo" style="max-width:160px;max-height:80px;object-fit:contain;background:#081527;border-radius:8px;border:1px solid var(--line);padding:8px"></div>
                @endif
                <input class="control" type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                <div class="sub">PNG, JPEG, WebP, or SVG. Upload directly — no API needed.</div>
            </div>

            <div class="form-grid">
                <label><input type="checkbox" name="is_active" value="1" {{ $b->is_active ? 'checked' : '' }}> Active</label>
                <label><input type="checkbox" name="is_featured" value="1" {{ $b->is_featured ? 'checked' : '' }}> Featured</label>
                <label><input type="checkbox" name="is_menu_visible" value="1" {{ $b->is_menu_visible ? 'checked' : '' }}> Show in Menu</label>
            </div>

            <button class="btn btn-primary" type="submit">Save Brand</button>
        </form>
    </section>

    {{-- SEO --}}
    <section class="card" style="padding:20px">
        <div class="card-h"><h2>SEO Metadata</h2><span class="badge b-info">Search engines</span></div>
        <form class="form-stack" method="post" action="/admin/brands/{{ $b->id }}">
            @csrf
            <div class="field"><label>SEO Title</label><input class="control" name="seo_title" value="{{ old('seo_title', $seo['title'] ?? '') }}" placeholder="Buy {Brand} products online | NeoGiga"><div class="sub">Appears in search results and browser tabs</div></div>
            <div class="field"><label>Meta Description</label><textarea class="control" name="seo_description" rows="2" maxlength="158" placeholder="Shop authentic {Brand} components...">{{ old('seo_description', $seo['description'] ?? '') }}</textarea><div class="sub">Max 158 characters for search snippet</div></div>
            <div class="field"><label>SEO Keywords</label><input class="control" name="seo_keywords" value="{{ old('seo_keywords', $seo['keywords'] ?? '') }}" placeholder="brand, components, electronics"><div class="sub">Comma-separated</div></div>

            <input type="hidden" name="name" value="{{ $b->name }}">
            <input type="hidden" name="description" value="{{ $b->description }}">
            <input type="hidden" name="website_url" value="{{ $b->website_url }}">
            <input type="hidden" name="is_active" value="{{ $b->is_active ? 1 : 0 }}">
            <input type="hidden" name="is_featured" value="{{ $b->is_featured ? 1 : 0 }}">
            <input type="hidden" name="is_menu_visible" value="{{ $b->is_menu_visible ? 1 : 0 }}">

            <button class="btn btn-primary" type="submit">Save SEO</button>
        </form>
    </section>
</div>

<div style="margin-top:16px">
    <a class="btn btn-ghost" href="/admin/products?brand_id={{ $b->id }}">View {{ number_format($productCount) }} Products →</a>
    <a class="btn btn-ghost" href="/admin/brands">← Back to Brands</a>
</div>
@endsection
