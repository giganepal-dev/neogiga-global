<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Manufacturer Portal') — NeoGiga</title>
    <style>
        :root{--bg:#0a1210;--bg2:#060c0a;--s1:#101c18;--cyan:#28d8fb;--gold:#f9bd2c;--on:#dde4e0;--muted:#b0bdb6;--faint:#718078;--line:rgba(40,216,251,.1);--r:14px}
        *{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--on);font-family:ui-sans-serif,system-ui,-apple-system,sans-serif;line-height:1.55}
        .wrap{width:min(1280px,calc(100% - 40px));margin-inline:auto}
        .top-bar{background:var(--bg2);border-bottom:1px solid var(--line);padding:12px 0}.top-bar .wrap{display:flex;align-items:center;justify-content:space-between}
        .brand{font-weight:800;font-size:1.1rem;color:#fff}
        .main-grid{display:grid;grid-template-columns:220px 1fr;gap:24px;padding:24px 0}
        .sidebar{display:grid;gap:4px;align-self:start;position:sticky;top:24px}
        .sidebar a{padding:10px 14px;border-radius:8px;color:var(--muted);font-weight:600;font-size:.9rem;transition:.15s}
        .sidebar a:hover,.sidebar a.active{background:rgba(40,216,251,.1);color:var(--cyan)}
        .card{background:rgba(40,216,251,.03);border:1px solid var(--line);border-radius:var(--r);padding:20px}
        .kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:24px}
        .kpi{background:rgba(40,216,251,.03);border:1px solid var(--line);border-radius:12px;padding:16px}.kpi .t{color:var(--faint);font-size:.76rem;text-transform:uppercase;letter-spacing:.08em}.kpi .v{font-size:1.6rem;font-weight:800;margin:4px 0}.kpi .s{color:var(--muted);font-size:.76rem}
        .btn{display:inline-flex;align-items:center;gap:6px;min-height:40px;border-radius:8px;padding:0 16px;font-weight:600;font-size:.86rem}.btn-ghost{border:1px solid var(--line);background:transparent;color:var(--on)}.btn-ghost:hover{border-color:var(--cyan);color:var(--cyan)}
        .table-wrap{overflow-x:auto}.table{width:100%;border-collapse:collapse;font-size:.86rem}.table th,.table td{padding:10px 12px;border-bottom:1px solid var(--line);text-align:left}.table th{color:var(--faint);font-size:.74rem;text-transform:uppercase;letter-spacing:.06em}
        .badge{display:inline-flex;padding:2px 8px;border-radius:6px;font-size:.7rem;font-weight:600}.b-ok{background:rgba(16,185,129,.15);color:#34d399}.b-info{background:rgba(40,216,251,.14);color:var(--cyan)}.b-muted{background:rgba(255,255,255,.06);color:var(--muted)}
        @media(max-width:768px){.main-grid{grid-template-columns:1fr}.sidebar{position:static;display:flex;flex-wrap:wrap;gap:8px}}
    </style>
</head>
<body>
<div class="top-bar"><div class="wrap">
    <a href="/manufacturer" class="brand" style="text-decoration:none">NeoGiga Manufacturer</a>
    <form method="post" action="/manufacturer/logout" style="margin:0">@csrf<button class="btn btn-ghost" type="submit">Sign Out</button></form>
</div></div>
<div class="wrap">
<div class="main-grid">
    <nav class="sidebar">
        <a href="/manufacturer" class="{{ request()->is('manufacturer') ? 'active' : '' }}">Dashboard</a>
        <a href="/manufacturer/products" class="{{ request()->is('manufacturer/products') ? 'active' : '' }}">Products</a>
        <a href="/manufacturer/orders" class="{{ request()->is('manufacturer/orders') ? 'active' : '' }}">Orders</a>
    </nav>
    <main>@yield('content')</main>
</div>
</div>
</body>
</html>
