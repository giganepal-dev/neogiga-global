@extends('admin.layout')
@section('title', 'Email Templates')
@section('crumb', 'Email / Templates')

@section('content')
<div class="page-head">
    <div>
        <h2>Email Templates</h2>
        <p>Create and manage reusable HTML email templates with merge tags.</p>
    </div>
    <div class="page-actions">
        <a href="/email/templates/create" class="btn btn-primary">New Template</a>
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="card-h">
        <h2>Templates ({{ $templates->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="control" name="search" value="{{ request('search') }}" placeholder="Search templates..." style="width:220px">
            <select class="control" name="type" style="width:140px">
                <option value="">All Types</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
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
                    <th>Event Key</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $t)
                <tr>
                    <td>
                        <a href="/email/templates/{{ $t->id }}" style="font-weight:600;color:var(--fg)">{{ $t->name }}</a>
                        @if($t->is_default)<span class="badge b-info" style="margin-left:6px">default</span>@endif
                    </td>
                    <td class="mono" style="font-size:.82rem">{{ $t->event_key }}</td>
                    <td><span class="badge b-muted">{{ $t->type ?? '—' }}</span></td>
                    <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $t->subject }}</td>
                    <td class="num">v{{ $t->version }}</td>
                    <td>
                        @if($t->is_active)
                            <span class="badge b-ok">active</span>
                        @else
                            <span class="badge b-muted">draft</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap">{{ $t->updated_at?->diffForHumans() ?? '—' }}</td>
                    <td style="white-space:nowrap">
                        <a href="/email/templates/{{ $t->id }}" class="btn btn-ghost btn-sm">View</a>
                        <a href="/email/templates/{{ $t->id }}/edit" class="btn btn-ghost btn-sm">Edit</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty">
                        <p>No templates yet. Create your first template to get started.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $templates->withQueryString()->links() }}
</div>

<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Available Merge Tags</h2></div>
    <div class="card-body">
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            @foreach(['first_name','last_name','contact_name','customer_name','company_name','email','phone','country','country_name','marketplace_name','marketplace_url','order_number','order_date','order_status','order_total','currency','invoice_number','invoice_url','unsubscribe_url','preferences_url','current_year'] as $tag)
                <code style="padding:4px 8px;background:var(--bg);border:1px solid var(--border);border-radius:4px;font-size:.82rem">{{{{ $tag }}}}</code>
            @endforeach
        </div>
    </div>
</div>
@endsection
