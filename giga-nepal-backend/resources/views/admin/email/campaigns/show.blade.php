@extends('admin.layout')
@section('title', $row->name ?? 'Campaign')
@section('crumb', 'Email / Campaigns / ' . ($row->name ?? ''))

@section('content')
<div class="page-head">
    <div>
        <h2>{{ $row->name }}</h2>
        <p>{{ $row->subject }}</p>
    </div>
    <div class="page-actions">
        @if($row->status === 'draft')
            <form method="POST" action="/email/campaigns/{{ $row->id }}/launch" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-primary" data-confirm="Launch this campaign?">Launch</button>
            </form>
        @endif
        @if($row->status === 'sending')
            <form method="POST" action="/email/campaigns/{{ $row->id }}/pause" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-ghost">Pause</button>
            </form>
        @endif
        @if($row->status === 'paused')
            <form method="POST" action="/email/campaigns/{{ $row->id }}/resume" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-primary">Resume</button>
            </form>
        @endif
        @if(in_array($row->status, ['draft', 'scheduled']))
            <form method="POST" action="/email/campaigns/{{ $row->id }}/cancel" style="display:inline">
                @csrf
                <button type="submit" class="btn ghost danger" data-confirm="Cancel this campaign?">Cancel</button>
            </form>
        @endif
        <form method="POST" action="/email/campaigns/{{ $row->id }}/duplicate" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-ghost">Duplicate</button>
        </form>
        <a href="/email/campaigns/{{ $row->id }}/edit" class="btn btn-ghost">Edit</a>
        <form method="POST" action="/email/campaigns/{{ $row->id }}" style="display:inline">
            @csrf @method('DELETE')
            <button type="submit" class="btn danger" data-confirm="Delete this campaign?">Delete</button>
        </form>
    </div>
</div>

<div class="grid kpis">
    <div class="kpi">
        <div class="t">Status</div>
        <div class="v" style="font-size:1rem">
            @if($row->status === 'sent')
                <span class="badge b-ok">sent</span>
            @elseif($row->status === 'sending')
                <span class="badge b-info">sending</span>
            @elseif($row->status === 'scheduled')
                <span class="badge b-warn">scheduled</span>
            @else
                <span class="badge b-muted">{{ $row->status }}</span>
            @endif
        </div>
    </div>
    <div class="kpi">
        <div class="t">Recipients</div>
        <div class="v">{{ number_format($row->recipient_count ?? 0) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Sent</div>
        <div class="v">{{ number_format($row->sent_count ?? 0) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Opened</div>
        <div class="v">{{ number_format($row->open_count ?? 0) }}</div>
        <div class="s">{{ ($row->recipient_count ?? 0) > 0 ? round(($row->open_count / max($row->recipient_count, 1)) * 100, 1).'%' : '—' }}</div>
    </div>
    <div class="kpi">
        <div class="t">Clicked</div>
        <div class="v">{{ number_format($row->click_count ?? 0) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Bounced</div>
        <div class="v">{{ number_format($row->bounce_count ?? 0) }}</div>
    </div>
</div>

<div class="grid split" style="margin-top:16px">
    <div class="card">
        <div class="card-h"><h2>Details</h2></div>
        <div style="padding:16px">
            <table class="tbl">
                <tr><td style="font-weight:600;width:140px">Subject</td><td>{{ $row->subject }}</td></tr>
                <tr><td style="font-weight:600">Preview Text</td><td>{{ $row->preview_text ?? '—' }}</td></tr>
                <tr><td style="font-weight:600">Template</td><td>{{ $template->name ?? '—' }}</td></tr>
                <tr><td style="font-weight:600">Sender</td><td>{{ $sender->sender_name ?? 'Default' }} {{ $sender ? "<{$sender->sender_email}>" : '' }}</td></tr>
                <tr><td style="font-weight:600">Segment</td><td>{{ $segment->name ?? '—' }}</td></tr>
                <tr><td style="font-weight:600">Scheduled</td><td>{{ $row->scheduled_at?->format('M j, Y g:i A') ?? 'Not scheduled' }}</td></tr>
                <tr><td style="font-weight:600">Sent At</td><td>{{ $row->sent_at?->format('M j, Y g:i A') ?? '—' }}</td></tr>
                <tr><td style="font-weight:600">Created</td><td>{{ $row->created_at?->format('M j, Y g:i A') ?? '—' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-h"><h2>Quick Actions</h2></div>
        <div style="padding:16px;display:flex;flex-direction:column;gap:8px">
            @if($row->status !== 'sent')
            <form method="POST" action="/email/campaigns/{{ $row->id }}/test">
                @csrf
                <div style="display:flex;gap:8px">
                    <input class="control" name="test_email" type="email" placeholder="Test email address" required style="flex:1">
                    <button type="submit" class="btn btn-ghost">Send Test</button>
                </div>
            </form>
            @endif
            <a href="/email/campaigns/{{ $row->id }}/recipients" class="btn btn-ghost">View Recipients</a>
            <a href="/email/campaigns/{{ $row->id }}/analytics" class="btn btn-ghost">View Analytics</a>
        </div>
    </div>
</div>

@if(isset($showAnalytics) && $showAnalytics && isset($deliveryLogs))
<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Delivery Breakdown</h2></div>
    <div style="padding:16px">
        <table class="tbl">
            <thead>
                <tr><th>Event</th><th class="num">Count</th></tr>
            </thead>
            <tbody>
                @foreach($deliveryLogs as $status => $count)
                <tr>
                    <td><span class="badge {{ $status === 'sent' ? 'b-ok' : ($status === 'opened' ? 'b-info' : 'b-muted') }}">{{ $status }}</span></td>
                    <td class="num">{{ number_format($count) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
