<!DOCTYPE html>
<html lang="{{ $locale ?? 'en' }}">
@php
    $activePrefix = strtolower((string) request()->segment(1));
    $activePrefix = array_key_exists($activePrefix, config('neogiga_global.prefixes', []))
        ? $activePrefix
        : config('neogiga_global.default_prefix', 'en');
    $publicBase = '/'.$activePrefix;
    $sensitivePage = request()->is('cart', 'checkout*', 'forgot-password', 'reset-password*', 'login', 'register', 'account*');
    $resolvedRobots = $robots ?? ($sensitivePage ? 'noindex,nofollow' : ($marketplaceSeo['robots'] ?? 'index,follow'));
    $resolvedCanonical = $canonical ?? ($marketplaceSeo['canonical'] ?? url()->current());
    $resolvedSocialImage = $ogImage ?? ($marketplaceSeo['og_image'] ?? null) ?: url('/images/og/neogiga-default-2026.png');
    $flag = fn(string $code): string => mb_chr(0x1F1E6 + ord(strtoupper($code)[0]) - ord('A')) . mb_chr(0x1F1E6 + ord(strtoupper($code)[1]) - ord('A'));
@endphp
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $marketplaceSeo['title'] ?? 'NeoGiga - Global Engineering Marketplace')</title>
    <meta name="description" content="@yield('description', $marketplaceSeo['description'] ?? 'Global marketplace for semiconductors, IoT, robotics, automation, battery technology, power storage and engineering tools.')">
    <link rel="canonical" href="{{ $resolvedCanonical }}">
    <meta name="robots" content="{{ $resolvedRobots }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="NeoGiga">
    <meta property="og:title" content="@yield('title', $marketplaceSeo['og_title'] ?? 'NeoGiga')">
    <meta property="og:description" content="@yield('description', $marketplaceSeo['og_description'] ?? 'Global engineering marketplace.')">
    <meta property="og:url" content="{{ $resolvedCanonical }}">
    <meta property="og:image" content="{{ $resolvedSocialImage }}">
    <meta property="og:image:alt" content="@yield('title', $marketplaceSeo['og_title'] ?? 'NeoGiga')">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', $marketplaceSeo['twitter_title'] ?? 'NeoGiga')">
    <meta name="twitter:description" content="@yield('description', $marketplaceSeo['twitter_description'] ?? 'Global engineering marketplace.')">
    <meta name="twitter:image" content="{{ $twitterImage ?? ($marketplaceSeo['twitter_image'] ?? null) ?: $resolvedSocialImage }}">
    @if(!empty($marketplaceSeo['schema_json']))<script type="application/ld+json">{!! $marketplaceSeo['schema_json'] !!}</script>@endif
    @foreach(($marketplaceContext['hreflang'] ?? []) as $alternate)
        <link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['url'] }}">
    @endforeach
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('/images/brand/neogiga-favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ url('/images/brand/neogiga-icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('/images/brand/neogiga-apple-touch-icon-180.png') }}">
    <x-icon-styles/>
    @stack('head')
    <style>
        /* NeoGiga "Precision Engineering" design system (dark, platform-wide) */
        :root{--bg:#0a1210;--bg2:#060c0a;--s1:#101c18;--s2:#15241f;--s3:#1d2d27;--blue:#123a6b;--cyan:#28d8fb;--gold:#f9bd2c;--white:#fff;--soft:#d7e2ef;--on:#dde4e0;--muted:#b0bdb6;--faint:#718078;--ink:#dde4e0;--line:rgba(40,216,251,.1);--glass:rgba(40,216,251,.03);--success:#10b981;--max:1280px;--r:14px}
        *{box-sizing:border-box}html{scroll-behavior:smooth}
        body{margin:0;background-color:var(--bg);background-image:radial-gradient(circle,rgba(40,216,251,.06) 1px,transparent 1px),radial-gradient(circle at 20% 30%,rgba(249,189,44,.04),transparent 50rem),radial-gradient(circle at 80% 10%,rgba(40,216,251,.07),transparent 40rem),linear-gradient(135deg,rgba(40,216,251,.03) 25%,transparent 25%),linear-gradient(225deg,rgba(249,189,44,.02) 25%,transparent 25%),linear-gradient(45deg,transparent 48%,rgba(40,216,251,.04) 48%,rgba(40,216,251,.04) 52%,transparent 52%);background-size:18px 18px,100% 100%,100% 100%,60px 60px,60px 60px,120px 120px;background-position:0 0,0 0,0 0,0 0,0 0,0 0;color:var(--on);font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;line-height:1.55;letter-spacing:0;-webkit-font-smoothing:antialiased}
        a{color:inherit;text-decoration:none}img,svg{max-width:100%;display:block}button,input,select,textarea{font:inherit}button{cursor:pointer}
        .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;letter-spacing:.02em}
        .wrap{width:min(var(--max),calc(100% - 40px));margin-inline:auto}.skip{position:absolute;left:-999px;top:8px;background:#fff;color:#000;padding:8px 10px;border-radius:6px;z-index:100}.skip:focus{left:8px}
        .top-strip{background:var(--bg2);color:var(--muted);font-size:.78rem;border-bottom:1px solid var(--line)}.top-strip .wrap{min-height:34px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}.edition-links{display:flex;gap:12px;flex-wrap:wrap}.edition-links a,.edition-links button{color:var(--muted);background:none;border:0;padding:0;font:inherit;cursor:pointer;transition:color .18s}.edition-links a:hover,.edition-links button:hover{color:var(--cyan)}
        .site-head{position:sticky;top:0;z-index:60;background:rgba(16,20,23,.82);backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}.head-main{min-height:76px;display:grid;grid-template-columns:auto minmax(280px,1fr) auto;gap:18px;align-items:center}.brand{display:flex;align-items:center;gap:11px;color:#fff;font-weight:800;letter-spacing:-.01em}.mark{width:40px;height:40px;border:1px solid rgba(40,216,251,.4);border-radius:10px;display:grid;place-items:center;background:linear-gradient(135deg,rgba(40,216,251,.16),rgba(249,189,44,.06))}.brand small{display:block;color:var(--gold);font-size:.62rem;letter-spacing:.18em;text-transform:uppercase;margin-top:-2px}
        .search-wrap{position:relative}.search{display:grid;grid-template-columns:150px 1fr auto;border:1px solid var(--line);border-radius:10px;overflow:hidden;background:var(--s1)}.search select,.search input{border:0;min-height:46px;padding:0 14px;color:var(--on);background:transparent}.search input::placeholder{color:rgba(197,198,205,.5)}.search select{background:rgba(255,255,255,.04);border-right:1px solid var(--line);color:var(--muted)}.search button{border:0;background:var(--cyan);color:#003640;font-weight:700;padding:0 20px;transition:filter .15s}.search button:hover{filter:brightness(1.1)}.search-panel{position:absolute;top:100%;left:0;right:0;margin-top:4px;background:var(--s2);border:1px solid var(--line);border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.6);z-index:100;max-height:420px;overflow-y:auto;padding:6px}.search-panel:empty,.search-panel[hidden]{display:none}.search-item{display:grid;grid-template-columns:44px 1fr auto;gap:10px;align-items:center;padding:10px 12px;border-radius:8px;cursor:pointer;transition:background .12s}.search-item:hover,.search-item.active{background:rgba(40,216,251,.1)}.search-item img{width:44px;height:33px;object-fit:contain;border-radius:4px;background:#081527}.search-item .si-name{color:var(--on);font-weight:600;font-size:.88rem;line-height:1.2}.search-item .si-meta{color:var(--faint);font-size:.74rem;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}.search-item .si-badge{font-size:.68rem;padding:2px 8px;border-radius:999px;background:rgba(40,216,251,.12);color:var(--cyan);white-space:nowrap}.search-panel .sp-empty{padding:20px;text-align:center;color:var(--faint);font-size:.84rem}.head-actions{display:flex;align-items:center;gap:8px}.switcher-form{display:flex;gap:6px;align-items:center}.select-lite,.icon-btn,.switch-btn{min-height:40px;border:1px solid var(--line);border-radius:10px;background:rgba(255,255,255,.05);color:#fff;padding:0 12px;transition:.15s}.select-lite:hover,.icon-btn:hover,.switch-btn:hover{border-color:rgba(40,216,251,.5)}.switch-btn{font-weight:700;background:var(--cyan);color:#003640;border-color:transparent}.icon-btn{display:inline-flex;align-items:center;gap:6px;font-weight:600;font-size:.86rem}.icon-btn.gold{border-color:rgba(249,189,44,.45);color:var(--gold)}
        .nav-row{border-top:1px solid var(--line)}.nav-row .wrap{display:flex;align-items:center;gap:18px;min-height:46px}.mega{position:relative}.mega summary{list-style:none;display:flex;align-items:center;gap:8px;color:#fff;font-weight:700;font-size:.9rem}.mega summary::-webkit-details-marker{display:none}.mega-panel{position:absolute;top:40px;left:0;width:min(920px,calc(100vw - 32px));background:var(--s1);color:var(--on);border:1px solid var(--line);border-radius:14px;box-shadow:0 24px 80px rgba(0,0,0,.5);padding:20px;display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:18px;backdrop-filter:blur(14px)}.mega-col{display:grid;gap:6px}.mega-col h3{font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--faint);margin:0 0 4px}.mega-col a{padding:8px 10px;border-radius:8px;color:var(--muted);transition:.15s}.mega-col a:hover{background:rgba(40,216,251,.1);color:var(--cyan)}.primary-nav{display:flex;gap:18px;color:var(--muted);font-size:.9rem;font-weight:600;flex-wrap:wrap}.primary-nav a{transition:color .15s}.primary-nav a:hover{color:var(--cyan)}
        main{min-height:60vh}.hero{background:radial-gradient(circle at 18% 20%,rgba(40,216,251,.16),transparent 34rem),radial-gradient(circle at 80% 6%,rgba(249,189,44,.1),transparent 28rem),linear-gradient(135deg,#0b1220,#101417 60%,#0b0f11);color:#fff;border-bottom:1px solid var(--line)}.hero-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr);gap:34px;align-items:center;padding:64px 0 52px}.eyebrow{color:var(--cyan);font-weight:700;letter-spacing:.14em;text-transform:uppercase;font-size:.74rem}.hero h1,.page-title{font-size:clamp(2.35rem,6vw,4.6rem);font-weight:800;line-height:1.02;letter-spacing:-.02em;margin:12px 0 18px;text-shadow:0 0 44px rgba(40,216,251,.18)}.hero p,.lead{color:var(--muted);font-size:1.08rem;max-width:72ch}.hero-search{margin:26px 0 12px;max-width:760px}.ai-bar{display:flex;gap:10px;align-items:center;background:var(--s1);border:1px solid var(--line);border-radius:12px;padding:12px}.ai-bar input{flex:1;border:0;background:transparent;color:var(--on);border-radius:6px;min-height:44px;padding:0 12px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;border-radius:10px;padding:0 18px;font-weight:600;font-size:.9rem;border:1px solid transparent;transition:.15s}.btn:hover{transform:translateY(-1px)}.btn-primary{background:var(--cyan);color:#003640}.btn-primary:hover{filter:brightness(1.1)}.btn-gold{background:var(--gold);color:#261900}.btn-ghost{border-color:var(--line);background:transparent;color:var(--on)}.btn-ghost:hover{border-color:var(--cyan);color:var(--cyan)}.btn-dark{border-color:var(--line);color:#fff;background:rgba(255,255,255,.05)}
        .panel{background:var(--glass);border:1px solid var(--line);border-radius:var(--r);backdrop-filter:blur(12px)}.panel.dark{background:var(--glass);border-color:var(--line);box-shadow:none}
        .marketplace-recommend{background:rgba(249,189,44,.1);border-bottom:1px solid rgba(249,189,44,.3);color:var(--gold)}.marketplace-recommend .wrap{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;flex-wrap:wrap}.marketplace-recommend strong{color:#fff}.recommend-actions{display:flex;gap:8px;flex-wrap:wrap}.recommend-actions form{margin:0}.recommend-actions button{border:1px solid rgba(249,189,44,.4);border-radius:8px;min-height:36px;padding:0 12px;font-weight:700;background:transparent;color:var(--gold)}.recommend-actions .primary{background:var(--gold);color:#261900;border-color:transparent}
        .section{padding:64px 0}.section-head{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:24px}.section h2,.section-title{font-size:clamp(1.6rem,3vw,2.2rem);font-weight:700;letter-spacing:-.015em;line-height:1.1;margin:0;color:var(--on)}.sub{color:var(--muted)}
        .grid{display:grid;gap:20px}.category-grid{grid-template-columns:repeat(auto-fill,minmax(200px,1fr))}
        .category-card,.product-card,.info-card{background:var(--glass);border:1px solid var(--line);border-radius:var(--r);padding:20px;backdrop-filter:blur(12px);transition:transform .2s,border-color .2s,box-shadow .2s}.category-card:hover,.product-card:hover{border-color:rgba(40,216,251,.5);box-shadow:0 20px 50px rgba(0,0,0,.35);transform:translateY(-3px)}
        .cat-icon{width:44px;height:44px;border-radius:12px;background:rgba(40,216,251,.12);color:var(--cyan);display:grid;place-items:center;font-weight:700;margin-bottom:12px}.product-card{display:flex;flex-direction:column;gap:10px}.product-img{aspect-ratio:4/3;border-radius:10px;background-color:var(--s1);background-image:url('{{ url('/images/products/neogiga-product-placeholder-2026.png') }}');background-position:center;background-repeat:no-repeat;background-size:cover;display:grid;place-items:center;color:#fff;font-weight:700;text-shadow:0 1px 5px rgba(0,0,0,.9);overflow:hidden}
        .badge{display:inline-flex;align-items:center;gap:4px;border-radius:8px;padding:3px 9px;font-size:.72rem;font-weight:600;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;border:1px solid transparent}.b-ok{background:rgba(16,185,129,.15);color:#34d399;border-color:rgba(16,185,129,.3)}.b-warn{background:rgba(249,189,44,.15);color:var(--gold);border-color:rgba(249,189,44,.3)}.b-info{background:rgba(40,216,251,.14);color:var(--cyan);border-color:rgba(40,216,251,.3)}.b-muted{background:rgba(255,255,255,.06);color:var(--muted);border-color:var(--line)}
        .product-img--badged,.product-gallery-main{position:relative}.product-status-badges{display:flex;gap:6px;flex-wrap:wrap}.product-status-badges--summary{margin-bottom:12px}.product-status-badges--overlay{position:absolute;z-index:2;top:10px;left:10px;max-width:calc(100% - 20px);pointer-events:none}.product-status-badge{display:inline-flex;align-items:center;border:1px solid transparent;border-radius:7px;padding:4px 7px;font-size:.68rem;font-weight:700;line-height:1.1;background:#132033;color:#fff;box-shadow:0 1px 4px rgba(0,0,0,.35)}.product-status-badge--ok{background:rgba(6,95,70,.94);border-color:#34d399}.product-status-badge--info{background:rgba(8,96,125,.94);border-color:var(--cyan)}.product-status-badge--warn,.product-status-badge--gold{background:rgba(126,76,4,.95);border-color:var(--gold);color:#fff6d5}.product-status-badge--danger{background:rgba(127,29,29,.95);border-color:#f87171}.product-status-badge--teal{background:rgba(15,118,110,.95);border-color:#5eead4}.product-status-badge--muted{background:rgba(51,65,85,.95);border-color:#94a3b8}.product-category-chips{display:flex;flex-wrap:wrap;gap:7px;margin:0 0 16px}.product-category-chips a,.product-document-tag{display:inline-flex;align-items:center;border:1px solid rgba(40,216,251,.35);border-radius:7px;padding:4px 8px;color:var(--cyan);font-size:.76rem;text-decoration:none}.product-category-chips a:hover,.product-document-tag:hover{border-color:var(--cyan);background:rgba(40,216,251,.1)}
        .layout-2{display:grid;grid-template-columns:280px 1fr;gap:24px}.filter{position:sticky;top:140px;align-self:start;padding:18px;background:var(--glass);border:1px solid var(--line);border-radius:var(--r)}.field{display:grid;gap:6px;margin-bottom:12px}.field label{font-weight:600;color:var(--muted);font-size:.8rem}.control{width:100%;border:1px solid var(--line);border-radius:10px;min-height:44px;padding:8px 12px;background:var(--s1);color:var(--on)}.control:focus{outline:0;border-color:var(--cyan)}
        .spec-table{width:100%;border-collapse:collapse;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;font-size:.86rem}.spec-table th,.spec-table td{padding:11px 14px;border-bottom:1px solid var(--line);text-align:left;color:var(--on)}.spec-table th{background:var(--s1);color:var(--muted);width:38%;font-weight:600}
        .product-gallery{display:grid;gap:10px}.product-gallery-main{display:grid;place-items:center;aspect-ratio:4/3;border:1px solid var(--line);border-radius:10px;overflow:hidden;background:#fff}.product-gallery-main img{width:100%;height:100%;object-fit:contain}.product-gallery-main img.product-gallery-placeholder{object-fit:cover}.product-gallery-thumbs{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}.product-gallery-thumb{display:grid;place-items:center;aspect-ratio:1;border:1px solid var(--line);border-radius:9px;overflow:hidden;background:#fff;color:var(--faint);padding:0}.product-gallery-thumb img{width:100%;height:100%;object-fit:contain}.product-gallery-thumb.active,.product-gallery-thumb:hover,.product-gallery-thumb:focus-visible{border-color:var(--cyan);outline:0;box-shadow:0 0 0 2px rgba(40,216,251,.18)}
        .product-price-card{padding:14px;border:1px solid rgba(40,216,251,.28);border-radius:10px;background:rgba(40,216,251,.07);margin-bottom:12px}.product-price-card strong{display:block;color:#fff;font-size:1.7rem;margin:2px 0}.product-detail-section{background:var(--bg2)}.product-review{padding:14px 0;border-top:1px solid var(--line)}.spec-group th{background:rgba(40,216,251,.11);color:var(--cyan)}
        .crumbs{display:flex;flex-wrap:wrap;gap:7px;align-items:center;color:var(--faint);font-size:.85rem;margin:18px 0}.crumbs a{color:var(--cyan)}
        .footer{background:var(--bg2);color:var(--muted);padding:56px 0 100px;border-top:1px solid var(--line)}.foot-grid{display:grid;grid-template-columns:1.5fr repeat(4,1fr);gap:24px}.footer h3{color:#fff;font-size:.82rem;text-transform:uppercase;letter-spacing:.08em}.footer a{display:block;color:var(--muted);margin:8px 0;transition:color .15s}.footer a:hover{color:var(--cyan)}.newsletter{display:flex;gap:8px;flex-wrap:wrap}.newsletter input{min-height:44px;border-radius:10px;border:1px solid var(--line);background:var(--s1);color:var(--on);padding:0 14px}
        .float-ai{position:fixed;right:18px;bottom:20px;z-index:50;background:var(--cyan);color:#003640;border-radius:999px;padding:12px 18px;font-weight:700;box-shadow:0 14px 40px rgba(40,216,251,.3);transition:transform .15s}.float-ai:hover{transform:translateY(-2px)}.mobile-bottom{display:none}
        @media(max-width:980px){.head-main{grid-template-columns:1fr;gap:10px;padding:12px 0}.head-actions{overflow-x:auto}.search{grid-template-columns:1fr auto}.search select{display:none}.nav-row{display:none}.hero-grid,.layout-2,.product-primary-grid{grid-template-columns:1fr!important}.foot-grid{grid-template-columns:1fr 1fr}.filter{position:static}.mobile-bottom{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:55;background:rgba(11,15,17,.95);backdrop-filter:blur(12px);border-top:1px solid var(--line);justify-content:space-around;padding:8px 6px}.mobile-bottom a{color:var(--muted);font-size:.7rem;font-weight:600;text-align:center;display:flex;flex-direction:column;align-items:center;gap:3px;text-decoration:none}.float-ai{bottom:64px}.hero h1{font-size:3rem}}
        @media(max-width:620px){.wrap{width:min(var(--max),calc(100% - 24px))}.hero-grid{padding:44px 0}.hero h1{font-size:2.5rem}.section{padding:44px 0}.foot-grid{grid-template-columns:1fr 1fr}.ai-bar{display:grid}.category-grid{grid-template-columns:1fr 1fr}.section-head{display:block}.btn{width:100%}}
        @media(prefers-reduced-motion:reduce){*,*::before,*::after{animation:none!important;transition:none!important;scroll-behavior:auto!important}.category-card:hover,.product-card:hover,.btn:hover,.float-ai:hover{transform:none}}
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
        <span>
            @if($marketplaceContext['country_code']){{ $flag($marketplaceContext['country_code']) }} @endif{{ $marketplaceContext['current']->name ?? 'NeoGiga Global' }} · {{ $marketplaceContext['currency_code'] ?? 'USD' }}
        </span>
        <div class="edition-links" aria-label="Regional editions">
            @foreach(($marketplaceContext['editions'] ?? []) as $edition)
                <form method="post" action="{{ route('marketplace.preference') }}" title="{{ $edition['name'] }}">
                    @csrf
                    <input type="hidden" name="marketplace" value="{{ $edition['code'] }}">
                    <input type="hidden" name="return_path" value="{{ request()->getRequestUri() }}">
                    <button type="submit" title="{{ $edition['name'] }}">{{ $flag($edition['country_code']) }}</button>
                </form>
            @endforeach
        </div>
    </div>
</div>
<header class="site-head">
    <div class="wrap head-main">
        <a class="brand" href="{{ $publicBase }}" aria-label="NeoGiga home">
            <span class="mark"><img src="{{ url('/images/brand/neogiga-icon-192.png') }}" alt="" width="30" height="30" aria-hidden="true"></span>
            <span>NeoGiga<small>Engineering Marketplace</small></span>
        </a>
        <div class="search-wrap">
            <form class="search" method="get" action="{{ $publicBase }}/products" role="search" autocomplete="off">
                <select name="category" aria-label="Category"><option value="">All categories</option><option value="semiconductors">Semiconductors</option><option value="robotics">Robotics</option><option value="battery-technology">Battery</option><option value="industrial-automation">Automation</option></select>
                <input id="search-input" name="q" type="search" value="{{ request('q') }}" placeholder="Search products, MPN, SKU, category..." aria-label="Search NeoGiga" autocomplete="off">
                <button type="submit"><x-icon name="search" size="18"/> Search</button>
            </form>
            <div id="search-panel" class="search-panel" hidden></div>
        </div>
        <div class="head-actions">
            <select class="select-lite" aria-label="Language"><option>EN</option><option>HI</option><option>NE</option></select>
            <a class="icon-btn" href="/cart"><x-icon name="cart" size="18"/> Cart</a>
            <a class="icon-btn" href="/admin/login"><x-icon name="login" size="18"/> B2B Login</a>
            <a class="icon-btn gold" href="{{ $publicBase }}/sell-on-neogiga"><x-icon name="sellers" size="18"/> Seller</a>
        </div>
    </div>
    <div class="nav-row">
        <div class="wrap">
            <details class="mega">
                <summary><x-icon name="menu" size="18"/> Categories <x-icon name="expand" size="14"/></summary>
                <div class="mega-panel">
                    <div class="mega-col"><h3><x-icon name="categories" size="14"/> Featured Categories</h3><a href="{{ $publicBase }}/products?category=semiconductors">Semiconductors</a><a href="{{ $publicBase }}/products?category=electronic-components">Electronic Components</a><a href="{{ $publicBase }}/products?category=iot-wireless">IoT & Wireless</a><a href="{{ $publicBase }}/products?category=robotics">Robotics</a><a href="{{ $publicBase }}/products?category=battery-technology">Battery Technology</a></div>
                    <div class="mega-col"><h3><x-icon name="pcb" size="14"/> Build</h3><a href="{{ $publicBase }}/ai-commerce">AI Project Builder</a><a href="https://pcb.neogiga.com/en">PCB Fabrication</a><a href="{{ $publicBase }}/lms">Learning Hub</a><a href="{{ $publicBase }}/rfq">Bulk RFQ</a><a href="{{ $publicBase }}/sell-on-neogiga">Become a Seller</a></div>
                    <div class="mega-col"><h3><x-icon name="search" size="14"/> Popular searches</h3><a href="{{ $publicBase }}/products?q=ESP32">ESP32</a><a href="{{ $publicBase }}/products?q=LiFePO4">LiFePO4</a><a href="{{ $publicBase }}/products?q=PLC">PLC</a><a href="{{ $publicBase }}/products?q=robot">Robot kits</a></div>
                </div>
            </details>
            <nav class="primary-nav" aria-label="Primary navigation">
                <a href="{{ $publicBase }}/products"><x-icon name="products" size="16"/> Products</a><a href="{{ $publicBase }}/categories"><x-icon name="categories" size="16"/> Categories</a><a href="{{ $publicBase }}/brands"><x-icon name="brands" size="16"/> Brands</a><a href="https://pcb.neogiga.com/en"><x-icon name="pcb" size="16"/> PCB</a><a href="{{ $publicBase }}/ai-commerce"><x-icon name="ai-search" size="16"/> AI Builder</a><a href="{{ $publicBase }}/rfq"><x-icon name="rfq" size="16"/> RFQ</a><a href="{{ $publicBase }}/lms"><x-icon name="lms" size="16"/> LMS</a><a href="{{ $publicBase }}/distributors"><x-icon name="warehouses" size="16"/> Warehouses</a>
            </nav>
        </div>
    </div>
</header>
<main id="main">@yield('content')</main>
<footer class="footer">
    <div class="wrap foot-grid">
        <div><a class="brand" href="{{ $publicBase }}"><span class="mark"><img src="{{ url('/images/brand/neogiga-icon-192.png') }}" alt="" width="30" height="30" aria-hidden="true"></span><span>NeoGiga<small>Engineering the Future</small></span></a><p>Premium marketplace for semiconductors, IoT, robotics, automation, battery technology, power storage and industrial engineering tools.</p><form class="newsletter" method="post" action="/api/v1/newsletter/subscribe"><input type="email" name="email" placeholder="Engineering newsletter" aria-label="Email"><button class="btn btn-gold" type="submit"><x-icon name="email" size="16"/> Subscribe</button></form></div>
        <div><h3>Products</h3><a href="{{ $publicBase }}/products?category=semiconductors">Semiconductors</a><a href="{{ $publicBase }}/products?category=sensors">Sensors</a><a href="{{ $publicBase }}/products?category=robotics">Robotics</a><a href="{{ $publicBase }}/brands">Brands</a></div>
        <div><h3>Company</h3><a href="{{ $publicBase }}/ai-commerce">AI commerce</a><a href="{{ $publicBase }}/lms">Learning hub</a><a href="{{ $publicBase }}/rfq">RFQ sourcing</a><a href="{{ $publicBase }}/distributors">Distributors</a></div>
        <div><h3>Seller</h3><a href="{{ $publicBase }}/sell-on-neogiga">Become a seller</a><a href="{{ $publicBase }}/seller-early-access">Early access</a><a href="/admin/login">Seller portal</a><a href="/admin/login">B2B login</a></div>
        <div><h3>Regional editions</h3>@forelse(($marketplaceContext['editions'] ?? []) as $edition)<a href="{{ $edition['url'] }}">@if($edition['country_code']){{ $flag($edition['country_code']) }} @endif{{ $edition['name'] }}</a>@empty<a href="{{ $publicBase }}#regional-editions">Marketplace directory</a>@endforelse<a href="{{ $publicBase }}#regional-editions">Regional platform status</a></div>
    </div>
</footer>
<a class="float-ai" href="{{ $publicBase }}/ai-commerce" aria-label="Open NeoGiga AI assistant">Ask AI</a>
<nav class="mobile-bottom" aria-label="Mobile shortcuts">
    <a href="{{ $publicBase }}"><x-icon name="home" size="20"/><span>Home</span></a>
    <a href="{{ $publicBase }}/products"><x-icon name="search" size="20"/><span>Search</span></a>
    <a href="{{ $publicBase }}/categories"><x-icon name="categories" size="20"/><span>Categories</span></a>
    <a href="/cart"><x-icon name="cart" size="20"/><span>Cart</span></a>
    <a href="{{ $publicBase }}/ai-commerce"><x-icon name="ai-search" size="20"/><span>AI</span></a>
</nav>
@stack('foot')
<script>
(function(){
var input=document.getElementById('search-input'),panel=document.getElementById('search-panel'),active=-1,timer,abort;
if(!input||!panel)return;
var base=input.closest('form')? input.closest('form').getAttribute('action')+'/suggest' : '/en/products/suggest';
input.addEventListener('input',function(){clearTimeout(timer);var q=input.value.trim();if(q.length<2){panel.hidden=true;return}
timer=setTimeout(function(){
if(abort)abort();
var ctrl=new AbortController();abort=function(){ctrl.abort()};
fetch(base+'?q='+encodeURIComponent(q),{signal:ctrl.signal,headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
.then(function(r){return r.json()}).then(function(data){render(data.data||data)}).catch(function(){})
},180)});
input.addEventListener('keydown',function(e){
var items=panel.querySelectorAll('.search-item');
if(e.key==='ArrowDown'){e.preventDefault();active=Math.min(active+1,items.length-1);highlight(items)}
else if(e.key==='ArrowUp'){e.preventDefault();active=Math.max(active-1,-1);highlight(items)}
else if(e.key==='Enter'&&active>=0&&items[active]){e.preventDefault();items[active].querySelector('a').click()}
else if(e.key==='Escape'){panel.hidden=true;active=-1}
});
document.addEventListener('click',function(e){if(!input.contains(e.target)&&!panel.contains(e.target)){panel.hidden=true;active=-1}});
input.addEventListener('focus',function(){if(panel.children.length&&!panel.hidden)panel.hidden=false});
function highlight(items){items.forEach(function(el,i){el.classList.toggle('active',i===active)})}
function render(results){
active=-1;panel.innerHTML='';
if(!results||!results.length){panel.innerHTML='<div class=sp-empty>No matches found. Try a different keyword or <a href=/en/products> browse all.</a></div>';panel.hidden=false;return}
results.forEach(function(p,i){
var div=document.createElement('div');div.className='search-item';
var img=p.image||'';
div.innerHTML='<a href='+(p.url||'/en/products/'+p.slug)+' style=display:contents><img src='+img+' alt=\"\" width=44 height=33 loading=lazy onerror=this.remove()><span><span class=si-name>'+esc(p.name)+'</span><br><span class=si-meta>'+(p.mpn||p.sku||'')+'</span></span><span class=si-badge>'+(p.category||'')+'</span></a>';
div.addEventListener('mouseenter',function(){active=i;highlight(panel.querySelectorAll('.search-item'))});
panel.appendChild(div)
});panel.hidden=false}
function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML}
})();
</script>
</body>
</html>
