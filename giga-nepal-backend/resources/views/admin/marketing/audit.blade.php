@extends('admin.layout')
@section('title','Marketing Audit Log')
@section('crumb','Admin marketing activity and accountability')
@section('content')
<div class="note"><strong>Audit trail.</strong> Marketing admin writes are recorded here with action, entity, actor, IP address, and metadata. Logs are append-only at application level.</div>
<div class="card">
    <div class="card-h"><h2>Recent Marketing Admin Actions</h2><div class="sub">{{ number_format($logs->total()) }} entries</div></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Entity</th><th>IP</th><th>Metadata</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="mono">{{ $log->created_at }}</td>
                    <td>{{ $log->user_email ?? ('User #'.$log->user_id) ?? '—' }}</td>
                    <td><span class="badge b-info">{{ $log->action }}</span></td>
                    <td class="mono">{{ $log->entity_type ?? '—' }}{{ $log->entity_id ? '#'.$log->entity_id : '' }}</td>
                    <td class="mono">{{ $log->ip_address ?? '—' }}</td>
                    <td class="mono">{{ Str::limit((string) $log->metadata, 140) }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty"><h3>No audit entries yet</h3><p>Entries appear after authenticated admins create or update marketing records.</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())<div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $logs->links() }}</div>@endif
</div>
@endsection
