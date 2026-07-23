@extends('admin.layout')
@section('title','Brand Logos')
@section('crumb','Catalog / Brand Logo Manager')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Total brands</div><div class="v tnum">{{ number_format($brands->total()) }}</div><div class="s">with logo tracking</div></div>
    <div class="kpi"><div class="t">With logos</div><div class="v tnum">{{ number_format($brands->filter(fn($b) => !empty($b->logo_url))->count()) }}</div><div class="s">have logo file</div></div>
    <div class="kpi"><div class="t">Missing logos</div><div class="v tnum">{{ number_format($brands->filter(fn($b) => empty($b->logo_url))->count()) }}</div><div class="s">need attention</div></div>
    <div class="kpi"><div class="t">Page</div><div class="v">{{ $brands->currentPage() }} / {{ $brands->lastPage() }}</div><div class="s">50 per page</div></div>
</div>

<section class="card">
    <div class="card-h"><div><h2>Brand Logo Inventory</h2><div class="sub">Product brands with logo URLs, verification status, and product counts</div></div></div>
    @if ($brands->isEmpty())
        <div class="empty"><h3>No brands found</h3><p>Import products with brand data to populate this list.</p></div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Brand</th>
                        <th>Slug</th>
                        <th>Website</th>
                        <th>Source</th>
                        <th>Verified</th>
                        <th>Products</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($brands as $brand)
                        <tr>
                            <td>
                                @if ($brand->logo_url)
                                    <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }} logo" width="40" height="40" style="width:40px;height:40px;object-fit:contain;background:#081527;border-radius:6px;border:1px solid var(--line)">
                                @else
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;background:rgba(13,34,64,.5);border-radius:6px;border:1px solid var(--line);color:var(--muted);font-size:.7rem">—</span>
                                @endif
                            </td>
                            <td><strong>{{ $brand->name }}</strong></td>
                            <td class="mono" style="font-size:.78rem;color:var(--muted)">{{ $brand->slug }}</td>
                            <td>
                                @if ($brand->website_url)
                                    <a href="{{ $brand->website_url }}" target="_blank" rel="noopener" style="color:var(--cyan);font-size:.8rem">{{ parse_url($brand->website_url, PHP_URL_HOST) }}</a>
                                @else
                                    <span style="color:var(--muted)">—</span>
                                @endif
                            </td>
                            <td><span class="badge b-muted">manual</span></td>
                            <td><span class="badge b-muted">unverified</span></td>
                            <td class="tnum">{{ number_format($brand->product_count) }}</td>
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
