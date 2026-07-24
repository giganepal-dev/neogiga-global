@extends('admin.layout')
@section('title', $row->name ?? 'Group')
@section('crumb', 'Email / Groups / ' . ($row->name ?? ''))

@section('content')
<div class="page-head">
    <div>
        <h2>{{ $row->name }}</h2>
        <p>{{ $row->description ?? 'No description' }}</p>
    </div>
    <div class="page-actions">
        <a href="/email/groups/{{ $row->id }}/export" class="btn btn-ghost">Export CSV</a>
        <a href="/email/groups/{{ $row->id }}/edit" class="btn btn-ghost">Edit</a>
        <form method="POST" action="/email/groups/{{ $row->id }}" style="display:inline">
            @csrf @method('DELETE')
            <button type="submit" class="btn danger" data-confirm="Delete this group?">Delete</button>
        </form>
    </div>
</div>

<div class="grid kpis">
    <div class="kpi">
        <div class="t">Subscribers</div>
        <div class="v">{{ number_format($row->subscriber_count ?? 0) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Type</div>
        <div class="v" style="font-size:1rem">{{ $row->group_type ?? 'manual' }}</div>
    </div>
    <div class="kpi">
        <div class="t">Country</div>
        <div class="v" style="font-size:1rem">{{ $row->country_code ?? 'Global' }}</div>
    </div>
    <div class="kpi">
        <div class="t">Daily Limit</div>
        <div class="v" style="font-size:1rem">{{ $row->max_emails_per_day ?? 'Unlimited' }}</div>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-h">
        <h2>Subscribers</h2>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Source</th>
                    <th>Primary</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $s)
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
                    <td><span class="badge b-muted">{{ $s->assignment_source ?? '—' }}</span></td>
                    <td>{{ $s->is_primary ? '★' : '—' }}</td>
                    <td>
                        <form method="POST" action="/email/groups/{{ $row->id }}/remove-subscribers" style="display:inline">
                            @csrf
                            <input type="hidden" name="subscriber_ids[]" value="{{ $s->id }}">
                            <button type="submit" class="btn btn-ghost btn-sm danger" data-confirm="Remove from group?">Remove</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="empty"><p>No subscribers in this group.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $subscribers->links() }}
</div>
@endsection
