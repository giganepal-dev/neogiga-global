@extends('frontend.layout')

@section('title', 'Enable Two-Factor Authentication — NeoGiga')
@section('description', 'Secure your NeoGiga account with two-factor authentication.')

@section('content')
<div class="section" style="max-width:520px;margin:60px auto">
    <div class="panel" style="padding:32px">
        <h1 style="font-size:24px;margin-bottom:8px">Set up Two-Factor Authentication</h1>
        <p class="sub">Add an extra layer of security to your account. Use any authenticator app (Google Authenticator, Authy, 1Password) to scan the QR code.</p>

        <div style="text-align:center;margin:24px 0">
            <div style="background:#fff;padding:16px;border-radius:8px;display:inline-block">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrUri) }}"
                     alt="QR code" width="200" height="200" style="display:block">
            </div>
            <p class="sub" style="margin-top:8px">Scan with your authenticator app</p>
        </div>

        <details style="margin-bottom:20px">
            <summary style="cursor:pointer;color:var(--muted);font-size:13px">Can't scan? Enter this key manually</summary>
            <code style="display:block;background:var(--bg);padding:8px;border-radius:4px;word-break:break-all;font-size:13px;margin-top:8px">{{ $secret }}</code>
        </details>

        <form method="POST" action="{{ route('2fa.enable') }}">
            @csrf
            <label class="form-label" for="code">Enter the 6-digit code from your app</label>
            <input type="text" name="code" id="code" class="control" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autofocus
                   style="font-size:24px;text-align:center;letter-spacing:8px;font-family:monospace">

            @error('code')
                <p class="form-error">{{ $message }}</p>
            @enderror

            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:16px">Verify & Enable</button>
        </form>

        <p class="sub" style="margin-top:16px;text-align:center">
            <a href="{{ route('frontend.account') }}">Skip for now</a>
        </p>
    </div>
</div>
@endsection
