@extends('admin.layout')
@section('title', 'Email Campaigns')
@section('crumb', 'Email / Campaigns')

@section('content')
<div class="page-head">
    <div>
        <h2>Email Campaigns</h2>
        <p>Create and manage email marketing campaigns.</p>
    </div>
    <div class="page-actions">
        <a href="/email/campaigns/create" class="btn btn-primary">New Campaign</a>
    </div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Campaigns ({{ $campaigns->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="control" name="search" value="{{ request('search') }}" placeholder="Search campaigns..." style="width:220px">
            <select class="control" name="status" style="width:140px">
                <option value="">All Status</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Recipients</th>
                    <th>Sent</th>
                    <th>Opens</th>
                    <th>Clicks</th>
                    <th>Scheduled</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $c)
                <tr>
                    <td>
                        <a href="/email/campaigns/{{ $c->id }}" style="font-weight:600;color:var(--fg)">{{ $c->name }}</a>
                    </td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $c->subject }}</td>
                    <td>
                        @if($c->status === 'sent')
                            <span class="badge b-ok">sent</span>
                        @elseif($c->status === 'sending')
                            <span class="badge b-info">sending</span>
                        @elseif($c->status === 'scheduled')
                            <span class="badge b-warn">scheduled</span>
                        @elseif($c->status === 'paused')
                            <span class="badge b-warn">paused</span>
                        @elseif($c->status === 'draft')
                            <span class="badge b-muted">draft</span>
                        @elseif($c->status === 'cancelled')
                            <span class="badge b-danger">cancelled</span>
                        @else
                            <span class="badge b-muted">{{ $c->status }}</span>
                        @endif
                    </td>
                    <td class="num">{{ number_format($c->recipient_count ?? 0) }}</td>
                    <td class="num">{{ number_format($c->sent_count ?? 0) }}</td>
                    <td class="num">{{ number_format($c->open_count ?? 0) }}</td>
                    <td class="num">{{ number_format($c->click_count ?? 0) }}</td>
                    <td style="white-space:nowrap">{{ $c->scheduled_at?->format('M j, g:i A') ?? '—' }}</td>
                    <td style="white-space:nowrap">
                        <a href="/email/campaigns/{{ $c->id }}" class="btn btn-ghost btn-sm">View</a>
                        <a href="/email/campaigns/{{ $c->id }}/edit" class="btn btn-ghost btn-sm">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="empty"><p>No campaigns yet. Create your first campaign.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $campaigns->withQueryString()->links() }}
</div>
@endsection
