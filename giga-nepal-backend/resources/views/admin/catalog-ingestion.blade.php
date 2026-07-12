@extends('admin.layout')
@section('title','Catalogue Ingestion')
@section('crumb','Catalog / Supplier Ingestion')
@section('page_actions')
    <a class="btn btn-ghost" href="/admin/imports/jlcpcb">JLCPCB Review</a>
    <a class="btn btn-ghost" href="/admin/products?status=pending">Pending Products</a>
@endsection
@section('content')
<div class="note">Supplier imports, media downloads, marketplace pricing, and public publication are independently controlled. A quality score is advisory only.</div>

<div class="grid kpis">
    <div class="kpi"><div class="t">Sources</div><div class="v tnum">{{ number_format($stats['sources']) }}</div><div class="s">registered supplier policies</div></div>
    <div class="kpi"><div class="t">Policy Review</div><div class="v tnum">{{ number_format($stats['policy_pending']) }}</div><div class="s">manual decisions pending</div></div>
    <div class="kpi"><div class="t">Open Tasks</div><div class="v tnum">{{ number_format($stats['open_tasks']) }}</div><div class="s">source and data review</div></div>
    <div class="kpi"><div class="t">Low Quality</div><div class="v tnum">{{ number_format($stats['low_quality']) }}</div><div class="s">score below 60 / 100</div></div>
</div>

<section class="card" style="margin-bottom:16px">
    <div class="card-h"><div><h2>Supplier Quotation Staging</h2><div class="sub">Load the normalized CSV from a supplier quotation into the private review queue.</div></div><span class="badge b-warn">pending review only</span></div>
    <form class="form-stack" method="post" action="/admin/catalog-ingestion/stage-document" enctype="multipart/form-data" style="padding:16px">@csrf
        <div class="dropzone"><div><strong>Normalized supplier quotation CSV</strong></div><div class="sub" style="margin:4px 0 12px">Product names, source quote prices, and labelled specifications are retained as provenance. No inventory, marketplace price, media, search index, or public product status changes here.</div><input class="control" type="file" name="quotation_csv" accept=".csv,text/csv" required></div>
        <label><input type="checkbox" name="dry_run" value="1"> Validate only and create a report without database records</label>
        <div class="actions"><button class="btn btn-primary" type="submit">Stage for Review</button><span class="sub">CSV only, maximum 50 MB</span></div>
    </form>
</section>

<section class="card" style="margin-bottom:16px">
    <div class="card-h"><div><h2>Catalogue Sources</h2><div class="sub">Audit robots and terms before enabling an approved, reviewed source.</div></div></div>
    <div class="scroll-x"><table class="tbl"><thead><tr><th>Supplier</th><th>Policy</th><th>Import</th><th>Media</th><th>Rate Limit</th><th>Last Sync</th><th>Actions</th></tr></thead><tbody>
        @forelse($sources as $source)
            @php $policy = is_string($source->catalogue_policy ?? null) ? json_decode($source->catalogue_policy, true) : (array) ($source->catalogue_policy ?? []); @endphp
            <tr>
                <td><strong>{{ $source->name }}</strong><div class="sub mono">{{ $source->code }}</div></td>
                <td><span class="badge {{ $source->status === 'approved' ? 'b-ok' : ($source->status === 'blocked' ? 'b-danger' : 'b-warn') }}">{{ str_replace('_',' ',$source->status) }}</span><div class="sub">{{ $source->description_reuse_status }}</div></td>
                <td><span class="badge {{ $source->import_enabled ? 'b-ok' : 'b-muted' }}">{{ $source->import_enabled ? 'enabled' : 'disabled' }}</span></td>
                <td><span class="badge {{ $source->media_download_enabled ? 'b-ok' : 'b-muted' }}">{{ $source->media_download_enabled ? 'enabled' : 'disabled' }}</span></td>
                <td class="tnum">{{ number_format($source->maximum_requests_per_minute ?? 0) }} rpm</td>
                <td>{{ $source->last_successful_sync_at ?: 'Never' }}</td>
                <td class="actions">
                    <form method="post" action="/admin/catalog-ingestion/sources/{{ $source->code }}/audit">@csrf<button class="btn btn-ghost" type="submit">Audit</button></form>
                    <details class="modal"><summary class="btn btn-ghost">Policy</summary><div class="modal-panel"><div class="modal-h"><h3>{{ $source->name }} Policy</h3><span class="badge b-warn">manual review</span></div><form class="modal-b form-stack" method="post" action="/admin/catalog-ingestion/sources/{{ $source->code }}">@csrf
                        <div class="field"><label>Status</label><select class="control" name="status">@foreach(['pending_manual_review','approved','blocked','disabled'] as $status)<option value="{{ $status }}" @selected($source->status === $status)>{{ str_replace('_',' ',$status) }}</option>@endforeach</select></div>
                        <div class="field"><label>Description reuse</label><select class="control" name="description_reuse_status">@foreach(['unknown','not_permitted','permitted'] as $status)<option value="{{ $status }}" @selected($source->description_reuse_status === $status)>{{ str_replace('_',' ',$status) }}</option>@endforeach</select></div>
                        <label><input type="checkbox" name="import_enabled" value="1" @checked($source->import_enabled)> Enable product import after approval</label>
                        <label><input type="checkbox" name="media_download_enabled" value="1" @checked($source->media_download_enabled)> Enable media download</label>
                        <label><input type="checkbox" name="media_rights_confirmed" value="1" @checked($policy['media_rights_confirmed'] ?? false)> I confirmed media redistribution rights</label>
                        <div class="field"><label>Decision note</label><textarea class="control" name="note" required placeholder="Terms/robots scope, source approval, media rights evidence">{{ $policy['manual_review_note'] ?? '' }}</textarea></div>
                        <button class="btn btn-primary" type="submit">Save Policy</button>
                    </form></div></details>
                </td>
            </tr>
        @empty <tr><td colspan="7"><div class="empty"><h3>No supplier sources audited</h3><p>Run a policy audit before enabling supplier discovery.</p></div></td></tr> @endforelse
    </tbody></table></div>
</section>

<section class="card" style="margin-bottom:16px">
    <div class="card-h"><div><h2>Review Queue</h2><div class="sub">Resolve source, matching, and completeness work without publishing products.</div></div><span class="badge b-info">{{ number_format($tasks->total()) }} tasks</span></div>
    <form class="filters" method="get"><select class="control" name="supplier"><option value="">All suppliers</option>@foreach($sources as $source)<option value="{{ $source->code }}" @selected($filters['supplier'] === $source->code)>{{ $source->name }}</option>@endforeach</select><select class="control" name="task_status">@foreach(['open'=>'Open','deferred'=>'Deferred','resolved'=>'Resolved',''=>'All states'] as $value=>$label)<option value="{{ $value }}" @selected($filters['task_status'] === $value)>{{ $label }}</option>@endforeach</select><button class="btn btn-ghost" type="submit">Filter</button></form>
    <div class="scroll-x"><table class="tbl"><thead><tr><th>Product</th><th>Supplier</th><th>Task</th><th>Quality</th><th>Status</th><th>Action</th></tr></thead><tbody>
        @forelse($tasks as $task)
            @php $evidence = is_string($task->evidence_json ?? null) ? json_decode($task->evidence_json, true) : (array) ($task->evidence_json ?? []); $quality = (float) ($task->data_quality_score ?? 0); @endphp
            <tr><td><strong>{{ $task->product_name ?: $task->supplier_product_name ?: 'Unmatched source record' }}</strong><div class="sub mono">{{ $task->product_sku ?: $task->supplier_sku ?: 'No SKU' }}</div></td><td>{{ $task->source_name ?: $task->source_code ?: '—' }}</td><td><span class="badge b-muted">{{ str_replace('_',' ',$task->task_type) }}</span><div class="sub">{{ implode(', ', array_slice((array) ($evidence['missing_fields'] ?? []), 0, 3)) ?: 'No missing-field summary' }}</div></td><td><span class="badge {{ $quality >= 80 ? 'b-ok' : ($quality >= 60 ? 'b-warn' : 'b-danger') }}">{{ number_format($quality, 0) }}/100</span></td><td><span class="badge {{ $task->status === 'resolved' ? 'b-ok' : ($task->status === 'deferred' ? 'b-warn' : 'b-muted') }}">{{ $task->status }}</span></td><td><details class="modal"><summary class="btn btn-ghost">Review</summary><div class="modal-panel"><div class="modal-h"><h3>Review Task #{{ $task->id }}</h3></div><form class="modal-b form-stack" method="post" action="/admin/catalog-ingestion/review-tasks/{{ $task->id }}">@csrf<div class="field"><label>Status</label><select class="control" name="status">@foreach(['open','deferred','resolved'] as $status)<option value="{{ $status }}" @selected($task->status === $status)>{{ $status }}</option>@endforeach</select></div><div class="field"><label>Review note</label><textarea class="control" name="note" required>{{ $evidence['review_note'] ?? '' }}</textarea></div><button class="btn btn-primary" type="submit">Save Review</button></form></div></details></td></tr>
        @empty <tr><td colspan="6"><div class="empty"><h3>No review tasks</h3><p>Approved source imports create review work here; products remain hidden until existing approval controls are used.</p></div></td></tr> @endforelse
    </tbody></table></div>
    @if($tasks->hasPages())<div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $tasks->links() }}</div>@endif
</section>

<section class="card"><div class="card-h"><div><h2>Import Runs</h2><div class="sub">Run status and counters. No automatic public publication.</div></div></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Run</th><th>Supplier</th><th>Status</th><th>Mode</th><th class="num">Discovered</th><th class="num">Created</th><th class="num">Review</th><th>Started</th></tr></thead><tbody>@forelse($runs as $run)<tr><td class="mono">{{ IlluminateSupportStr::limit($run->id, 12, '') }}</td><td>{{ $run->source_code }}</td><td><span class="badge {{ $run->status === 'completed' ? 'b-ok' : ($run->status === 'failed' ? 'b-danger' : 'b-warn') }}">{{ $run->status }}</span></td><td>{{ $run->mode }}</td><td class="num tnum">{{ number_format($run->products_discovered) }}</td><td class="num tnum">{{ number_format($run->products_created) }}</td><td class="num tnum">{{ number_format($run->products_queued_for_review) }}</td><td>{{ $run->started_at }}</td></tr>@empty<tr><td colspan="8"><div class="empty"><h3>No supplier runs</h3><p>Policy-gated dry runs and imports will appear here after supplier approval.</p></div></td></tr>@endforelse</tbody></table></div></section>
@endsection
