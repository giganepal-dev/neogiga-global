@extends('frontend.layout')
@section('title','Choose a new password — NeoGiga')
@section('description','Set a new password for your NeoGiga account.')

@section('content')
<style nonce="{{ $csp_nonce ?? '' }}">
    .auth-card{max-width:440px;margin:48px auto;padding:32px;border:1px solid rgba(15,23,42,.12);border-radius:14px;background:#fff}
    .auth-card h1{font-size:1.4rem;margin:0 0 8px}
    .auth-card p{color:#475569;margin:0 0 20px}
    .auth-card label{display:block;font-weight:600;font-size:.9rem;margin:0 0 6px}
    .auth-card input{width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid rgba(15,23,42,.2);border-radius:9px;font:inherit;margin-bottom:16px}
    .auth-msg{padding:12px 14px;border-radius:9px;margin-bottom:16px;font-size:.92rem}
    .auth-msg.err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
</style>
<div class="wrap">
    <div class="auth-card">
        <h1><x-icon name="password" size="22"/> Choose a new password</h1>
        <p>Set a new password for your account. The link you followed expires after a short time.</p>

        @if (isset($errors) && $errors->any())
            <div class="auth-msg err">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email">Email address</label>
            <input id="email" type="email" name="email" required autocomplete="email" value="{{ old('email', $email) }}">

            <label for="password">New password</label>
            <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">

            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">

            <button class="btn btn-primary" type="submit" style="width:100%"><x-icon name="password" size="16"/> Reset password</button>
        </form>
    </div>
</div>
@endsection
