<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>{{ $subject ?? 'NeoGiga' }}</title>
<style>
  body { margin:0; padding:0; background:#f5f7fa; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif; -webkit-font-smoothing:antialiased; color:#1e2a36; line-height:1.55 }
  .wrap { max-width:600px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #dfe6ef }
  .header { background:linear-gradient(135deg,#0f5bd7,#0f62e6); padding:28px 24px; text-align:center }
  .header img { width:40px; height:40px; border-radius:10px; border:1px solid rgba(255,255,255,.3); background:rgba(255,255,255,.15) }
  .header h1 { color:#fff; font-size:20px; margin:12px 0 0; font-weight:700 }
  .header .region { color:#cfdcf5; font-size:13px; margin-top:4px }
  .body { padding:24px }
  .body p { margin:0 0 14px; color:#33445c; font-size:15px }
  .body h2 { margin:0 0 10px; font-size:18px; color:#0f5bd7 }
  .btn { display:inline-block; background:#0f62e6; color:#fff !important; text-decoration:none; padding:12px 24px; border-radius:8px; font-weight:700; font-size:15px; margin:8px 0 }
  .muted { color:#8a97a8; font-size:13px }
  .divider { border-top:1px solid #dfe6ef; margin:20px 0 }
  .footer { padding:20px 24px; background:#f8fafc; border-top:1px solid #dfe6ef; color:#8a97a8; font-size:12px; text-align:center }
  .footer a { color:#0f5bd7; text-decoration:none }
  .order-table { width:100%; border-collapse:collapse; margin:12px 0 }
  .order-table th,.order-table td { padding:10px 12px; border-bottom:1px solid #dfe6ef; text-align:left; font-size:14px }
  .order-table th { background:#f4f7fb; color:#54657a; font-weight:600 }
  .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700 }
  .badge-ok { background:#e5f6ef; color:#067a55 }
  .badge-warn { background:#fdf1dc; color:#92400e }
  .badge-muted { background:#f1f4f8; color:#54657a }
  @media(max-width:480px){ .body{padding:16px} .header{padding:20px 16px} }
</style>
</head>
<body>
<div style="padding:24px 16px">
<div class="wrap">
  <div class="header">
    <div style="font-size:28px;line-height:1">⚡</div>
    <h1>{{ $brand ?? 'NeoGiga' }}</h1>
    @if(!empty($regionName))<div class="region">{{ $regionName }}</div>@endif
  </div>
  <div class="body">
    @yield('content')
  </div>
  <div class="footer">
    <p>{{ $brand ?? 'NeoGiga' }} — Global Engineering Marketplace</p>
    <p><a href="https://neogiga.com/en/contact">Contact Support</a> · <a href="https://neogiga.com/en/terms">Terms</a> · <a href="https://neogiga.com/en/privacy">Privacy</a></p>
    @if(!empty($securityNote))<p class="muted">{{ $securityNote }}</p>@endif
  </div>
</div>
</div>
</body>
</html>
