@extends('admin.layout')
@section('title','Email Providers')
@section('crumb','Marketing / Email / Providers')

@section('page_actions')
<a class="btn btn-primary" href="/admin/email/providers/create">Add Provider</a>
<a class="btn btn-ghost" href="/admin/marketing/settings#email">Marketing Settings</a>
@endsection

@section('content')
<div class="card">
<div class="card-h"><h2>Email Providers</h2><span class="sub">Amazon SES, Mailgun, Postmark, SendGrid &amp; SMTP</span></div>
<div class="scroll-x"><table class="tbl">
<thead><tr><th>Name</th><th>Driver</th><th>Type</th><th>From</th><th>Active</th><th></th></tr></thead>
<tbody>
@forelse($providers as $p)
<tr><td><strong>{{$p->name}}</strong></td>
<td class="mono">{{$p->driver ?? 'smtp'}}</td>
<td><span class="badge {{($p->type ?? 'transactional')==='marketing'?'b-info':'b-muted'}}">{{$p->type ?? 'transactional'}}</span></td>
<td class="mono">{{$p->from_address ?? '—'}}</td>
<td><span class="badge {{($p->is_active ?? 1)?'b-ok':'b-muted'}}">{{($p->is_active ?? 1)?'Active':'Inactive'}}</span></td>
<td><a class="btn btn-ghost" href="/admin/email/providers/{{$p->id}}">View</a></td></tr>
@empty
<tr><td colspan="6"><div class="empty"><h3>No email providers</h3><p>Add a provider to start sending emails.</p></div></td></tr>
@endforelse
</tbody></table></div>
{{ $providers->links() }}
</div>
@endsection
