@extends('frontend.layout')
@section('title','Sign in — NeoGiga')
@section('description','Sign in to your NeoGiga account to manage orders, RFQs, and BOM lists.')

@section('content')
<style>
    .auth-card{max-width:440px;margin:48px auto;padding:32px;border:1px solid var(--line);border-radius:var(--r);background:var(--glass);backdrop-filter:blur(12px)}
    .auth-card h1{font-size:1.4rem;margin:0 0 8px;color:var(--soft)}
    .auth-card p{color:var(--muted);margin:0 0 20px}
    .auth-card label{display:block;font-weight:600;font-size:.88rem;margin:0 0 6px;color:var(--soft)}
    .auth-card input[type="email"],.auth-card input[type="password"],.auth-card input[type="text"]{width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid var(--line);border-radius:9px;font:inherit;margin-bottom:14px;background:var(--bg);color:var(--on)}
    .auth-card input:focus{outline:none;border-color:var(--cyan);box-shadow:0 0 0 3px rgba(40,216,251,.12)}
    .auth-msg{padding:12px 14px;border-radius:9px;margin-bottom:16px;font-size:.92rem}
    .auth-msg.ok{background:rgba(16,185,129,.12);color:#10b981;border:1px solid rgba(16,185,129,.3)}
    .auth-msg.err{background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.3)}
    .auth-links{display:flex;justify-content:space-between;align-items:center;margin-top:18px;font-size:.88rem}
    .auth-links a{color:var(--cyan);transition:color .15s}.auth-links a:hover{color:var(--gold)}
    .check-row{display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:.86rem;color:var(--muted)}
    .check-row input[type="checkbox"]{accent-color:var(--cyan)}
</style>
<div class="wrap">
    <div class="auth-card">
        @if(session('registration_success'))
            <div class="auth-msg ok" style="text-align:center">
                <strong>✅ Account created!</strong><br>
                Welcome {{ session('registration_success')['name'] }}. @if(session('registration_success')['verification_sent'])A verification email was sent to {{ session('registration_success')['email'] }}.@endif
                @if(session('registration_success')['company_name'])<br>Company: {{ session('registration_success')['company_name'] }}@endif
            </div>
        @endif
        <h1>Sign in</h1>
        <p>Access your orders, RFQs and BOM tools.</p>

        @if (session('status'))
            <div class="auth-msg ok">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="auth-msg err">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ url('/login') }}">
            @csrf
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">

            <div class="check-row">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember" style="margin:0">Stay signed in</label>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">Sign in</button>
        </form>

        <div class="auth-links">
            <a href="{{ url('/register') }}">Create an account</a>
            <a href="{{ url('/forgot-password') }}">Forgot password?</a>
        </div>
    </div>
</div>
@endsection
