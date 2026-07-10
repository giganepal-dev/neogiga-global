@extends('admin.layout')
@section('title','Support')
@section('crumb','Customer, seller and product inquiry inbox')

@php
    $statusBadge = fn($s) => match($s) {
        'resolved', 'closed' => 'b-ok',
        'in_progress' => 'b-info',
        'waiting_customer' => 'b-warn',
        default => 'b-muted',
    };
    $priorityBadge = fn($p) => match($p) {
        'urgent' => 'b-danger',
        'high' => 'b-warn',
        'medium' => 'b-info',
        default => 'b-muted',
    };
    $statuses = ['open','in_progress','waiting_customer','resolved','closed'];
@endphp

@section('actions')
    <details class="modal">
        <summary class="btn btn-primary">Create Ticket</summary>
        <div class="modal-panel">
            <div class="modal-h"><h3>Create support ticket</h3><span class="badge b-info">admin</span></div>
            <form method="post" action="/admin/support/tickets" class="modal-b form-stack">@csrf
                <div class="field"><label>Subject</label><input class="control" name="subject" required maxlength="255"></div>
                <div class="field"><label>Description</label><textarea class="control" name="description" required rows="5" maxlength="5000"></textarea></div>
                <div class="form-grid">
                    <div class="field"><label>Priority</label><select class="control" name="priority">@foreach(['medium','low','high','urgent'] as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach</select></div>
                    <div class="field"><label>Category</label><input class="control" name="category" maxlength="80" placeholder="product inquiry"></div>
                    <div class="field"><label>Customer</label><select class="control" name="customer_id"><option value="">No customer</option>@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                    <div class="field"><label>Assign to</label><select class="control" name="assigned_to"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                </div>
                <input type="hidden" name="channel" value="admin">
                <button class="btn btn-primary" type="submit">Create ticket</button>
            </form>
        </div>
    </details>
@endsection

@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Tickets</div><div class="v tnum">{{ number_format($stats['total']) }}</div><div class="s">all support records</div></div>
    <div class="kpi"><div class="t">Open</div><div class="v tnum">{{ number_format($stats['open']) }}</div><div class="s">needs owner</div></div>
    <div class="kpi"><div class="t">Pending</div><div class="v tnum">{{ number_format($stats['pending']) }}</div><div class="s">waiting customer</div></div>
    <div class="kpi"><div class="t">Closed</div><div class="v tnum">{{ number_format($stats['closed']) }}</div><div class="s">resolved history</div></div>
</div>

<div class="card">
    <div class="card-h">
        <h2>Support Inbox</h2>
        <form method="get" action="/admin/support" style="display:flex;gap:8px;flex-wrap:wrap">
            <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Ticket, subject, email" style="min-height:34px;max-width:210px">
            <select class="control" name="status" style="min-height:34px">
                <option value="">All statuses</option>
                @foreach($statuses as $s)<option value="{{ $s }}" @selected($filters['status']===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
            </select>
            <select class="control" name="priority" style="min-height:34px">
                <option value="">All priorities</option>
                @foreach(['low','medium','high','urgent'] as $p)<option value="{{ $p }}" @selected($filters['priority']===$p)>{{ ucfirst($p) }}</option>@endforeach
            </select>
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Ticket</th><th>Requester</th><th>Status</th><th>Owner</th><th>Timeline / Actions</th></tr></thead>
        <tbody>
        @forelse($tickets as $t)
            @php
                $meta = is_string($t->metadata ?? null) ? json_decode($t->metadata, true) : [];
                $aiHandoff = (bool) ($meta['ai_handoff'] ?? false);
            @endphp
            <tr>
                <td style="min-width:240px">
                    <strong>{{ $t->subject }}</strong>
                    <div class="sub mono">{{ $t->ticket_number }}</div>
                    <div class="sub">{{ $t->category ?? 'general' }} · {{ $t->created_at }}</div>
                </td>
                <td>{{ $t->customer_name ?? $t->requester_name ?? '—' }}<div class="sub">{{ $t->requester_email ?? '' }}</div></td>
                <td>
                    <span class="badge {{ $statusBadge($t->status) }}">{{ str_replace('_',' ',$t->status) }}</span>
                    <span class="badge {{ $priorityBadge($t->priority) }}">{{ $t->priority }}</span>
                    @if($aiHandoff)<span class="badge b-info">AI handoff</span>@endif
                </td>
                <td>{{ $t->assigned_name ?? 'Unassigned' }}</td>
                <td style="min-width:520px">
                    <details>
                        <summary class="btn btn-ghost">View / Update</summary>
                        <div style="display:grid;gap:12px;padding-top:12px">
                            <div class="note" style="margin:0">{{ $t->description }}</div>
                            <div>
                                @forelse(($messages[$t->id] ?? collect()) as $m)
                                    <div style="padding:8px 0;border-bottom:1px solid var(--line)">
                                        <span class="badge b-muted">{{ $m->sender_type }}</span>
                                        <span class="sub">{{ $m->created_at }}</span>
                                        <div>{{ $m->message }}</div>
                                    </div>
                                @empty
                                    <div class="empty" style="padding:18px"><h3>No messages yet</h3></div>
                                @endforelse
                            </div>
                            <form method="post" action="/admin/support/tickets/{{ $t->id }}" class="form-stack">@csrf
                                <div class="form-grid">
                                    <select class="control" name="status">@foreach($statuses as $s)<option value="{{ $s }}" @selected($t->status===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
                                    <select class="control" name="priority">@foreach(['low','medium','high','urgent'] as $p)<option value="{{ $p }}" @selected($t->priority===$p)>{{ ucfirst($p) }}</option>@endforeach</select>
                                    <select class="control" name="assigned_to"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected($t->assigned_to==$u->id)>{{ $u->name }}</option>@endforeach</select>
                                    <label class="chip" style="justify-content:center"><input type="checkbox" name="ai_handoff" value="1" @checked($aiHandoff)> AI handoff</label>
                                </div>
                                <textarea class="control" name="resolution_notes" rows="2" placeholder="Resolution notes">{{ $t->resolution_notes }}</textarea>
                                <button class="btn" type="submit">Save ticket</button>
                            </form>
                            <form method="post" action="/admin/support/tickets/{{ $t->id }}/messages" class="form-stack">@csrf
                                <div style="display:grid;grid-template-columns:140px 1fr 160px;gap:8px">
                                    <select class="control" name="sender_type"><option value="admin">Admin</option><option value="customer">Customer</option><option value="seller">Seller</option><option value="system">System</option><option value="ai">AI</option></select>
                                    <input class="control" name="message" required maxlength="5000" placeholder="Add timeline message">
                                    <select class="control" name="mark_status"><option value="">Keep status</option>@foreach($statuses as $s)<option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
                                </div>
                                <button class="btn btn-primary" type="submit">Add message</button>
                            </form>
                        </div>
                    </details>
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No support tickets yet</h3><p>Customer, seller and product inquiry chats will appear here.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($tickets->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $tickets->links() }}</div>
    @endif
</div>

<div class="note" style="margin-top:16px">AI responses are advisory only. Staff should verify product, order and seller details before replying to customers.</div>

@endsection
