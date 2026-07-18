<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Seller Portal') · NeoGiga Seller</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='7' fill='%23081527'/><path d='M9 22V10l14 12V10' stroke='%23f9bd2c' stroke-width='2.4' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>">
    <x-icon-styles/>
    <style>
        :root{--navy:#0F172A;--line:rgba(148,163,184,.16);--muted:#64748B;--accent:#f9bd2c;--ok:#16a34a;--warn:#d97706;--bad:#e5484d;--ng-accent:#f9bd2c;--ng-focus:#f9bd2c}
        *{box-sizing:border-box}body{margin:0;font:15px/1.5 ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial;color:#0f172a;background:#f4f6fa}
        a{color:inherit;text-decoration:none}
        .shell{display:grid;grid-template-columns:230px 1fr;min-height:100dvh}
        .side{background:var(--navy);color:#CBD5E1;padding:14px;position:sticky;top:0;height:100dvh}
        .side .brand{display:flex;gap:10px;align-items:center;color:#fff;font-weight:800;padding:6px 8px 16px}
        .side .brand small{display:block;font-weight:500;color:#94A3B8;font-size:.72rem}
        .side nav{display:grid;gap:4px}
        .main{padding:20px 24px}
        .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
        .topbar h1{font-size:1.15rem;margin:0}
        .badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:.74rem;font-weight:700}
        .b-ok{background:rgba(22,163,74,.12);color:var(--ok)}.b-warn{background:rgba(217,119,6,.12);color:var(--warn)}.b-muted{background:rgba(100,116,139,.12);color:var(--muted)}.b-bad{background:rgba(229,72,77,.12);color:var(--bad)}
        .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:18px}
        .kpi{background:#fff;border:1px solid var(--line);border-radius:12px;padding:14px}
        .kpi .t{font-size:.74rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)}
        .kpi .v{font-size:1.5rem;font-weight:800;font-variant-numeric:tabular-nums}
        .kpi .s{font-size:.78rem;color:var(--muted)}
        .card{background:#fff;border:1px solid var(--line);border-radius:12px;margin-bottom:16px}
        .card-h{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--line)}
        .card-h h2{font-size:1rem;margin:0}
        .tbl{width:100%;border-collapse:collapse;font-size:.9rem}
        .tbl th,.tbl td{text-align:left;padding:9px 14px;border-bottom:1px solid var(--line)}
        .tbl th{font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted)}
        .num{text-align:right}.tnum{font-variant-numeric:tabular-nums}.mono{font-family:ui-monospace,Menlo,monospace;font-size:.84rem}
        .sub{color:var(--muted);font-size:.8rem}
        .filters{display:flex;gap:8px;padding:12px 16px;border-bottom:1px solid var(--line);flex-wrap:wrap}
        .control{border:1px solid var(--line);border-radius:9px;padding:8px 10px;font:inherit;min-width:140px;background:#fff}
        .btn{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);background:#fff;border-radius:9px;padding:8px 14px;font:inherit;font-weight:600;cursor:pointer}
        .btn-primary{background:var(--accent);border-color:transparent;color:#231a00}
        .empty{text-align:center;padding:36px 16px;color:var(--muted)}
        .scroll-x{overflow-x:auto}
        @media(max-width:840px){.shell{grid-template-columns:1fr}.side{position:static;height:auto}}
    </style>
</head>
<body>
<div class="shell">
    <aside class="side">
        <div class="brand">
            <svg width="22" height="22" viewBox="0 0 32 32" fill="none"><path d="M9 22V10l14 12V10" stroke="#f9bd2c" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>NeoGiga<small>Seller Portal</small></span>
        </div>
        <nav aria-label="Seller navigation">
            <x-sidebar-nav-item icon="dashboard" label="Dashboard" href="/seller" :active="request()->is('seller')" />
            <x-sidebar-nav-item icon="products" label="My Products" href="/seller/products" :active="request()->is('seller/products*')" />
            <x-sidebar-nav-item icon="orders" label="My Orders" href="/seller/orders" :active="request()->is('seller/orders*')" />
            <form method="post" action="/seller/logout" style="margin-top:14px">@csrf
                <button class="ng-navitem" type="submit" style="width:100%;background:none;border:0;cursor:pointer;font:inherit;color:inherit">
                    <x-icon name="logout" :size="20" /> <span class="ng-navitem__lbl">Log out</span>
                </button>
            </form>
        </nav>
    </aside>
    <main class="main">
        <div class="topbar">
            <h1>@yield('title', 'Dashboard')</h1>
            <span class="badge b-muted">{{ $vendor->name ?? '' }}</span>
        </div>
        @if (session('status'))<div class="badge b-ok" role="status" style="margin-bottom:12px">{{ session('status') }}</div>@endif
        @yield('content')
    </main>
</div>
</body>
</html>
