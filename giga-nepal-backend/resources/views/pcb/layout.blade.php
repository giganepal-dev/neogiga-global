<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="yandex-verification" content="208c27f08c871e90" />
    <!-- Google tag (gtag.js) -->
    <script nonce="{{ $csp_nonce ?? '' }}" async src="https://www.googletagmanager.com/gtag/js?id=G-6LCPY27D9N"></script>
    <script nonce="{{ $csp_nonce ?? '' }}">
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-6LCPY27D9N');
    </script>
    <title>@yield('title', 'NeoGiga PCB — PCB Fabrication & PCBA Assembly Platform')</title>
    <meta name="description" content="@yield('description', 'Instant PCB and PCBA quote. Gerber upload, DFM analysis, SMT assembly, component sourcing, engineering review and production tracking from NeoGiga.')">
    <meta name="robots" content="@yield('robots', 'index,follow')">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/png" sizes="32x32" href="https://neogiga.com/images/brand/neogiga-favicon-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="https://neogiga.com/images/brand/neogiga-icon-192.png">
    <style nonce="{{ $csp_nonce ?? '' }}">
        /* NeoGiga "Precision Engineering" design system — shared with neogiga.com */
        :root{--bg:#101417;--bg2:#0b0f11;--s1:#181c1f;--s2:#1d2023;--s3:#272a2d;--blue:#123a6b;--cyan:#28d8fb;--gold:#f9bd2c;--white:#fff;--soft:#d7e2ef;--on:#e0e3e6;--muted:#c5c6cd;--faint:#8f9097;--ink:#e0e3e6;--line:rgba(255,255,255,.08);--glass:rgba(255,255,255,.03);--success:#10b981;--danger:#ef4444;--max:1280px;--r:14px}
        *{box-sizing:border-box}html{scroll-behavior:smooth}
        body{margin:0;background:radial-gradient(circle at 16% -2%,rgba(40,216,251,.09),transparent 44rem),radial-gradient(circle at 86% 0%,rgba(249,189,44,.05),transparent 38rem),var(--bg);color:var(--on);font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;line-height:1.55;letter-spacing:0;-webkit-font-smoothing:antialiased}
        a{color:inherit;text-decoration:none}img,svg{max-width:100%;display:block}button,input,select,textarea{font:inherit;letter-spacing:0}button{cursor:pointer}
        .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;letter-spacing:.02em}
        .wrap{width:min(var(--max),calc(100% - 40px));margin-inline:auto}.skip{position:absolute;left:-999px;top:8px;background:#fff;color:#000;padding:8px 10px;border-radius:6px;z-index:100}.skip:focus{left:8px}

        /* Top strip — regional switcher */
        .top-strip{background:var(--bg2);color:var(--muted);font-size:.78rem;border-bottom:1px solid var(--line)}.top-strip .wrap{min-height:34px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}.edition-links{display:flex;gap:12px;flex-wrap:wrap}.edition-links a{color:var(--muted);transition:color .18s}.edition-links a:hover{color:var(--cyan)}

        /* Site header */
        .site-head{position:sticky;top:0;z-index:60;background:rgba(16,20,23,.82);backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}.head-main{min-height:76px;display:grid;grid-template-columns:auto minmax(280px,1fr) auto;gap:18px;align-items:center}.brand{display:flex;align-items:center;gap:11px;color:#fff;font-weight:800;letter-spacing:-.01em}.mark{width:40px;height:40px;border:1px solid rgba(40,216,251,.4);border-radius:10px;display:grid;place-items:center;background:linear-gradient(135deg,rgba(40,216,251,.16),rgba(249,189,44,.06));font-weight:900;color:var(--cyan);font-size:.9rem}.brand small{display:block;color:var(--gold);font-size:.62rem;letter-spacing:.18em;text-transform:uppercase;margin-top:-2px}
        .head-actions{display:flex;align-items:center;gap:8px}.icon-btn{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);border-radius:10px;min-height:40px;padding:0 12px;font-weight:600;font-size:.86rem;background:rgba(255,255,255,.05);color:#fff;transition:.15s}.icon-btn:hover{border-color:rgba(40,216,251,.5)}.icon-btn.gold{border-color:rgba(249,189,44,.45);color:var(--gold)}

        /* Nav row + mega menu */
        .nav-row{border-top:1px solid var(--line)}.nav-row .wrap{display:flex;align-items:center;gap:18px;min-height:46px}.mega{position:relative}.mega summary{list-style:none;display:flex;align-items:center;gap:8px;color:#fff;font-weight:700;font-size:.9rem;cursor:pointer}.mega summary::-webkit-details-marker{display:none}.mega-panel{position:absolute;top:40px;left:0;width:min(780px,calc(100vw - 32px));background:var(--s1);color:var(--on);border:1px solid var(--line);border-radius:14px;box-shadow:0 24px 80px rgba(0,0,0,.5);padding:20px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;backdrop-filter:blur(14px);z-index:70}.mega-col{display:grid;gap:6px}.mega-col h3{font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--faint);margin:0 0 4px}.mega-col a{padding:8px 10px;border-radius:8px;color:var(--muted);transition:.15s;font-size:.86rem}.mega-col a:hover{background:rgba(40,216,251,.1);color:var(--cyan)}.primary-nav{display:flex;gap:18px;color:var(--muted);font-size:.9rem;font-weight:600;flex-wrap:wrap}.primary-nav a{transition:color .15s}.primary-nav a:hover,.primary-nav a.active{color:var(--cyan)}

        /* Buttons */
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;border-radius:10px;padding:0 18px;font-weight:600;font-size:.9rem;border:1px solid transparent;transition:.15s;text-align:center}.btn:hover{transform:translateY(-1px)}.btn-primary{background:var(--cyan);color:#003640}.btn-primary:hover{filter:brightness(1.1)}.btn-gold{background:var(--gold);color:#261900}.btn-ghost{border-color:var(--line);background:transparent;color:var(--on)}.btn-ghost:hover{border-color:var(--cyan);color:var(--cyan)}.btn-dark{border-color:var(--line);color:#fff;background:rgba(255,255,255,.05)}.btn-danger{background:transparent;color:#ef4444;border-color:rgba(239,68,68,.3)}.btn-danger:hover{border-color:#ef4444;background:rgba(239,68,68,.1)}

        /* Cards */
        .card{background:var(--glass);border:1px solid var(--line);border-radius:var(--r);backdrop-filter:blur(12px)}.card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:18px 20px;border-bottom:1px solid var(--line)}.card-head h2{margin:0;font-size:1.05rem}.card-body{padding:20px}

        /* Typography */
        .eyebrow{color:var(--cyan);font-weight:700;letter-spacing:.14em;text-transform:uppercase;font-size:.74rem}
        h1,.page-title{font-size:clamp(2rem,5vw,3.6rem);font-weight:800;line-height:1.04;letter-spacing:-.02em;margin:10px 0 16px}.lead{color:var(--muted);font-size:1.05rem;max-width:72ch;margin:0}

        /* Grid */
        .grid{display:grid;gap:20px}.split{grid-template-columns:minmax(0,1.5fr) minmax(300px,.85fr);align-items:start}.kpis{grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:24px}.kpi{background:var(--glass);border:1px solid var(--line);border-radius:var(--r);padding:18px 20px}.kpi .t{display:block;color:var(--faint);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em}.kpi .v{display:block;font-size:1.75rem;font-weight:800;margin:6px 0 2px;color:var(--on)}.kpi .s{color:var(--muted);font-size:.78rem}

        /* Badges */
        .badge{display:inline-flex;align-items:center;gap:4px;border-radius:8px;padding:3px 10px;font-size:.72rem;font-weight:600;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;border:1px solid transparent;white-space:nowrap;text-transform:capitalize}.b-ok{background:rgba(16,185,129,.15);color:#34d399;border-color:rgba(16,185,129,.3)}.b-warn{background:rgba(249,189,44,.15);color:var(--gold);border-color:rgba(249,189,44,.3)}.b-info{background:rgba(40,216,251,.14);color:var(--cyan);border-color:rgba(40,216,251,.3)}.b-danger{background:rgba(239,68,68,.15);color:#f87171;border-color:rgba(239,68,68,.3)}.b-muted{background:rgba(255,255,255,.06);color:var(--muted);border-color:var(--line)}

        /* Forms */
        .form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.field{display:grid;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:.74rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.04em}.field .hint{color:var(--faint);font-size:.76rem}.control{width:100%;border:1px solid var(--line);border-radius:10px;min-height:44px;padding:9px 12px;background:var(--s1);color:var(--on)}.control:focus{outline:0;border-color:var(--cyan)}.control::placeholder{color:rgba(197,198,205,.4)}textarea.control{min-height:100px;resize:vertical}.check-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.check{display:flex;align-items:center;gap:8px;border:1px solid var(--line);padding:9px 11px;border-radius:9px;background:rgba(255,255,255,.03);font-size:.86rem;color:var(--muted)}.form-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px}

        /* Tables */
        .table-wrap{overflow-x:auto}.table{width:100%;border-collapse:collapse;font-size:.86rem}.table th,.table td{text-align:left;padding:11px 14px;border-bottom:1px solid var(--line);vertical-align:top;color:var(--on)}.table th{font-size:.71rem;text-transform:uppercase;color:var(--faint);background:var(--s1);font-weight:700}.table tr:last-child td{border-bottom:0}

        /* Misc */
        .notice{border:1px solid rgba(40,216,251,.3);background:rgba(40,216,251,.08);color:var(--cyan);padding:12px 15px;border-radius:10px;margin-bottom:16px}.errors{border-color:rgba(239,68,68,.3);background:rgba(239,68,68,.08);color:#f87171}.errors ul{margin:0;padding-left:20px}.crumbs{display:flex;flex-wrap:wrap;gap:7px;align-items:center;color:var(--faint);font-size:.85rem;margin-bottom:18px}.crumbs a{color:var(--cyan)}.crumbs a:hover{text-decoration:underline}.divider{border:0;border-top:1px solid var(--line);margin:20px 0}.muted{color:var(--muted)}.empty{padding:48px 20px;text-align:center;color:var(--muted)}.empty strong{display:block;color:var(--on);font-size:1.05rem;margin-bottom:6px}

        /* Spec list */
        .spec-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.spec-list div{border-bottom:1px solid var(--line);padding-bottom:10px}.spec-list small{display:block;color:var(--faint);text-transform:uppercase;font-weight:700;font-size:.7rem;letter-spacing:.06em}.spec-list b,.spec-list span{display:block;margin-top:3px;font-weight:600}

        /* Timeline */
        .timeline{display:grid;gap:0}.timeline-item{display:grid;grid-template-columns:10px 1fr;gap:12px;padding-bottom:16px}.timeline-dot{width:8px;height:8px;border-radius:50%;background:var(--cyan);margin-top:7px}.timeline-item p{margin:0;font-size:.88rem}.timeline-item time{font-size:.74rem;color:var(--faint)}

        /* Footer */
        .footer{background:var(--bg2);color:var(--muted);padding:56px 0 80px;border-top:1px solid var(--line);margin-top:64px}.foot-grid{display:grid;grid-template-columns:1.5fr repeat(4,1fr);gap:24px}.footer h3{color:#fff;font-size:.82rem;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px}.footer a{display:block;color:var(--muted);margin:8px 0;transition:color .15s;font-size:.86rem}.footer a:hover{color:var(--cyan)}.newsletter{display:flex;gap:8px;flex-wrap:wrap}.newsletter input{min-height:44px;border-radius:10px;border:1px solid var(--line);background:var(--s1);color:var(--on);padding:0 14px;flex:1;min-width:180px}

        /* Stack */
        .stack{display:grid;gap:16px}

        /* Details/advanced */
        details.advanced{border:1px solid var(--line);border-radius:10px;background:rgba(255,255,255,.02)}details.advanced summary{padding:12px 15px;font-weight:700;cursor:pointer;color:var(--muted)}details.advanced>div{padding:0 15px 15px}

        /* PCB-specific */
        .pcb-cyan{color:var(--cyan)}.pcb-gold{color:var(--gold)}.security-note{display:grid;grid-template-columns:30px 1fr;gap:10px;background:rgba(40,216,251,.07);border:1px solid rgba(40,216,251,.15);padding:12px;border-radius:10px}.security-icon{width:30px;height:30px;border-radius:8px;background:rgba(40,216,251,.16);color:var(--cyan);display:grid;place-items:center;font-weight:900;font-size:.85rem}.danger-zone .card-head{background:rgba(239,68,68,.06);border-color:rgba(239,68,68,.2)}.danger-zone .card-head h2{color:#f87171}

        /* Status tracker */
        .status-track{display:grid;grid-template-columns:repeat(5,1fr);gap:6px}.status-step{border-top:4px solid rgba(255,255,255,.08);padding-top:10px;color:var(--faint);font-size:.72rem;font-weight:700}.status-step.done{border-color:var(--cyan);color:var(--cyan)}.status-step.current{border-color:var(--gold);color:var(--gold)}

        /* Quote total */
        .quote-total{display:flex;align-items:end;justify-content:space-between;gap:18px;padding:16px;background:rgba(40,216,251,.06);border:1px solid rgba(40,216,251,.15);border-radius:10px}.quote-total strong{font-size:1.8rem;font-weight:800;color:var(--on)}

        /* Responsive */
        @media(max-width:980px){.head-main{grid-template-columns:1fr;gap:10px;padding:12px 0}.head-actions{overflow-x:auto}.nav-row{display:none}.split{grid-template-columns:1fr}.kpis{grid-template-columns:repeat(2,1fr)}.foot-grid{grid-template-columns:1fr 1fr}}
        @media(max-width:620px){.wrap{width:min(var(--max),calc(100% - 24px))}.kpis{grid-template-columns:1fr}.foot-grid{grid-template-columns:1fr 1fr}.form-grid,.spec-list,.check-grid{grid-template-columns:1fr}.status-track{grid-template-columns:1fr}.status-step{border-top:0;border-left:4px solid rgba(255,255,255,.08);padding:6px 0 6px 10px}.btn{width:100%}.form-actions{display:grid}.quote-total{display:block}.quote-total .btn{margin-top:12px}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important;scroll-behavior:auto!important}.btn:hover{transform:none}}
    </style>
    @stack('styles')
</head>
<body>
<a class="skip" href="#main">Skip to content</a>

<!-- Top strip — regional editions -->
<div class="top-strip">
    <div class="wrap">
        <span>NeoGiga global marketplace</span>
        <nav class="edition-links" aria-label="Regional editions">
            <a href="https://neogiga.com/en">Global (EN)</a>
            <a href="https://neogiga.in/en">India</a>
            <a href="https://np.neogiga.com/en">Nepal</a>
            <a href="https://bd.neogiga.com/en">Bangladesh</a>
            <a href="https://neogiga.com/en#regional-editions">26+ editions</a>
        </nav>
    </div>
</div>

<!-- Global header -->
<header class="site-head">
    <div class="wrap head-main">
        <a class="brand" href="https://neogiga.com/en" aria-label="NeoGiga home">
            <span class="mark">NG</span>
            <span>NeoGiga<small>PCB & PCBA Platform</small></span>
        </a>
        <div style="display:flex;align-items:center;gap:8px;color:var(--cyan);font-weight:700;font-size:.9rem">
            ⚡ PCB & PCBA Platform
        </div>
        <div class="head-actions">
            @auth
                <span style="color:var(--muted);font-size:.82rem;margin-right:8px">{{ auth()->user()->name }}</span>
                <a class="icon-btn" href="https://neogiga.com/account/pcb">Customer account</a>
                <form method="post" action="/en/logout">@csrf<button class="icon-btn" type="submit">Sign out</button></form>
            @else
                <a class="icon-btn" href="/en/login">Sign in</a>
                <a class="icon-btn gold" href="/en/register">Start project</a>
            @endauth
        </div>
    </div>
    <div class="nav-row">
        <div class="wrap">
            <details class="mega">
                <summary>☰ Platform</summary>
                <div class="mega-panel">
                    <div class="mega-col"><h3>PCB Services</h3><a href="/en/capabilities">Manufacturing capabilities</a><a href="/en/design-rules">Design rules</a><a href="/en#quote">Instant quote</a><a href="/en/register">Start PCB project</a></div>
                    <div class="mega-col"><h3>NeoGiga Global</h3><a href="https://neogiga.com/en/products">Component catalog</a><a href="https://neogiga.com/en/rfq">Bulk RFQ sourcing</a><a href="https://neogiga.com/en/ai-commerce">AI Project Builder</a><a href="https://neogiga.com/en/brands">Brands</a></div>
                    <div class="mega-col"><h3>Resources</h3><a href="/en/projects">My PCB projects</a><a href="https://neogiga.com/en/lms">Learning Hub</a><a href="mailto:pcb@neogiga.com">Engineering support</a><a href="https://neogiga.com/en/categories">Product categories</a></div>
                </div>
            </details>
            <nav class="primary-nav" aria-label="PCB platform navigation">
                <a href="/en" @if(request()->is('en') && !request()->is('en/*')) class="active" @endif>Instant Quote</a>
                <a href="/en/capabilities">Capabilities</a>
                <a href="/en/design-rules">Design Rules</a>
                <a href="/en/projects" @if(request()->is('en/projects*')) class="active" @endif>Projects</a>
                @auth<a href="https://neogiga.com/account/pcb">Account dashboard</a>@endauth
                <a href="https://neogiga.com/en/products">Components</a>
                <a href="https://neogiga.com/en/rfq">RFQ</a>
            </nav>
        </div>
    </div>
</header>

<main id="main">
    @yield('content')
</main>

<!-- Global footer -->
<footer class="footer">
    <div class="wrap foot-grid">
        <div>
            <a class="brand" href="https://neogiga.com/en"><span class="mark">NG</span><span>NeoGiga<small>Engineering Marketplace</small></span></a>
            <p style="color:var(--muted);font-size:.86rem">PCB fabrication, SMT assembly and component sourcing platform. Part of the NeoGiga global engineering marketplace.</p>
        </div>
        <div><h3>PCB Platform</h3><a href="/en">Instant quote</a><a href="/en/capabilities">Capabilities</a><a href="/en/design-rules">Design rules</a><a href="/en/register">Start project</a></div>
        <div><h3>NeoGiga</h3><a href="https://neogiga.com/en/products">Products</a><a href="https://neogiga.com/en/categories">Categories</a><a href="https://neogiga.com/en/brands">Brands</a><a href="https://neogiga.com/en/rfq">Bulk RFQ</a></div>
        <div><h3>Support</h3><a href="mailto:pcb@neogiga.com">PCB engineering</a><a href="https://neogiga.com/en/lms">Learning Hub</a><a href="https://neogiga.com/en/ai-commerce">AI Builder</a><a href="/en/projects">My projects</a></div>
        <div><h3>Regional</h3><a href="https://neogiga.com/en">Global</a><a href="https://neogiga.in/en">India</a><a href="https://np.neogiga.com/en">Nepal</a><a href="https://neogiga.com/en#regional">26+ markets</a></div>
    </div>
</footer>
@stack('scripts')
</body>
</html>
