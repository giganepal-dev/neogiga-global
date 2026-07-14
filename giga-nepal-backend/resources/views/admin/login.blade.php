<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · NeoGiga Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('/images/brand/neogiga-favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('/images/brand/neogiga-apple-touch-icon-180.png') }}">
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100dvh;display:grid;place-items:center;padding:20px;
            font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;color:#f7fbff;
            background:radial-gradient(circle at 20% 10%,rgba(25,211,245,.16),transparent 34rem),
                       radial-gradient(circle at 85% 20%,rgba(245,185,40,.10),transparent 26rem),
                       linear-gradient(180deg,#081527,#0b1f38 55%,#081527)}
        .card{width:min(400px,100%);background:rgba(12,26,46,.72);border:1px solid rgba(148,178,204,.18);
            border-radius:16px;padding:30px 28px;backdrop-filter:blur(14px);box-shadow:0 30px 80px rgba(2,6,23,.5)}
        .brand{display:flex;align-items:center;gap:11px;margin-bottom:22px}
        .brand .mark{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#0b1d38,#12304f);
            display:grid;place-items:center;box-shadow:inset 0 0 0 1px rgba(25,211,245,.4)}
        .brand b{font-size:1.12rem}.brand small{display:block;color:#F5B928;font-size:.62rem;letter-spacing:.2em;text-transform:uppercase;margin-top:1px}
        h1{font-size:1.28rem;margin:0 0 4px}p.sub{margin:0 0 22px;color:#a8b8ca;font-size:.9rem}
        label{display:block;font-size:.8rem;font-weight:600;color:#d7e2ef;margin:0 0 6px}
        .field{margin-bottom:16px}
        input{width:100%;height:44px;padding:0 13px;border-radius:9px;border:1px solid rgba(148,178,204,.28);
            background:rgba(5,11,20,.5);color:#fff;font-size:.95rem}
        input:focus{outline:2px solid #19D3F5;outline-offset:1px;border-color:transparent}
        .btn{width:100%;height:46px;border:0;border-radius:9px;background:#0369A1;color:#fff;font-weight:700;font-size:.95rem;cursor:pointer;transition:background .15s}
        .btn:hover{background:#0284c7}
        .err{background:rgba(220,38,38,.14);border:1px solid rgba(248,113,113,.4);color:#fecaca;
            padding:10px 13px;border-radius:9px;font-size:.85rem;margin-bottom:16px}
        .foot{margin-top:20px;text-align:center;color:#7f93a8;font-size:.78rem}
        .foot a{color:#46d7ff}
        @media (prefers-reduced-motion:reduce){*{transition:none}}
    </style>
</head>
<body>
    <form class="card" method="post" action="/admin/login" novalidate>
        @csrf
        <div class="brand">
            <span class="mark"><svg width="22" height="22" viewBox="0 0 32 32" fill="none"><path d="M9 22V10l14 12V10" stroke="#19D3F5" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span><b>NeoGiga</b><small>Admin Console</small></span>
        </div>
        <h1>Sign in</h1>
        <p class="sub">Authorized administrators only.</p>

        @if ($errors->any())
            <div class="err" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="username" required autofocus
                   value="{{ old('email') }}" placeholder="admin@neogiga.com">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="••••••••••">
        </div>
        <button class="btn" type="submit">Sign in</button>
        <div class="foot">Protected area · <a href="https://neogiga.com">Back to neogiga.com</a></div>
    </form>
</body>
</html>
