@extends('admin.layout')
@section('title', $row->email ?? 'Subscriber')
@section('crumb', 'Email / Subscribers / ' . ($row->email ?? ''))

@section('content')
<div class="page-head">
    <div>
        <h2>{{ $row->first_name }} {{ $row->last_name }}</h2>
        <p>{{ $row->email }}</p>
    </div>
    <div class="page-actions">
        <a href="/email/subscribers/{{ $row->id }}/edit" class="btn btn-ghost">Edit</a>
        <form method="POST" action="/email/subscribers/{{ $row->id }}" style="display:inline">
            @csrf @method('DELETE')
            <button type="submit" class="btn danger" data-confirm="Delete this subscriber?">Delete</button>
        </form>
    </div>
</div>

<div class="grid split">
    <div>
        <div class="card">
            <div class="card-h"><h2>Details</h2></div>
            <div style="padding:16px">
                <table class="tbl">
                    <tr><td style="font-weight:600;width:140px">Email</td><td>{{ $row->email }}</td></tr>
                    <tr><td style="font-weight:600">Name</td><td>{{ $row->first_name }} {{ $row->last_name }}</td></tr>
                    <tr><td style="font-weight:600">Phone</td><td>{{ $row->phone ?? '—' }}</td></tr>
                    <tr><td style="font-weight:600">Company</td><td>{{ $row->company ?? '—' }}</td></tr>
                    <tr><td style="font-weight:600">Country</td><td>{{ $row->country ?? '—' }}</td></tr>
                    <tr>
                        <td style="font-weight:600">Status</td>
                        <td>
                            @if($row->status === 'active')
                                <span class="badge b-ok">active</span>
                            @else
                                <span class="badge b-muted">{{ $row->status }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr><td style="font-weight:600">Source</td><td>{{ $row->source ?? '—' }}</td></tr>
                    <tr><td style="font-weight:600">Engagement</td><td>{{ $row->engagement_score ?? 0 }}</td></tr>
                    <tr><td style="font-weight:600">Created</td><td>{{ $row->created_at?->format('M j, Y g:i A') ?? '—' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Groups</h2></div>
            <div style="padding:16px">
                @forelse($groups as $g)
                    <span class="badge b-info" style="margin-right:4px">
                        {{ $g->name }}
                        @if($g->is_primary) ★ @endif
                    </span>
                @empty
                    <p style="color:var(--muted)">No groups assigned.</p>
                @endforelse
            </div>
        </div>

        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Tags</h2></div>
            <div style="padding:16px">
                @forelse($tags as $t)
                    <span class="badge b-muted" style="margin-right:4px">{{ $t->name }}</span>
                @empty
                    <p style="color:var(--muted)">No tags.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-h"><h2>Delivery History</h2></div>
            <div class="scroll-x">
                <table class="tbl">
                    <thead>
                        <tr><th>Event</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        @forelse($deliveryLogs as $log)
                        <tr>
                            <td><span class="badge {{ $log->status === 'sent' ? 'b-ok' : ($log->status === 'opened' ? 'b-info' : 'b-muted') }}">{{ $log->status }}</span></td>
                            <td>{{ $log->created_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="empty"><p>No delivery history.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
