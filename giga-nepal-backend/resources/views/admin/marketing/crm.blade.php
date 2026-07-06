@extends('admin.layout')
@section('title','CRM & Segments')
@section('crumb','Customers, consent, lists and audiences')
@section('content')

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Create Segment</h2><div class="sub">Validated admin action</div></div>
    <form method="post" action="/admin/marketing/segments" style="padding:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end">
        @csrf
        <label>Name<br><input name="name" required maxlength="190" style="width:100%;height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px"></label>
        <label>Customer type<br><input name="customer_type" maxlength="60" placeholder="b2b" style="width:100%;height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px"></label>
        <label>Lifecycle<br><input name="lifecycle_stage" maxlength="60" placeholder="lead" style="width:100%;height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px"></label>
        <label>Status<br><input name="status" maxlength="60" placeholder="active" style="width:100%;height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px"></label>
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="marketing_opt_in" value="1"> Email opt-in</label>
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="whatsapp_opt_in" value="1"> WhatsApp opt-in</label>
        <button class="btn btn-primary" type="submit">Create Segment</button>
    </form>
</div>

<div class="grid kpis">
 <div class="kpi"><div class="t">Customers</div><div class="v tnum">{{ number_format($customers->total()) }}</div><div class="s">profiles</div></div>
 <div class="kpi"><div class="t">Segments</div><div class="v tnum">{{ number_format($segments->count()) }}</div><div class="s">dynamic/static</div></div>
 <div class="kpi"><div class="t">Contact lists</div><div class="v tnum">{{ number_format($contactLists->count()) }}</div><div class="s">email/WhatsApp</div></div>
 <div class="kpi"><div class="t">Suppressed</div><div class="v tnum">{{ number_format($suppressed) }}</div><div class="s">do-not-contact</div></div>
</div>
<div class="card"><div class="card-h"><h2>Customer Profiles</h2><span class="sub">API: /api/admin/customers</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Type</th><th>Stage</th><th>Opt-in</th></tr></thead><tbody>
@forelse($customers as $c)<tr><td><strong>{{ trim(($c->first_name ?? '').' '.($c->last_name ?? '')) ?: '—' }}</strong></td><td class="mono">{{ $c->email ?? '—' }}</td><td>{{ $c->phone ?? $c->whatsapp_number ?? '—' }}</td><td>{{ $c->customer_type ?? 'retail' }}</td><td><span class="badge b-muted">{{ str_replace('_',' ', $c->lifecycle_stage ?? 'lead') }}</span></td><td>@if($c->marketing_opt_in)<span class="badge b-ok">Email</span>@else<span class="badge b-muted">No</span>@endif</td></tr>@empty<tr><td colspan="6"><div class="empty"><h3>No CRM profiles yet</h3><p>Profiles are created from registrations, OTP login, imports, and campaign forms.</p></div></td></tr>@endforelse
</tbody></table></div>@if($customers->hasPages())<div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $customers->links() }}</div>@endif</div>
<div class="card" style="margin-top:16px"><div class="card-h"><h2>Segments</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Type</th><th>Status</th><th>Last refresh</th><th></th></tr></thead><tbody>@foreach($segments as $s)<tr><td><strong>{{ $s->name }}</strong></td><td>{{ $s->type }}</td><td>@if($s->is_active)<span class="badge b-ok">Active</span>@else<span class="badge b-muted">Inactive</span>@endif</td><td>{{ $s->last_refreshed_at ?? '—' }}</td><td><form method="post" action="/admin/marketing/segments/{{ $s->id }}/refresh">@csrf<button class="btn btn-ghost" type="submit">Refresh</button></form></td></tr>@endforeach</tbody></table></div></div>
@endsection
