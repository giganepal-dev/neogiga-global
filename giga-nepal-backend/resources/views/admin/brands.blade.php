@extends('admin.layout')
@section('title','Brands')
@section('crumb','Catalog / Brand Manager')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Total brands</div><div class="v tnum">{{ number_format($brands->total()) }}</div><div class="s">with products</div></div>
    <div class="kpi"><div class="t">Active</div><div class="v tnum">{{ number_format($brands->filter(fn($b) => $b->is_active)->count()) }}</div><div class="s">on page</div></div>
    <div class="kpi"><div class="t">Featured</div><div class="v tnum">{{ number_format($brands->filter(fn($b) => $b->is_featured)->count()) }}</div><div class="s">on page</div></div>
    <div class="kpi"><div class="t">Page</div><div class="v">{{ $brands->currentPage() }} / {{ $brands->lastPage() }}</div><div class="s">50 per page</div></div>
</div>

<section class="card">
    <div class="card-h"><div><h2>Brand Manager</h2><div class="sub">Search, review, and manage product brands</div></div><a class="btn btn-ghost" href="/admin/brand-logos">Logo Inventory</a></div>
    <form class="filters" method="get">
        <input class="control" name="q" value="{{ $q }}" placeholder="Search brand name">
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>
    @if ($brands->isEmpty())
        <div class="empty"><h3>No brands found</h3><p>Import products with brand data to populate this list.</p></div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Brand</th>
                        <th>Slug</th>
                        <th>Website</th>
                        <th>Products</th>
                        <th>Active</th>
                        <th>Featured</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($brands as $brand)
                        <tr>
                            <td><a href="/admin/brands/{{ $brand->id }}" style="color:var(--cyan);font-weight:600">{{ $brand->name }}</a></td>
                            <td class="mono" style="font-size:.78rem;color:var(--muted)">{{ $brand->slug }}</td>
                            <td>
                                @if ($brand->website_url)
                                    <a href="{{ $brand->website_url }}" target="_blank" rel="noopener" style="color:var(--cyan);font-size:.8rem">{{ parse_url($brand->website_url, PHP_URL_HOST) }}</a>
                                @else
                                    <span style="color:var(--muted)">—</span>
                                @endif
                            </td>
                            <td class="tnum">{{ number_format($brand->product_count) }}</td>
                            <td>
                                <span class="badge {{ $brand->is_active ? 'b-ok' : 'b-muted' }}">{{ $brand->is_active ? 'Active' : 'Hidden' }}</span>
                            </td>
                            <td>
                                @if ($brand->is_featured)
                                    <span class="badge b-info">Featured</span>
                                @else
                                    <span class="badge b-muted">—</span>
                                @endif
                            </td>
                            <td class="mono" style="font-size:.72rem;color:var(--muted)">{{ $brand->updated_at ? \Carbon\Carbon::parse($brand->updated_at)->diffForHumans() : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:16px">{{ $brands->links() }}</div>
    @endif
</section>
@endsection
