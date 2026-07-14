@extends('admin.layout')
@section('title','Products')
@section('crumb','Catalog / Product Admin')
@section('page_actions')
<a class="btn btn-ghost" href="/admin/imports/jlcpcb">Import Review</a>
<details class="modal">
    <summary class="btn btn-primary">Add Product</summary>
    <div class="modal-panel">
        <div class="modal-h"><h3>Product Creation Wizard</h3><span class="badge b-info">9-step form</span></div>
        <form class="modal-b form-stack" method="post" action="/admin/products">@csrf
            <div class="actions" aria-label="Product fields included in this form"><span class="badge b-info">Basic</span><span class="badge b-muted">MPN</span><span class="badge b-muted">Category</span><span class="badge b-muted">Specs</span><span class="badge b-muted">Pricing</span><span class="badge b-muted">Inventory</span><span class="badge b-muted">Media</span><span class="badge b-muted">SEO</span><span class="badge b-muted">Publish</span></div>
            <div class="form-grid"><div class="field"><label>Name</label><input class="control" name="name" required></div><div class="field"><label>SKU</label><input class="control mono" name="sku" required></div><div class="field"><label>Slug</label><input class="control mono" name="slug"></div><div class="field"><label>MPN</label><input class="control" name="mpn"></div></div>
            <div class="form-grid"><div class="field"><label>Category</label><select class="control" name="category_id"><option value="">Unassigned</option>@foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select></div><div class="field"><label>Brand</label><select class="control" name="brand_id"><option value="">No brand</option>@foreach($brands as $brand)<option value="{{ $brand->id }}">{{ $brand->name }}</option>@endforeach</select></div><div class="field"><label>Seller</label><select class="control" name="vendor_id"><option value="">Platform</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach</select></div><div class="field"><label>Status</label><select class="control" name="status"><option>draft</option><option>active</option><option>approved</option><option>inactive</option></select></div></div>
            <div class="form-grid"><div class="field"><label>Base price</label><input class="control" type="number" step="0.01" name="base_price"></div><div class="field"><label>Sale price</label><input class="control" type="number" step="0.01" name="sale_price"></div><div class="field"><label>Stock</label><input class="control" type="number" name="stock_quantity" value="0"></div><div class="field"><label>Low stock</label><input class="control" type="number" name="low_stock_threshold" value="5"></div></div>
            <div class="form-grid"><div class="field"><label>Manufacturer</label><input class="control" name="manufacturer_name"></div><div class="field"><label>Model number</label><input class="control" name="model_number"></div><div class="field"><label>Origin</label><input class="control" name="country_of_origin"></div><div class="field"><label>Regional visibility</label><input class="control" name="regional_visibility" placeholder="Global/Nepal/India"></div></div>
            <div class="field"><label>Short description</label><textarea class="control" name="short_description"></textarea></div>
            <div class="field"><label>Description</label><textarea class="control" name="description"></textarea></div>
            <div class="field"><label>Specs / attributes JSON</label><textarea class="control mono" name="attributes_json" placeholder='{"voltage":"12V"}'></textarea></div>
            <div class="form-grid"><div class="field"><label>SEO title</label><input class="control" name="meta_title"></div><div class="field"><label>SEO description</label><input class="control" name="meta_description"></div></div>
            <button class="btn btn-primary" type="submit">Create Product</button>
        </form>
    </div>
</details>
@endsection
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Products</div><div class="v tnum">{{ number_format($stats['total']) }}</div><div class="s">catalog total</div></div>
    <div class="kpi"><div class="t">Active</div><div class="v tnum">{{ number_format($stats['active']) }}</div><div class="s">sellable</div></div>
    <div class="kpi"><div class="t">Draft</div><div class="v tnum">{{ number_format($stats['draft']) }}</div><div class="s">needs work</div></div>
    <div class="kpi"><div class="t">Low stock</div><div class="v tnum">{{ number_format($stats['lowStock']) }}</div><div class="s">review reorder</div></div>
    <div class="kpi"><div class="t">Import Review</div><div class="v tnum">{{ number_format($stats['importPending']) }}</div><div class="s"><a href="/admin/imports/jlcpcb">pending JLCPCB</a></div></div>
    <div class="kpi"><div class="t">Search Index</div><div class="v tnum">{{ number_format($stats['indexed']) }}</div><div class="s">{{ number_format($stats['indexFacets']) }} facet values</div></div>
</div>

<section class="card">
    <div class="card-h"><div><h2>Product Admin</h2><div class="sub">{{ number_format($products->total()) }} filtered products</div></div><span class="badge b-info">wizard + actions</span></div>
    <form class="filters" method="get">
        <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search name, SKU, MPN">
        <select class="control" name="category_id"><option value="">All categories</option>@foreach($categories as $cat)<option value="{{ $cat->id }}" @selected($filters['category_id']==$cat->id)>{{ $cat->name }}</option>@endforeach</select>
        <select class="control" name="brand_id"><option value="">All brands</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected($filters['brand_id']==$brand->id)>{{ $brand->name }}</option>@endforeach</select>
        <select class="control" name="vendor_id"><option value="">All sellers</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected($filters['vendor_id']==$vendor->id)>{{ $vendor->name }}</option>@endforeach</select>
        <select class="control" name="status"><option value="">All status</option>@foreach(['draft','active','approved','inactive','archived'] as $s)<option value="{{ $s }}" @selected($filters['status']===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
        <select class="control" name="stock"><option value="">All stock</option><option value="low" @selected($filters['stock']==='low')>Low stock</option><option value="out" @selected($filters['stock']==='out')>Out of stock</option></select>
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Name</th><th>SKU/MPN</th><th>Category</th><th>Brand</th><th class="num">Price</th><th class="num">Stock</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($products as $p)
            @php $s = $p->status ?? 'draft'; @endphp
            <tr>
                <td><a href="/admin/products/{{ $p->id }}"><strong>{{ $p->name }}</strong></a><div class="sub">{{ $p->manufacturer_name ?: 'No manufacturer' }}</div></td>
                <td class="mono">{{ $p->sku }}<div class="sub">{{ $p->mpn ?: 'no mpn' }}</div></td>
                <td>{{ $p->category_name ?: '—' }}</td>
                <td>{{ $p->brand_name ?: '—' }}</td>
                <td class="num tnum">{{ $p->base_price !== null ? number_format($p->base_price, 2) : '—' }}</td>
                <td class="num tnum">{{ number_format($p->stock_quantity ?? 0) }}</td>
                <td><span class="badge {{ in_array($s,['active','approved','published'])?'b-ok':($s==='draft'?'b-muted':'b-warn') }}">{{ ucfirst($s) }}</span></td>
                <td class="actions">
                    <details class="modal"><summary class="btn btn-ghost">View/Edit</summary><div class="modal-panel"><div class="modal-h"><h3>{{ $p->name }}</h3><span class="badge b-info">detail drawer</span></div><div class="modal-b form-stack">
                        <form class="form-stack" method="post" action="/admin/products">@csrf<input type="hidden" name="id" value="{{ $p->id }}"><div class="form-grid"><div class="field"><label>Name</label><input class="control" name="name" value="{{ $p->name }}" required></div><div class="field"><label>SKU</label><input class="control mono" name="sku" value="{{ $p->sku }}" required></div><div class="field"><label>MPN</label><input class="control" name="mpn" value="{{ $p->mpn }}"></div><div class="field"><label>Status</label><input class="control" name="status" value="{{ $p->status }}"></div></div><div class="form-grid"><div class="field"><label>Price</label><input class="control" type="number" step="0.01" name="base_price" value="{{ $p->base_price }}"></div><div class="field"><label>Sale</label><input class="control" type="number" step="0.01" name="sale_price" value="{{ $p->sale_price }}"></div><div class="field"><label>Stock</label><input class="control" type="number" name="stock_quantity" value="{{ $p->stock_quantity }}"></div><div class="field"><label>Low stock</label><input class="control" type="number" name="low_stock_threshold" value="{{ $p->low_stock_threshold }}"></div></div><div class="field"><label>Short description</label><textarea class="control" name="short_description">{{ $p->short_description }}</textarea></div><div class="field"><label>Description</label><textarea class="control" name="description">{{ $p->description }}</textarea></div><button class="btn btn-primary" type="submit">Save Product</button></form>
                        <nav class="tabs" aria-label="Product editor sections"><a class="tab active" href="#product-{{ $p->id }}-specs">Specs</a><a class="tab" href="#product-{{ $p->id }}-documents">Documents</a><a class="tab" href="#product-{{ $p->id }}-related">Related</a><a class="tab" href="#product-{{ $p->id }}-lms">LMS</a><a class="tab" href="#product-{{ $p->id }}-seo">SEO</a></nav>
                        <div class="card" id="product-{{ $p->id }}-specs"><div class="card-h"><h2>Technical specs</h2></div><div style="padding:12px">
                            @forelse(($productSpecs[$p->id] ?? collect()) as $spec)
                                <div style="display:flex;justify-content:space-between;gap:8px;padding:6px 0;border-bottom:1px solid var(--line)"><span><strong>{{ $spec->name }}</strong>: {{ $spec->value }} {{ $spec->unit }}</span><form method="post" action="/admin/products/{{ $p->id }}/specs/{{ $spec->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Delete</button></form></div>
                            @empty <div class="sub">No specs yet.</div> @endforelse
                            <form method="post" action="/admin/products/{{ $p->id }}/specs" class="form-grid" style="margin-top:10px">@csrf<input class="control" name="name" placeholder="Voltage" required><input class="control" name="value" placeholder="12"><input class="control" name="unit" placeholder="V"><button class="btn" type="submit">Add spec</button></form>
                        </div></div>
                        <div class="card" id="product-{{ $p->id }}-documents"><div class="card-h"><h2>Datasheets / Documents</h2></div><div style="padding:12px">
                            @forelse(($productDocuments[$p->id] ?? collect()) as $doc)
                                <div style="display:flex;justify-content:space-between;gap:8px;padding:6px 0;border-bottom:1px solid var(--line)"><span><strong>{{ $doc->title }}</strong><div class="sub">{{ $doc->document_type }} · {{ $doc->file_url ?: $doc->source_url }}</div></span><form method="post" action="/admin/products/{{ $p->id }}/documents/{{ $doc->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Deactivate</button></form></div>
                            @empty <div class="sub">No documents attached.</div> @endforelse
                            <form method="post" action="/admin/products/{{ $p->id }}/documents" class="form-stack" style="margin-top:10px">@csrf<div class="form-grid"><input class="control" name="title" placeholder="Datasheet" required><select class="control" name="document_type"><option>datasheet</option><option>cad</option><option>firmware</option><option>manual</option></select><input class="control" name="file_url" placeholder="File URL"><input class="control" name="source_url" placeholder="Source URL"></div><button class="btn" type="submit">Attach document</button></form>
                        </div></div>
                        <div class="card" id="product-{{ $p->id }}-related"><div class="card-h"><h2>Alternative / Related Parts</h2></div><div style="padding:12px">
                            @forelse(($productRelated[$p->id] ?? collect()) as $rel)
                                <div style="display:flex;justify-content:space-between;gap:8px;padding:6px 0;border-bottom:1px solid var(--line)"><span><strong>{{ $rel->related_name }}</strong><div class="sub">{{ $rel->relation_type }} · {{ $rel->related_sku }}</div></span><form method="post" action="/admin/products/{{ $p->id }}/related/{{ $rel->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Deactivate</button></form></div>
                            @empty <div class="sub">No related products.</div> @endforelse
                            <form method="post" action="/admin/products/{{ $p->id }}/related" class="form-grid" style="margin-top:10px">@csrf<select class="control" name="related_product_id" required><option value="">Select product</option>@foreach($allProducts as $ap)@if($ap->id !== $p->id)<option value="{{ $ap->id }}">{{ $ap->name }} · {{ $ap->sku }}</option>@endif @endforeach</select><select class="control" name="relation_type"><option>alternative</option><option>accessory</option><option>replacement</option><option>compatible</option></select><input class="control" name="notes" placeholder="Notes"><button class="btn" type="submit">Link</button></form>
                        </div></div>
                        <div class="card" id="product-{{ $p->id }}-lms"><div class="card-h"><h2>LMS Tutorial Links</h2></div><div style="padding:12px">
                            @forelse(($productLmsLinks[$p->id] ?? collect()) as $link)
                                <div style="padding:6px 0;border-bottom:1px solid var(--line)"><strong>{{ $link->title }}</strong><div class="sub">{{ $link->relation_type }} · {{ $link->url }}</div></div>
                            @empty <div class="sub">No LMS links.</div> @endforelse
                            <form method="post" action="/admin/products/{{ $p->id }}/lms-links" class="form-grid" style="margin-top:10px">@csrf<input class="control" name="title" placeholder="Tutorial title" required><input class="control" name="url" placeholder="/learn/projects/..."><select class="control" name="relation_type"><option>tutorial</option><option>course</option><option>lab_kit</option></select><button class="btn" type="submit">Attach LMS</button></form>
                        </div></div>
                        @php $seo = $productSeo[$p->id] ?? null; @endphp
                        <form method="post" action="/admin/products/{{ $p->id }}/seo" class="card form-stack" id="product-{{ $p->id }}-seo" style="padding:12px">@csrf
                            <div class="card-h" style="padding:0 0 10px;border-bottom:0"><h2>Product SEO</h2><span class="badge b-muted">Advisory only</span></div>
                            <input class="control" name="meta_title" value="{{ $seo->meta_title ?? $p->meta_title ?? '' }}" placeholder="Meta title">
                            <textarea class="control" name="meta_description" placeholder="Meta description">{{ $seo->meta_description ?? $p->meta_description ?? '' }}</textarea>
                            <div class="form-grid"><input class="control" name="canonical_url" value="{{ $seo->canonical_url ?? '' }}" placeholder="Canonical URL"><select class="control" name="robots"><option @selected(($seo->robots ?? '')==='index,follow')>index,follow</option><option @selected(($seo->robots ?? '')==='noindex,nofollow')>noindex,nofollow</option></select><input class="control" name="schema_type" value="{{ $seo->schema_type ?? 'Product' }}"><input class="control" name="confidence_level" value="{{ $seo->confidence_level ?? 'manual' }}"></div>
                            <div class="sub">source_notes: manual admin metadata · confidence_level: {{ $seo->confidence_level ?? 'manual' }} · last_updated: {{ $seo->updated_at ?? 'not saved' }} · Advisory only</div>
                            <button class="btn" type="submit">Save SEO</button>
                        </form>
                    </div></div></details>
                    <form method="post" action="/admin/products/{{ $p->id }}/duplicate">@csrf<button class="btn btn-ghost" type="submit">Duplicate</button></form>
                    <details class="modal"><summary class="btn btn-ghost">Stock</summary><div class="modal-panel"><div class="modal-h"><h3>Adjust Stock</h3></div><form class="modal-b form-stack" method="post" action="/admin/products/{{ $p->id }}/stock">@csrf<div class="field"><label>Stock quantity</label><input class="control" type="number" name="stock_quantity" value="{{ $p->stock_quantity ?? 0 }}" required></div><div class="field"><label>Low stock threshold</label><input class="control" type="number" name="low_stock_threshold" value="{{ $p->low_stock_threshold ?? 5 }}"></div><div class="field"><label>Note</label><textarea class="control" name="note"></textarea></div><button class="btn btn-primary" type="submit">Save Stock</button></form></div></details>
                    <form method="post" action="/admin/products/{{ $p->id }}/toggle" onsubmit="return confirm('Change product active status?')">@csrf<button class="btn btn-ghost danger" type="submit">{{ in_array($s,['inactive','archived']) ? 'Reactivate':'Deactivate' }}</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="8"><div class="empty"><h3>No products found</h3><p>Create a product from the Add Product action.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if ($products->hasPages())<div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $products->links() }}</div>@endif
</section>

@endsection
