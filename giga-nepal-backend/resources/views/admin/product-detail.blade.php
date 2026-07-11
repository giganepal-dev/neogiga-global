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
            <div class="field">
                <label>Short summary</label>
                <textarea class="control" name="short_description" rows="3" maxlength="1000" placeholder="Example: Compact ESP32 development board for Wi-Fi/Bluetooth prototypes, classroom labs, and IoT proof-of-concepts.">{{ $p->short_description }}</textarea>
                <div class="sub">Shown near the top of the product page. Keep it buyer-focused, specific, and easy to scan.</div>
            </div>
            <div class="field">
                <label>Detailed description</label>
                <textarea class="control" name="description" rows="8" placeholder="Describe practical use cases, compatibility, included items, engineering benefits, compliance notes, and sourcing guidance. Use short paragraphs rather than copied datasheet text.">{{ $p->description }}</textarea>
                <div class="sub">Used for the public Product overview section before technical specifications.</div>
            </div>
            <div class="field">
                <label>SEO title</label>
                <input class="control" name="meta_title" value="{{ $p->meta_title ?? '' }}" maxlength="60" placeholder="{{ $p->name }} - Buy Online from NeoGiga">
                <div class="sub">Recommended: 50-60 characters, include product type, MPN or manufacturer when useful.</div>
            </div>
            <div class="field">
                <label>SEO description</label>
                <textarea class="control" name="meta_description" rows="3" maxlength="160" placeholder="Buy {{ $p->name }} with specs, RFQ sourcing, regional availability, and engineering support from NeoGiga.">{{ $p->meta_description ?? '' }}</textarea>
                <div class="sub">Recommended: 140-160 characters. This maps to the existing product save flow.</div>
            </div>
            <div class="sub">Copy standard: write original marketplace copy. Do not paste copyrighted distributor descriptions.</div>
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
                <div style="display:flex;justify-content:space-between;gap:8px;padding:7px 0;border-bottom:1px solid var(--line)"><span><strong>{{ $spec->name }}</strong>: {{ $spec->value }} {{ $spec->unit }}<div class="sub">Sort {{ $spec->sort_order ?? 100 }} · {{ $spec->is_visible ? 'visible on product page' : 'hidden from product page' }}{{ $spec->is_filterable ? ' · filterable in catalog' : '' }}</div></span><form method="post" action="/admin/products/{{ $p->id }}/specs/{{ $spec->id }}">@csrf @method('DELETE')<button class="btn btn-ghost danger" type="submit">Delete</button></form></div>
            @empty <div class="sub">No specs yet.</div> @endforelse
            <form method="post" action="/admin/products/{{ $p->id }}/specs" class="form-stack" style="margin-top:12px">@csrf
                <div class="form-grid">
                    <div class="field"><label>Spec name</label><input class="control" name="name" placeholder="Voltage / Connector / Package" required></div>
                    <div class="field"><label>Value</label><input class="control" name="value" placeholder="12 / 2.4 / 1000"></div>
                    <div class="field"><label>Unit</label><input class="control" name="unit" placeholder="V / mm / pcs"></div>
                    <div class="field"><label>Sort order</label><input class="control" type="number" name="sort_order" min="0" value="100" placeholder="100"></div>
                </div>
                <div class="form-grid">
                    <label><input type="checkbox" name="is_visible" value="1" checked> Visible on public page</label>
                    <label><input type="checkbox" name="is_filterable" value="1"> Filterable in catalog</label>
                </div>
                <div class="sub">Use simple specs for buyer-facing facts. Put detailed electrical/parametric specs in advanced template specs when available.</div>
                <button class="btn" type="submit">Add spec</button>
            </form>
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
