@extends('admin.layout')
@section('title','WhatsApp Campaigns')
@section('crumb','Opt-in, templates and manual export')
@section('content')
<div class="note"><strong>Compliance gate:</strong> WhatsApp campaign delivery remains placeholder/manual export unless opt-in and provider credentials are configured.</div>

<div class="grid" style="grid-template-columns:1fr 1fr;align-items:start;margin-bottom:16px">
    <div class="card"><div class="card-h"><h2>Create WhatsApp Template</h2></div><form method="post" action="/admin/marketing/whatsapp/templates" style="padding:16px;display:grid;gap:10px">@csrf
        <input name="name" required maxlength="190" placeholder="Template name" style="height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px">
        <input name="provider_template_name" maxlength="190" placeholder="Provider template name" style="height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px">
        <textarea name="body" rows="4" placeholder="Approved template body placeholder" style="border:1px solid var(--line);border-radius:7px;padding:10px"></textarea>
        <button class="btn btn-primary" type="submit">Create Template</button>
    </form></div>
    <div class="card"><div class="card-h"><h2>Create WhatsApp Campaign</h2></div><form method="post" action="/admin/marketing/whatsapp/campaigns" style="padding:16px;display:grid;gap:10px">@csrf
        <input name="name" required maxlength="190" placeholder="Campaign name" style="height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px">
        <input name="scheduled_at" type="datetime-local" style="height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px">
        <button class="btn btn-primary" type="submit">Create Placeholder Campaign</button>
    </form></div>
</div>

<div class="grid kpis"><div class="kpi"><div class="t">Opt-ins</div><div class="v tnum">{{ number_format($optIns) }}</div><div class="s">active</div></div><div class="kpi"><div class="t">Templates</div><div class="v tnum">{{ number_format($templates->count()) }}</div><div class="s">placeholders</div></div><div class="kpi"><div class="t">Campaigns</div><div class="v tnum">{{ number_format($campaigns->count()) }}</div><div class="s">recent</div></div></div>
<div class="card" style="margin-bottom:16px"><div class="card-h"><h2>WhatsApp Manual Export Queue</h2><span class="sub">Opt-in phones only</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Campaign</th><th>Status</th><th>Scheduled</th><th>Test</th><th>Action</th></tr></thead><tbody>@forelse($campaigns as $c)<tr><td><strong>{{ $c->name }}</strong><div class="sub">#{{ $c->id }}</div></td><td><span class="badge b-muted">{{ $c->status }}</span></td><td>{{ $c->scheduled_at ?? '—' }}</td><td><form method="post" action="/admin/marketing/whatsapp/campaigns/{{ $c->id }}/send-test" style="display:flex;gap:8px;min-width:260px">@csrf<input name="phone" required placeholder="+9779800000000" style="height:34px;border:1px solid var(--line);border-radius:7px;padding:0 10px"><button class="btn" type="submit">Queue Test</button></form></td><td><form method="post" action="/admin/marketing/whatsapp/campaigns/{{ $c->id }}/queue">@csrf<button class="btn btn-primary" type="submit">Queue Export</button></form></td></tr>@empty<tr><td colspan="5"><div class="empty"><h3>No WhatsApp campaigns yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
<div class="card"><div class="card-h"><h2>Templates</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Status</th><th>Provider name</th></tr></thead><tbody>@foreach($templates as $t)<tr><td><strong>{{ $t->name }}</strong></td><td><span class="badge b-muted">{{ $t->approval_status }}</span></td><td class="mono">{{ $t->provider_template_name ?? '—' }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
