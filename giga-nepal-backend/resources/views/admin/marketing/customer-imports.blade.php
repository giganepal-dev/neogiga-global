@extends('admin.layout')
@section('title','Customer Imports')
@section('crumb','Customer Management / Import Customers')
@section('content')

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Import Customer Invoice Details</h2><span class="sub">XLSX, XLS, CSV or ODS · 20 MB maximum</span></div>
    <form method="post" action="/admin/marketing/customer-imports/preview" enctype="multipart/form-data" style="padding:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:end">
        @csrf
        <label>Spreadsheet<br><input type="file" name="file" required accept=".xlsx,.xls,.csv,.ods"></label>
        <label>Saved profile<br><select name="profile" required>@foreach($profiles as $profile)<option>{{ $profile }}</option>@endforeach</select></label>
        <label>Worksheet (optional)<br><input name="sheet" maxlength="190" placeholder="Customer Invoice Details"></label>
        <button class="btn btn-primary" type="submit">Upload &amp; Preview</button>
    </form>
    <div style="padding:0 16px 16px;color:var(--muted);font-size:.86rem">Imported invoice contacts remain <strong>unknown for marketing</strong>. No promotional opt-in is inferred from a transaction record.</div>
</div>

@if($preview)
<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Validation Preview</h2><span class="badge {{ $preview['missing_required'] ? 'b-warn' : 'b-ok' }}">{{ $preview['missing_required'] ? 'Mapping incomplete' : 'Ready' }}</span></div>
    <div style="padding:16px"><p><strong>{{ $preview['file_name'] }}</strong> · {{ $preview['worksheet'] }} · {{ number_format($preview['total_rows']) }} data rows</p>
        @if($preview['missing_required'])<p style="color:#b45309">Missing: {{ implode(', ', $preview['missing_required']) }}</p>@endif
    </div>
    <div class="scroll-x"><table class="tbl"><thead><tr>@foreach($preview['headers'] as $header)<th>{{ $header }}</th>@endforeach</tr></thead><tbody>
        @foreach($preview['rows'] as $row)<tr>@foreach($preview['headers'] as $header)<td>{{ $row['raw'][$header] ?? '—' }}</td>@endforeach</tr>@endforeach
    </tbody></table></div>
    @unless($preview['missing_required'])
    <form method="post" action="/admin/marketing/customer-imports" style="padding:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;align-items:end">
        @csrf<input type="hidden" name="preview_token" value="{{ $token }}"><input type="hidden" name="sheet" value="{{ $preview['worksheet'] }}">
        <label>Source name<br><input name="source" maxlength="190" value="Customer Invoice Details"></label>
        <label>Marketplace<br><select name="marketplace"><option value="">Auto/global</option>@foreach($marketplaces as $marketplace)<option value="{{ $marketplace->id }}">{{ $marketplace->name }}</option>@endforeach</select></label>
        <label>Batch reference<br><input name="batch" maxlength="190"></label>
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="only_valid" value="1"> Process valid rows only</label>
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="update_existing" value="1"> Fill blank exact-match fields</label>
        <button class="btn btn-primary" type="submit">Confirm &amp; Queue Import</button>
    </form>
    @endunless
</div>
@endif

<div class="grid kpis">
    <div class="kpi"><div class="t">Imports</div><div class="v tnum">{{ number_format($imports->total()) }}</div><div class="s">history retained</div></div>
    <div class="kpi"><div class="t">Countries</div><div class="v tnum">{{ number_format($countries->count()) }}</div><div class="s">with imported companies</div></div>
    <div class="kpi"><div class="t">Marketing default</div><div class="v" style="font-size:1.25rem">Unknown</div><div class="s">review required</div></div>
</div>

<div class="card" style="margin-bottom:16px"><div class="card-h"><h2>Country Audiences</h2><span class="sub">Company/contact counts; marketing eligibility remains consent-gated</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Country</th><th>Companies</th><th>Contacts</th><th>Valid emails</th><th>Marketable</th><th>Transactional / review</th><th>Invoices</th></tr></thead><tbody>
@forelse($countries as $country)<tr><td><strong>{{ $country->name }}</strong> <span class="badge b-muted">{{ $country->iso_code_2 }}</span></td><td>{{ number_format($country->companies) }}</td><td>{{ number_format($country->contacts) }}</td><td>{{ number_format($country->valid_emails) }}</td><td>{{ number_format($country->marketable) }}</td><td>{{ number_format($country->transactional_only) }}</td><td>{{ number_format($country->invoice_references) }}</td></tr>@empty<tr><td colspan="7"><div class="empty"><h3>No imported country audiences yet</h3></div></td></tr>@endforelse
</tbody></table></div></div>

<div class="card"><div class="card-h"><h2>Import History</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>File</th><th>Worksheet</th><th>Status</th><th>Rows</th><th>Imported</th><th>Duplicates</th><th>Warnings</th><th>Errors</th><th>Started</th><th></th></tr></thead><tbody>
@forelse($imports as $import)<tr><td><strong>{{ $import->original_file_name }}</strong><br><span class="sub">{{ $import->source_name }}</span></td><td>{{ $import->worksheet }}</td><td><span class="badge {{ $import->status === 'completed' ? 'b-ok' : ($import->status === 'failed' ? 'b-warn' : 'b-muted') }}">{{ str_replace('_',' ', $import->status) }}</span></td><td>{{ number_format($import->total_rows) }}</td><td>{{ number_format($import->imported_rows + $import->updated_rows) }}</td><td>{{ number_format($import->duplicate_rows) }}</td><td>{{ number_format($import->warning_rows) }}</td><td>{{ number_format($import->error_rows) }}</td><td>{{ $import->started_at ?? '—' }}</td><td><a class="btn btn-ghost" href="/admin/marketing/customer-imports/{{ $import->id }}">Report</a></td></tr>@empty<tr><td colspan="10"><div class="empty"><h3>No customer imports yet</h3></div></td></tr>@endforelse
</tbody></table></div>@if($imports->hasPages())<div style="padding:12px 16px">{{ $imports->links() }}</div>@endif</div>
@endsection
