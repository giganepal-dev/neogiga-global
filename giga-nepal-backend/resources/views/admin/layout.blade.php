<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') · NeoGiga Admin</title>
    <link rel="icon" href="{{ url('/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('/images/brand/neogiga-icon-16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('/images/brand/neogiga-favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('/images/brand/neogiga-apple-touch-icon-180.png') }}">
    <style nonce="{{ $csp_nonce ?? '' }}">
        :root{
            --navy:#0F172A;--navy-2:#111f38;--slate:#334155;--line:#E2E8F0;
            --bg:#F8FAFC;--surface:#FFFFFF;--fg:#020617;--muted:#64748B;
            --primary:#0369A1;--primary-600:#075985;--cyan:#19D3F5;--gold:#F5B928;
            --ok:#059669;--warn:#D97706;--danger:#DC2626;
            --r:10px;--r-sm:7px;--sb:248px;
            --shadow:0 1px 2px rgba(2,6,23,.06),0 1px 3px rgba(2,6,23,.08);
        }
        *{box-sizing:border-box}
        html{-webkit-text-size-adjust:100%}
        body{margin:0;background:var(--bg);color:var(--fg);
            font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
            font-size:15px;line-height:1.55;-webkit-font-smoothing:antialiased}
        a{color:inherit;text-decoration:none}
        .tnum{font-variant-numeric:tabular-nums;font-feature-settings:"tnum"}
        .mono{font-family:ui-monospace,SFMono-Regular,"SF Mono",Menlo,Consolas,monospace}

        /* Layout shell */
        .app{display:flex;align-items:stretch;min-height:100dvh;background:var(--bg)}
        .sidebar{background:var(--navy);color:#CBD5E1;position:sticky;top:0;height:100dvh;
            width:var(--sb);min-width:var(--sb);max-width:var(--sb);flex:0 0 var(--sb);
            display:flex;flex-direction:column;overflow-y:auto;z-index:30}
        .brand{display:flex;align-items:center;gap:11px;padding:18px 18px;border-bottom:1px solid rgba(148,163,184,.14)}
        .brand .mark{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#0b1d38,#12304f);
            display:grid;place-items:center;box-shadow:inset 0 0 0 1px rgba(25,211,245,.35)}
        .brand b{color:#fff;font-size:1.02rem;letter-spacing:-.01em}
        .brand small{display:block;color:var(--gold);font-size:.62rem;letter-spacing:.18em;text-transform:uppercase;margin-top:1px}
        .nav{padding:12px 10px;display:flex;flex-direction:column;gap:2px;flex:1;min-width:0}
        .nav .lbl{color:#64748B;font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;padding:14px 12px 6px}
        .nav a{display:flex;align-items:center;gap:11px;padding:9px 12px;border-radius:8px;color:#CBD5E1;
            font-weight:500;font-size:.9rem;transition:background .15s,color .15s;width:100%;min-width:0}
        .nav a span{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .nav a svg{width:18px;height:18px;flex:none;opacity:.85}
        .nav a:hover{background:rgba(148,163,184,.10);color:#fff}
        .nav a.active{background:rgba(25,211,245,.14);color:#fff;box-shadow:inset 2px 0 0 var(--cyan)}
        .nav a.active svg{opacity:1;color:var(--cyan)}
        .sidebar .foot{padding:12px;border-top:1px solid rgba(148,163,184,.14);font-size:.78rem;color:#64748B}

        .main{min-width:0;flex:1 1 auto;display:flex;flex-direction:column;background:var(--bg);color:var(--fg)}
        .topbar{position:sticky;top:0;z-index:20;background:rgba(255,255,255,.85);backdrop-filter:blur(8px);
            border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 22px}
        .topbar h1{font-size:1.05rem;margin:0;font-weight:700;letter-spacing:-.01em}
        .crumb{color:var(--muted);font-size:.8rem}
        .who{display:flex;align-items:center;gap:10px}
        .who .av{width:32px;height:32px;border-radius:50%;background:var(--navy);color:#fff;display:grid;place-items:center;font-size:.8rem;font-weight:700}
        .top-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end}
        .searchbox{width:min(320px,30vw);position:relative}
        .searchbox input{padding-left:34px}
        .searchbox svg{position:absolute;left:10px;top:10px;width:17px;height:17px;color:var(--muted)}
        .chip{display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 10px;border:1px solid var(--line);border-radius:999px;background:#fff;color:var(--slate);font-size:.78rem;font-weight:700}
        .page-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px}
        .page-head h2{margin:0;font-size:1.35rem;letter-spacing:-.02em}
        .page-head p{margin:4px 0 0;color:var(--muted);font-size:.9rem}
        .page-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end}
        .content{padding:22px;max-width:1280px;width:100%;margin:0 auto}

        /* Buttons */
        .btn{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 14px;border-radius:var(--r-sm);
            font-weight:600;font-size:.86rem;border:1px solid transparent;cursor:pointer;transition:background .15s,border-color .15s,box-shadow .15s}
        .btn svg{width:16px;height:16px}
        .btn-primary{background:var(--primary);color:#fff}
        .btn-primary:hover{background:var(--primary-600)}
        .btn-ghost{background:#fff;border-color:var(--line);color:var(--slate)}
        .btn-ghost:hover{border-color:#cbd5e1;background:#f8fafc}
        .btn:focus-visible,a:focus-visible,input:focus-visible{outline:2px solid var(--primary);outline-offset:2px}
        .control{width:100%;min-height:38px;border:1px solid var(--line);border-radius:var(--r-sm);padding:8px 10px;
            color:var(--fg);background:#fff;font:inherit;box-shadow:none}
        textarea.control{min-height:84px;resize:vertical}
        .control:focus{border-color:#93c5fd;outline:2px solid rgba(3,105,161,.16);outline-offset:0}
        .form-stack{display:grid;gap:10px}
        .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
        .field label{display:block;color:var(--muted);font-size:.73rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin:0 0 5px}
        .filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;padding:14px 16px;border-bottom:1px solid var(--line);background:#fbfdff}
        .actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
        .icon-btn{width:34px;height:34px;padding:0;justify-content:center}
        .danger{color:var(--danger);border-color:#fecaca;background:#fff}
        .tabs{display:flex;gap:6px;flex-wrap:wrap;padding:10px 16px;border-bottom:1px solid var(--line);background:#fff}
        .tab{height:32px;border:1px solid var(--line);background:#fff;border-radius:999px;padding:0 12px;font-size:.78rem;font-weight:700;color:var(--slate);display:inline-flex;align-items:center}
        .tab.active{background:#e0f2fe;border-color:#bae6fd;color:#075985}
        .drawer{border-left:1px solid var(--line);background:#fbfdff;padding:14px}
        details.modal{display:inline-block}
        details.modal > summary{list-style:none}
        details.modal > summary::-webkit-details-marker{display:none}
        details.modal[open]::before{content:"";position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:80}
        .modal-panel{position:fixed;right:24px;top:24px;bottom:24px;width:min(560px,calc(100vw - 48px));overflow:auto;background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 24px 80px rgba(2,6,23,.28);z-index:90}
        .modal-h{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px;border-bottom:1px solid var(--line)}
        .modal-h h3{margin:0;font-size:1rem}
        .modal-b{padding:16px}
        .dropzone{border:1.5px dashed #cbd5e1;border-radius:10px;background:#f8fafc;padding:24px;text-align:center;color:var(--muted)}

        /* Cards / KPIs */
        .grid{display:grid;gap:14px}
        .kpis{grid-template-columns:repeat(auto-fill,minmax(190px,1fr));margin-bottom:18px}
        .kpi{background:var(--surface);border:1px solid var(--line);border-radius:var(--r);padding:16px;box-shadow:var(--shadow)}
        .kpi .t{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
        .kpi .t svg{width:16px;height:16px;color:var(--primary)}
        .kpi .v{font-size:1.8rem;font-weight:800;letter-spacing:-.02em;margin-top:8px}
        .kpi .s{color:var(--muted);font-size:.78rem;margin-top:2px}

        .card{background:var(--surface);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);overflow:hidden}
        .card-h{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid var(--line)}
        .card-h h2{margin:0;font-size:.98rem;font-weight:700}
        .card-h .sub{color:var(--muted);font-size:.8rem}
        .split{grid-template-columns:repeat(2,minmax(0,1fr));align-items:start}
        .dashboard-split{grid-template-columns:minmax(0,1.4fr) minmax(320px,1fr);align-items:start}
        .stack-gap{margin-top:16px}

        /* Table */
        .tbl{width:100%;border-collapse:collapse;font-size:.88rem}
        .tbl th{text-align:left;color:var(--muted);font-weight:600;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;
            padding:10px 16px;border-bottom:1px solid var(--line);background:#fbfdff;position:sticky;top:0}
        .tbl td{padding:11px 16px;border-bottom:1px solid #eef2f6;vertical-align:middle}
        .tbl tr:last-child td{border-bottom:0}
        .tbl tbody tr{transition:background .12s}
        .tbl tbody tr:hover{background:#f6fafe}
        .tbl .num{text-align:right;font-variant-numeric:tabular-nums}
        .scroll-x{overflow-x:auto}

        /* Badges */
        .badge{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:999px;font-size:.72rem;font-weight:700;line-height:1.7}
        .b-ok{background:#dcfce7;color:#166534}.b-muted{background:#eef2f6;color:#475569}
        .b-warn{background:#fef3c7;color:#92400e}.b-info{background:#e0f2fe;color:#075985}
        .b-danger{background:#fee2e2;color:#991b1b}

        /* Empty state */
        .empty{padding:44px 20px;text-align:center;color:var(--muted)}
        .empty svg{width:38px;height:38px;color:#cbd5e1;margin-bottom:10px}
        .empty h3{margin:0 0 4px;color:var(--slate);font-size:1rem}
        .empty p{margin:0 auto;max-width:430px;font-size:.88rem}

        /* Category tree */
        .tree{list-style:none;margin:0;padding:8px 4px}
        .tree li{padding:0}
        .tree .row{display:flex;align-items:center;gap:9px;padding:7px 12px;border-radius:7px}
        .tree .row:hover{background:#f6fafe}
        .tree .dot{width:7px;height:7px;border-radius:50%;background:var(--cyan);flex:none}
        .tree .kids{list-style:none;margin:0;padding-left:22px;border-left:1px dashed #dbe4ec;margin-left:15px}
        .tree .cnt{margin-left:auto;color:var(--muted);font-size:.75rem}

    /* Collapsible menu sections */
    .nav-section{margin-bottom:2px}
    .nav-section-header{display:flex;align-items:center;justify-content:space-between;padding:8px 14px 6px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);cursor:pointer;user-select:none;border-radius:var(--r-sm);margin:4px 8px 0}
    .nav-section-header:hover{color:var(--accent);background:#f8fafc}
    .nav-section-header::after{content:'▾';font-size:10px;transition:transform .2s}
    .nav-section.collapsed .nav-section-header::after{transform:rotate(-90deg)}
    .nav-section-body{overflow:hidden;transition:max-height .25s ease}
    .nav-section.collapsed .nav-section-body{max-height:0 !important}

        .note{background:#eff6ff;border:1px solid #dbeafe;color:#1e3a8a;border-radius:var(--r-sm);padding:11px 14px;font-size:.84rem;margin-bottom:16px}

        @media (max-width:900px){
            .app{display:block}
            .sidebar{position:fixed;left:0;top:0;width:82%;max-width:300px;transform:translateX(-100%);transition:transform .22s;z-index:60;box-shadow:0 20px 60px rgba(2,6,23,.4)}
            .app.open .sidebar{transform:none}
            .app.open .scrim{position:fixed;inset:0;background:rgba(2,6,23,.5);z-index:50}
            .burger{display:inline-flex !important}
            .content{padding:16px}
            .topbar{padding:11px 16px}
            .who span[style*="text-align:right"]{display:none}
            .split,.dashboard-split{grid-template-columns:1fr !important}
            .card-h{align-items:flex-start;flex-direction:column}
        }
        @media (max-width:640px){
            .who form{display:none}
            .searchbox,.top-actions .chip{display:none}
            .page-head{display:block}
            .page-actions{justify-content:flex-start;margin-top:10px}
            .form-grid{grid-template-columns:1fr}
            .kpis{grid-template-columns:1fr}
            .tbl th,.tbl td{padding:9px 11px}
        }
        .burger{display:none;background:none;border:1px solid var(--line);border-radius:7px;width:38px;height:38px;align-items:center;justify-content:center;cursor:pointer}
        @media (prefers-reduced-motion:reduce){*{transition:none !important}}
    </style>
</head>
<body>
<div class="app" id="app">
    <div class="scrim" data-close></div>
    <aside class="sidebar">
        <a class="brand" href="/admin" aria-label="NeoGiga admin dashboard">
            <span class="mark">
                <img src="{{ url('/images/brand/neogiga-icon-192.png') }}" alt="" width="28" height="28" aria-hidden="true">
            </span>
            <span><b>NeoGiga</b><small>Admin Console</small></span>
        </a>
        @include('admin.partials.sidebar-nav')
    </aside>

    <div class="main">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:12px">
                <button class="burger" data-open aria-label="Open menu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
                </button>
                <div>
                    <h1>@yield('title','Dashboard')</h1>
                    <div class="crumb">@yield('crumb','NeoGiga Admin')</div>
                </div>
            </div>
            <div class="top-actions">
                <form class="searchbox" method="get" action="/admin/products">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4" stroke-linecap="round"/></svg>
                    <input class="control" name="q" placeholder="Search products, MPN or SKU" value="{{ request('q') }}">
                </form>
                <a class="chip" href="/admin/settings?setting_group=localization" title="Open localization settings">EN</a>
                <a class="chip" href="/admin/marketplaces" title="Open marketplace configuration">Global</a>
                <details class="modal">
                    <summary class="btn btn-ghost icon-btn" title="Notifications">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9" stroke-linecap="round"/><path d="M10 21h4" stroke-linecap="round"/></svg>
                    </summary>
                    <div class="modal-panel" style="max-width:420px">
                        <div class="modal-h"><h3>Operations Inbox</h3><span class="badge b-info">live destinations</span></div>
                        <div class="modal-b grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
                            <a class="btn btn-ghost" href="/admin/inventory">Stock alerts</a>
                            <a class="btn btn-ghost" href="/admin/applications">Seller approvals</a>
                            <a class="btn btn-ghost" href="/admin/rfqs">RFQ inbox</a>
                            <a class="btn btn-ghost" href="/admin/system-health">Queue &amp; health</a>
                        </div>
                    </div>
                </details>
                <details class="modal">
                    <summary class="btn btn-primary">Quick Actions</summary>
                    <div class="modal-panel">
                        <div class="modal-h"><h3>Quick Actions</h3><span class="badge b-info">NeoGiga</span></div>
                        <div class="modal-b grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
                            <a class="btn btn-ghost" href="/admin/products">Add Product</a>
                            <a class="btn btn-ghost" href="/admin/categories">Add Category</a>
                            <a class="btn btn-ghost" href="/admin/applications">Add Seller</a>
                            <a class="btn btn-ghost" href="/admin/marketing/email">Add Campaign</a>
                            <a class="btn btn-ghost" href="/admin/lms">Add Course</a>
                            <a class="btn btn-ghost" href="/admin/inventory">Add Warehouse</a>
                            <a class="btn btn-ghost" href="/admin/quotations">Create RFQ</a>
                            <a class="btn btn-ghost" href="/admin/media">Upload Media</a>
                        </div>
                    </div>
                </details>
                <div class="who">
                <span style="text-align:right;line-height:1.2">
                    <strong style="font-size:.86rem">{{ auth()->user()->name ?? 'Admin' }}</strong><br>
                    <span style="color:var(--muted);font-size:.74rem">{{ ucfirst(str_replace('_',' ', auth()->user()->role->name ?? 'admin')) }}</span>
                </span>
                <span class="av">{{ strtoupper(substr(auth()->user()->name ?? 'A',0,1)) }}</span>
                <form method="post" action="/admin/logout">@csrf
                    <button class="btn btn-ghost" type="submit" title="Sign out">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 12H3m0 0l4-4m-4 4l4 4M13 4h6a1 1 0 011 1v14a1 1 0 01-1 1h-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>
                </div>
            </div>
        </header>
        <main class="content">
            <div class="page-head">
                <div>
                    <h2>@yield('title','Dashboard')</h2>
                    <p>@yield('crumb','NeoGiga Admin')</p>
                </div>
                <div class="page-actions">@yield('page_actions')</div>
            </div>
            @if (session('status'))
                <div class="note"><strong>Saved.</strong> {{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="note" style="background:#fef2f2;border-color:#fecaca;color:#991b1b"><strong>Action failed.</strong> {{ session('error') }}</div>
            @endif
            @if (isset($errors) && $errors->any())
                <div class="note" style="background:#fef2f2;border-color:#fecaca;color:#991b1b"><strong>Check input.</strong> {{ $errors->first() }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
<script nonce="{{ $csp_nonce ?? '' }}">
(function(){
    var app=document.getElementById('app');
    document.querySelectorAll('[data-open]').forEach(function(b){b.addEventListener('click',function(){app.classList.add('open')})});
    document.querySelectorAll('[data-close]').forEach(function(b){b.addEventListener('click',function(){app.classList.remove('open')})});
})();
</script>
</body>
</html>
