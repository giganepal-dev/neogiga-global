@extends('admin.layout')
@section('title','ElecForest Catalog Imports')
@section('crumb','Catalog / ElecForest Imports')
@section('page_actions')
    <a class="btn btn-ghost" href="/admin/products?status=draft">Draft Products</a>
    <a class="btn btn-ghost" href="/admin/imports/jlcpcb">JLCPCB Review</a>
@endsection
@section('content')

@if(session('success'))
    <div class="card" style="padding:14px 16px;margin-bottom:16px;border-color:#86efac;background:#f0fdf4">{{ session('success') }}</div>
@endif

<div class="grid kpis">
    <div class="kpi"><div class="t">Source Records</div><div class="v tnum">{{ number_format($stats['source_records']) }}</div><div class="s">linked ElecForest rows</div></div>
    <div class="kpi"><div class="t">Draft Products</div><div class="v tnum">{{ number_format($stats['drafts']) }}</div><div class="s">hidden and review-required</div></div>
    <div class="kpi"><div class="t">Open Reviews</div><div class="v tnum">{{ number_format($stats['open_reviews']) }}</div><div class="s">identity, taxonomy, content or rights</div></div>
    <div class="kpi"><div class="t">Unresolved Failures</div><div class="v tnum">{{ number_format($stats['failed_rows']) }}</div><div class="s">safe to retry by run</div></div>
    <div class="kpi"><div class="t">Images Downloaded</div><div class="v tnum">{{ number_format($stats['media_downloaded']) }}</div><div class="s">inactive until rights approval</div></div>
    <div class="kpi"><div class="t">Media Review</div><div class="v tnum">{{ number_format($stats['media_pending']) }}</div><div class="s">pending redistribution rights</div></div>
</div>

<section class="card" style="margin-bottom:16px">
    <div class="card-h"><div><h2>Review Readiness</h2><div class="sub">Source quality and publication blockers across the imported catalog.</div></div></div>
    <div class="grid kpis" style="padding:16px">
        <div class="kpi"><div class="t">Duplicate Candidates</div><div class="v tnum">{{ number_format($stats['duplicate_candidates']) }}</div></div>
        <div class="kpi"><div class="t">Unresolved Taxonomy</div><div class="v tnum">{{ number_format($stats['unresolved_categories']) }}</div></div>
        <div class="kpi"><div class="t">Missing Brand</div><div class="v tnum">{{ number_format($stats['missing_brand']) }}</div></div>
        <div class="kpi"><div class="t">Missing Manufacturer</div><div class="v tnum">{{ number_format($stats['missing_manufacturer']) }}</div></div>
        <div class="kpi"><div class="t">Missing MPN</div><div class="v tnum">{{ number_format($stats['missing_mpn']) }}</div></div>
        <div class="kpi"><div class="t">Missing / Failed Images</div><div class="v tnum">{{ number_format($stats['missing_images']) }} / {{ number_format($stats['failed_images']) }}</div></div>
        <div class="kpi"><div class="t">Missing Description / SEO</div><div class="v tnum">{{ number_format($stats['missing_descriptions']) }} / {{ number_format($stats['missing_seo']) }}</div></div>
        <div class="kpi"><div class="t">Ready / Review</div><div class="v tnum">{{ number_format($stats['ready_to_publish']) }} / {{ number_format($stats['requiring_review']) }}</div></div>
    </div>
</section>

<section class="card" style="margin-bottom:16px">
    <div class="card-h"><div><h2>Production-safe Operations</h2><div class="sub">All catalog records remain draft/hidden. Supplier price and availability never write NeoGiga selling prices or warehouse inventory.</div></div></div>
    <div class="grid two" style="padding:16px">
        <form class="form-stack" method="post" action="/admin/imports/elecforest/start">@csrf
            <h3>Start audited import</h3>
            <select class="control" name="mode"><option value="dry_run">20-row dry run</option><option value="queue">Queue production import</option></select>
            <div class="grid two">
                <label class="sub">Start line<input class="control" type="number" min="1" max="3178" name="start_line" value="2"></label>
                <label class="sub">Limit (0 = all)<input class="control" type="number" min="0" max="3178" name="limit" value="20"></label>
            </div>
            <label class="sub"><input type="checkbox" name="download_images" value="1"> Queue secure image downloads for internal rights review</label>
            <button class="btn btn-primary" type="submit" onclick="return confirm('Start this ElecForest operation?')">Start Operation</button>
        </form>
        <div class="form-stack">
            <h3>Maintenance</h3>
            <form method="post" action="/admin/imports/elecforest/generate-seo">@csrf
                <input type="hidden" name="limit" value="0"><button class="btn btn-ghost" type="submit">Regenerate Draft SEO</button>
            </form>
            <form method="post" action="/admin/imports/elecforest/download-images">@csrf
                <input type="hidden" name="limit" value="0"><button class="btn btn-ghost" type="submit">Queue Pending Images</button>
            </form>
            <form method="post" action="/admin/imports/elecforest/publish-qualified">@csrf
                <button class="btn btn-ghost" type="submit" onclick="return confirm('Run strict publication gates? Unqualified products remain draft.')">Publish Qualified Only</button>
            </form>
            <div class="sub">Isolation check: <span class="badge {{ $validation['isolation_passed'] ? 'b-ok' : 'b-warn' }}">{{ $validation['isolation_passed'] ? 'passed' : 'failed' }}</span></div>
            <div class="sub">Warehouse {{ number_format($validation['warehouse_stock_rows']) }} · marketplace price {{ number_format($validation['marketplace_price_rows']) }} · vendor price {{ number_format($validation['vendor_price_rows']) }} · country price {{ number_format($validation['country_price_rows']) }}</div>
        </div>
    </div>
</section>

<section class="card" style="margin-bottom:16px">
    <div class="card-h"><div><h2>Imported Product Review</h2><div class="sub">{{ number_format($products->total()) }} source-linked products in this view. Use product detail to edit content, identity, specifications and SEO.</div></div><span class="badge b-info">source: elecforest</span></div>
    <form class="filters" method="get">
        <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Product, NeoGiga SKU, supplier SKU or URL">
        <select class="control" name="review_status">
            <option value="">All review states</option>
            @foreach(['pending_review','approved','rejected'] as $status)<option value="{{ $status }}" @selected($filters['review_status']===$status)>{{ str_replace('_',' ',$status) }}</option>@endforeach
        </select>
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Product</th><th>Identity</th><th>Taxonomy</th><th>Supplier Observation</th><th>Quality</th><th>Review</th></tr></thead>
        <tbody>
        @forelse($products as $row)
            <tr>
                <td><strong>{{ $row->name }}</strong><div class="sub"><a href="/admin/products/{{ $row->product_id }}">edit product</a> · <span class="mono">{{ $row->sku }}</span></div><div class="sub">{{ $row->status }} / {{ $row->visibility_status }}</div></td>
                <td><div>Supplier SKU: <span class="mono">{{ $row->supplier_sku ?: 'missing' }}</span></div><div class="sub">Brand: {{ $row->brand_name ?: 'review required' }}</div><div class="sub">Manufacturer: {{ $row->manufacturer_name ?: 'review required' }}</div></td>
                <td>{{ $row->category_name ?: 'ElecForest Review' }}</td>
                <td><div class="tnum">{{ $row->source_currency ?: '—' }} {{ $row->source_price !== null ? number_format((float)$row->source_price,2) : '—' }}</div><div class="sub">{{ $row->source_stock_state ?: 'unknown' }} · source-only</div><a class="sub" href="{{ $row->source_url }}" target="_blank" rel="noopener noreferrer">source page</a></td>
                <td><span class="badge {{ (float)$row->data_quality_score >= .75 ? 'b-ok' : 'b-warn' }}">{{ number_format((float)$row->data_quality_score,2) }}</span></td>
                <td><span class="badge b-muted">{{ str_replace('_',' ',$row->review_status) }}</span><div class="sub">last seen {{ $row->last_seen_at }}</div><div class="sub"><a href="/admin/products/{{ $row->product_id }}">compare, assign identity, edit or approve</a></div></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No ElecForest products found</h3><p>Run a 20-row dry run before starting a queued import.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($products->hasPages())<div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $products->links() }}</div>@endif
</section>

<section class="card" style="margin-bottom:16px">
    <div class="card-h"><div><h2>Category Mapping</h2><div class="sub">Map only to existing NeoGiga category names or slugs; no duplicate taxonomy is created here.</div></div></div>
    <form class="filters" method="post" action="/admin/imports/elecforest/map-category">@csrf
        <input class="control" name="source_category" required placeholder="Modules / Boards">
        <input class="control" name="neo_category" required placeholder="development-boards">
        <button class="btn btn-primary" type="submit">Approve Mapping</button>
    </form>
    <div class="scroll-x"><table class="tbl"><thead><tr><th>Source path</th><th>NeoGiga category</th><th>Confidence</th><th>Status</th></tr></thead><tbody>
        @foreach($mappings as $mapping)<tr><td>{{ $mapping->source_category_path }}</td><td>{{ $mapping->category_name ?: 'unresolved' }}</td><td>{{ number_format((float)$mapping->confidence,2) }}</td><td>{{ str_replace('_',' ',$mapping->mapping_status) }}</td></tr>@endforeach
    </tbody></table></div>
</section>

<section class="card" style="margin-bottom:16px">
    <div class="card-h"><div><h2>Import Runs</h2><div class="sub">Resumable runs with exact source checksum and line checkpoint.</div></div></div>
    <div class="scroll-x"><table class="tbl"><thead><tr><th>Run</th><th>Status</th><th>Mode</th><th>Discovered</th><th>Created</th><th>Updated</th><th>Unchanged</th><th>Rejected</th><th>Last line</th><th>Action</th></tr></thead><tbody>
        @foreach($runs as $run)<tr><td class="mono">{{ $run->id }}</td><td>{{ $run->status }}</td><td>{{ $run->mode }}</td><td>{{ number_format($run->products_discovered) }}</td><td>{{ number_format($run->products_created) }}</td><td>{{ number_format($run->products_updated) }}</td><td>{{ number_format($run->products_unchanged) }}</td><td>{{ number_format($run->products_rejected) }}</td><td>{{ number_format($run->last_line ?? 0) }}</td><td><div style="display:flex;gap:6px;flex-wrap:wrap">@if(in_array($run->status,['running','queued']))<form method="post" action="/admin/imports/elecforest/runs/{{ $run->id }}/pause">@csrf<button class="btn btn-ghost" type="submit">Pause</button></form>@elseif($run->status==='paused')<form method="post" action="/admin/imports/elecforest/runs/{{ $run->id }}/resume">@csrf<button class="btn btn-ghost" type="submit">Resume</button></form>@endif @if(($run->failed_records ?? 0)>0)<form method="post" action="/admin/imports/elecforest/runs/{{ $run->id }}/retry">@csrf<button class="btn btn-ghost" type="submit">Retry failures</button></form>@endif</div></td></tr>@endforeach
    </tbody></table></div>
</section>

<section class="card">
    <div class="card-h"><div><h2>Recent Failures</h2><div class="sub">Raw records stay internal and can be retried without changing existing catalog data.</div></div></div>
    <div class="scroll-x"><table class="tbl"><thead><tr><th>Run</th><th>Line</th><th>Error</th><th>Attempts</th><th>Status</th></tr></thead><tbody>
        @forelse($failures as $failure)<tr><td class="mono">{{ $failure->catalog_import_run_id }}</td><td>{{ $failure->line_number }}</td><td>{{ $failure->error_message }}</td><td>{{ $failure->attempts }}</td><td>{{ $failure->retry_status }}</td></tr>@empty<tr><td colspan="5">No failures recorded.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endsection
