@extends('admin.layout')
@section('title','Product '.$p->sku)
@section('crumb','Catalog / Product detail')
@section('page_actions')
    <a class="btn btn-ghost" href="/admin/products">Back to products</a>
    <form method="post" action="/admin/products/{{ $p->id }}/duplicate">@csrf<button class="btn btn-ghost" type="submit">Duplicate</button></form>
@endsection
@section('content')

@php
    $status = $p->status ?? 'draft';
@endphp

<div class="grid kpis">
    <div class="kpi"><div class="t">Status</div><div class="v">{{ ucfirst($status) }}</div><div class="s">catalog state</div></div>
    <div class="kpi"><div class="t">Stock</div><div class="v tnum">{{ number_format($p->stock_quantity ?? 0) }}</div><div class="s">threshold {{ number_format($p->low_stock_threshold ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Price</div><div class="v tnum">{{ number_format((float) $p->base_price, 2) }}</div><div class="s">sale {{ $p->sale_price ? number_format((float) $p->sale_price, 2) : '—' }}</div></div>
    <div class="kpi"><div class="t">MPN</div><div class="v" style="font-size:1.1rem">{{ $p->mpn ?: '—' }}</div><div class="s">{{ $p->manufacturer_name ?: 'manufacturer not set' }}</div></div>
</div>

<div class="grid dashboard-split">
    <div class="card">
        <div class="card-h"><h2>Core Product</h2><span class="badge {{ in_array($status,['active','approved','published']) ? 'b-ok' : 'b-muted' }}">{{ $status }}</span></div>
        <form class="form-stack" method="post" action="/admin/products" style="padding:16px">@csrf
            <input type="hidden" name="id" value="{{ $p->id }}">
            <div class="form-grid">
                <div class="field"><label>Name</label><input class="control" name="name" value="{{ $p->name }}" required></div>
                <div class="field"><label>SKU</label><input class="control mono" name="sku" value="{{ $p->sku }}" required></div>
                <div class="field"><label>Slug</label><input class="control mono" name="slug" value="{{ $p->slug }}"></div>
                <div class="field"><label>MPN</label><input class="control" name="mpn" value="{{ $p->mpn }}"></div>
                <div class="field"><label>Category</label><select class="control" name="category_id"><option value="">Unassigned</option>@foreach($categories as $cat)<option value="{{ $cat->id }}" @selected($p->category_id==$cat->id)>{{ $cat->name }}</option>@endforeach</select></div>
                <div class="field"><label>Brand</label><select class="control" name="brand_id"><option value="">No brand</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @selected($p->brand_id==$brand->id)>{{ $brand->name }}</option>@endforeach</select></div>
                <div class="field"><label>Seller</label><select class="control" name="vendor_id"><option value="">Platform</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected($p->vendor_id==$vendor->id)>{{ $vendor->name }}</option>@endforeach</select></div>
                <div class="field"><label>Status</label><input class="control" name="status" value="{{ $p->status }}"></div>
                <div class="field"><label>Base price</label><input class="control" type="number" step="0.01" name="base_price" value="{{ $p->base_price }}"></div>
                <div class="field"><label>Sale price</label><input class="control" type="number" step="0.01" name="sale_price" value="{{ $p->sale_price }}"></div>
                <div class="field"><label>Stock</label><input class="control" type="number" name="stock_quantity" value="{{ $p->stock_quantity }}"></div>
                <div class="field"><label>Low stock</label><input class="control" type="number" name="low_stock_threshold" value="{{ $p->low_stock_threshold }}"></div>
            </div>
            <div class="field"><label>Short description</label><textarea class="control" name="short_description">{{ $p->short_description }}</textarea></div>
            <div class="field"><label>Description</label><textarea class="control" name="description" rows="6">{{ $p->description }}</textarea></div>
            <button class="btn btn-primary" type="submit">Save product</button>
        </form>
    </div>

    <div class="card">
        <div class="card-h"><h2>Inventory Snapshot</h2><span class="sub">latest stock rows</span></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Warehouse</th><th class="num">Available</th><th class="num">Reserved</th></tr></thead>
            <tbody>
            @forelse($recentStocks as $stock)
                <tr><td>{{ $stock->warehouse_id ?? '—' }}</td><td class="num tnum">{{ number_format((float) ($stock->available_quantity ?? $stock->quantity_on_hand ?? 0)) }}</td><td class="num tnum">{{ number_format((float) ($stock->reserved_quantity ?? 0)) }}</td></tr>
            @empty
                <tr><td colspan="3"><div class="empty"><h3>No warehouse rows yet</h3></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>

<div class="grid split stack-gap">
    <div class="card">
        <div class="card-h"><h2>Technical Specs</h2></div>
        <div style="padding:16px">
            @forelse($productSpecs as $spec)
                <div style="display:flex;justify-content:space-between;gap:8px;padding:7px 0;border-bottom:1px solid var(--line)"><span><strong>{{ $spec->name }}</strong>: {{ $spec->value }} {{ $spec->unit }}</span><form method="post" action="/admin/products/{{ $p->id }}/specs/{{ $spec->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Delete</button></form></div>
            @empty <div class="sub">No specs yet.</div> @endforelse
            <form method="post" action="/admin/products/{{ $p->id }}/specs" class="form-grid" style="margin-top:12px">@csrf<input class="control" name="name" placeholder="Voltage" required><input class="control" name="value" placeholder="12"><input class="control" name="unit" placeholder="V"><button class="btn" type="submit">Add spec</button></form>
        </div>
    </div>

    <div class="card">
        <div class="card-h"><h2>Documents</h2></div>
        <div style="padding:16px">
            @forelse($productDocuments as $doc)
                <div style="display:flex;justify-content:space-between;gap:8px;padding:7px 0;border-bottom:1px solid var(--line)"><span><strong>{{ $doc->title }}</strong><div class="sub">{{ $doc->document_type }} · {{ $doc->file_url ?: $doc->source_url }} @if(! empty($doc->media_asset_id)) · media #{{ $doc->media_asset_id }} @endif</div></span><form method="post" action="/admin/products/{{ $p->id }}/documents/{{ $doc->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Deactivate</button></form></div>
            @empty <div class="sub">No documents attached.</div> @endforelse
            <form method="post" action="/admin/products/{{ $p->id }}/documents" enctype="multipart/form-data" class="form-stack" style="margin-top:12px">@csrf<div class="form-grid"><input class="control" name="title" placeholder="Datasheet" required><select class="control" name="document_type"><option>datasheet</option><option>cad</option><option>firmware</option><option>manual</option></select><select class="control" name="media_asset_id"><option value="">Choose from media library</option>@foreach($mediaAssets as $asset)<option value="{{ $asset->id }}">{{ $asset->title ?: $asset->original_name }} @if($asset->folder) · {{ $asset->folder }} @endif</option>@endforeach</select><input class="control" type="file" name="file"><input class="control" name="file_url" placeholder="File URL"><input class="control" name="source_url" placeholder="Source URL"></div><div class="sub">Attach by media library, upload, or URL. Uploaded files are stored in product-documents/{{ $p->id }}.</div><button class="btn" type="submit">Attach document</button></form>
        </div>
    </div>
</div>

<div class="grid split stack-gap">
    <div class="card">
        <div class="card-h"><h2>Related Parts</h2></div>
        <div style="padding:16px">
            @forelse($productRelated as $rel)
                <div style="display:flex;justify-content:space-between;gap:8px;padding:7px 0;border-bottom:1px solid var(--line)"><span><strong>{{ $rel->related_name }}</strong><div class="sub">{{ $rel->relation_type }} · {{ $rel->related_sku }}</div></span><form method="post" action="/admin/products/{{ $p->id }}/related/{{ $rel->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Deactivate</button></form></div>
            @empty <div class="sub">No related products.</div> @endforelse
            <form method="post" action="/admin/products/{{ $p->id }}/related" class="form-grid" style="margin-top:12px">@csrf<select class="control" name="related_product_id" required><option value="">Select product</option>@foreach($allProducts as $ap)<option value="{{ $ap->id }}">{{ $ap->name }} · {{ $ap->sku }}</option>@endforeach</select><select class="control" name="relation_type"><option>alternative</option><option>accessory</option><option>replacement</option><option>compatible</option></select><input class="control" name="notes" placeholder="Notes"><button class="btn" type="submit">Link</button></form>
        </div>
    </div>

    <div class="card">
        <div class="card-h"><h2>LMS + SEO</h2></div>
        <div style="padding:16px;display:grid;gap:14px">
            <div>
                @forelse($productLmsLinks as $link)
                    <div style="padding:7px 0;border-bottom:1px solid var(--line)"><strong>{{ $link->title }}</strong><div class="sub">{{ $link->relation_type }} · {{ $link->url }}</div></div>
                @empty <div class="sub">No LMS links.</div> @endforelse
                <form method="post" action="/admin/products/{{ $p->id }}/lms-links" class="form-grid" style="margin-top:12px">@csrf<input class="control" name="title" placeholder="Tutorial title" required><input class="control" name="url" placeholder="/learn/projects/..."><select class="control" name="relation_type"><option>tutorial</option><option>course</option><option>lab_kit</option></select><button class="btn" type="submit">Attach LMS</button></form>
            </div>
            <form method="post" action="/admin/products/{{ $p->id }}/seo" class="form-stack">@csrf
                <input class="control" name="meta_title" value="{{ $productSeo->meta_title ?? $p->meta_title ?? '' }}" placeholder="Meta title">
                <textarea class="control" name="meta_description" placeholder="Meta description">{{ $productSeo->meta_description ?? $p->meta_description ?? '' }}</textarea>
                <div class="form-grid"><input class="control" name="canonical_url" value="{{ $productSeo->canonical_url ?? '' }}" placeholder="Canonical URL"><select class="control" name="robots"><option @selected(($productSeo->robots ?? '')==='index,follow')>index,follow</option><option @selected(($productSeo->robots ?? '')==='noindex,nofollow')>noindex,nofollow</option></select><input class="control" name="schema_type" value="{{ $productSeo->schema_type ?? 'Product' }}"><input class="control" name="confidence_level" value="{{ $productSeo->confidence_level ?? 'manual' }}"></div>
                <div class="sub">source_notes: manual admin metadata · confidence_level: {{ $productSeo->confidence_level ?? 'manual' }} · last_updated: {{ $productSeo->updated_at ?? 'not saved' }} · Advisory only</div>
                <button class="btn" type="submit">Save SEO</button>
            </form>
        </div>
    </div>
</div>

@endsection
