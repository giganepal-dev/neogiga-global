<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', $portal['name']) · NeoGiga</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='7' fill='%23081527'/><path d='M9 22V10l14 12V10' stroke='%23f9bd2c' stroke-width='2.4' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>">
    <x-icon-styles/>
    <style nonce="{{ $csp_nonce ?? '' }}">
        :root{
            --navy:#0F172A;--navy-soft:#1E293B;--line:rgba(148,163,184,.18);--muted:#64748B;
            --accent:#f9bd2c;--accent-soft:rgba(249,189,44,.14);--ok:#16a34a;--warn:#d97706;
            --bad:#e5484d;--info:#0f62e6;--ng-accent:#f9bd2c;--ng-focus:#f9bd2c;
            --shadow:0 1px 2px rgba(15,23,42,.06),0 8px 24px rgba(15,23,42,.06);
        }
        *{box-sizing:border-box}
        body{margin:0;font:15px/1.55 ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial;color:#0f172a;background:linear-gradient(180deg,#eef2f7 0%,#f8fafc 220px)}
        a{color:inherit;text-decoration:none}
        .shell{display:grid;grid-template-columns:240px 1fr;min-height:100dvh}
        .side{background:linear-gradient(180deg,var(--navy) 0%,#111827 100%);color:#CBD5E1;padding:16px 14px;position:sticky;top:0;height:100dvh;display:flex;flex-direction:column;gap:12px;border-right:1px solid rgba(255,255,255,.06)}
        .side .brand{display:flex;gap:10px;align-items:center;color:#fff;font-weight:800;padding:8px 10px 12px}
        .side .brand small{display:block;font-weight:500;color:#94A3B8;font-size:.72rem;margin-top:2px}
        .side nav{display:grid;gap:4px;flex:1}
        .side-foot{margin-top:auto;padding:8px 10px;font-size:.75rem;color:#94A3B8}
        .main{padding:22px 28px 40px;max-width:1280px}
        .topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap}
        .topbar h1{font-size:1.2rem;margin:0;font-weight:700}
        .topbar-meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .user-chip{display:inline-flex;align-items:center;gap:8px;padding:6px 12px 6px 6px;border-radius:999px;background:#fff;border:1px solid var(--line);box-shadow:var(--shadow)}
        .user-chip .avatar{width:28px;height:28px;border-radius:50%;background:var(--accent-soft);color:#92400e;display:grid;place-items:center;font-size:.78rem;font-weight:800}
        .badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;font-size:.74rem;font-weight:700}
        .b-ok{background:rgba(22,163,74,.12);color:var(--ok)}.b-warn{background:rgba(217,119,6,.12);color:var(--warn)}.b-muted{background:rgba(100,116,139,.12);color:var(--muted)}.b-bad{background:rgba(229,72,77,.12);color:var(--bad)}.b-info{background:rgba(15,98,230,.1);color:var(--info)}
        .page-intro{margin-bottom:20px}
        .page-intro h1{margin:0 0 6px;font-size:1.35rem}
        .page-intro p{margin:0;color:var(--muted)}
        .page-intro--row{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}
        .kpis,.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:20px}
        .kpi{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px;box-shadow:var(--shadow)}
        .kpi .t{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)}
        .kpi .v{font-size:1.55rem;font-weight:800;font-variant-numeric:tabular-nums;margin-top:4px}
        .kpi .s{font-size:.78rem;color:var(--muted);margin-top:2px}
        .card{background:#fff;border:1px solid var(--line);border-radius:14px;margin-bottom:16px;box-shadow:var(--shadow);overflow:hidden}
        .card-h{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;border-bottom:1px solid var(--line);background:#fafbfd}
        .card-h h2{font-size:.98rem;margin:0;font-weight:700}
        .card-body{padding:16px 18px}
        .empty-card{text-align:center;padding:48px 20px}
        .empty-card p{color:var(--muted);margin:0 0 16px}
        .tbl,.table{width:100%;border-collapse:collapse;font-size:.9rem}
        .tbl th,.tbl td,.table th,.table td{text-align:left;padding:10px 16px;border-bottom:1px solid var(--line);vertical-align:middle}
        .tbl th,.table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);background:#fafbfd}
        .num{text-align:right}.tnum{font-variant-numeric:tabular-nums}.mono{font-family:ui-monospace,Menlo,monospace;font-size:.84rem}
        .sub{color:var(--muted);font-size:.8rem}
        .filters{display:flex;gap:8px;padding:12px 16px;border-bottom:1px solid var(--line);flex-wrap:wrap}
        .field{display:grid;gap:6px;margin-bottom:12px}.field label{font-weight:600;color:var(--muted);font-size:.8rem}
        .field--sm{min-width:120px}
        .control{border:1px solid var(--line);border-radius:10px;padding:9px 11px;font:inherit;width:100%;background:#fff}
        .control:focus{outline:2px solid var(--accent-soft);border-color:var(--accent)}
        textarea.control{resize:vertical;min-height:96px}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid var(--line);background:#fff;border-radius:10px;padding:9px 15px;font:inherit;font-weight:600;cursor:pointer;transition:transform .12s ease, box-shadow .12s ease}
        .btn:hover{box-shadow:var(--shadow)}
        .btn-primary{background:var(--accent);border-color:transparent;color:#231a00}
        .btn-ghost{background:#fff}
        .actions-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
        .rfq-row{display:grid;grid-template-columns:2fr 1fr .7fr .9fr;gap:12px;padding-bottom:12px;margin-bottom:12px;border-bottom:1px dashed var(--line)}
        .form-card .card-body + .card-h{border-top:1px solid var(--line)}
        .flash{margin-bottom:14px;padding:10px 14px;border-radius:10px;background:rgba(22,163,74,.1);color:var(--ok);font-weight:600}
        .empty{text-align:center;padding:36px 16px;color:var(--muted)}
        .scroll-x,.table-wrap{overflow-x:auto}
        .menu-toggle{display:none}
        @media(max-width:920px){
            .shell{grid-template-columns:1fr}
            .side{position:fixed;inset:0 auto 0 0;width:min(84vw,280px);transform:translateX(-105%);transition:transform .2s ease;z-index:40;height:100dvh}
            .side.is-open{transform:translateX(0)}
            .menu-toggle{display:inline-flex}
            .rfq-row{grid-template-columns:1fr}
            .main{padding:16px}
        }
    </style>
</head>
<body>
@php
    $entityName = $vendor->name ?? $distributor->name ?? $reseller->name ?? $manufacturer->name ?? $account->name ?? '';
    $initial = strtoupper(substr(trim($entityName ?: 'N'), 0, 1));
@endphp
<div class="shell">
    <aside class="side" id="portal-side">
        <div class="brand">
            <svg width="22" height="22" viewBox="0 0 32 32" fill="none" aria-hidden="true"><path d="M9 22V10l14 12V10" stroke="#f9bd2c" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>NeoGiga<small>{{ $portal['name'] }}</small></span>
        </div>
        <nav aria-label="{{ $portal['name'] }} navigation">
            @php $seenGroups = []; @endphp
            @foreach($portal['nav'] as $item)
                <x-sidebar-nav-item
                    :icon="$item['icon']"
                    :label="$item['label']"
                    :href="$item['href']"
                    :active="request()->is($item['pattern'])"
                    :group="$item['group'] ?? null"
                    :method="$item['method'] ?? 'GET'"
                    :loop="$loop"
                    :portal="$portal"
                />
            @endforeach
            <form method="post" action="/{{ $portal['slug'] }}/logout" style="margin-top:14px">@csrf
                <button class="ng-navitem" type="submit" style="width:100%;background:none;border:0;cursor:pointer;font:inherit;color:inherit">
                    <x-icon name="logout" :size="20" /> <span class="ng-navitem__lbl">Log out</span>
                </button>
            </form>
        </nav>
        <div class="side-foot">NeoGiga Partner Network</div>
    </aside>
    <main class="main">
        <div class="topbar">
            <div style="display:flex;align-items:center;gap:10px">
                <button type="button" class="btn btn-ghost menu-toggle" id="portal-menu-toggle" aria-label="Open navigation">Menu</button>
                <h1>@yield('title', 'Dashboard')</h1>
            </div>
            <div class="topbar-meta">
                @if($entityName)
                    <span class="user-chip"><span class="avatar">{{ $initial }}</span><span>{{ $entityName }}</span></span>
                @endif
                <span class="badge b-muted">{{ $portal['name'] }}</span>
            </div>
        </div>
        @if (session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
        @yield('content')
    </main>
</div>
<script nonce="{{ $csp_nonce ?? '' }}">
document.getElementById('portal-menu-toggle')?.addEventListener('click', function () {
    document.getElementById('portal-side')?.classList.toggle('is-open');
});
</script>
</body>
</html>
