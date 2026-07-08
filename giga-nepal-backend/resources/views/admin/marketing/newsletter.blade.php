@extends('admin.layout')
@section('title','Newsletter')
@section('crumb','Subscribers, categories and campaigns')
@section('content')

<div class="grid split" style="margin-bottom:16px">
    <div class="card"><div class="card-h"><h2>Create Newsletter Template</h2></div><form method="post" action="/admin/marketing/newsletter/templates" class="form-stack" style="padding:16px">@csrf
        <input class="control" name="name" required maxlength="190" placeholder="Template name">
        <input class="control" name="subject" maxlength="190" placeholder="Subject">
        <textarea class="control" name="text_body" rows="4" placeholder="Plain text body"></textarea>
        <button class="btn btn-primary" type="submit">Create Template</button>
    </form></div>
    <div class="card"><div class="card-h"><h2>Create Newsletter Campaign</h2></div><form method="post" action="/admin/marketing/newsletter/campaigns" class="form-stack" style="padding:16px">@csrf
        <input class="control" name="name" required maxlength="190" placeholder="Campaign name">
        <input class="control" name="subject" maxlength="190" placeholder="Subject">
        <input class="control" name="scheduled_at" type="datetime-local">
        <button class="btn btn-primary" type="submit">Create Campaign</button>
    </form></div>
</div>

<div class="grid kpis"><div class="kpi"><div class="t">Subscribers</div><div class="v tnum">{{ number_format($subscribers->total()) }}</div><div class="s">all statuses</div></div><div class="kpi"><div class="t">Categories</div><div class="v tnum">{{ number_format($categories->count()) }}</div><div class="s">interests</div></div><div class="kpi"><div class="t">Campaigns</div><div class="v tnum">{{ number_format($campaigns->count()) }}</div><div class="s">recent</div></div></div>
<div class="card" style="margin-bottom:16px"><div class="card-h"><h2>Newsletter Campaign Queue</h2><span class="sub">Subscribed audience only</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Campaign</th><th>Subject</th><th>Status</th><th>Test</th><th>Action</th></tr></thead><tbody>@forelse($campaigns as $c)<tr><td><strong>{{ $c->name }}</strong><div class="sub">#{{ $c->id }}</div></td><td>{{ $c->subject ?? '—' }}</td><td><span class="badge b-muted">{{ $c->status }}</span></td><td><form method="post" action="/admin/marketing/newsletter/campaigns/{{ $c->id }}/send-test" style="display:flex;gap:8px;min-width:260px">@csrf<input class="control" name="email" type="email" required placeholder="test@example.com" style="min-height:34px"><button class="btn" type="submit">Queue Test</button></form></td><td><form method="post" action="/admin/marketing/newsletter/campaigns/{{ $c->id }}/queue">@csrf<button class="btn btn-primary" type="submit">Queue Now</button></form></td></tr>@empty<tr><td colspan="5"><div class="empty"><h3>No newsletter campaigns yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
<div class="card"><div class="card-h"><h2>Subscribers</h2><span class="sub">Double opt-in supported</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Email</th><th>Name</th><th>Status</th><th>Source</th><th>Confirmed</th></tr></thead><tbody>@forelse($subscribers as $s)<tr><td class="mono">{{ $s->email }}</td><td>{{ $s->name ?? '—' }}</td><td><span class="badge {{ $s->status==='subscribed'?'b-ok':'b-muted' }}">{{ $s->status }}</span></td><td>{{ $s->source ?? '—' }}</td><td>{{ $s->confirmed_at ?? '—' }}</td></tr>@empty<tr><td colspan="5"><div class="empty"><h3>No subscribers yet</h3></div></td></tr>@endforelse</tbody></table></div>@if($subscribers->hasPages())<div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $subscribers->links() }}</div>@endif</div>
<div class="card stack-gap"><div class="card-h"><h2>Newsletter Categories</h2></div><div style="padding:16px;display:flex;gap:8px;flex-wrap:wrap">@foreach($categories as $c)<span class="badge b-info">{{ $c->name }}</span>@endforeach</div></div>
@endsection
