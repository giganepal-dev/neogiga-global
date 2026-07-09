@extends('frontend.layout')
@section('title','Reset your password — NeoGiga')
@section('description','Request a password reset link for your NeoGiga account.')

@section('content')
<style>
    .auth-card{max-width:440px;margin:48px auto;padding:32px;border:1px solid rgba(15,23,42,.12);border-radius:14px;background:#fff}
    .auth-card h1{font-size:1.4rem;margin:0 0 8px}
    .auth-card p{color:#475569;margin:0 0 20px}
    .auth-card label{display:block;font-weight:600;font-size:.9rem;margin:0 0 6px}
    .auth-card input{width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid rgba(15,23,42,.2);border-radius:9px;font:inherit;margin-bottom:16px}
    .auth-msg{padding:12px 14px;border-radius:9px;margin-bottom:16px;font-size:.92rem}
    .auth-msg.ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
    .auth-msg.err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
</style>
<div class="wrap">
    <div class="auth-card">
        <h1>Reset your password</h1>
        <p>Enter your account email and we'll send you a reset link.</p>

        @if (session('status'))
            <div class="auth-msg ok">{{ session('status') }}</div>
        @endif
        @if (isset($errors) && $errors->any())
            <div class="auth-msg err">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" required autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com">
            <button class="btn btn-primary" type="submit" style="width:100%">Send reset link</button>
        </form>
    </div>
</div>
@endsection
