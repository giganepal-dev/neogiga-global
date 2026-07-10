<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'NeoGiga — Global Engineering Marketplace')</title>
    <meta name="description" content="@yield('description', 'Semiconductors, electronics, IoT, robotics, batteries and engineering tools — sourced globally, delivered regionally.')">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta name="robots" content="index, follow">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="NeoGiga">
    <meta property="og:title" content="@yield('title', 'NeoGiga')">
    <meta property="og:description" content="@yield('description', 'Global engineering marketplace.')">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='7' fill='%23081527'/><path d='M9 22V10l14 12V10' stroke='%2319D3F5' stroke-width='2.4' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>">
    @stack('head')
    <style>
        :root{--bg:#081527;--panel:#0d2240;--line:rgba(158,178,204,.16);--text:#f7fbff;--muted:#a8b8ca;
            --soft:#d7e2ef;--blue:#123A6B;--cyan:#19D3F5;--gold:#F5B928;--green:#42d392;--max:1180px;--r:12px}
        *{box-sizing:border-box}
        body{margin:0;background:
            radial-gradient(circle at 16% 6%,rgba(25,211,245,.12),transparent 32rem),
            linear-gradient(180deg,#081527,#0a1c34 46%,#081527);color:var(--text);min-height:100dvh;
            font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;line-height:1.6}
        a{color:inherit;text-decoration:none}
        .wrap{width:min(var(--max),calc(100% - 32px));margin:0 auto}
        .nav{position:sticky;top:0;z-index:40;border-bottom:1px solid var(--line);background:rgba(8,21,39,.82);backdrop-filter:blur(14px)}
        .nav-in{height:64px;display:flex;align-items:center;justify-content:space-between;gap:20px}
        .brand{display:flex;align-items:center;gap:11px;font-weight:900}
        .brand .mark{width:36px;height:36px;border:1px solid rgba(25,211,245,.4);border-radius:9px;display:grid;place-items:center;background:rgba(18,58,107,.35)}
        .brand small{display:block;color:var(--gold);font-size:.62rem;letter-spacing:.2em;text-transform:uppercase;margin-top:-2px}
        nav.links{display:flex;gap:22px;color:var(--muted);font-weight:600;font-size:.92rem}
        nav.links a:hover{color:#fff}
        .btn{display:inline-flex;align-items:center;gap:7px;min-height:42px;padding:0 16px;border-radius:8px;font-weight:800;border:1px solid transparent}
        .btn-primary{background:var(--cyan);color:#03131f}.btn-primary:hover{background:#48e0ff}
        .btn-ghost{border-color:rgba(255,255,255,.2);color:#fff}.btn-ghost:hover{border-color:var(--gold);color:var(--gold)}
        a:focus-visible,.btn:focus-visible{outline:2px solid var(--cyan);outline-offset:2px}
        .crumbs{display:flex;flex-wrap:wrap;gap:6px;color:var(--muted);font-size:.84rem;padding:22px 0 4px}
        .crumbs a:hover{color:var(--cyan)}.crumbs span{opacity:.5}
        main{padding-bottom:60px}
        h1{font-size:clamp(1.9rem,4vw,2.8rem);letter-spacing:-.02em;margin:6px 0 8px}
        .lead{color:var(--soft);max-width:70ch;margin:0 0 26px}
        .foot{border-top:1px solid var(--line);background:rgba(6,16,30,.6);padding:36px 0;color:var(--muted);font-size:.9rem}
        .foot-grid{display:grid;grid-template-columns:1.4fr repeat(3,1fr);gap:26px}
        .foot h4{color:#fff;font-size:.8rem;letter-spacing:.08em;text-transform:uppercase;margin:0 0 12px}
        .foot a{display:block;padding:4px 0}.foot a:hover{color:var(--cyan)}
        .foot .bottom{margin-top:26px;padding-top:16px;border-top:1px solid var(--line);display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:.82rem}
        @media (max-width:820px){nav.links{display:none}.foot-grid{grid-template-columns:1fr 1fr}}
        @media (prefers-reduced-motion:reduce){*{transition:none}}
    </style>
</head>
<body>
    <header class="nav">
        <div class="wrap nav-in">
            <a class="brand" href="/">
                <span class="mark"><svg width="20" height="20" viewBox="0 0 32 32" fill="none"><path d="M9 22V10l14 12V10" stroke="#19D3F5" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>NeoGiga<small>Engineering Marketplace</small></span>
            </a>
            <nav class="links" aria-label="Primary">
                <a href="/categories">Categories</a>
                <a href="/#featured">Products</a>
                <a href="/ai-commerce">AI Commerce</a>
                <a href="/sell-on-neogiga">Sell on NeoGiga</a>
                <a href="/distributors">Distributors</a>
            </nav>
            <a class="btn btn-ghost" href="/categories">Browse catalog</a>
        </div>
    </header>

    <main>
        <div class="wrap">
            @yield('content')
        </div>
    </main>

    <footer class="foot">
        <div class="wrap">
            <div class="foot-grid">
                <div>
                    <a class="brand" href="/" style="margin-bottom:10px"><span class="mark"><svg width="18" height="18" viewBox="0 0 32 32" fill="none"><path d="M9 22V10l14 12V10" stroke="#19D3F5" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span>NeoGiga</span></a>
                    <p style="max-width:34ch">Global marketplace for semiconductors, electronics, robotics, IoT, batteries and engineering tools.</p>
                </div>
                <div><h4>Catalog</h4><a href="/categories">All categories</a><a href="/#featured">Featured</a><a href="/#new">New arrivals</a></div>
                <div><h4>Platform</h4><a href="/ai-commerce">AI Commerce</a><a href="/learn">Tutorials</a><a href="/sell-on-neogiga">Become a seller</a><a href="/distributors">Distributor network</a></div>
                <div><h4>Editions</h4><a href="https://neogiga.com">Global · neogiga.com</a><a href="https://neogiga.in">India · neogiga.in</a><a href="https://giganepal.com">Nepal · giganepal.com</a></div>
            </div>
            <div class="bottom"><span>© {{ date('Y') }} Giga Ventures Pvt. Ltd.</span><span>Engineering the future.</span></div>
        </div>
    </footer>
    @stack('foot')
</body>
</html>
