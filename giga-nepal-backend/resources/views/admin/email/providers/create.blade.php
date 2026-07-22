@extends('admin.layout')
@section('title','Add Email Provider')
@section('crumb','Marketing / Email / Providers / Create')

@section('content')
<div class="card"><div class="card-h"><h2>Add Email Provider</h2></div>
<form class="card-body form-stack" method="post" action="/admin/email/providers">@csrf
<div class="form-grid">
<div class="field"><label>Name</label><input class="control" name="name" required placeholder="e.g. AWS SES Production"></div>
<div class="field"><label>Driver</label><select class="control" name="driver" required>
<option value="ses">Amazon SES</option><option value="mailgun">Mailgun</option>
<option value="postmark">Postmark</option><option value="sendgrid">SendGrid</option>
<option value="smtp">SMTP</option><option value="log">Log Only</option></select></div>
</div>
<div class="form-grid">
<div class="field"><label>Type</label><select class="control" name="type"><option value="transactional">Transactional</option><option value="marketing">Marketing</option></select></div>
<div class="field"><label>From Address</label><input class="control" name="from_address" type="email" placeholder="noreply@neogiga.com"></div>
</div>
<div class="field"><label>API Key / Secret</label><input class="control" name="api_key" placeholder="API key or access token"></div>
<div class="field"><label>Region / Endpoint</label><input class="control" name="region" placeholder="us-east-1 or smtp.example.com"></div>
<button class="btn btn-primary" type="submit">Save Provider</button>
<a class="btn btn-ghost" href="/admin/email/providers">Cancel</a>
</form></div>
@endsection
