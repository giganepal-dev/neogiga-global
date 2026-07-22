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
    $lifecycle = strtoupper((string) ($p->lifecycle_status ?? ''));
    $lifecycleOptions = ['ACTIVE' => 'Active', 'NRND' => 'NRND', 'EOL' => 'End of Life', 'OBSOLETE' => 'Obsolete', 'DISCONTINUED' => 'Discontinued', 'LAST_TIME_BUY' => 'Last Time Buy', 'PREVIEW' => 'Preview', 'NEW' => 'New'];
    $catalogAuditFlags = [];
    $qualityChecks = [];
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
        <div class="card-h"><h2>Regional Inventory</h2><span class="sub">warehouse + country stock</span></div>
        <form class="form-stack" method="post" action="/admin/products/{{ $p->id }}/regional-stock" style="padding:16px;border-bottom:1px solid var(--line)">@csrf
            <div class="form-grid">
                <div class="field"><label>Warehouse</label><select class="control" name="warehouse_id" required>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach</select></div>
                <div class="field"><label>Country</label><select class="control" name="country_id"><option value="">Global</option>@foreach($countries as $country)<option value="{{ $country->id }}">{{ $country->name }}</option>@endforeach</select></div>
                <div class="field"><label>Available</label><input class="control" type="number" name="quantity_available" min="0" required></div>
                <div class="field"><label>Reserved</label><input class="control" type="number" name="quantity_reserved" min="0" value="0"></div>
                <div class="field"><label>Incoming</label><input class="control" type="number" name="quantity_incoming" min="0" value="0"></div>
                <div class="field"><label>Reorder point</label><input class="control" type="number" name="reorder_point" min="0" value="{{ $p->low_stock_threshold ?? 5 }}"></div>
                <div class="field"><label>Reorder qty</label><input class="control" type="number" name="reorder_quantity" min="0" value="0"></div>
                <div class="field"><label>Unit cost</label><input class="control" type="number" step="0.01" name="unit_cost" min="0"></div>
                <div class="field"><label>Status</label><select class="control" name="status"><option>active</option><option>inactive</option><option>backorder</option><option>quote_only</option></select></div>
            </div>
            <div class="form-grid">
                <label><input type="checkbox" name="backorder_allowed" value="1"> Backorder allowed</label>
                <label><input type="checkbox" name="quote_only" value="1"> Quote only</label>
            </div>
            <div class="field"><label>Note</label><textarea class="control" name="notes"></textarea></div>
            <button class="btn btn-primary" type="submit">Save Regional Stock</button>
        </form>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Warehouse</th><th>Country</th><th class="num">Available</th><th class="num">Reserved</th><th class="num">Incoming</th><th>Flags</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($recentStocks as $stock)
                <tr>
                    <td>{{ $stock->warehouse_name ?? ('#'.$stock->warehouse_id) }}<div class="sub mono">{{ $stock->warehouse_code ?? 'stock #'.$stock->id }}</div></td>
                    <td>{{ $stock->country_name ?? 'Global' }}</td>
                    <td class="num tnum">{{ number_format((float) ($stock->quantity_available ?? 0)) }}</td>
                    <td class="num tnum">{{ number_format((float) ($stock->quantity_reserved ?? 0)) }}</td>
                    <td class="num tnum">{{ number_format((float) ($stock->quantity_incoming ?? 0)) }}</td>
                    <td><span class="badge {{ ($stock->status ?? 'active') === 'active' ? 'b-ok' : 'b-muted' }}">{{ $stock->status ?? 'active' }}</span><div class="sub">reorder {{ number_format((float) ($stock->reorder_point ?? 0)) }}</div></td>
                    <td>
                        <details class="modal">
                            <summary class="btn btn-ghost">Edit</summary>
                            <div class="modal-panel">
                                <div class="modal-h"><h3>Edit Regional Stock</h3></div>
                                <form class="modal-b form-stack" method="post" action="/admin/products/{{ $p->id }}/regional-stock">@csrf
                                    <input type="hidden" name="inventory_stock_id" value="{{ $stock->id }}">
                                    <div class="form-grid">
                                        <div class="field"><label>Warehouse</label><select class="control" name="warehouse_id" required>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($stock->warehouse_id==$warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></div>
                                        <div class="field"><label>Country</label><select class="control" name="country_id"><option value="">Global</option>@foreach($countries as $country)<option value="{{ $country->id }}" @selected($stock->country_id==$country->id)>{{ $country->name }}</option>@endforeach</select></div>
                                        <div class="field"><label>Available</label><input class="control" type="number" name="quantity_available" min="0" value="{{ (int) $stock->quantity_available }}" required></div>
                                        <div class="field"><label>Reserved</label><input class="control" type="number" name="quantity_reserved" min="0" value="{{ (int) $stock->quantity_reserved }}"></div>
                                        <div class="field"><label>Incoming</label><input class="control" type="number" name="quantity_incoming" min="0" value="{{ (int) $stock->quantity_incoming }}"></div>
                                        <div class="field"><label>Reorder point</label><input class="control" type="number" name="reorder_point" min="0" value="{{ (int) $stock->reorder_point }}"></div>
                                        <div class="field"><label>Reorder qty</label><input class="control" type="number" name="reorder_quantity" min="0" value="{{ (int) ($stock->reorder_quantity ?? 0) }}"></div>
                                        <div class="field"><label>Unit cost</label><input class="control" type="number" step="0.01" name="unit_cost" min="0" value="{{ $stock->unit_cost }}"></div>
                                        <div class="field"><label>Status</label><select class="control" name="status"><option @selected($stock->status==='active')>active</option><option @selected($stock->status==='inactive')>inactive</option><option @selected($stock->status==='backorder')>backorder</option><option @selected($stock->status==='quote_only')>quote_only</option></select></div>
                                    </div>
                                    <div class="form-grid"><label><input type="checkbox" name="backorder_allowed" value="1" @checked($stock->backorder_allowed)> Backorder allowed</label><label><input type="checkbox" name="quote_only" value="1" @checked($stock->quote_only)> Quote only</label></div>
                                    <div class="field"><label>Note</label><textarea class="control" name="notes"></textarea></div>
                                    <button class="btn btn-primary" type="submit">Save Regional Stock</button>
                                </form>
                            </div>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty"><h3>No warehouse rows yet</h3></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>

<section class="card stack-gap" aria-labelledby="product-media-heading">
    <div class="card-h"><div><h2 id="product-media-heading">Product Images</h2><div class="sub">Primary image, gallery order, source and regional visibility</div></div><span class="badge b-info">{{ $productImages->where('is_active', true)->count() }} active / {{ $productImages->count() }} total</span></div>
    <div style="padding:16px;display:grid;gap:18px">
        <form id="product-image-upload" class="form-stack" method="post" action="/admin/products/{{ $p->id }}/images" enctype="multipart/form-data">
            @csrf
            <label id="product-image-dropzone" for="product-image-files" style="display:grid;place-items:center;min-height:140px;padding:20px;border:2px dashed var(--line);border-radius:12px;text-align:center;cursor:pointer;background:var(--bg)">
                <strong>Drop product images here or choose files</strong>
                <span class="sub">JPG, PNG, WebP or AVIF · 120–12000 px · up to 12 MB each · maximum 20</span>
                <input id="product-image-files" type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/avif" multiple required style="position:absolute;opacity:0;pointer-events:none">
            </label>
            <div id="product-image-previews" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px" aria-live="polite"></div>
            <div class="form-grid">
                <div class="field"><label>Alt text</label><input class="control" name="alt_text" value="{{ $p->name }}" maxlength="500"></div>
                <div class="field"><label>Caption</label><input class="control" name="caption" maxlength="1000"></div>
                <div class="field"><label>Source name</label><input class="control" name="source_name" value="NeoGiga admin upload" maxlength="255"></div>
                <div class="field"><label>Source page URL</label><input class="control" type="url" name="source_page_url" maxlength="4000"></div>
                <div class="field"><label>License note</label><input class="control" name="license_note" maxlength="2000"></div>
                <div class="field"><label>Confidence</label><select class="control" name="confidence_level"><option value="admin_uploaded_unverified">Uploaded — needs review</option><option value="admin_verified">Admin verified</option><option value="licensed_source_verified">Licensed source verified</option></select></div>
            </div>
            <progress id="product-image-progress" max="100" value="0" hidden style="width:100%"></progress>
            <div id="product-image-upload-status" class="sub" aria-live="polite">Uploaded images are active by default; source/licensing should be verified before public use.</div>
            <button class="btn btn-primary" type="submit">Upload images</button>
        </form>

        @if($productImages->isNotEmpty())
            <form id="product-image-reorder" method="post" action="/admin/products/{{ $p->id }}/images/reorder" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
                @csrf @method('PATCH')
                <div id="product-image-order-inputs">@foreach($productImages as $image)<input type="hidden" name="image_ids[]" value="{{ $image->id }}">@endforeach</div>
                <span class="sub">Drag cards to reorder, then save. Inactive historical rows remain preserved.</span>
                <button class="btn btn-ghost" type="submit">Save gallery order</button>
            </form>
        @endif

        <div id="product-image-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px">
            @forelse($productImages as $image)
                <article class="product-image-admin-card" draggable="true" data-image-id="{{ $image->id }}" style="border:1px solid var(--line);border-radius:12px;padding:12px;background:var(--bg)">
                    <div style="position:relative;aspect-ratio:4/3;border-radius:9px;overflow:hidden;background:#fff;display:grid;place-items:center">
                        <img src="{{ $image->publicUrl() }}" alt="{{ $image->alt_text ?: $p->name }}" loading="lazy" style="width:100%;height:100%;object-fit:contain">
                        <div style="position:absolute;top:8px;left:8px;display:flex;gap:5px"><span class="badge {{ $image->is_active ? 'b-ok' : 'b-muted' }}">{{ $image->is_active ? 'Active' : 'Inactive' }}</span>@if($image->is_primary)<span class="badge b-info">Primary</span>@endif</div>
                    </div>
                    <form class="form-stack" method="post" action="/admin/products/{{ $p->id }}/images/{{ $image->id }}" enctype="multipart/form-data" style="margin-top:10px">
                        @csrf @method('PATCH')
                        <div class="field"><label>Alt text</label><input class="control" name="alt_text" value="{{ $image->alt_text }}" maxlength="500"></div>
                        <div class="field"><label>Caption</label><input class="control" name="caption" value="{{ $image->caption }}" maxlength="1000"></div>
                        <div class="form-grid"><input class="control" name="source_name" value="{{ $image->source_name }}" placeholder="Source"><input class="control" type="url" name="source_page_url" value="{{ $image->source_page_url ?: data_get($image->metadata, 'source_page_url') }}" placeholder="Source page URL"></div>
                        <div class="form-grid"><input class="control" name="source_license" value="{{ $image->source_license }}" placeholder="License"><input class="control" name="confidence_level" value="{{ $image->confidence_level ?: data_get($image->metadata, 'confidence_level') }}" placeholder="Confidence"></div>
                        <div class="field"><label>Replace file</label><input class="control" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif"></div>
                        <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($image->is_active)> Active</label>
                        <div class="sub">{{ $image->width ?: '?' }}×{{ $image->height ?: '?' }} · {{ $image->mime_type ?: 'legacy URL' }} · #{{ $image->id }}</div>
                        <button class="btn btn-ghost" type="submit">Save image</button>
                    </form>
                    <div style="display:flex;gap:7px;flex-wrap:wrap;margin-top:8px">
                        @if($image->is_active && ! $image->is_primary)<form method="post" action="/admin/products/{{ $p->id }}/images/{{ $image->id }}/primary">@csrf<button class="btn btn-ghost" type="submit">Set primary</button></form>@endif
                        @if($image->is_active)<form method="post" action="/admin/products/{{ $p->id }}/images/{{ $image->id }}" onsubmit="return confirm('Deactivate this image? Its database row and file will be preserved.')">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Deactivate</button></form>@endif
                    </div>
                </article>
            @empty
                <div class="empty"><h3>No product images yet</h3><p>Upload a primary image and optional gallery images above.</p></div>
            @endforelse
        </div>
    </div>
</section>

<div class="grid split stack-gap">
    <div class="card">
        <div class="card-h"><h2>Marketplace Prices</h2><span class="sub">regional catalog pricing</span></div>
        <div style="padding:16px">
            <form class="form-grid" method="post" action="/admin/products/{{ $p->id }}/marketplace-prices" style="margin-bottom:14px">@csrf
                <select class="control" name="marketplace_id" required><option value="">Marketplace</option>@foreach($marketplaces as $marketplace)<option value="{{ $marketplace->id }}">{{ $marketplace->name }} · {{ $marketplace->code }}</option>@endforeach</select>
                <select class="control" name="currency_code" required>@foreach($currencies as $currency)<option value="{{ $currency->code }}">{{ $currency->code }} {{ $currency->symbol ?: $currency->native_symbol }}</option>@endforeach</select>
                <input class="control" type="number" step="0.01" min="0" name="base_price" placeholder="Base price" required>
                <input class="control" type="number" step="0.01" min="0" name="sale_price" placeholder="Sale price">
                <input class="control" type="number" step="0.01" min="0" name="cost_price" placeholder="Cost price">
                <input class="control" type="number" step="0.01" min="0" max="100" name="tax_rate" placeholder="Tax %">
                <label><input type="checkbox" name="is_tax_inclusive" value="1"> Tax inclusive</label>
                <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <button class="btn btn-primary" type="submit">Save marketplace price</button>
            </form>
            <div class="scroll-x"><table class="tbl">
                <thead><tr><th>Marketplace</th><th>Currency</th><th class="num">Base</th><th class="num">Sale</th><th class="num">Tax</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($marketplacePrices as $price)
                    <tr>
                        <td>{{ $price->marketplace_name ?? ('#'.$price->marketplace_id) }}<div class="sub">{{ $price->marketplace_code }}</div></td>
                        <td>{{ $price->currency_code }}</td>
                        <td class="num tnum">{{ number_format((float) $price->base_price, 2) }}</td>
                        <td class="num tnum">{{ $price->sale_price ? number_format((float) $price->sale_price, 2) : '—' }}</td>
                        <td class="num">{{ number_format((float) ($price->tax_rate ?? 0), 2) }}%</td>
                        <td><span class="badge {{ $price->is_active ? 'b-ok' : 'b-muted' }}">{{ $price->is_active ? 'active' : 'inactive' }}</span>@if($price->is_tax_inclusive)<div class="sub">tax inclusive</div>@endif</td>
                        <td>
                            <details class="modal">
                                <summary class="btn btn-ghost">Edit</summary>
                                <div class="modal-panel">
                                    <div class="modal-h"><h3>Edit Marketplace Price</h3></div>
                                    <form class="modal-b form-stack" method="post" action="/admin/products/{{ $p->id }}/marketplace-prices">@csrf
                                        <input type="hidden" name="id" value="{{ $price->id }}">
                                        <div class="form-grid">
                                            <select class="control" name="marketplace_id" required>@foreach($marketplaces as $marketplace)<option value="{{ $marketplace->id }}" @selected($price->marketplace_id==$marketplace->id)>{{ $marketplace->name }} · {{ $marketplace->code }}</option>@endforeach</select>
                                            <select class="control" name="currency_code" required>@foreach($currencies as $currency)<option value="{{ $currency->code }}" @selected($price->currency_code===$currency->code)>{{ $currency->code }} {{ $currency->symbol ?: $currency->native_symbol }}</option>@endforeach</select>
                                            <input class="control" type="number" step="0.01" min="0" name="base_price" value="{{ $price->base_price }}" required>
                                            <input class="control" type="number" step="0.01" min="0" name="sale_price" value="{{ $price->sale_price }}">
                                            <input class="control" type="number" step="0.01" min="0" name="cost_price" value="{{ $price->cost_price }}">
                                            <input class="control" type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ $price->tax_rate }}">
                                        </div>
                                        <div class="form-grid"><label><input type="checkbox" name="is_tax_inclusive" value="1" @checked($price->is_tax_inclusive)> Tax inclusive</label><label><input type="checkbox" name="is_active" value="1" @checked($price->is_active)> Active</label></div>
                                        <button class="btn btn-primary" type="submit">Save price</button>
                                    </form>
                                </div>
                            </details>
                            <form method="post" action="/admin/products/{{ $p->id }}/marketplace-prices/{{ $price->id }}/toggle" style="margin-top:6px">@csrf<button class="btn btn-ghost" type="submit">{{ $price->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty"><h3>No marketplace prices yet</h3><p>Add one row to make the public product page show regional pricing.</p></div></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>

    <div class="card">
        <div class="card-h"><h2>Seller Offers</h2><span class="sub">vendor price rows</span></div>
        <div style="padding:16px">
            <form class="form-grid" method="post" action="/admin/products/{{ $p->id }}/vendor-prices" style="margin-bottom:14px">@csrf
                <select class="control" name="vendor_id" required><option value="">Seller</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>@endforeach</select>
                <select class="control" name="currency_code" required>@foreach($currencies as $currency)<option value="{{ $currency->code }}">{{ $currency->code }} {{ $currency->symbol ?: $currency->native_symbol }}</option>@endforeach</select>
                <input class="control" type="number" step="0.01" min="0" name="selling_price" placeholder="Selling price" required>
                <input class="control" type="number" step="0.01" min="0" name="min_price" placeholder="Negotiated floor">
                <input class="control" type="number" step="0.01" min="0" name="cost_price" placeholder="Seller cost">
                <label><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <button class="btn btn-primary" type="submit">Save seller offer</button>
            </form>
            <div class="scroll-x"><table class="tbl">
                <thead><tr><th>Seller</th><th>Currency</th><th class="num">Selling</th><th class="num">Min</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($vendorPrices as $price)
                    <tr>
                        <td>{{ $price->vendor_name ?? ('#'.$price->vendor_id) }}<div class="sub">{{ $price->vendor_slug }}</div></td>
                        <td>{{ $price->currency_code }}</td>
                        <td class="num tnum">{{ number_format((float) $price->selling_price, 2) }}</td>
                        <td class="num tnum">{{ $price->min_price ? number_format((float) $price->min_price, 2) : '—' }}</td>
                        <td><span class="badge {{ $price->is_active ? 'b-ok' : 'b-muted' }}">{{ $price->is_active ? 'active' : 'inactive' }}</span></td>
                        <td>
                            <details class="modal">
                                <summary class="btn btn-ghost">Edit</summary>
                                <div class="modal-panel">
                                    <div class="modal-h"><h3>Edit Seller Offer</h3></div>
                                    <form class="modal-b form-stack" method="post" action="/admin/products/{{ $p->id }}/vendor-prices">@csrf
                                        <input type="hidden" name="id" value="{{ $price->id }}">
                                        <div class="form-grid">
                                            <select class="control" name="vendor_id" required>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected($price->vendor_id==$vendor->id)>{{ $vendor->name }}</option>@endforeach</select>
                                            <select class="control" name="currency_code" required>@foreach($currencies as $currency)<option value="{{ $currency->code }}" @selected($price->currency_code===$currency->code)>{{ $currency->code }} {{ $currency->symbol ?: $currency->native_symbol }}</option>@endforeach</select>
                                            <input class="control" type="number" step="0.01" min="0" name="selling_price" value="{{ $price->selling_price }}" required>
                                            <input class="control" type="number" step="0.01" min="0" name="min_price" value="{{ $price->min_price }}">
                                            <input class="control" type="number" step="0.01" min="0" name="cost_price" value="{{ $price->cost_price }}">
                                        </div>
                                        <label><input type="checkbox" name="is_active" value="1" @checked($price->is_active)> Active</label>
                                        <button class="btn btn-primary" type="submit">Save seller offer</button>
                                    </form>
                                </div>
                            </details>
                            <form method="post" action="/admin/products/{{ $p->id }}/vendor-prices/{{ $price->id }}/toggle" style="margin-top:6px">@csrf<button class="btn btn-ghost" type="submit">{{ $price->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty"><h3>No seller offers yet</h3><p>Add vendor prices to populate the public seller offers section.</p></div></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<div class="card stack-gap">
    <div class="card-h"><h2>Product Reviews</h2><span class="badge {{ ($reviewSummary->pending ?? 0) > 0 ? 'b-warn' : 'b-muted' }}">{{ $reviewSummary->pending ?? 0 }} pending</span></div>
    <div style="padding:16px">
        <div class="grid kpis" style="margin-bottom:12px">
            <div class="kpi"><div class="t">Approved</div><div class="v tnum">{{ number_format($reviewSummary->approved ?? 0) }}</div><div class="s">public reviews</div></div>
            <div class="kpi"><div class="t">Average</div><div class="v tnum">{{ $reviewSummary->average ?? '—' }}</div><div class="s">approved rating</div></div>
            <div class="kpi"><div class="t">Total</div><div class="v tnum">{{ number_format($reviewSummary->total ?? 0) }}</div><div class="s">all statuses</div></div>
        </div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Review</th><th>Customer</th><th>Rating</th><th>Status</th><th>Moderation</th></tr></thead>
            <tbody>
            @forelse($productReviews as $review)
                <tr>
                    <td><strong>{{ $review->title ?: 'Untitled review' }}</strong><div class="sub">{{ \Illuminate\Support\Str::limit($review->body, 180) }}</div>@if($review->use_case)<div class="sub">Use case: {{ $review->use_case }}</div>@endif</td>
                    <td>{{ $review->user_name ?: ($review->reviewer_name ?: 'Guest') }}<div class="sub">{{ $review->user_email ?: $review->reviewer_email }}</div></td>
                    <td><span class="badge b-info">{{ $review->rating }}/5</span></td>
                    <td><span class="badge {{ $review->status === 'approved' ? 'b-ok' : ($review->status === 'pending' ? 'b-warn' : 'b-muted') }}">{{ $review->status }}</span></td>
                    <td>
                        <form class="form-grid" method="post" action="/admin/products/{{ $p->id }}/reviews/{{ $review->id }}">@csrf
                            <select class="control" name="status"><option @selected($review->status==='pending')>pending</option><option @selected($review->status==='approved')>approved</option><option @selected($review->status==='rejected')>rejected</option><option @selected($review->status==='hidden')>hidden</option></select>
                            <input class="control" name="moderation_note" value="{{ $review->moderation_note }}" placeholder="Moderation note">
                            <button class="btn" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty"><h3>No reviews yet</h3><p>Public product reviews will queue here for moderation.</p></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>

<div class="grid split stack-gap">
    <div class="card">
        <div class="card-h"><h2>Technical Specs</h2></div>
        <div style="padding:16px">
            <h3 style="margin-top:0">Advanced Template Specs</h3>
            @forelse($advancedProductSpecs as $spec)
                <div style="display:flex;justify-content:space-between;gap:8px;padding:7px 0;border-bottom:1px solid var(--line)"><span><strong>{{ $spec->field_label }}</strong>: {{ $spec->value }} {{ $spec->unit_override ?: $spec->unit }}<div class="sub">{{ $spec->template_name }} · {{ $spec->field_name }}</div></span><form method="post" action="/admin/products/{{ $p->id }}/advanced-specs/{{ $spec->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Delete</button></form></div>
            @empty <div class="sub">No advanced template specs yet.</div> @endforelse
            @if($advancedSpecFields->isNotEmpty())
                <form method="post" action="/admin/products/{{ $p->id }}/advanced-specs" class="form-grid" style="margin-top:12px">@csrf
                    <select class="control" name="template_field_id" required><option value="">Choose template field</option>@foreach($advancedSpecFields as $field)<option value="{{ $field->id }}">{{ $field->template_name }} · {{ $field->field_label }} @if($field->unit)({{ $field->unit }})@endif</option>@endforeach</select>
                    <input class="control" name="value" placeholder="Value" required>
                    <input class="control" name="unit_override" placeholder="Unit override">
                    <label><input type="checkbox" name="is_visible" value="1" checked> Visible</label>
                    <button class="btn" type="submit">Save advanced spec</button>
                </form>
            @else
                <div class="sub" style="margin-top:10px">No spec template fields exist for this product category. Run or create category spec templates before adding advanced specs.</div>
            @endif
            <hr style="border:0;border-top:1px solid var(--line);margin:16px 0">
            <h3>Simple Specs</h3>
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
                <div class="form-grid"><input class="control" name="canonical_url" value="{{ $productSeo->canonical_url ?? '' }}" placeholder="Canonical URL"><select class="control" name="robots"><option @selected(($productSeo->robots ?? '')==='index,follow')>index,follow</option><option @selected(($productSeo->robots ?? '')==='noindex,follow')>noindex,follow</option><option @selected(($productSeo->robots ?? '')==='noindex,nofollow')>noindex,nofollow</option></select><input class="control" name="schema_type" value="{{ $productSeo->schema_type ?? 'Product' }}"><input class="control" name="confidence_level" value="{{ $productSeo->confidence_level ?? 'manual_admin_override' }}"></div>
                <div class="field"><label>Robots reason</label><input class="control" name="robots_reason" value="{{ $productSeo->robots_reason ?? '' }}" placeholder="Human-readable indexability reason"></div>
                <label><input type="hidden" name="is_locked" value="0"><input type="checkbox" name="is_locked" value="1" @checked($productSeo->is_locked ?? false)> Lock manual SEO against bulk regeneration</label>
                @if($productSeo && ($productSeo->generated_title ?? null))<details><summary class="sub" style="cursor:pointer">Compare generated metadata</summary><div class="note" style="margin-top:8px"><strong>{{ $productSeo->generated_title }}</strong><br>{{ $productSeo->generated_description }}<br><span class="mono">{{ $productSeo->generated_canonical_url }} · {{ $productSeo->generated_robots }}</span></div></details>@endif
                <div class="sub">active_source: {{ $productSeo->active_source ?? 'manual' }} · source_notes: manual admin metadata · confidence_level: {{ $productSeo->confidence_level ?? 'manual_admin_override' }} · last_updated: {{ $productSeo->updated_at ?? 'not saved' }} · Advisory only</div>
                <button class="btn" type="submit">Save SEO</button>
            </form>
            @if($seoVersions->isNotEmpty())
                <details>
                    <summary class="sub" style="cursor:pointer">Version history and rollback ({{ $seoVersions->count() }})</summary>
                    <div class="scroll-x" style="margin-top:10px"><table class="tbl">
                        <thead><tr><th>Version</th><th>Source</th><th>Title</th><th>Changed</th><th>Action</th></tr></thead>
                        <tbody>@foreach($seoVersions as $version)
                            <tr>
                                <td class="mono">v{{ $version->version }}<div class="sub">{{ $version->change_type }}</div></td>
                                <td>{{ $version->active_source }}<div class="sub">{{ $version->confidence_level }} · Advisory only</div></td>
                                <td>{{ $version->title ?: '—' }}<div class="sub">{{ $version->robots ?: '—' }}</div></td>
                                <td class="sub">{{ $version->last_updated ?: $version->created_at }}</td>
                                <td><form method="post" action="/admin/products/{{ $p->id }}/seo/versions/{{ $version->id }}/rollback" onsubmit="return confirm('Restore this SEO version? The current values will be retained as a safety snapshot.')">@csrf<button class="btn btn-ghost" type="submit">Restore</button></form></td>
                            </tr>
                        @endforeach</tbody>
                    </table></div>
                </details>
            @endif
        </div>
    </div>
</div>

<script nonce="{{ $csp_nonce ?? '' }}">
(function(){
    var input=document.getElementById('product-image-files'), zone=document.getElementById('product-image-dropzone'), previews=document.getElementById('product-image-previews');
    function render(files){previews.innerHTML='';Array.from(files||[]).forEach(function(file){var box=document.createElement('div');box.style.cssText='border:1px solid var(--line);border-radius:9px;padding:7px;overflow:hidden';var img=document.createElement('img');img.style.cssText='width:100%;aspect-ratio:1;object-fit:contain;background:#fff;border-radius:6px';img.alt=file.name;img.src=URL.createObjectURL(file);var label=document.createElement('div');label.className='sub';label.textContent=file.name;box.append(img,label);previews.append(box)})}
    if(input&&zone){input.addEventListener('change',function(){render(input.files)});['dragenter','dragover'].forEach(function(e){zone.addEventListener(e,function(ev){ev.preventDefault();zone.style.borderColor='var(--cyan)'})});['dragleave','drop'].forEach(function(e){zone.addEventListener(e,function(ev){ev.preventDefault();zone.style.borderColor='var(--line)'})});zone.addEventListener('drop',function(ev){if(ev.dataTransfer.files.length){input.files=ev.dataTransfer.files;render(input.files)}})}
    var upload=document.getElementById('product-image-upload');
    if(upload&&window.XMLHttpRequest){upload.addEventListener('submit',function(ev){ev.preventDefault();var xhr=new XMLHttpRequest(),progress=document.getElementById('product-image-progress'),status=document.getElementById('product-image-upload-status');xhr.open('POST',upload.action);progress.hidden=false;xhr.upload.onprogress=function(e){if(e.lengthComputable){progress.value=Math.round(e.loaded/e.total*100);status.textContent='Uploading '+progress.value+'%'}};xhr.onload=function(){if(xhr.status>=200&&xhr.status<400){status.textContent='Upload complete. Refreshing media…';window.location.reload()}else{progress.hidden=true;status.textContent='Upload failed. Check file type, size, dimensions, duplicate media, and permission.'}};xhr.onerror=function(){progress.hidden=true;status.textContent='Upload failed because the connection was interrupted.'};xhr.send(new FormData(upload))})}
    var grid=document.getElementById('product-image-grid'),dragged=null;
    function sync(){var holder=document.getElementById('product-image-order-inputs');if(!holder||!grid)return;holder.innerHTML='';grid.querySelectorAll('[data-image-id]').forEach(function(card){var field=document.createElement('input');field.type='hidden';field.name='image_ids[]';field.value=card.dataset.imageId;holder.append(field)})}
    if(grid){grid.querySelectorAll('[data-image-id]').forEach(function(card){card.addEventListener('dragstart',function(){dragged=card;card.style.opacity='.55'});card.addEventListener('dragend',function(){dragged=null;card.style.opacity='1';sync()});card.addEventListener('dragover',function(ev){ev.preventDefault();if(dragged&&dragged!==card){var rect=card.getBoundingClientRect();grid.insertBefore(dragged,ev.clientX<rect.left+rect.width/2?card:card.nextSibling)}})})}
})();
</script>

@endsection
