@extends('frontend.layout')

@section('title', 'Two-Factor Authentication — NeoGiga')

@section('content')
<div class="section" style="max-width:420px;margin:60px auto">
    <div class="panel" style="padding:32px">
        <h1 style="font-size:24px;margin-bottom:8px">Two-Factor Authentication</h1>
        <p class="sub">Enter the 6-digit code from your authenticator app, or use a recovery code.</p>

        <form method="POST" action="{{ route('2fa.verify') }}">
            @csrf
            <input type="text" name="code" class="control" inputmode="numeric" pattern="[0-9]{6,10}" maxlength="10" placeholder="6-digit code or recovery code" required autofocus
                   style="font-size:20px;text-align:center;letter-spacing:6px;font-family:monospace">

            @error('code')
                <p class="form-error">{{ $message }}</p>
            @enderror

            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:16px">Verify</button>
        </form>

        <p class="sub" style="margin-top:16px;text-align:center">
            <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit()">Cancel and log out</a>
        </p>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
    </div>
</div>
@endsection
