@extends('admin.layout')
@section('title', 'Import Detail')
@section('crumb', 'Marketing / Campaign Contacts / Import #{{ $import->id }}')

@section('content')
<div class="page-head">
    <div>
        <h2>Import #{{ $import->id }}</h2>
        <p>{{ $import->name ?? 'Campaign Contact Import' }}</p>
    </div>
    <div class="page-actions">
        <a href="/admin/marketing/campaign-contacts" class="btn btn-ghost">Back to List</a>
    </div>
</div>

<div class="grid kpis">
    <div class="kpi">
        <div class="t">Total Rows</div>
        <div class="v">{{ number_format($import->total_rows) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Created</div>
        <div class="v" style="color:var(--ok)">{{ number_format($import->created_rows) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Updated</div>
        <div class="v">{{ number_format($import->updated_rows) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Skipped</div>
        <div class="v">{{ number_format($import->skipped_rows) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Errors</div>
        <div class="v" style="color:{{ $import->error_rows > 0 ? 'var(--danger)' : 'var(--ok)' }}">{{ number_format($import->error_rows) }}</div>
    </div>
</div>

<div class="card">
    <div class="card-h"><h2>Import Details</h2></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
            <div>
                <div class="sub">Source</div>
                <div style="font-weight:600">{{ strtoupper($import->source) }}</div>
            </div>
            <div>
                <div class="sub">Status</div>
                <div><span class="badge {{ $import->status === 'completed' ? 'b-ok' : ($import->status === 'failed' ? 'b-danger' : 'b-muted') }}">{{ ucfirst($import->status) }}</span></div>
            </div>
            <div>
                <div class="sub">Batch</div>
                <div>{{ $import->batch ?? '-' }}</div>
            </div>
            <div>
                <div class="sub">Created At</div>
                <div>{{ $import->created_at }}</div>
            </div>
        </div>
    </div>
</div>

@if($errors->count() > 0)
<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Errors ({{ $errors->count() }})</h2></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Row</th>
                    <th>Field</th>
                    <th>Code</th>
                    <th>Severity</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                @foreach($errors as $error)
                <tr>
                    <td class="num">{{ $error->row_number }}</td>
                    <td>{{ $error->field ?? '-' }}</td>
                    <td class="mono">{{ $error->code }}</td>
                    <td><span class="badge {{ $error->severity === 'error' ? 'b-danger' : 'b-warn' }}">{{ $error->severity }}</span></td>
                    <td>{{ $error->message }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card" style="margin-top:16px">
    <div class="empty">
        <p>No errors found in this import.</p>
    </div>
</div>
@endif
@endsection
