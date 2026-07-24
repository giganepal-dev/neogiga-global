@extends('admin.layout')
@section('title', 'Import Details')
@section('crumb', 'Email / Imports / ' . ($row->filename ?? ''))

@section('content')
<div class="page-head">
    <div>
        <h2>Import: {{ $row->filename }}</h2>
        <p>Import job #{{ $row->id }}</p>
    </div>
    <div class="page-actions">
        @if($row->status === 'completed')
            <a href="/email/imports/{{ $row->id }}/download-success" class="btn btn-ghost">Download Success</a>
            <a href="/email/imports/{{ $row->id }}/download-errors" class="btn btn-ghost">Download Errors</a>
            <a href="/email/imports/{{ $row->id }}/download-duplicates" class="btn btn-ghost">Download Duplicates</a>
        @endif
    </div>
</div>

<div class="grid kpis">
    <div class="kpi">
        <div class="t">Status</div>
        <div class="v" style="font-size:1rem">
            @if($row->status === 'completed')
                <span class="badge b-ok">completed</span>
            @elseif($row->status === 'processing')
                <span class="badge b-info">processing</span>
            @else
                <span class="badge b-warn">{{ $row->status }}</span>
            @endif
        </div>
    </div>
    <div class="kpi">
        <div class="t">Total Rows</div>
        <div class="v">{{ number_format($row->total_rows ?? 0) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Imported</div>
        <div class="v">{{ number_format($row->imported_count ?? 0) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Duplicates</div>
        <div class="v">{{ number_format($row->duplicate_count ?? 0) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Errors</div>
        <div class="v">{{ number_format($row->error_count ?? 0) }}</div>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Details</h2></div>
    <div style="padding:16px">
        <table class="tbl">
            <tr><td style="font-weight:600;width:160px">File</td><td>{{ $row->filename }}</td></tr>
            <tr><td style="font-weight:600">Created</td><td>{{ $row->created_at?->format('M j, Y g:i A') ?? '—' }}</td></tr>
            <tr><td style="font-weight:600">Completed</td><td>{{ $row->completed_at?->format('M j, Y g:i A') ?? '—' }}</td></tr>
        </table>
    </div>
</div>

@if($errors->count())
<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Import Errors</h2></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Row</th>
                    <th>Email</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
                @foreach($errors as $e)
                <tr>
                    <td class="num">{{ $e->row_number }}</td>
                    <td class="mono">{{ $e->email ?? '—' }}</td>
                    <td style="color:var(--danger)">{{ $e->error }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($row->status === 'uploaded' || $row->status === 'mapped')
<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Process Import</h2></div>
    <form method="POST" action="/email/imports/process">
        @csrf
        <input type="hidden" name="import_id" value="{{ $row->id }}">
        <div style="padding:16px">
            <p style="color:var(--muted);margin-bottom:12px">Ready to process this import. This will add subscribers to your list.</p>
        </div>
        <div style="padding:16px;border-top:1px solid var(--line);display:flex;gap:8px;justify-content:flex-end">
            <button type="submit" class="btn btn-primary" data-confirm="Process this import?">Process Import</button>
        </div>
    </form>
</div>
@endif
@endsection
