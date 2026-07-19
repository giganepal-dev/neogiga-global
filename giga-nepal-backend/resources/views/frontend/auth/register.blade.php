@extends('frontend.layout')
@section('title','Create an account — NeoGiga')
@section('description','Join NeoGiga to access engineering tools, RFQ sourcing, BOM management and global parts marketplace.')

@section('content')
<style>
    .auth-card{max-width:440px;margin:48px auto;padding:32px;border:1px solid var(--line);border-radius:var(--r);background:var(--glass);backdrop-filter:blur(12px)}
    .auth-card h1{font-size:1.4rem;margin:0 0 8px;color:var(--soft)}
    .auth-card p{color:var(--muted);margin:0 0 20px}
    .auth-card label{display:block;font-weight:600;font-size:.88rem;margin:0 0 6px;color:var(--soft)}
    .auth-card input[type="email"],.auth-card input[type="password"],.auth-card input[type="text"]{width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid var(--line);border-radius:9px;font:inherit;margin-bottom:14px;background:var(--bg);color:var(--on)}
    .auth-card input:focus{outline:none;border-color:var(--cyan);box-shadow:0 0 0 3px rgba(40,216,251,.12)}
    .auth-msg{padding:12px 14px;border-radius:9px;margin-bottom:16px;font-size:.92rem}
    .auth-msg.err{background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.3)}
    .auth-links{text-align:center;margin-top:18px;font-size:.88rem}
    .auth-links a{color:var(--cyan)}.auth-links a:hover{color:var(--gold)}
    .check-row{display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:.86rem;color:var(--muted)}
    .check-row input[type="checkbox"]{accent-color:var(--cyan)}
</style>
<div class="wrap">
    <div class="auth-card">
        <h1>Create an account</h1>
        <p>Join NeoGiga — it's free and takes under a minute.</p>

        @if ($errors->any())
            <div class="auth-msg err">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ url('/register') }}">
            @csrf
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">

            <label for="company_name">Company name <span style="color:var(--faint);font-weight:400">(optional)</span></label>
            <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" maxlength="190" autocomplete="organization">

            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">

            <label for="password_confirmation">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required autocomplete="new-password">

            <div class="check-row">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms" style="margin:0">I agree to the <a href="/en/terms" style="color:var(--cyan)">Terms</a> and <a href="/en/privacy" style="color:var(--cyan)">Privacy Policy</a></label>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%">Create account</button>
        </form>

        <div class="auth-links">
            Already have an account? <a href="{{ url('/login') }}">Sign in</a>
        </div>
    </div>
</div>
@endsection
