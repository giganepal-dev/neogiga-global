@extends('admin.layout')
@section('title','Marketing Settings')
@section('crumb','Provider, consent, GA4 and limits')
@section('content')
<div class="note"><strong>Controlled settings.</strong> Provider credentials are still loaded only from environment variables; this form stores non-secret operational settings.</div>

<div class="card" style="margin-bottom:16px"><div class="card-h"><h2>Update Settings</h2><div class="sub">Credentials remain environment-only</div></div>
<form method="post" action="/admin/marketing/settings" style="padding:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:end">@csrf
    <label>Email provider<br><input name="email_provider" maxlength="80" placeholder="log" style="width:100%;height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px"></label>
    <label>WhatsApp provider<br><input name="whatsapp_provider" maxlength="80" placeholder="manual_export" style="width:100%;height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px"></label>
    <label>Cart reminder minutes<br><input name="abandoned_cart_first_reminder_minutes" type="number" min="15" max="10080" style="width:100%;height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px"></label>
    <label>Campaign daily limit<br><input name="campaign_daily_limit" type="number" min="1" max="100000" style="width:100%;height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px"></label>
    <label>GA4 measurement ID<br><input name="ga_measurement_id" maxlength="80" placeholder="G-..." style="width:100%;height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px"></label>
    <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="newsletter_double_opt_in" value="1"> Double opt-in</label>
    <button class="btn btn-primary" type="submit">Save Settings</button>
</form></div>

@foreach ([['Marketing settings',$marketingSettings],['Analytics settings',$analyticsSettings],['Notification settings',$notificationSettings]] as [$title,$rows])
<div class="card" style="margin-bottom:16px"><div class="card-h"><h2>{{ $title }}</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Key</th><th>Group</th><th>Value</th></tr></thead><tbody>@forelse($rows as $row)<tr><td class="mono">{{ $row->key }}</td><td>{{ $row->group ?? '—' }}</td><td class="mono">{{ is_string($row->value) ? Str::limit($row->value, 120) : json_encode($row->value) }}</td></tr>@empty<tr><td colspan="3"><div class="empty"><h3>No settings</h3></div></td></tr>@endforelse</tbody></table></div></div>
@endforeach
@endsection
