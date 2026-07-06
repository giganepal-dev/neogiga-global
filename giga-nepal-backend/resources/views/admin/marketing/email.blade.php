@extends('admin.layout')
@section('title','Email Campaigns')
@section('crumb','Templates, campaigns and queued messages')
@section('content')
<div class="note"><strong>Safe mode:</strong> Current provider is log/test mode. Marketing sends must respect unsubscribe and suppression lists.</div>

<div class="grid" style="grid-template-columns:1fr 1fr;align-items:start;margin-bottom:16px">
    <div class="card"><div class="card-h"><h2>Create Email Template</h2></div><form method="post" action="/admin/marketing/email/templates" style="padding:16px;display:grid;gap:10px">@csrf
        <input name="name" required maxlength="190" placeholder="Template name" style="height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px">
        <input name="type" required maxlength="80" placeholder="newsletter, welcome, order_confirmation" style="height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px">
        <input name="subject" required maxlength="190" placeholder="Subject" style="height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px">
        <textarea name="text_body" rows="4" placeholder="Use variables like @{{customer_name}} and @{{unsubscribe_url}}" style="border:1px solid var(--line);border-radius:7px;padding:10px"></textarea>
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_transactional" value="1"> Transactional</label>
        <button class="btn btn-primary" type="submit">Create Template</button>
    </form></div>
    <div class="card"><div class="card-h"><h2>Create Email Campaign</h2></div><form method="post" action="/admin/marketing/email/campaigns" style="padding:16px;display:grid;gap:10px">@csrf
        <input name="name" required maxlength="190" placeholder="Campaign name" style="height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px">
        <input name="type" maxlength="60" value="marketing" style="height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px">
        <input name="scheduled_at" type="datetime-local" style="height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px">
        <button class="btn btn-primary" type="submit">Create Campaign</button>
    </form></div>
</div>

<div class="card" style="margin-bottom:16px"><div class="card-h"><h2>Email Campaign Queue</h2><span class="sub">Safe-mode queue only</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Campaign</th><th>Status</th><th>Scheduled</th><th>Test</th><th>Action</th></tr></thead><tbody>@forelse($campaigns as $c)<tr><td><strong>{{ $c->name }}</strong><div class="sub">#{{ $c->id }} {{ $c->type }}</div></td><td><span class="badge b-muted">{{ $c->status }}</span></td><td>{{ $c->scheduled_at ?? '—' }}</td><td><form method="post" action="/admin/marketing/email/campaigns/{{ $c->id }}/send-test" style="display:flex;gap:8px;min-width:260px">@csrf<input name="email" type="email" required placeholder="test@example.com" style="height:34px;border:1px solid var(--line);border-radius:7px;padding:0 10px"><button class="btn" type="submit">Queue Test</button></form></td><td><form method="post" action="/admin/marketing/email/campaigns/{{ $c->id }}/queue">@csrf<button class="btn btn-primary" type="submit">Queue Now</button></form></td></tr>@empty<tr><td colspan="5"><div class="empty"><h3>No email campaigns yet</h3></div></td></tr>@endforelse</tbody></table></div></div>

<div class="grid" style="grid-template-columns:1fr 1fr;align-items:start">
<div class="card"><div class="card-h"><h2>Email Templates</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Type</th><th>Transactional</th></tr></thead><tbody>@foreach($templates as $t)<tr><td><strong>{{ $t->name }}</strong></td><td>{{ $t->type }}</td><td>@if($t->is_transactional)<span class="badge b-info">Yes</span>@else<span class="badge b-muted">Marketing</span>@endif</td></tr>@endforeach</tbody></table></div></div>
<div class="card"><div class="card-h"><h2>Recent Messages</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>To</th><th>Type</th><th>Status</th></tr></thead><tbody>@forelse($messages as $m)<tr><td class="mono">{{ $m->to_email }}</td><td>{{ $m->message_type }}</td><td><span class="badge b-muted">{{ $m->status }}</span></td></tr>@empty<tr><td colspan="3"><div class="empty"><h3>No queued messages</h3></div></td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection
