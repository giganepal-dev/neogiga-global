@extends('frontend.layout')

@section('title', 'Recovery Codes — NeoGiga')

@section('content')
<div class="section" style="max-width:520px;margin:60px auto">
    <div class="panel" style="padding:32px">
        <h1 style="font-size:24px;margin-bottom:8px">Recovery Codes</h1>
        <p class="sub">Store these codes in a safe place. Each code can be used once to sign in if you lose access to your authenticator app.</p>

        <div style="background:var(--bg);padding:16px;border-radius:8px;font-family:monospace;font-size:15px;margin:16px 0">
            @foreach($codes as $code)
                <div style="padding:4px 0;border-bottom:1px solid var(--border)">{{ $code }}</div>
            @endforeach
        </div>

        <div style="display:flex;gap:8px">
            <button onclick="window.print()" class="btn btn-ghost" style="flex:1">Print</button>
            <a href="{{ route('frontend.account') }}" class="btn btn-primary" style="flex:1;text-align:center">Done</a>
        </div>

        <p class="sub" style="margin-top:12px;text-align:center">
            <a href="{{ route('2fa.new-codes') }}">Generate new codes</a> (invalidates these)
        </p>
    </div>
</div>
@endsection
