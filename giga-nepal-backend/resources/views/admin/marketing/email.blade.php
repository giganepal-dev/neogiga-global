@extends('admin.layout')
@section('title','Email Campaigns')
@section('crumb','Compose, approve, schedule and run campaigns')
@section('content')
<div class="note"><strong>Delivery status:</strong> provider <span class="mono">{{ $marketingProvider }}</span>; {{ $providerSummary['source'] }} configuration; production sending <strong>{{ $productionSendingEnabled ? 'enabled' : 'disabled / test mode' }}</strong>. Allowed test recipients: {{ $testRecipients ? implode(', ', $testRecipients) : 'none configured' }}. <a href="/admin/marketing/settings">Configure SMTP or provider API</a> · <a href="/admin/marketing/customer-imports">Import customers from Excel</a>.</div>
<div class="note"><strong>Campaign safety:</strong> live delivery still requires an approved campaign, explicit production authorization, a verified sender, frozen consent-qualified audience, suppression checks, and available rate/daily capacity.</div>

<div class="grid split" style="margin-bottom:16px">
    <div class="card"><div class="card-h"><div><h2>1. Compose Email</h2><div class="sub">Creates an immutable versioned template</div></div></div><form method="post" action="/admin/marketing/email/templates" class="form-stack" style="padding:16px">@csrf
        <input class="control" name="name" required maxlength="190" value="{{ old('name') }}" placeholder="Template name">
        <input class="control" name="type" required maxlength="80" value="{{ old('type', 'campaign') }}" placeholder="campaign, newsletter, product_update">
        <input class="control" name="subject" required maxlength="190" value="{{ old('subject') }}" placeholder="Email subject">
        <textarea class="control" name="html_body" rows="10" placeholder="HTML body with NeoGiga identity. Use variables such as @{{customer_name}}, @{{unsubscribe_url}}, and @{{preferences_url}}">{{ old('html_body') }}</textarea>
        <textarea class="control" name="text_body" rows="6" placeholder="Plain-text fallback">{{ old('text_body') }}</textarea>
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_transactional" value="1" @checked(old('is_transactional'))> Transactional template (does not require marketing unsubscribe variables)</label>
        <button class="btn btn-primary" type="submit">Save email content</button>
    </form></div>
    <div class="card"><div class="card-h"><div><h2>2. Create Campaign</h2><div class="sub">Select content and audience; approval comes next</div></div></div><form method="post" action="/admin/marketing/email/campaigns" class="form-stack" style="padding:16px">@csrf
        <input class="control" name="name" required maxlength="190" value="{{ old('name') }}" placeholder="Campaign name">
        <input class="control" name="type" maxlength="60" value="{{ old('type', 'marketing') }}" placeholder="marketing">
        <label>Email content<select class="control" name="email_template_id" required><option value="">Select template</option>@foreach($templates->where('is_active', true)->where('is_transactional', false) as $template)<option value="{{ $template->id }}" @selected((int)old('email_template_id')===$template->id)>{{ $template->name }} — {{ $template->subject }}</option>@endforeach</select></label>
        <label>Customer segment<select class="control" name="segment_id"><option value="">All consent-qualified customers</option>@foreach($segments as $segment)<option value="{{ $segment->id }}" @selected((int)old('segment_id')===$segment->id)>{{ $segment->name }}</option>@endforeach</select></label>
        <label>Schedule (optional)<input class="control" name="scheduled_at" type="datetime-local" value="{{ old('scheduled_at') }}"></label>
        <button class="btn btn-primary" type="submit">Create draft campaign</button>
    </form></div>
</div>

<div class="card" style="margin-bottom:16px"><div class="card-h"><div><h2>3. Approve and Run Campaign</h2><div class="sub">Prepare freezes the audience, then rate-limited workers deliver it</div></div></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Campaign / content</th><th>Status</th><th>Audience</th><th>Safe test</th><th>Approval and controls</th></tr></thead><tbody>
@forelse($campaigns as $campaign)
@php($template = $templates->firstWhere('id', $campaign->email_template_id))
<tr>
    <td><strong>{{ $campaign->name }}</strong><div class="sub">#{{ $campaign->id }} {{ $campaign->type }} · {{ $campaign->internal_reference ?? 'no reference' }}</div><div class="sub">{{ $template?->name ?? 'No template selected' }}{{ $campaign->scheduled_at ? ' · scheduled '.$campaign->scheduled_at : '' }}</div></td>
    <td><span class="badge {{ in_array($campaign->status, ['approved','sending','completed']) ? 'b-ok' : ($campaign->status === 'paused' ? 'b-warn' : 'b-muted') }}">{{ $campaign->status }}</span>@if($campaign->production_send_enabled)<div><span class="badge b-warn">Live authorized</span></div>@endif</td>
    <td>{{ number_format($campaign->eligible_count ?? 0) }} eligible<div class="sub">{{ number_format($campaign->excluded_count ?? 0) }} excluded</div></td>
    <td><form method="post" action="/admin/marketing/email/campaigns/{{ $campaign->id }}/send-test" style="display:flex;gap:8px;min-width:260px">@csrf<input class="control" name="email" type="email" required placeholder="allowed test recipient"><button class="btn" type="submit">Prepare test</button></form></td>
    <td><div class="actions">
        @if(!$campaign->approved_at)
        <form method="post" action="/admin/marketing/email/campaigns/{{ $campaign->id }}/approve" style="display:flex;gap:6px;align-items:center">@csrf<label class="sub"><input type="checkbox" name="production_send_enabled" value="1"> Authorize live</label><button class="btn btn-ghost" type="submit">Approve</button></form>
        @endif
        @if($campaign->status==='paused')<form method="post" action="/admin/marketing/email/campaigns/{{ $campaign->id }}/resume">@csrf<button class="btn btn-ghost" type="submit">Resume</button></form>@else<form method="post" action="/admin/marketing/email/campaigns/{{ $campaign->id }}/pause">@csrf<button class="btn btn-ghost" type="submit">Pause</button></form>@endif
        @if($campaign->approved_at)<form method="post" action="/admin/marketing/email/campaigns/{{ $campaign->id }}/queue" onsubmit="return confirm('Prepare this campaign audience and run it when all delivery gates pass?')">@csrf<button class="btn btn-primary" type="submit">Prepare &amp; run</button></form>@endif
        @if(!$campaign->cancelled_at)<form method="post" action="/admin/marketing/email/campaigns/{{ $campaign->id }}/cancel" onsubmit="return confirm('Cancel this campaign and all unsent messages?')">@csrf<button class="btn danger" type="submit">Cancel</button></form>@endif
    </div></td>
</tr>
@empty<tr><td colspan="5"><div class="empty"><h3>No email campaigns yet</h3><p>Compose content, then create your first draft campaign.</p></div></td></tr>@endforelse
</tbody></table></div></div>

<div class="grid split">
<div class="card"><div class="card-h"><h2>Email Content Library</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Subject</th><th>Type</th><th>Channel</th></tr></thead><tbody>@forelse($templates as $template)<tr><td><strong>{{ $template->name }}</strong></td><td>{{ $template->subject }}</td><td>{{ $template->type }}</td><td>@if($template->is_transactional)<span class="badge b-info">Transactional</span>@else<span class="badge b-muted">Marketing</span>@endif</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No email content yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
<div class="card"><div class="card-h"><h2>Recent Messages</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>To</th><th>Type</th><th>Status</th></tr></thead><tbody>@forelse($messages as $message)<tr><td class="mono">{{ $message->to_email }}</td><td>{{ $message->message_type }}</td><td><span class="badge b-muted">{{ $message->status }}</span></td></tr>@empty<tr><td colspan="3"><div class="empty"><h3>No queued messages</h3></div></td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection
