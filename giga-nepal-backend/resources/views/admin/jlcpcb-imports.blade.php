@extends('admin.layout')
@section('title','JLCPCB Import Review')
@section('crumb','Catalog / Import Review')
@section('page_actions')
    <a class="btn btn-ghost" href="/admin/products?status=draft">Draft Products</a>
    <a class="btn btn-ghost" href="/admin/products">All Products</a>
@endsection
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Imported</div><div class="v tnum">{{ number_format($stats['total']) }}</div><div class="s">JLCPCB source links</div></div>
    <div class="kpi"><div class="t">Pending</div><div class="v tnum">{{ number_format($stats['pending']) }}</div><div class="s">needs admin review</div></div>
    <div class="kpi"><div class="t">Approved</div><div class="v tnum">{{ number_format($stats['approved']) }}</div><div class="s">catalog accepted</div></div>
    <div class="kpi"><div class="t">Rejected</div><div class="v tnum">{{ number_format($stats['rejected']) }}</div><div class="s">hidden / not used</div></div>
</div>

<section class="card">
    <div class="card-h">
        <div><h2>Imported Product Queue</h2><div class="sub">{{ number_format($imports->total()) }} rows in this view</div></div>
        <span class="badge b-info">source: jlcpcb_parts_database</span>
    </div>
    <form class="filters" method="get">
        <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search product, SKU, MPN, source ID">
        <select class="control" name="review_status">
            <option value="" @selected($filters['review_status']==='')>All review states</option>
            @foreach(['needs_review' => 'Needs review', 'pending_review' => 'Pending review', 'source_imported_pending_approval' => 'Imported pending approval', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                <option value="{{ $value }}" @selected($filters['review_status']===$value)>{{ $label }}</option>
            @endforeach
        </select>
        <select class="control" name="batch_id">
            <option value="">All batches</option>
            @foreach($batches as $batch)
                <option value="{{ $batch->id }}" @selected($filters['batch_id']===$batch->id)>{{ $batch->id }} · {{ $batch->status }}</option>
            @endforeach
        </select>
        <select class="control" name="quality">
            <option value="">All quality</option>
            <option value="high" @selected($filters['quality']==='high')>Score >= 0.85</option>
            <option value="low" @selected($filters['quality']==='low')>Score < 0.85</option>
        </select>
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>

    <form id="bulkImportApprove" method="post" action="/admin/imports/jlcpcb/bulk-approve">@csrf</form>
        <div style="display:flex;gap:10px;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--line);background:#fff">
            <div class="sub">Select up to 100 pending rows, then approve. Public publishing is optional and off by default.</div>
            <div class="actions">
                <label class="sub"><input form="bulkImportApprove" type="checkbox" name="publish_public" value="1"> Publish public</label>
                <input form="bulkImportApprove" class="control" name="note" placeholder="Review note" style="min-width:220px">
                <button form="bulkImportApprove" class="btn btn-primary" type="submit" onclick="return confirm('Approve selected imported products?')">Bulk Approve</button>
            </div>
        </div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th></th><th>Product</th><th>Source</th><th>Manufacturer</th><th>Category</th><th>Quality</th><th>Offer</th><th>Review</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($imports as $row)
                @php
                    $review = $row->review_status ?? 'pending_review';
                    $raw = is_string($row->raw_snapshot ?? null) ? json_decode($row->raw_snapshot, true) : (array) ($row->raw_snapshot ?? []);
                    $warnings = $raw['warnings'] ?? [];
                    $docs = $documents[$row->product_id] ?? collect();
                @endphp
                <tr>
                    <td>
                        @if($review === 'pending_review')
                            <input form="bulkImportApprove" type="checkbox" name="source_ids[]" value="{{ $row->id }}">
                        @endif
                    </td>
                    <td>
                        <strong>{{ $row->product_name }}</strong>
                        <div class="sub mono">{{ $row->sku }} · {{ $row->mpn ?: 'no mpn' }}</div>
                        <div class="sub"><a href="/admin/products/{{ $row->product_id }}">admin detail</a> · <a href="/products/{{ $row->product_slug }}" target="_blank">public page</a></div>
                    </td>
                    <td>
                        <span class="mono">{{ $row->source_part_id }}</span>
                        <div class="sub">batch {{ \Illuminate\Support\Str::limit((string) $row->import_batch_id, 12, '') }}</div>
                        <details><summary class="sub">source snapshot</summary><pre class="mono" style="white-space:pre-wrap;max-width:420px;max-height:240px;overflow:auto">{{ json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details>
                    </td>
                    <td>{{ $row->manufacturer_name ?: $row->brand_name ?: '—' }}</td>
                    <td>{{ $row->category_name ?: '—' }}</td>
                    <td>
                        <span class="badge {{ (float) $row->data_quality_score >= 0.85 ? 'b-ok' : 'b-warn' }}">{{ number_format((float) $row->data_quality_score, 2) }}</span>
                        @if($warnings)<div class="sub">{{ implode(', ', array_slice($warnings, 0, 2)) }}</div>@endif
                    </td>
                    <td>
                        <div class="tnum">{{ number_format((float) ($row->offer_stock ?? 0)) }}</div>
                        <div class="sub">{{ number_format((float) $row->offer_count) }} offer row(s)</div>
                    </td>
                    <td>
                        <span class="badge {{ $review === 'approved' ? 'b-ok' : ($review === 'rejected' ? 'b-warn' : 'b-muted') }}">{{ str_replace('_', ' ', $review) }}</span>
                        <div class="sub">product: {{ $row->product_status }} / {{ $row->approval_status ?? 'n/a' }} / {{ $row->visibility_status ?? 'n/a' }}</div>
                        @if($docs->isNotEmpty())<div class="sub">{{ $docs->count() }} document(s)</div>@endif
                    </td>
                    <td class="actions">
                        @if($review !== 'approved')
                            <form method="post" action="/admin/imports/jlcpcb/{{ $row->id }}/approve">@csrf
                                <input type="hidden" name="note" value="Approved from import review queue">
                                <button class="btn btn-ghost" type="submit">Approve</button>
                            </form>
                            <details class="modal">
                                <summary class="btn btn-ghost">Publish</summary>
                                <div class="modal-panel">
                                    <div class="modal-h"><h3>Approve & Publish</h3><span class="badge b-warn">public</span></div>
                                    <form class="modal-b form-stack" method="post" action="/admin/imports/jlcpcb/{{ $row->id }}/approve">@csrf
                                        <input type="hidden" name="publish_public" value="1">
                                        <textarea class="control" name="note" placeholder="Review note"></textarea>
                                        <button class="btn btn-primary" type="submit" onclick="return confirm('Approve and make this imported product public?')">Approve & Publish</button>
                                    </form>
                                </div>
                            </details>
                        @endif
                        @if($review !== 'rejected')
                            <details class="modal">
                                <summary class="btn btn-ghost danger">Reject</summary>
                                <div class="modal-panel">
                                    <div class="modal-h"><h3>Reject Import</h3></div>
                                    <form class="modal-b form-stack" method="post" action="/admin/imports/jlcpcb/{{ $row->id }}/reject">@csrf
                                        <textarea class="control" name="reason" required placeholder="Reason"></textarea>
                                        <button class="btn btn-primary" type="submit" onclick="return confirm('Reject and hide this imported product?')">Reject Import</button>
                                    </form>
                                </div>
                            </details>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><div class="empty"><h3>No import rows found</h3><p>Change filters or run a reviewed pilot import.</p></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    @if($imports->hasPages())<div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $imports->links() }}</div>@endif
</section>

@endsection
