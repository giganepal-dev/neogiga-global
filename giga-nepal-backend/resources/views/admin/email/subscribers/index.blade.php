@extends('admin.layout')
@section('title', 'Email Subscribers')
@section('crumb', 'Email / Subscribers')

@section('content')
<div class="page-head">
    <div>
        <h2>Email Subscribers</h2>
        <p>Manage your email subscriber list.</p>
    </div>
    <div class="page-actions">
        <a href="/email/subscribers/export" class="btn btn-ghost">Export CSV</a>
        <a href="/email/subscribers/create" class="btn btn-primary">Add Subscriber</a>
    </div>
</div>

<div class="grid kpis">
    <div class="kpi">
        <div class="t">Total</div>
        <div class="v">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Active</div>
        <div class="v">{{ number_format($stats['active']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Unsubscribed</div>
        <div class="v">{{ number_format($stats['unsubscribed']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Bounced</div>
        <div class="v">{{ number_format($stats['bounced']) }}</div>
    </div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Subscribers ({{ $subscribers->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="control" name="search" value="{{ request('search') }}" placeholder="Search email or name..." style="width:220px">
            <select class="control" name="status" style="width:140px">
                <option value="">All Status</option>
                @foreach(['active', 'unsubscribed', 'bounced', 'complained'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>
    <form method="POST" action="/email/subscribers/bulk-action">
        @csrf
        <div style="padding:10px 16px;border-bottom:1px solid var(--line);display:flex;gap:8px;align-items:center">
            <select name="action" class="control" style="width:160px">
                <option value="activate">Activate</option>
                <option value="deactivate">Unsubscribe</option>
                <option value="delete">Delete</option>
            </select>
            <button type="submit" class="btn btn-ghost" data-confirm="Apply bulk action to selected subscribers?">Apply</button>
        </div>
        <div class="scroll-x">
            <table class="tbl">
                <thead>
                    <tr>
                        <th style="width:40px"><input type="checkbox" data-check-all></th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Engagement</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $s)
                    <tr>
                        <td><input type="checkbox" name="subscriber_ids[]" value="{{ $s->id }}" data-check></td>
                        <td><a href="/email/subscribers/{{ $s->id }}" style="font-weight:600;color:var(--fg)">{{ $s->email }}</a></td>
                        <td>{{ $s->first_name }} {{ $s->last_name }}</td>
                        <td>
                            @if($s->status === 'active')
                                <span class="badge b-ok">active</span>
                            @elseif($s->status === 'unsubscribed')
                                <span class="badge b-warn">unsubscribed</span>
                            @else
                                <span class="badge b-muted">{{ $s->status }}</span>
                            @endif
                        </td>
                        <td><span class="badge b-muted">{{ $s->source ?? '—' }}</span></td>
                        <td class="num">{{ $s->engagement_score ?? 0 }}</td>
                        <td style="white-space:nowrap">{{ $s->created_at?->diffForHumans() ?? '—' }}</td>
                        <td style="white-space:nowrap">
                            <a href="/email/subscribers/{{ $s->id }}" class="btn btn-ghost btn-sm">View</a>
                            <a href="/email/subscribers/{{ $s->id }}/edit" class="btn btn-ghost btn-sm">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="empty"><p>No subscribers found.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
    {{ $subscribers->withQueryString()->links() }}
</div>
@endsection
