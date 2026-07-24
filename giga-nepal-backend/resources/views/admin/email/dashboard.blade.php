@extends('admin.layout')
@section('title', 'Email Marketing Dashboard')
@section('crumb', 'Email / Dashboard')

@section('content')
<div class="page-head">
    <div>
        <h2>Email Marketing Dashboard</h2>
        <p>Overview of your email marketing performance.</p>
    </div>
    <div class="page-actions">
        <a href="/email/campaigns/create" class="btn btn-primary">New Campaign</a>
        <a href="/email/subscribers" class="btn btn-ghost">Subscribers</a>
    </div>
</div>

<div class="grid kpis">
    <div class="kpi">
        <div class="t">Total Subscribers</div>
        <div class="v">{{ number_format($stats['total_subscribers']) }}</div>
        <div class="s">{{ $stats['active_subscribers'] }} active</div>
    </div>
    <div class="kpi">
        <div class="t">Total Campaigns</div>
        <div class="v">{{ number_format($stats['total_campaigns']) }}</div>
        <div class="s">{{ $stats['sent_campaigns'] }} sent, {{ $stats['draft_campaigns'] }} drafts</div>
    </div>
    <div class="kpi">
        <div class="t">Emails Sent</div>
        <div class="v">{{ number_format($stats['total_sent']) }}</div>
        <div class="s">{{ number_format($stats['total_opened']) }} opened</div>
    </div>
    <div class="kpi">
        <div class="t">Open Rate</div>
        <div class="v">{{ $stats['total_sent'] > 0 ? round(($stats['total_opened'] / max($stats['total_sent'], 1)) * 100, 1).'%' : '—' }}</div>
        <div class="s">{{ number_format($stats['total_clicked']) }} clicked</div>
    </div>
    <div class="kpi">
        <div class="t">Click Rate</div>
        <div class="v">{{ $stats['total_sent'] > 0 ? round(($stats['total_clicked'] / max($stats['total_sent'], 1)) * 100, 1).'%' : '—' }}</div>
        <div class="s">{{ number_format($stats['total_bounced']) }} bounced</div>
    </div>
    <div class="kpi">
        <div class="t">Groups</div>
        <div class="v">{{ number_format($stats['total_groups']) }}</div>
        <div class="s">{{ number_format($stats['total_segments']) }} segments</div>
    </div>
    <div class="kpi">
        <div class="t">Suppressed</div>
        <div class="v">{{ number_format($stats['suppressed_count']) }}</div>
        <div class="s">emails blocked</div>
    </div>
</div>

<div class="grid split" style="margin-top:16px">
    <div class="card">
        <div class="card-h">
            <h2>Recent Campaigns</h2>
            <a href="/email/campaigns" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="scroll-x">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Recipients</th>
                        <th>Sent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentCampaigns as $c)
                    <tr>
                        <td><a href="/email/campaigns/{{ $c->id }}" style="font-weight:600;color:var(--fg)">{{ $c->name }}</a></td>
                        <td>
                            @if($c->status === 'sent')
                                <span class="badge b-ok">sent</span>
                            @elseif($c->status === 'sending')
                                <span class="badge b-info">sending</span>
                            @elseif($c->status === 'scheduled')
                                <span class="badge b-warn">scheduled</span>
                            @elseif($c->status === 'draft')
                                <span class="badge b-muted">draft</span>
                            @else
                                <span class="badge b-muted">{{ $c->status }}</span>
                            @endif
                        </td>
                        <td class="num">{{ number_format($c->recipient_count) }}</td>
                        <td class="num">{{ number_format($c->sent_count) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty"><p>No campaigns yet.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-h">
            <h2>Recent Subscribers</h2>
            <a href="/email/subscribers" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="scroll-x">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSubscribers as $s)
                    <tr>
                        <td><a href="/email/subscribers/{{ $s->id }}" style="font-weight:600;color:var(--fg)">{{ $s->email }}</a></td>
                        <td>{{ $s->first_name }} {{ $s->last_name }}</td>
                        <td>
                            @if($s->status === 'active')
                                <span class="badge b-ok">active</span>
                            @else
                                <span class="badge b-muted">{{ $s->status }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="empty"><p>No subscribers yet.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
