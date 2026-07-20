<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $auth['title'] }} · NeoGiga</title>
    <x-icon-styles/>
    <style>
        :root{--ng-accent:#f9bd2c;--ng-focus:#f9bd2c}
        *{box-sizing:border-box}body{margin:0;font:15px/1.5 ui-sans-serif,system-ui;background:#0F172A;color:#0f172a;display:grid;place-items:center;min-height:100dvh}
        .box{background:#fff;border-radius:14px;padding:32px;width:min(400px,92vw)}
        .box h1{font-size:1.2rem;margin:0 0 4px;display:flex;align-items:center;gap:8px}
        .sub{color:#64748B;font-size:.85rem;margin:0 0 18px}
        label{display:flex;align-items:center;gap:6px;font-weight:600;font-size:.86rem;margin:12px 0 4px}
        input{width:100%;padding:10px 12px;border:1px solid rgba(15,23,42,.2);border-radius:9px;font:inherit}
        button{margin-top:16px;width:100%;padding:11px;border:0;border-radius:9px;background:#f9bd2c;color:#231a00;font:inherit;font-weight:700;cursor:pointer}
        .err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:9px;padding:10px 12px;font-size:.86rem;margin-bottom:8px}
        .alt{margin-top:14px;font-size:.82rem;color:#64748B;text-align:center}
        .alt a{color:#b45309;font-weight:600}
    </style>
</head>
<body>
    <form class="box" method="post" action="{{ $auth['action'] }}">
        @csrf
        <h1><x-icon :name="$auth['icon']" :size="22" :label="$auth['title']" /> {{ $auth['title'] }}</h1>
        <p class="sub">{{ $auth['subtitle'] }}</p>
        @if ($errors->any())<div class="err" role="alert">{{ $errors->first() }}</div>@endif
        <label for="email"><x-icon name="email" :size="15" /> Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        <label for="password"><x-icon name="password" :size="15" /> Password</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">
        <button type="submit">Sign in</button>
        @if(!empty($auth['footer']))<p class="alt">{!! $auth['footer'] !!}</p>@endif
    </form>
</body>
</html>
