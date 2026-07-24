@extends('admin.layout')
@section('title', 'Email Imports')
@section('crumb', 'Email / Imports')

@section('content')
<div class="page-head">
    <div>
        <h2>Email Imports</h2>
        <p>Import subscribers from CSV files.</p>
    </div>
    <div class="page-actions">
        <a href="/email/imports/create" class="btn btn-primary">New Import</a>
    </div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Import Jobs ({{ $imports->total() }})</h2>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>File</th>
                    <th>Status</th>
                    <th>Total Rows</th>
                    <th>Imported</th>
                    <th>Duplicates</th>
                    <th>Errors</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($imports as $imp)
                <tr>
                    <td>
                        <a href="/email/imports/{{ $imp->id }}" style="font-weight:600;color:var(--fg)">{{ $imp->filename ?? '—' }}</a>
                    </td>
                    <td>
                        @if($imp->status === 'completed')
                            <span class="badge b-ok">completed</span>
                        @elseif($imp->status === 'processing')
                            <span class="badge b-info">processing</span>
                        @elseif($imp->status === 'uploaded')
                            <span class="badge b-warn">uploaded</span>
                        @else
                            <span class="badge b-muted">{{ $imp->status }}</span>
                        @endif
                    </td>
                    <td class="num">{{ number_format($imp->total_rows ?? 0) }}</td>
                    <td class="num">{{ number_format($imp->imported_count ?? 0) }}</td>
                    <td class="num">{{ number_format($imp->duplicate_count ?? 0) }}</td>
                    <td class="num">{{ number_format($imp->error_count ?? 0) }}</td>
                    <td style="white-space:nowrap">{{ $imp->created_at?->diffForHumans() ?? '—' }}</td>
                    <td>
                        <a href="/email/imports/{{ $imp->id }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty"><p>No imports yet.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $imports->links() }}
</div>
@endsection
