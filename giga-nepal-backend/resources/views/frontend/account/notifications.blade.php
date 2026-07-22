@extends('frontend.account.layout')
@section('title','Notifications — NeoGiga')
@section('account-content')
<header class="account-topbar"><div><h1>Notifications</h1><p>Transactional, account and sourcing updates.</p></div></header>
<section class="account-panel">
    <div class="account-panel-head"><div><h2>Communication preferences</h2><p>Security and account alerts are mandatory. Other channels are your choice.</p></div></div>
    <form class="account-form" method="post" action="/account/notifications">@csrf @method('PATCH')
        <div class="account-table-wrap"><table class="account-table"><thead><tr><th>Update</th><th>Email</th><th>Push</th><th>SMS</th><th>WhatsApp</th></tr></thead><tbody>
        @foreach($preferences as $preference)<tr><td>{{ $preference->label }}@if($preference->is_mandatory) <small>(required)</small>@endif</td>
            @foreach(['email','push','sms','whatsapp'] as $channel)<td><input type="checkbox" name="preferences[{{ $preference->type }}][{{ $channel }}]" value="1" @checked($preference->{$channel.'_enabled'}) @disabled($preference->is_mandatory && in_array($channel,['email','push'],true))>@if($preference->is_mandatory && in_array($channel,['email','push'],true))<input type="hidden" name="preferences[{{ $preference->type }}][{{ $channel }}]" value="1">@endif</td>@endforeach
        </tr>@endforeach
        </tbody></table></div><div><button class="account-button" type="submit">Save preferences</button></div>
    </form>
</section>
<section class="account-panel"><div class="account-panel-head"><div><h2>Recent delivery history</h2></div></div>
@if($notifications->isEmpty())<div class="account-empty">No notifications yet.</div>@else<div class="account-table-wrap"><table class="account-table"><thead><tr><th>Notification</th><th>Details</th><th>Channel</th><th>Status</th><th>Received</th></tr></thead><tbody>@foreach($notifications as $notification)<tr><td>{{ $notification->title ?: 'Update' }}</td><td>{{ $notification->body ?: '—' }}</td><td>{{ $notification->channel }}</td><td><span class="account-badge {{ $notification->status }}">{{ $notification->status }}</span></td><td>{{ \Carbon\Carbon::parse($notification->created_at)->format('d M Y, H:i') }}</td></tr>@endforeach</tbody></table></div>@endif
</section>
@endsection
