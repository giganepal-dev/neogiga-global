@extends('frontend.account.layout')
@section('title','Security — NeoGiga')
@section('account-content')
<header class="account-topbar"><div><h1>Security</h1><p>Password, two-factor authentication and active-session protection.</p></div></header>
<section class="account-panel">
    <div class="account-panel-head"><div><h2>Two-factor authentication</h2><p>Add a time-based one-time code to your sign-in.</p></div><span class="account-badge {{ $user->two_factor_enabled ? 'approved' : 'pending' }}">{{ $user->two_factor_enabled ? 'Enabled' : 'Not enabled' }}</span></div>
    <div class="account-actions"><a class="account-button secondary" href="{{ $user->two_factor_enabled ? '/2fa/manage' : '/2fa/setup' }}">{{ $user->two_factor_enabled ? 'Manage 2FA' : 'Set up 2FA' }}</a></div>
</section>
<section class="account-panel">
    <div class="account-panel-head"><div><h2>Change password</h2><p>A strong password signs other sessions out after it is changed.</p></div></div>
    <form class="account-form" method="post" action="/account/password">@csrf @method('patch')
        <div class="account-form-grid">
            <div class="account-field full"><label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" autocomplete="current-password" required></div>
            <div class="account-field"><label for="password">New password</label><input id="password" type="password" name="password" autocomplete="new-password" required></div>
            <div class="account-field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div>
        </div><div><button class="account-button" type="submit">Change password</button></div>
    </form>
</section>
@endsection
