@extends('admin.layout')
@section('title', 'AI Commerce')
@section('crumb', 'AI Commerce')

@section('content')
<div class="page-head">
    <div>
        <h2>AI Commerce</h2>
        <p>Monitor AI sessions, BOM builds, and system configuration.</p>
    </div>
    <div class="page-actions">
        <a href="/admin/ai-commerce/sessions" class="btn btn-ghost">All Sessions</a>
        <a href="/admin/ai-commerce/bom-builds" class="btn btn-ghost">BOM Builds</a>
        <a href="/admin/ai-commerce/settings" class="btn btn-primary">Settings</a>
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif

{{-- KPIs --}}
<div class="kpis">
    <div class="kpi">
        <div class="t">Total Sessions</div>
        <div class="v">{{ number_format($stats['total_sessions']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Active Sessions</div>
        <div class="v" style="color:var(--ok)">{{ number_format($stats['active_sessions']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Sessions Today</div>
        <div class="v">{{ number_format($stats['sessions_today']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">BOM Builds</div>
        <div class="v">{{ number_format($stats['total_bom_builds']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">BOM Today</div>
        <div class="v">{{ number_format($stats['bom_builds_today']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Cart Actions</div>
        <div class="v">{{ number_format($stats['total_cart_actions']) }}</div>
    </div>
</div>

{{-- Recent Sessions --}}
<div class="card" style="margin-top:20px">
    <div class="card-h">
        <h2>Recent Sessions</h2>
        <a href="/admin/ai-commerce/sessions" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Session ID</th>
                    <th>User</th>
                    <th>Context</th>
                    <th>Goal</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentSessions as $s)
                <tr>
                    <td class="mono" style="font-size:.78rem">{{ substr($s->session_id, 0, 12) }}...</td>
                    <td>
                        {{ $s->user_name ?? 'Anonymous' }}
                        @if($s->user_email)
                            <br><span style="color:var(--muted);font-size:.78rem">{{ $s->user_email }}</span>
                        @endif
                    </td>
                    <td><span class="badge b-muted">{{ $s->context }}</span></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $s->current_goal ?? '—' }}</td>
                    <td style="white-space:nowrap;font-size:.82rem">{{ $s->created_at?->diffForHumans() ?? '—' }}</td>
                    <td>
                        <a href="/admin/ai-commerce/sessions/{{ $s->session_id }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty">
                        <p>No AI sessions yet.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
