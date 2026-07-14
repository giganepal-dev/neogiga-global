@extends('admin.layout')
@section('title','Email & Marketing Settings')
@section('crumb','SMTP, provider API, senders, consent and limits')
@section('content')
@php
    $marketingValues = $marketingSettings->mapWithKeys(fn ($row) => [$row->key => json_decode((string) $row->value, true)]);
    $analyticsValues = $analyticsSettings->mapWithKeys(fn ($row) => [$row->key => json_decode((string) $row->value, true)]);
@endphp

<div class="note"><strong>Encrypted provider configuration.</strong> SMTP passwords, API keys, and webhook secrets are encrypted with the application key and are never displayed again. Saving a provider does not bypass campaign approval, consent, suppression, sender verification, test-mode, or rate-limit controls.</div>

<div class="grid split" style="margin-bottom:16px">
@foreach (['marketing' => 'Marketing campaigns', 'transactional' => 'Transactional email'] as $channel => $label)
@php($provider = $providerSummaries[$channel])
<div class="card" id="{{ $channel }}-provider" style="scroll-margin-top:20px">
    <div class="card-h"><div><h2>{{ $label }} provider</h2><div class="sub">Source: {{ $provider['source'] }} · Last test: {{ $provider['last_test_status'] ?? 'not tested' }}</div></div><span class="badge {{ $provider['is_enabled'] && !$provider['test_mode'] ? 'b-ok' : 'b-warn' }}">{{ $provider['is_enabled'] ? ($provider['test_mode'] ? 'Test mode' : 'Enabled') : 'Disabled' }}</span></div>
    <form method="post" action="/admin/marketing/settings/email-provider" class="form-stack" style="padding:16px">
        @csrf
        <input type="hidden" name="channel" value="{{ $channel }}">
        <label>Transport
            <select class="control" name="transport" required>
                @foreach(($channel === 'marketing' ? ['sandbox' => 'Sandbox / no delivery', 'smtp' => 'SMTP', 'generic_http' => 'Provider API'] : ['log' => 'Log / no delivery', 'smtp' => 'SMTP']) as $value => $name)
                    <option value="{{ $value }}" @selected($provider['transport'] === $value)>{{ $name }}</option>
                @endforeach
            </select>
        </label>
        <div style="display:flex;gap:18px;flex-wrap:wrap">
            <input type="hidden" name="is_enabled" value="0"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_enabled" value="1" @checked($provider['is_enabled'])> Enable channel</label>
            <input type="hidden" name="test_mode" value="0"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="test_mode" value="1" @checked($provider['test_mode'])> Test mode (no production delivery)</label>
        </div>

        <div class="note"><strong>SMTP</strong> — fill these fields when transport is SMTP. Leave username/password blank to preserve saved credentials.</div>
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:10px">
            <input class="control" name="smtp_host" value="{{ $provider['smtp_host'] }}" maxlength="255" placeholder="smtp.example.com">
            <input class="control" name="smtp_port" type="number" min="1" max="65535" value="{{ $provider['smtp_port'] ?: 587 }}" placeholder="587">
            <select class="control" name="smtp_encryption"><option value="tls" @selected($provider['smtp_encryption']==='tls')>TLS</option><option value="ssl" @selected($provider['smtp_encryption']==='ssl')>SSL</option><option value="none" @selected($provider['smtp_encryption']==='none')>None</option></select>
        </div>
        <input class="control" name="smtp_local_domain" value="{{ $provider['smtp_local_domain'] }}" maxlength="255" placeholder="EHLO domain (optional)">
        <input class="control" name="smtp_username" autocomplete="off" maxlength="500" placeholder="SMTP username {{ $provider['smtp_username_configured'] ? '(saved — leave blank to keep)' : '' }}">
        <input class="control" name="smtp_password" type="password" autocomplete="new-password" maxlength="2000" placeholder="SMTP password {{ $provider['smtp_password_configured'] ? '(saved — leave blank to keep)' : '' }}">

        @if($channel === 'marketing')
        <div class="note"><strong>Provider API</strong> — HTTPS endpoint and token for a compatible batch-email API.</div>
        <input class="control" name="api_base_url" type="url" value="{{ $provider['api_base_url'] }}" maxlength="2048" placeholder="https://api.provider.example/v1">
        <input class="control" name="account_id" value="{{ $provider['account_id'] }}" maxlength="255" placeholder="Account / workspace ID (optional)">
        <input class="control" name="api_key" type="password" autocomplete="new-password" maxlength="4000" placeholder="API key {{ $provider['api_key_configured'] ? '(saved — leave blank to keep)' : '' }}">
        <input class="control" name="webhook_secret" type="password" autocomplete="new-password" maxlength="4000" placeholder="Webhook signing secret {{ $provider['webhook_secret_configured'] ? '(saved — leave blank to keep)' : '' }}">
        <textarea class="control" name="test_recipients" rows="2" placeholder="Allowed test recipients, comma or line separated">{{ implode(', ', $provider['test_recipients']) }}</textarea>
        @else
        <input class="control" name="test_recipient" type="email" value="{{ $provider['test_recipient'] }}" maxlength="190" placeholder="Authorized transactional test recipient">
        @endif

        <label>Sender profile
            <select class="control" name="sender_profile_id"><option value="">Automatic regional sender</option>@foreach($senderProfiles->where('purpose', $channel) as $profile)<option value="{{ $profile->id }}" @selected((int)$provider['sender_profile_id']===$profile->id)>{{ $profile->name }} — {{ $profile->from_email }}</option>@endforeach</select>
        </label>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
            <label>Per minute<input class="control" name="rate_limit_per_minute" type="number" min="1" max="10000" value="{{ $provider['rate_limit_per_minute'] }}" required></label>
            <label>Daily limit<input class="control" name="daily_limit" type="number" min="1" max="1000000" value="{{ $provider['daily_limit'] }}" required></label>
            <label>Timeout seconds<input class="control" name="timeout" type="number" min="2" max="120" value="{{ $provider['timeout'] }}" required></label>
        </div>
        <input type="hidden" name="clear_credentials" value="0"><label style="display:flex;gap:8px;align-items:center;color:#b45309"><input type="checkbox" name="clear_credentials" value="1"> Clear all saved credentials for this channel</label>
        <button class="btn btn-primary" type="submit">Save encrypted provider</button>
    </form>
    @if($channel === 'marketing')
        <form method="post" action="/admin/marketing/settings/email-provider/test-marketing" style="padding:0 16px 16px">@csrf<button class="btn btn-ghost" type="submit">Test marketing provider</button></form>
    @else
        <form method="post" action="/admin/marketing/settings/email-provider/test-transactional" style="padding:0 16px 16px;display:flex;gap:8px">@csrf<input class="control" name="email" type="email" value="{{ $provider['test_recipient'] }}" placeholder="Authorized test recipient"><button class="btn btn-ghost" type="submit">Test transactional provider</button></form>
    @endif
</div>
@endforeach
</div>

<div class="card" style="margin-bottom:16px"><div class="card-h"><h2>Operational Settings</h2><div class="sub">Non-secret campaign and analytics controls</div></div>
<form method="post" action="/admin/marketing/settings" style="padding:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:end">@csrf
    <label>Email provider label<br><input class="control" name="email_provider" maxlength="80" value="{{ $marketingValues['email_provider'] ?? '' }}" placeholder="admin configured"></label>
    <label>WhatsApp provider<br><input class="control" name="whatsapp_provider" maxlength="80" value="{{ $marketingValues['whatsapp_provider'] ?? '' }}" placeholder="manual_export"></label>
    <label>Cart reminder minutes<br><input class="control" name="abandoned_cart_first_reminder_minutes" type="number" min="15" max="10080" value="{{ $marketingValues['abandoned_cart_first_reminder_minutes'] ?? '' }}"></label>
    <label>Campaign daily limit<br><input class="control" name="campaign_daily_limit" type="number" min="1" max="100000" value="{{ $marketingValues['campaign_daily_limit'] ?? '' }}"></label>
    <label>GA4 measurement ID<br><input class="control" name="ga_measurement_id" maxlength="80" value="{{ $analyticsValues['ga_measurement_id'] ?? '' }}" placeholder="G-..."></label>
    <input type="hidden" name="newsletter_double_opt_in" value="0"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="newsletter_double_opt_in" value="1" @checked($marketingValues['newsletter_double_opt_in'] ?? false)> Double opt-in</label>
    <button class="btn btn-primary" type="submit">Save operational settings</button>
</form></div>

<div class="card" style="margin-bottom:16px"><div class="card-h"><h2>Sender Profiles</h2><span class="sub">A live campaign requires a verified and enabled marketing sender</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Name / purpose</th><th>From name</th><th>From email</th><th>Reply-to</th><th>Domain</th><th>Base URL</th><th>Verified / enabled</th><th></th></tr></thead><tbody>
@forelse($senderProfiles as $profile)
<tr>
    <td><form id="sender-{{ $profile->id }}" method="post" action="/admin/marketing/settings/senders/{{ $profile->id }}">@csrf</form><strong>{{ $profile->name }}</strong><div class="sub">{{ $profile->purpose }}</div></td>
    <td><input form="sender-{{ $profile->id }}" class="control" name="from_name" value="{{ $profile->from_name }}" required maxlength="190"></td>
    <td><input form="sender-{{ $profile->id }}" class="control" name="from_email" type="email" value="{{ $profile->from_email }}" required maxlength="190"></td>
    <td><input form="sender-{{ $profile->id }}" class="control" name="reply_to" type="email" value="{{ $profile->reply_to }}" maxlength="190"></td>
    <td><input form="sender-{{ $profile->id }}" class="control" name="domain" value="{{ $profile->domain }}" required maxlength="255"></td>
    <td><input form="sender-{{ $profile->id }}" class="control" name="base_url" type="url" value="{{ $profile->base_url }}" required maxlength="2048"></td>
    <td><input form="sender-{{ $profile->id }}" type="hidden" name="is_verified" value="0"><label><input form="sender-{{ $profile->id }}" type="checkbox" name="is_verified" value="1" @checked($profile->is_verified)> DNS/provider verified</label><br><input form="sender-{{ $profile->id }}" type="hidden" name="is_enabled" value="0"><label><input form="sender-{{ $profile->id }}" type="checkbox" name="is_enabled" value="1" @checked($profile->is_enabled)> Enabled</label></td>
    <td><button form="sender-{{ $profile->id }}" class="btn btn-ghost" type="submit">Save</button></td>
</tr>
@empty<tr><td colspan="8"><div class="empty"><h3>No sender profiles</h3></div></td></tr>@endforelse
</tbody></table></div></div>

<div class="card" style="margin-bottom:16px"><div class="card-h"><h2>Deliverability Checklist</h2><span class="sub">DNS is never modified automatically</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Domain</th><th>Purpose</th><th>SPF</th><th>DKIM</th><th>DMARC</th><th>Provider</th><th>Return path</th><th>Bounce domain</th></tr></thead><tbody>@forelse($emailDomains as $domain)<tr><td class="mono"><strong>{{ $domain->domain }}</strong></td><td>{{ $domain->purpose }}</td><td><span class="badge {{ $domain->spf_status==='verified'?'b-ok':'b-warn' }}">{{ $domain->spf_status }}</span></td><td><span class="badge {{ $domain->dkim_status==='verified'?'b-ok':'b-warn' }}">{{ $domain->dkim_status }}</span></td><td><span class="badge {{ $domain->dmarc_status==='verified'?'b-ok':'b-warn' }}">{{ $domain->dmarc_status }}</span></td><td>{{ $domain->provider_verification_status }}</td><td class="mono">{{ $domain->return_path_domain }}</td><td class="mono">{{ $domain->bounce_domain }}</td></tr>@empty<tr><td colspan="8"><div class="empty"><h3>No email domains</h3></div></td></tr>@endforelse</tbody></table></div></div>

<div class="grid split" style="margin-bottom:16px"><div class="card"><div class="card-h"><h2>Provider Status</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Configuration</th><th>Channel</th><th>Mode</th><th>Last test</th></tr></thead><tbody>@forelse($providerConfigs as $provider)<tr><td>{{ $provider->provider }}</td><td>{{ $provider->channel }}</td><td>{{ $provider->is_enabled ? ($provider->test_mode ? 'test' : 'enabled') : 'disabled' }}</td><td>{{ $provider->last_test_status ?? 'not tested' }}</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No saved admin provider configuration</h3></div></td></tr>@endforelse</tbody></table></div></div><div class="card"><div class="card-h"><h2>Queue Separation</h2></div><div style="padding:16px"><p class="sub">Transactional work remains independent and higher priority than campaigns.</p>@foreach(array_filter(array_unique($queueNames)) as $queue)<span class="badge b-info" style="margin:4px">{{ $queue }}</span>@endforeach</div></div></div>

@foreach ([['Marketing settings',$marketingSettings],['Analytics settings',$analyticsSettings],['Notification settings',$notificationSettings]] as [$title,$rows])
<div class="card" style="margin-bottom:16px"><div class="card-h"><h2>{{ $title }}</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Key</th><th>Group</th><th>Value</th></tr></thead><tbody>@forelse($rows as $row)<tr><td class="mono">{{ $row->key }}</td><td>{{ $row->group ?? '—' }}</td><td class="mono">{{ is_string($row->value) ? Str::limit($row->value, 120) : json_encode($row->value) }}</td></tr>@empty<tr><td colspan="3"><div class="empty"><h3>No settings</h3></div></td></tr>@endforelse</tbody></table></div></div>
@endforeach
@endsection
