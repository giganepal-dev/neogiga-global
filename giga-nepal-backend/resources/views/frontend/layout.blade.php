<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $marketplaceSeo['title'] ?? 'NeoGiga - Global Engineering Marketplace')</title>
    <meta name="description" content="@yield('description', $marketplaceSeo['description'] ?? 'Global marketplace for semiconductors, IoT, robotics, automation, battery technology, power storage and engineering tools.')">
    <link rel="canonical" href="{{ $canonical ?? ($marketplaceSeo['canonical'] ?? url()->current()) }}">
    <meta name="robots" content="{{ $marketplaceSeo['robots'] ?? 'index, follow' }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="NeoGiga">
    <meta property="og:title" content="@yield('title', $marketplaceSeo['og_title'] ?? 'NeoGiga')">
    <meta property="og:description" content="@yield('description', $marketplaceSeo['og_description'] ?? 'Global engineering marketplace.')">
    <meta property="og:url" content="{{ $canonical ?? ($marketplaceSeo['canonical'] ?? url()->current()) }}">
    @if(!empty($marketplaceSeo['og_image']))<meta property="og:image" content="{{ $marketplaceSeo['og_image'] }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', $marketplaceSeo['twitter_title'] ?? 'NeoGiga')">
    <meta name="twitter:description" content="@yield('description', $marketplaceSeo['twitter_description'] ?? 'Global engineering marketplace.')">
    @if(!empty($marketplaceSeo['twitter_image']))<meta name="twitter:image" content="{{ $marketplaceSeo['twitter_image'] }}">@endif
    @if(!empty($marketplaceSeo['schema_json']))<script type="application/ld+json">{!! $marketplaceSeo['schema_json'] !!}</script>@endif
    @foreach(($marketplaceContext['hreflang'] ?? []) as $alternate)
        <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['url'] }}">
    @endforeach
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='7' fill='%23081527'/><path d='M9 22V10l14 12V10' stroke='%2319D3F5' stroke-width='2.4' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>">
    @stack('head')
    <style>
        :root{--bg:#081527;--bg2:#0d2240;--blue:#123a6b;--cyan:#19d3f5;--gold:#f5b928;--white:#fff;--soft:#d7e2ef;--muted:#8fa4bd;--gray:#f5f7fb;--ink:#0b1728;--line:rgba(144,164,190,.22);--max:1240px;--r:8px}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--gray);color:var(--ink);font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;line-height:1.55;letter-spacing:0}a{color:inherit;text-decoration:none}img,svg{max-width:100%;display:block}button,input,select,textarea{font:inherit}button{cursor:pointer}
        .wrap{width:min(var(--max),calc(100% - 32px));margin-inline:auto}.skip{position:absolute;left:-999px;top:8px;background:#fff;color:#000;padding:8px 10px;border-radius:6px;z-index:100}.skip:focus{left:8px}.top-strip{background:#061120;color:#b7c7dc;font-size:.78rem}.top-strip .wrap{min-height:34px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}.edition-links{display:flex;gap:12px;flex-wrap:wrap}.edition-links a,.edition-links button{color:#d7e2ef;background:none;border:0;padding:0;font:inherit;cursor:pointer}.edition-links a:hover,.edition-links button:hover{color:var(--cyan)}
        .site-head{position:sticky;top:0;z-index:60;background:rgba(8,21,39,.96);border-bottom:1px solid rgba(25,211,245,.2);box-shadow:0 14px 40px rgba(0,0,0,.18)}.head-main{min-height:76px;display:grid;grid-template-columns:auto minmax(280px,1fr) auto;gap:18px;align-items:center}.brand{display:flex;align-items:center;gap:11px;color:#fff;font-weight:900}.mark{width:40px;height:40px;border:1px solid rgba(25,211,245,.45);border-radius:8px;display:grid;place-items:center;background:linear-gradient(135deg,rgba(25,211,245,.16),rgba(245,185,40,.08))}.brand small{display:block;color:var(--gold);font-size:.62rem;letter-spacing:.18em;text-transform:uppercase;margin-top:-2px}
        .search{display:grid;grid-template-columns:150px 1fr auto;border:1px solid rgba(255,255,255,.16);border-radius:8px;overflow:hidden;background:#fff}.search select,.search input{border:0;min-height:44px;padding:0 12px;color:#0f172a}.search select{background:#edf5ff;border-right:1px solid #d8e2ee}.search button{border:0;background:var(--cyan);color:#03131f;font-weight:900;padding:0 18px}.head-actions{display:flex;align-items:center;gap:8px}.switcher-form{display:flex;gap:6px;align-items:center}.select-lite,.icon-btn,.switch-btn{min-height:40px;border:1px solid rgba(255,255,255,.16);border-radius:8px;background:rgba(255,255,255,.06);color:#fff;padding:0 10px}.switch-btn{font-weight:900}.icon-btn{display:inline-flex;align-items:center;gap:6px;font-weight:800}.icon-btn.gold{border-color:rgba(245,185,40,.45);color:var(--gold)}
        .nav-row{border-top:1px solid rgba(255,255,255,.08)}.nav-row .wrap{display:flex;align-items:center;gap:18px;min-height:44px}.mega{position:relative}.mega summary{list-style:none;display:flex;align-items:center;gap:8px;color:#fff;font-weight:900}.mega summary::-webkit-details-marker{display:none}.mega-panel{position:absolute;top:38px;left:0;width:min(920px,calc(100vw - 32px));background:#fff;color:var(--ink);border:1px solid #dbe5ef;border-radius:8px;box-shadow:0 24px 80px rgba(0,0,0,.28);padding:18px;display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:18px}.mega-col{display:grid;gap:8px}.mega-col h3{font-size:.78rem;text-transform:uppercase;letter-spacing:.08em;color:#62728a;margin:0}.mega-col a{padding:8px 10px;border-radius:6px}.mega-col a:hover{background:#eef9ff;color:#075985}.primary-nav{display:flex;gap:16px;color:#d7e2ef;font-size:.91rem;font-weight:700;flex-wrap:wrap}.primary-nav a:hover{color:var(--cyan)}
        main{min-height:60vh}.hero{background:radial-gradient(circle at 18% 20%,rgba(25,211,245,.18),transparent 32rem),radial-gradient(circle at 78% 8%,rgba(245,185,40,.12),transparent 28rem),linear-gradient(135deg,#081527,#0d2240 58%,#061120);color:#fff}.hero-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr);gap:34px;align-items:center;padding:58px 0 46px}.eyebrow{color:var(--gold);font-weight:900;letter-spacing:.14em;text-transform:uppercase;font-size:.76rem}.hero h1,.page-title{font-size:clamp(2.35rem,6vw,5.8rem);line-height:.92;letter-spacing:0;margin:12px 0 18px}.hero p,.lead{color:#d7e2ef;font-size:1.08rem;max-width:72ch}.hero-search{margin:26px 0 12px;max-width:760px}.ai-bar{display:flex;gap:10px;align-items:center;background:rgba(255,255,255,.08);border:1px solid rgba(25,211,245,.28);border-radius:8px;padding:10px}.ai-bar input{flex:1;border:0;border-radius:6px;min-height:44px;padding:0 12px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;border-radius:8px;padding:0 16px;font-weight:900;border:1px solid transparent}.btn-primary{background:var(--cyan);color:#03131f}.btn-gold{background:var(--gold);color:#081527}.btn-ghost{border-color:rgba(15,23,42,.16);background:#fff;color:#0f172a}.btn-dark{border-color:rgba(255,255,255,.18);color:#fff;background:rgba(255,255,255,.05)}.btn:hover{filter:brightness(1.04)}
        .panel{background:#fff;border:1px solid #dfe7f1;border-radius:8px;box-shadow:0 12px 36px rgba(8,21,39,.06)}.panel.dark{background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.14);box-shadow:none}.marketplace-recommend{background:#fff7df;border-bottom:1px solid #f4d47b;color:#3b2b06}.marketplace-recommend .wrap{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;flex-wrap:wrap}.marketplace-recommend strong{color:#0f172a}.recommend-actions{display:flex;gap:8px;flex-wrap:wrap}.recommend-actions form{margin:0}.recommend-actions button{border:1px solid #d9a514;border-radius:8px;min-height:36px;padding:0 12px;font-weight:900;background:#fff}.recommend-actions .primary{background:var(--gold);color:#081527}.section{padding:54px 0}.section-head{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:20px}.section h2,.section-title{font-size:clamp(1.6rem,3vw,2.6rem);line-height:1.05;margin:0;color:#0f172a}.sub{color:#617189}.grid{display:grid;gap:16px}.category-grid{grid-template-columns:repeat(auto-fill,minmax(190px,1fr))}.category-card,.product-card,.info-card{background:#fff;border:1px solid #dfe7f1;border-radius:8px;padding:18px}.category-card:hover,.product-card:hover{border-color:rgba(25,211,245,.75);box-shadow:0 16px 42px rgba(8,21,39,.08);transform:translateY(-2px)}.cat-icon{width:42px;height:42px;border-radius:8px;background:#e9fbff;color:#075985;display:grid;place-items:center;font-weight:900;margin-bottom:12px}.product-card{display:flex;flex-direction:column;gap:10px}.product-img{aspect-ratio:4/3;border-radius:8px;background:linear-gradient(135deg,#edf5ff,#fff9e6);display:grid;place-items:center;color:#60758f;font-weight:900}.badge{display:inline-flex;align-items:center;gap:4px;border-radius:999px;padding:3px 9px;font-size:.74rem;font-weight:900}.b-ok{background:#dcfce7;color:#166534}.b-warn{background:#fef3c7;color:#92400e}.b-info{background:#cffafe;color:#155e75}.b-muted{background:#e5e7eb;color:#374151}.layout-2{display:grid;grid-template-columns:280px 1fr;gap:20px}.filter{position:sticky;top:136px;align-self:start;padding:16px}.field{display:grid;gap:6px;margin-bottom:12px}.field label{font-weight:900;color:#334155;font-size:.82rem}.control{width:100%;border:1px solid #d3deea;border-radius:8px;min-height:42px;padding:8px 10px;background:#fff;color:#0f172a}.spec-table{width:100%;border-collapse:collapse}.spec-table th,.spec-table td{padding:10px 12px;border-bottom:1px solid #e5edf5;text-align:left}.spec-table th{background:#f8fafc;color:#475569;width:38%}.crumbs{display:flex;flex-wrap:wrap;gap:7px;align-items:center;color:#65758b;font-size:.88rem;margin:18px 0}.crumbs a{color:#0e7490}.footer{background:#061120;color:#b7c7dc;padding:46px 0 90px}.foot-grid{display:grid;grid-template-columns:1.5fr repeat(4,1fr);gap:24px}.footer h3{color:#fff;font-size:.86rem;text-transform:uppercase;letter-spacing:.08em}.footer a{display:block;color:#b7c7dc;margin:7px 0}.footer a:hover{color:var(--cyan)}.newsletter{display:flex;gap:8px;flex-wrap:wrap}.newsletter input{min-height:42px;border-radius:8px;border:1px solid rgba(255,255,255,.16);background:#0d2240;color:#fff;padding:0 12px}.float-ai{position:fixed;right:18px;bottom:20px;z-index:50;background:var(--cyan);color:#03131f;border-radius:999px;padding:12px 16px;font-weight:1000;box-shadow:0 14px 34px rgba(25,211,245,.28)}.mobile-bottom{display:none}
        @media(max-width:980px){.head-main{grid-template-columns:1fr;gap:10px;padding:12px 0}.head-actions{overflow-x:auto}.search{grid-template-columns:1fr auto}.search select{display:none}.nav-row{display:none}.hero-grid,.layout-2{grid-template-columns:1fr}.foot-grid{grid-template-columns:1fr 1fr}.filter{position:static}.mobile-bottom{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:55;background:#081527;border-top:1px solid rgba(25,211,245,.2);justify-content:space-around;padding:8px 6px}.mobile-bottom a{color:#d7e2ef;font-size:.75rem;font-weight:800;text-align:center}.float-ai{bottom:64px}.hero h1{font-size:3.2rem}}
        @media(max-width:620px){.wrap{width:min(var(--max),calc(100% - 22px))}.hero-grid{padding:38px 0}.hero h1{font-size:2.6rem}.section{padding:38px 0}.foot-grid{grid-template-columns:1fr}.ai-bar{display:grid}.category-grid{grid-template-columns:1fr 1fr}.section-head{display:block}.btn{width:100%}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important;scroll-behavior:auto!important}.category-card:hover,.product-card:hover{transform:none}}
    </style>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
@if($marketplaceContext['show_recommendation'] ?? false)
    <aside class="marketplace-recommend" aria-label="Marketplace recommendation">
        <div class="wrap">
            <span>We detected a closer NeoGiga edition: <strong>{{ $marketplaceContext['recommended']['name'] }}</strong> with {{ $marketplaceContext['recommended']['currency_code'] }} pricing and regional stock. Your choice is remembered.</span>
            <div class="recommend-actions">
                <form method="post" action="{{ route('marketplace.preference') }}">
                    @csrf
                    <input type="hidden" name="marketplace" value="{{ $marketplaceContext['recommended']['code'] }}">
                    <input type="hidden" name="return_path" value="{{ request()->getRequestUri() }}">
                    <input type="hidden" name="action" value="switch">
                    <button class="primary" type="submit">Switch to {{ $marketplaceContext['recommended']['name'] }}</button>
                </form>
                <form method="post" action="{{ route('marketplace.preference') }}">
                    @csrf
                    <input type="hidden" name="marketplace" value="{{ strtolower($marketplaceContext['current']->code ?? 'global') }}">
                    <input type="hidden" name="return_path" value="{{ request()->getRequestUri() }}">
                    <input type="hidden" name="action" value="stay">
                    <button type="submit">Stay on {{ $marketplaceContext['current']->name ?? 'NeoGiga Global' }}</button>
                </form>
            </div>
        </div>
    </aside>
@endif
<div class="top-strip">
    <div class="wrap">
        <span>{{ $marketplaceContext['current']->name ?? 'NeoGiga Global' }} · {{ $marketplaceContext['currency_code'] ?? 'USD' }} pricing · single global MPN catalog.</span>
        <div class="edition-links" aria-label="Regional editions">
            @foreach(($marketplaceContext['editions'] ?? []) as $edition)
                <form method="post" action="{{ route('marketplace.preference') }}">
                    @csrf
                    <input type="hidden" name="marketplace" value="{{ $edition['code'] }}">
                    <input type="hidden" name="return_path" value="{{ request()->getRequestUri() }}">
                    <button type="submit">{{ $edition['name'] }}</button>
                </form>
            @endforeach
        </div>
    </div>
</div>
<header class="site-head">
    <div class="wrap head-main">
        <a class="brand" href="/" aria-label="NeoGiga home">
            <span class="mark"><svg width="22" height="22" viewBox="0 0 32 32" fill="none"><path d="M9 22V10l14 12V10" stroke="#19D3F5" stroke-width="2.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span>NeoGiga<small>Engineering Marketplace</small></span>
        </a>
        <form class="search" method="get" action="/products" role="search">
            <select name="category" aria-label="Category"><option value="">All categories</option><option value="semiconductors">Semiconductors</option><option value="robotics">Robotics</option><option value="battery-technology">Battery</option><option value="industrial-automation">Automation</option></select>
            <input name="q" type="search" value="{{ request('q') }}" placeholder="Search products, MPN, SKU, category..." aria-label="Search NeoGiga">
            <button type="submit">Search</button>
        </form>
        <div class="head-actions">
            <form class="switcher-form" method="post" action="{{ route('marketplace.preference') }}">
                @csrf
                <input type="hidden" name="return_path" value="{{ request()->getRequestUri() }}">
                <select class="select-lite" name="marketplace" aria-label="Marketplace">
                    @foreach(($marketplaceContext['editions'] ?? []) as $edition)
                        <option value="{{ $edition['code'] }}" @selected(($marketplaceContext['current']->id ?? null) === $edition['id'])>{{ $edition['name'] }}</option>
                    @endforeach
                </select>
                <button class="switch-btn" type="submit">Apply</button>
            </form>
            <select class="select-lite" aria-label="Language"><option>EN</option><option>HI</option><option>NE</option></select>
            <a class="icon-btn" href="/cart">Cart</a>
            <a class="icon-btn" href="/admin/login">B2B Login</a>
            <a class="icon-btn gold" href="/sell-on-neogiga">Seller</a>
        </div>
    </div>
    <div class="nav-row">
        <div class="wrap">
            <details class="mega">
                <summary>☰ Categories</summary>
                <div class="mega-panel">
                    <div class="mega-col"><h3>Featured Categories</h3><a href="/products?category=semiconductors">Semiconductors</a><a href="/products?category=electronic-components">Electronic Components</a><a href="/products?category=iot-wireless">IoT & Wireless</a><a href="/products?category=robotics">Robotics</a><a href="/products?category=battery-technology">Battery Technology</a></div>
                    <div class="mega-col"><h3>Build</h3><a href="/ai-commerce">AI Project Builder</a><a href="/learn">Learning Hub</a><a href="/rfq">Bulk RFQ</a><a href="/sell-on-neogiga">Become a Seller</a></div>
                    <div class="mega-col"><h3>Popular searches</h3><a href="/products?q=ESP32">ESP32</a><a href="/products?q=LiFePO4">LiFePO4</a><a href="/products?q=PLC">PLC</a><a href="/products?q=robot">Robot kits</a></div>
                </div>
            </details>
            <nav class="primary-nav" aria-label="Primary navigation">
                <a href="/products">Products</a><a href="/categories">Categories</a><a href="/ai-commerce">AI Builder</a><a href="/rfq">RFQ</a><a href="/learn">LMS</a><a href="/distributors">Warehouses</a>
            </nav>
        </div>
    </div>
</header>
<main id="main">@yield('content')</main>
<footer class="footer">
    <div class="wrap foot-grid">
        <div><a class="brand" href="/"><span class="mark">NG</span><span>NeoGiga<small>Engineering the Future</small></span></a><p>Premium marketplace for semiconductors, IoT, robotics, automation, battery technology, power storage and industrial engineering tools.</p><form class="newsletter" method="post" action="/api/v1/newsletter/subscribe"><input type="email" name="email" placeholder="Engineering newsletter" aria-label="Email"><button class="btn btn-gold" type="submit">Subscribe</button></form></div>
        <div><h3>Products</h3><a href="/products?category=semiconductors">Semiconductors</a><a href="/products?category=sensors">Sensors</a><a href="/products?category=robotics">Robotics</a><a href="/products?category=power-storage">Power storage</a></div>
        <div><h3>Company</h3><a href="/ai-commerce">AI commerce</a><a href="/learn">Learning hub</a><a href="/rfq">RFQ sourcing</a><a href="/distributors">Distributors</a></div>
        <div><h3>Seller</h3><a href="/sell-on-neogiga">Become a seller</a><a href="/seller-early-access">Early access</a><a href="/admin/login">Seller portal</a><a href="/admin/login">B2B login</a></div>
        <div><h3>Countries</h3>@foreach(($marketplaceContext['editions'] ?? []) as $edition)<a href="{{ $edition['url'] }}">{{ $edition['name'] }}</a>@endforeach<a href="#">Bangladesh</a><a href="#">Sri Lanka</a></div>
    </div>
</footer>
<a class="float-ai" href="/ai-commerce" aria-label="Open NeoGiga AI assistant">Ask AI</a>
<nav class="mobile-bottom" aria-label="Mobile shortcuts"><a href="/">Home</a><a href="/products">Search</a><a href="/categories">Categories</a><a href="/cart">Cart</a><a href="/ai-commerce">AI</a></nav>
@stack('foot')
</body>
</html>
