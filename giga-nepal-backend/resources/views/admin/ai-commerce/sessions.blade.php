@extends('admin.layout')
@section('title', 'AI Sessions')
@section('crumb', 'AI Commerce / Sessions')

@section('content')
<div class="page-head">
    <div>
        <h2>AI Sessions</h2>
        <p>Browse and inspect all AI conversation sessions.</p>
    </div>
    <div class="page-actions">
        <a href="/admin/ai-commerce" class="btn btn-ghost">Back to Dashboard</a>
    </div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Sessions ({{ $sessions->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="control" name="search" value="{{ request('search') }}" placeholder="Search sessions..." style="width:200px">
            <select class="control" name="context" style="width:120px">
                <option value="">All Context</option>
                @foreach(['general','bom','pos','lms'] as $ctx)
                    <option value="{{ $ctx }}" {{ request('context') === $ctx ? 'selected' : '' }}>{{ ucfirst($ctx) }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Session ID</th>
                    <th>User</th>
                    <th>Context</th>
                    <th>Goal</th>
                    <th>Messages</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $s)
                @php
                    $history = json_decode($s->conversation_history ?? '[]', true) ?? [];
                    $msgCount = is_array($history) ? count($history) : 0;
                    $isActive = ! $s->expires_at || \Carbon\Carbon::parse($s->expires_at)->isFuture();
                @endphp
                <tr>
                    <td class="mono" style="font-size:.78rem">{{ substr($s->session_id, 0, 16) }}...</td>
                    <td>
                        {{ $s->user_name ?? 'Anonymous' }}
                        @if($s->user_email)
                            <br><span style="color:var(--muted);font-size:.78rem">{{ $s->user_email }}</span>
                        @endif
                    </td>
                    <td><span class="badge b-muted">{{ $s->context }}</span></td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $s->current_goal ?? '—' }}</td>
                    <td class="num">{{ $msgCount }}</td>
                    <td>
                        @if($isActive)
                            <span class="badge b-ok">active</span>
                        @else
                            <span class="badge b-muted">ended</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap;font-size:.82rem">{{ $s->created_at?->diffForHumans() ?? '—' }}</td>
                    <td>
                        <a href="/admin/ai-commerce/sessions/{{ $s->session_id }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty">
                        <p>No sessions found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $sessions->withQueryString()->links() }}
</div>
@endsection
