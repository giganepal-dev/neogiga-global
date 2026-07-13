@extends('admin.layout')

@section('title', 'Brands')
@section('crumb', 'Catalog / Storefront brand menu')

@section('page_actions')
<details class="modal">
    <summary class="btn btn-primary">Add Brand</summary>
    <div class="modal-panel modal-wide">
        <div class="modal-h"><h3>Create Brand</h3><span class="badge b-info">catalog-owned</span></div>
        @include('admin.partials.brand-form', ['brand' => null])
    </div>
</details>
@endsection

@section('content')
<section class="card">
    <div class="card-h"><div><h2>Brand Directory</h2><div class="sub">Control menu, marketplace, country and publication visibility.</div></div><span class="badge b-info">{{ number_format($brands->total()) }} brands</span></div>
    <form class="filters" method="get">
        <input class="control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search brand or slug">
        <select class="control" name="status"><option value="">All states</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option></select>
        <select class="control" name="menu"><option value="">All menu states</option><option value="visible" @selected(($filters['menu'] ?? '') === 'visible')>Menu visible</option><option value="hidden" @selected(($filters['menu'] ?? '') === 'hidden')>Menu hidden</option></select>
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>
    <div class="scroll-x"><table class="tbl"><thead><tr><th>Brand</th><th>Coverage</th><th>Products</th><th>Display</th><th>Publication</th><th>Actions</th></tr></thead><tbody>
        @forelse($brands as $brand)
            <tr>
                <td><strong>{{ $brand->name }}</strong><div class="sub mono">{{ $brand->slug }}</div></td>
                <td><div class="sub">{{ collect($brand->marketplace_visibility)->count() ?: 'All' }} marketplaces</div><div class="sub">{{ collect($brand->country_visibility)->count() ?: 'All' }} countries</div></td>
                <td class="tnum">{{ number_format($brand->products_count) }}</td>
                <td><span class="badge {{ $brand->is_active ? 'b-ok' : 'b-muted' }}">{{ $brand->is_active ? 'Active' : 'Inactive' }}</span> <span class="badge {{ $brand->is_menu_visible ? 'b-info' : 'b-muted' }}">{{ $brand->is_menu_visible ? 'Menu' : 'Hidden' }}</span><div class="sub">{{ $brand->display_desktop ? 'Desktop' : '' }} {{ $brand->display_mobile ? 'Mobile' : '' }}</div></td>
                <td class="sub">{{ $brand->publication_starts_at?->format('Y-m-d') ?: 'Now' }} - {{ $brand->publication_ends_at?->format('Y-m-d') ?: 'Open' }}</td>
                <td class="actions">
                    <details class="modal"><summary class="btn btn-ghost">Edit</summary><div class="modal-panel modal-wide"><div class="modal-h"><h3>Edit {{ $brand->name }}</h3><span class="badge b-info">#{{ $brand->id }}</span></div>@include('admin.partials.brand-form', ['brand' => $brand])</div></details>
                    <form method="post" action="/admin/brands/{{ $brand->id }}/toggle">@csrf<button class="btn btn-ghost" type="submit">{{ $brand->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                    @if($brand->products_count === 0)<form method="post" action="/admin/brands/{{ $brand->id }}" onsubmit="return confirm('Delete this unused brand?')">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Delete</button></form>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No brands</h3><p>Create a verified brand before assigning it to products or menus.</p></div></td></tr>
        @endforelse
    </tbody></table></div>
    @if($brands->hasPages())<div class="pagination-wrap">{{ $brands->links() }}</div>@endif
</section>
@endsection
